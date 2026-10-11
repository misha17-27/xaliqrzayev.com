<?php
/*
 * Sadə SMTP göndərici (PHPMailer-siz). Həm sayt formu, həm də admin paneli istifadə edir.
 * Ayarlar bazadakı settings cədvəlindədir: smtp_host, smtp_port, smtp_user, smtp_pass,
 * smtp_secure (tls|ssl|none), smtp_from, smtp_from_name.
 */
declare(strict_types=1);

/** $get — açar üzrə ayarı qaytaran funksiya (sayt: cms_setting, panel: kv_get) */
function smtp_send_with(callable $get, string $to, string $subject, string $body, string &$err = '', string $replyTo = ''): bool {
    $host = (string)$get('smtp_host');
    if ($host === '') { $err = 'SMTP-сервер не заполнен'; return false; }
    $port   = (int)($get('smtp_port') ?: 587);
    $user   = (string)$get('smtp_user');
    $pass   = (string)$get('smtp_pass');
    $secure = (string)($get('smtp_secure') ?: 'tls');
    $from   = (string)($get('smtp_from') ?: $user);
    $fname  = (string)($get('smtp_from_name') ?: 'xaliqrzayev.com');
    $target = $secure === 'ssl' ? "ssl://$host" : $host;
    $helo   = (defined('SITE_URL') ? parse_url(SITE_URL, PHP_URL_HOST) : null) ?: ($_SERVER['HTTP_HOST'] ?? 'localhost');

    $fp = @fsockopen($target, $port, $eno, $estr, 15);
    if (!$fp) { $err = "Подключение не удалось: $estr ($eno)"; return false; }
    stream_set_timeout($fp, 15);
    $read = function () use ($fp) { $d = ''; while (($l = fgets($fp, 515)) !== false) { $d .= $l; if (strlen($l) < 4 || $l[3] === ' ') break; } return $d; };
    $cmd  = function ($c) use ($fp, $read) { fwrite($fp, $c . "\r\n"); return $read(); };
    $ok   = function ($r, $code) { return strncmp(trim((string)$r), $code, 3) === 0; };

    $read();
    $cmd("EHLO $helo");
    if ($secure === 'tls') {
        if (!$ok($cmd('STARTTLS'), '220') || !stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
            $err = 'STARTTLS не удалось'; fclose($fp); return false;
        }
        $cmd("EHLO $helo");
    }
    if ($user !== '') {
        $cmd('AUTH LOGIN'); $cmd(base64_encode($user));
        if (!$ok($cmd(base64_encode($pass)), '235')) { $err = 'Авторизация SMTP не прошла'; fclose($fp); return false; }
    }
    if (!$ok($cmd("MAIL FROM:<$from>"), '250')) { $err = 'Сервер отклонил отправителя'; fclose($fp); return false; }
    if (!$ok($cmd("RCPT TO:<$to>"), '25'))     { $err = 'Сервер отклонил получателя'; fclose($fp); return false; }
    if (!$ok($cmd('DATA'), '354')) { $err = 'Сервер не принял DATA'; fclose($fp); return false; }

    $hdr = "Date: " . date('r') . "\r\n"
         . "From: =?UTF-8?B?" . base64_encode($fname) . "?= <$from>\r\nTo: <$to>\r\n"
         . ($replyTo !== '' ? "Reply-To: <$replyTo>\r\n" : '')
         . "Subject: =?UTF-8?B?" . base64_encode($subject) . "?=\r\nMIME-Version: 1.0\r\n"
         . "Content-Type: text/plain; charset=UTF-8\r\nContent-Transfer-Encoding: 8bit\r\n\r\n";
    $body = str_replace(["\r\n", "\r"], "\n", $body);
    $body = str_replace("\n", "\r\n", $body);
    $r = $cmd($hdr . preg_replace('/^\./m', '..', $body) . "\r\n.");
    $cmd('QUIT'); fclose($fp);
    if (!$ok($r, '250')) { $err = 'Письмо не отправлено: ' . trim((string)$r); return false; }
    return true;
}
