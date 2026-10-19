<?php
/*
 * Qəbula yazılma formunun emalı.
 * JS ilə göndərildikdə JSON qaytarır, JS-siz — ana səhifəyə yönləndirir.
 * Bildirişlər: app/notify.php (e-poçt, WhatsApp API, pasiyent üçün WhatsApp keçidi).
 */
declare(strict_types=1);
require __DIR__ . '/_init.php';
require APP_DIR . '/notify.php';

$isAjax = ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'fetch';

function respond(bool $ok, string $msg, bool $ajax, array $extra = []): void {
    if ($ajax) {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code($ok ? 200 : 422);
        echo json_encode(['ok' => $ok, 'msg' => $msg] + $extra, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    } else {
        header('Location: ' . url('/') . '?' . ($ok ? 'sent=1' : 'err=' . rawurlencode($msg)) . '#elaqe', true, 303);
    }
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') { header('Location: ' . url('/')); exit; }

$name    = trim((string)($_POST['name'] ?? ''));
$phone   = trim((string)($_POST['phone'] ?? ''));
$email   = trim((string)($_POST['email'] ?? ''));
$message = trim((string)($_POST['message'] ?? ''));

/* Bot tələləri: gizli sahə doludursa — səssizcə "uğurlu" cavab */
if (trim((string)($_POST['website'] ?? '')) !== '') respond(true, $TEXTS['form_ok'], $isAjax);
if (form_token_check((string)($_POST['_t'] ?? '')) !== 0) respond(false, $TEXTS['form_err_refresh'], $isAjax);

if (mb_strlen($name) < 2 || mb_strlen($name) > 80)              respond(false, $TEXTS['form_err_name'], $isAjax);
$digits = preg_replace('/\D/', '', $phone);
if (strlen($digits) < 7 || strlen($digits) > 15 || !preg_match('/^[0-9+()\s-]+$/', $phone))
                                                               respond(false, $TEXTS['form_err_phone'], $isAjax);
if ($email !== '' && (mb_strlen($email) > 190 || !filter_var($email, FILTER_VALIDATE_EMAIL)))
                                                               respond(false, $TEXTS['form_err_email'], $isAjax);
if (mb_strlen($message) > 1500)                                respond(false, $TEXTS['form_err_long'], $isAjax);
if (!turnstile_verify((string)($_POST['cf-turnstile-response'] ?? ''))) respond(false, $TEXTS['form_err_robot'], $isAjax);

$sub = [
    'name' => $name, 'phone' => $phone, 'email' => $email, 'message' => $message,
    'ip' => client_ip(), 'created_at' => date('Y-m-d H:i:s'),
];
$db = cms_db();

/* Eyni IP-dən 10 dəqiqədə 3-dən çox müraciət olmasın */
if ($db && $sub['ip'] !== '') {
    try {
        $st = $db->prepare('SELECT COUNT(*) FROM submissions WHERE ip = ? AND created_at > ?');
        $st->execute([$sub['ip'], date('Y-m-d H:i:s', time() - 600)]);
        if ((int)$st->fetchColumn() >= 3) respond(false, $TEXTS['form_err_rate'], $isAjax);
    } catch (Throwable $e) {}
}

$id = 0;
if ($db) {
    try {
        $db->prepare('INSERT INTO submissions (name, phone, email, message, ip, user_agent, is_read, created_at) VALUES (?,?,?,?,?,?,0,?)')
           ->execute([$name, $phone, $email, $message, $sub['ip'], mb_substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 250), $sub['created_at']]);
        $id = (int)$db->lastInsertId();
    } catch (Throwable $e) {}
}

/* Bildirişlər */
$mailed = notify_email($sub, 'cms_setting');
$waSent = notify_whatsapp($sub, 'cms_setting', $CONTACT);

if ($db && $id) {
    $st = [];
    $st[] = 'e-mail: ' . ($mailed ? 'ok' : (cms_setting('mail_to') !== '' ? 'xəta' : '—'));
    $st[] = 'WhatsApp: ' . ($waSent ? 'ok' : (cms_setting('wa_api_on') === '1' ? 'xəta' : '—'));
    try { $db->prepare('UPDATE submissions SET notified = ? WHERE id = ?')->execute([implode(', ', $st), $id]); } catch (Throwable $e) {}
}

/* Heç yerə yazılmadısa — ehtiyat log */
if (!$id && !$mailed && !$waSent) {
    @file_put_contents(STORAGE_DIR . '/muracietler.log', str_replace("\n", ' | ', submission_text($sub)) . "\n", FILE_APPEND | LOCK_EX);
}

$extra = [];
if (cms_setting('wa_visitor_btn') !== '0') {
    $link = wa_visitor_link($sub, $CONTACT, $TEXTS['form_wa_intro']);
    if ($link !== '') $extra = ['wa' => $link, 'wa_label' => $TEXTS['form_wa_btn']];
}
respond(true, $TEXTS['form_ok'], $isAjax, $extra);
