<?php
/*
 * Müraciət bildirişləri: e-poçt, WhatsApp (TextMeBot / CallMeBot / öz URL) və pasiyent üçün WhatsApp keçidi.
 *
 * Ayarlar (settings cədvəli, admin → «WhatsApp-bildiriş» və «Poçt (SMTP)»):
 *   mail_to         — müraciətlərin göndəriləcəyi e-poçt
 *   wa_api_on       — '1' olarsa API ilə avtomatik göndər
 *   wa_api_provider — textmebot | callmebot | custom
 *   wa_api_url      — custom üçün URL şablonu: {phone} {text} {key}
 *   wa_api_phone    — bildiriş alan nömrə (boşdursa — saytdakı WhatsApp nömrəsi)
 *   wa_api_key      — xidmətin verdiyi apikey
 *   wa_visitor_btn  — '0' deyilsə, göndərişdən sonra pasiyentə «WhatsApp-a da göndər» düyməsi göstərilir
 */
declare(strict_types=1);

/** Bildiriş mətni (e-poçt və WhatsApp üçün eyni) */
function submission_text(array $s, bool $withMeta = true): string {
    $t = "Saytdan yeni müraciət\n\nAd: {$s['name']}\nTelefon: {$s['phone']}\n";
    if (($s['email'] ?? '') !== '')   $t .= "E-poçt: {$s['email']}\n";
    if (($s['message'] ?? '') !== '') $t .= "Mesaj:\n{$s['message']}\n";
    if ($withMeta) $t .= "\nTarix: {$s['created_at']}\nIP: {$s['ip']}\n";
    return $t;
}

/** E-poçt: SMTP (doldurulubsa) və ya hostinqin mail() funksiyası */
function notify_email(array $s, callable $setting): bool {
    $to = (string)$setting('mail_to') ?: (defined('MAIL_TO') ? MAIL_TO : '');
    if ($to === '' || !filter_var($to, FILTER_VALIDATE_EMAIL)) return false;
    $subject = 'Yeni müraciət — ' . $s['name'];
    $body    = submission_text($s);
    $reply   = filter_var($s['email'] ?? '', FILTER_VALIDATE_EMAIL) ? (string)$s['email'] : '';
    if ((string)$setting('smtp_host') !== '') {
        $err = '';
        return smtp_send_with($setting, $to, $subject, $body, $err, $reply);
    }
    $host = parse_url(SITE_URL, PHP_URL_HOST) ?: 'localhost';
    $hdr  = "From: no-reply@$host\r\nMIME-Version: 1.0\r\nContent-Type: text/plain; charset=UTF-8"
          . ($reply !== '' ? "\r\nReply-To: <$reply>" : '');
    return @mail($to, '=?UTF-8?B?' . base64_encode($subject) . '?=', $body, $hdr);
}

/** WhatsApp nömrəsi: yalnız rəqəmlər */
function wa_digits(string $p): string { return preg_replace('/\D/', '', $p); }

/* GET ilə mesaj göndərən WhatsApp xidmətləri: {phone} — yalnız rəqəmlər, {text} və {key} URL-kodlanır */
const WA_PROVIDERS = [
    'textmebot' => 'https://api.textmebot.com/send.php?recipient=%2B{phone}&apikey={key}&text={text}',
    'callmebot' => 'https://api.callmebot.com/whatsapp.php?phone=%2B{phone}&apikey={key}&text={text}',
];

/**
 * WhatsApp mesajı API vasitəsilə. Uğurlu olarsa true; $err — xəta mətni (admin test düyməsi üçün).
 * Uğur meyarı: HTTP 2xx və cavabda xəta sözləri yoxdur (xidmətlər formatı sənədləşdirmir).
 */
function wa_api_send(string $provider, string $phone, string $apikey, string $text, string $customUrl = '', string &$err = ''): bool {
    $phone = wa_digits($phone);
    $tpl = $provider === 'custom' ? trim($customUrl) : (WA_PROVIDERS[$provider] ?? '');
    if ($tpl === '' || !preg_match('~^https://~i', $tpl)) { $err = 'URL'; return false; }
    if ($phone === '') { $err = 'phone'; return false; }
    if ($apikey === '' && strpos($tpl, '{key}') !== false) { $err = 'apikey'; return false; }
    $url = strtr($tpl, ['{phone}' => $phone, '{text}' => rawurlencode($text), '{key}' => rawurlencode($apikey)]);

    $ctx = stream_context_create(['http' => ['method' => 'GET', 'timeout' => 12, 'ignore_errors' => true, 'header' => "User-Agent: xaliqrzayev.com\r\n"]]);
    $res = @file_get_contents($url, false, $ctx);
    if ($res === false) { $err = 'no connection to ' . (parse_url($tpl, PHP_URL_HOST) ?: 'API'); return false; }
    $code = 0;
    foreach ((array)($http_response_header ?? []) as $h) if (preg_match('~^HTTP/\S+\s+(\d{3})~', $h, $m)) $code = (int)$m[1];
    $plain = trim(preg_replace('/\s+/', ' ', strip_tags((string)$res)));
    if ($code >= 200 && $code < 300 && !preg_match('/\b(error|invalid|incorrect|wrong|not (allowed|registered|activated|authori[sz]ed)|expired|failed|denied)\b/i', $plain)) {
        return true;
    }
    $err = 'HTTP ' . $code . ': ' . mb_substr($plain, 0, 200);
    return false;
}

function notify_whatsapp(array $s, callable $setting, array $contact): bool {
    if ((string)$setting('wa_api_on') !== '1') return false;
    $phone = (string)$setting('wa_api_phone') ?: (string)($contact['whatsapp'] ?? '');
    $err = '';
    return wa_api_send((string)$setting('wa_api_provider') ?: 'textmebot', $phone, (string)$setting('wa_api_key'),
        submission_text($s, false), (string)$setting('wa_api_url'), $err);
}

/** Pasiyentin öz WhatsApp-ından həkimə göndərə biləcəyi hazır mesaj keçidi */
function wa_visitor_link(array $s, array $contact, string $intro): string {
    $num = wa_digits((string)($contact['whatsapp'] ?? '') ?: (string)($contact['phone_raw'] ?? ''));
    if ($num === '') return '';
    $t = trim($intro) . "\n\nAd: {$s['name']}\nTelefon: {$s['phone']}";
    if (($s['email'] ?? '') !== '')   $t .= "\nE-poçt: {$s['email']}";
    if (($s['message'] ?? '') !== '') $t .= "\n" . $s['message'];
    return 'https://wa.me/' . $num . '?text=' . rawurlencode($t);
}
