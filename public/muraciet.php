<?php
/*
 * Qəbula yazılma formunun emalı.
 * Müraciət bazada saxlanılır və WhatsApp ilə göndərilir:
 *   · pasiyentin WhatsApp-ı həkimin nömrəsinə hazır mətnlə açılır (həmişə);
 *   · admin paneldə qoşulubsa — həm də API ilə avtomatik (app/notify.php).
 * JS ilə: JSON (wa keçidi ilə); JS-siz: birbaşa WhatsApp-a yönləndirmə.
 */
declare(strict_types=1);
require __DIR__ . '/_init.php';
require APP_DIR . '/notify.php';

send_security_headers('site');
$isAjax = ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'fetch';
const FORM_MAX_PER_HOUR = 30;

/** $key — $TEXTS açarı (form_ok, form_err_*). URL-də yalnız açar gedir, mətn yox — saxta mesaj göstərmək olmasın. */
function respond(bool $ok, string $key, bool $ajax, array $extra = []): void {
    global $TEXTS;
    $msg = (string)($TEXTS[$key] ?? '');
    if ($ajax) {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code($ok ? 200 : 422);
        echo json_encode(['ok' => $ok, 'msg' => $msg] + $extra, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    } elseif ($ok && !empty($extra['wa'])) {
        header('Location: ' . $extra['wa'], true, 303);
    } else {
        header('Location: ' . url('/') . '?' . ($ok ? 'sent=1' : 'err=' . rawurlencode($key)) . '#elaqe', true, 303);
    }
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') { header('Location: ' . url('/')); exit; }
if ((int)($_SERVER['CONTENT_LENGTH'] ?? 0) > 20000) { http_response_code(413); exit; }

/* Görünməyən idarəetmə simvolları silinir (yeni sətir qalır) */
function clean_input(string $v): string {
    return trim((string)preg_replace('/[\x00-\x09\x0B-\x1F\x7F\x{200B}-\x{200F}\x{202A}-\x{202E}\x{2066}-\x{2069}]/u', '', $v));
}

$name    = clean_input((string)($_POST['name'] ?? ''));
$phone   = clean_input((string)($_POST['phone'] ?? ''));
$message = clean_input(str_replace("\r\n", "\n", (string)($_POST['message'] ?? '')));

/* Bot tələləri: gizli sahə doludursa — səssizcə "uğurlu" cavab */
if (trim((string)($_POST['website'] ?? '')) !== '') respond(true, 'form_ok', $isAjax);
if (form_token_check((string)($_POST['_t'] ?? '')) !== 0) respond(false, 'form_err_refresh', $isAjax);

if (mb_strlen($name) < 2 || mb_strlen($name) > 80)              respond(false, 'form_err_name', $isAjax);
$digits = preg_replace('/\D/', '', $phone);
if (strlen($digits) < 7 || strlen($digits) > 15 || !preg_match('/^[0-9+()\s-]+$/', $phone))
                                                               respond(false, 'form_err_phone', $isAjax);
if (mb_strlen($message) > 1500)                                respond(false, 'form_err_long', $isAjax);
if (!turnstile_verify((string)($_POST['cf-turnstile-response'] ?? ''))) respond(false, 'form_err_robot', $isAjax);

$sub = [
    'name' => $name, 'phone' => $phone, 'message' => $message,
    'ip' => client_ip(), 'created_at' => date('Y-m-d H:i:s'),
];
$db = cms_db();

/* Eyni IP-dən 10 dəqiqədə 3-dən çox müraciət olmasın */
if ($db && $sub['ip'] !== '') {
    try {
        $st = $db->prepare('SELECT COUNT(*) FROM submissions WHERE ip = ? AND created_at > ?');
        $st->execute([$sub['ip'], date('Y-m-d H:i:s', time() - 600)]);
        if ((int)$st->fetchColumn() >= 3) respond(false, 'form_err_rate', $isAjax);
    } catch (Throwable $e) {}
}
/* Bütün sayt üzrə: saatda 30-dan çox müraciət — çox güman ki, bot hücumudur */
if ($db) {
    try {
        $st = $db->prepare('SELECT COUNT(*) FROM submissions WHERE created_at > ?');
        $st->execute([date('Y-m-d H:i:s', time() - 3600)]);
        if ((int)$st->fetchColumn() >= FORM_MAX_PER_HOUR) respond(false, 'form_err_rate', $isAjax);
    } catch (Throwable $e) {}
}

$id = 0;
if ($db) {
    try {
        $db->prepare('INSERT INTO submissions (name, phone, message, ip, user_agent, is_read, created_at) VALUES (?,?,?,?,?,0,?)')
           ->execute([$name, $phone, $message, $sub['ip'], mb_substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 250), $sub['created_at']]);
        $id = (int)$db->lastInsertId();
    } catch (Throwable $e) {}
}

/* API ilə avtomatik WhatsApp (qoşulubsa) */
$apiOn  = cms_setting('wa_api_on') === '1';
$waSent = $apiOn && notify_whatsapp($sub, 'cms_setting', $CONTACT);
if ($db && $id) {
    $status = 'WhatsApp: ' . ($apiOn ? ($waSent ? 'API ok' : 'API xəta') . ' + ' : '') . 'pasiyent';
    try { $db->prepare('UPDATE submissions SET notified = ? WHERE id = ?')->execute([$status, $id]); } catch (Throwable $e) {}
}

/* Heç yerə yazılmadısa — ehtiyat log */
if (!$id && !$waSent) {
    @file_put_contents(STORAGE_DIR . '/muracietler.log', str_replace("\n", ' | ', submission_text($sub)) . "\n", FILE_APPEND | LOCK_EX);
}

$link = wa_visitor_link($sub, $CONTACT, $TEXTS['form_wa_intro']);
respond(true, 'form_ok', $isAjax, $link !== '' ? ['wa' => $link, 'wa_label' => $TEXTS['form_wa_btn']] : []);
