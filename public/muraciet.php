<?php
/*
 * Qəbula yazılma formunun emalı.
 * JS ilə göndərildikdə JSON qaytarır, JS-siz — ana səhifəyə yönləndirir.
 */
declare(strict_types=1);
require __DIR__ . '/_init.php';

$isAjax = ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'fetch';

function respond(bool $ok, string $msg, bool $ajax): void {
    if ($ajax) {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code($ok ? 200 : 422);
        echo json_encode(['ok' => $ok, 'msg' => $msg], JSON_UNESCAPED_UNICODE);
    } else {
        header('Location: ' . url('/') . '?' . ($ok ? 'sent=1' : 'err=' . rawurlencode($msg)) . '#elaqe', true, 303);
    }
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') { header('Location: ' . url('/')); exit; }

$name    = trim((string)($_POST['name'] ?? ''));
$phone   = trim((string)($_POST['phone'] ?? ''));
$message = trim((string)($_POST['message'] ?? ''));

/* Bot tələləri: gizli sahə doludursa — səssizcə "uğurlu" cavab */
if (trim((string)($_POST['website'] ?? '')) !== '') respond(true, $TEXTS['form_ok'], $isAjax);
if (form_token_check((string)($_POST['_t'] ?? '')) !== 0) respond(false, 'Səhifəni yeniləyib yenidən cəhd edin.', $isAjax);

if (mb_strlen($name) < 2 || mb_strlen($name) > 80)              respond(false, 'Adınızı daxil edin.', $isAjax);
$digits = preg_replace('/\D/', '', $phone);
if (strlen($digits) < 7 || strlen($digits) > 15 || !preg_match('/^[0-9+()\s-]+$/', $phone))
                                                               respond(false, 'Telefon nömrəsini düzgün daxil edin.', $isAjax);
if (mb_strlen($message) > 1500)                                respond(false, 'Mesaj çox uzundur.', $isAjax);
if (!turnstile_verify((string)($_POST['cf-turnstile-response'] ?? ''))) respond(false, 'Robot olmadığınızı təsdiqləyin.', $isAjax);

$ip  = client_ip();
$now = date('Y-m-d H:i:s');
$db  = cms_db();

/* Eyni IP-dən 10 dəqiqədə 3-dən çox müraciət olmasın */
if ($db && $ip !== '') {
    try {
        $st = $db->prepare('SELECT COUNT(*) FROM submissions WHERE ip = ? AND created_at > ?');
        $st->execute([$ip, date('Y-m-d H:i:s', time() - 600)]);
        if ((int)$st->fetchColumn() >= 3) respond(false, 'Çox sayda müraciət. Bir az sonra yenidən cəhd edin və ya zəng edin.', $isAjax);
    } catch (Throwable $e) {}
}

$saved = false;
if ($db) {
    try {
        $db->prepare('INSERT INTO submissions (name, phone, message, ip, user_agent, is_read, created_at) VALUES (?,?,?,?,?,0,?)')
           ->execute([$name, $phone, $message, $ip, mb_substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 250), $now]);
        $saved = true;
    } catch (Throwable $e) {}
}

/* E-poçt bildirişi */
$to = cms_setting('mail_to') ?: MAIL_TO;
$body = "Saytdan yeni müraciət\n\nAd: $name\nTelefon: $phone\n" . ($message !== '' ? "Mesaj:\n$message\n" : '') . "\nTarix: $now\nIP: $ip\n";
$mailed = false;
if ($to !== '' && filter_var($to, FILTER_VALIDATE_EMAIL)) {
    $err = '';
    if (cms_setting('smtp_host') !== '') {
        $mailed = smtp_send_with('cms_setting', $to, 'Yeni müraciət — ' . $name, $body, $err);
    } else {
        $host = parse_url(SITE_URL, PHP_URL_HOST) ?: 'localhost';
        $mailed = @mail($to, '=?UTF-8?B?' . base64_encode('Yeni müraciət — ' . $name) . '?=', $body,
            "From: no-reply@$host\r\nMIME-Version: 1.0\r\nContent-Type: text/plain; charset=UTF-8");
    }
}

/* Heç yerə yazılmadısa — ehtiyat log */
if (!$saved && !$mailed) {
    @file_put_contents(STORAGE_DIR . '/muracietler.log', str_replace("\n", ' | ', $body) . "\n", FILE_APPEND | LOCK_EX);
}

respond(true, $TEXTS['form_ok'], $isAjax);
