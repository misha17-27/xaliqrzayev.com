<?php
/*
 * İki mərhələli giriş (2FA) — TOTP, RFC 6238 (Google Authenticator, Microsoft Authenticator, 1Password …).
 * 30 saniyəlik addım, 6 rəqəm, HMAC-SHA1. Saat fərqi üçün ±1 addım qəbul olunur,
 * eyni kod iki dəfə işlədilə bilməz (admins.totp_last).
 */
declare(strict_types=1);

const TOTP_STEP   = 30;
const TOTP_DIGITS = 6;

function base32_encode(string $bin): string {
    $alpha = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    $bits = '';
    foreach (str_split($bin) as $c) $bits .= str_pad(decbin(ord($c)), 8, '0', STR_PAD_LEFT);
    $out = '';
    foreach (str_split($bits, 5) as $chunk) $out .= $alpha[bindec(str_pad($chunk, 5, '0', STR_PAD_RIGHT))];
    return $out;
}

function base32_decode(string $b32): string {
    $alpha = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    $b32 = strtoupper(preg_replace('/[^A-Za-z2-7]/', '', $b32));
    $bits = '';
    foreach (str_split($b32) as $c) {
        $v = strpos($alpha, $c);
        if ($v === false) return '';
        $bits .= str_pad(decbin($v), 5, '0', STR_PAD_LEFT);
    }
    $out = '';
    foreach (str_split($bits, 8) as $byte) if (strlen($byte) === 8) $out .= chr(bindec($byte));
    return $out;
}

function totp_new_secret(): string { return base32_encode(random_bytes(20)); }

function totp_code(string $secret, int $step): string {
    $key = base32_decode($secret);
    $msg = pack('N*', 0) . pack('N*', $step);
    $h = hash_hmac('sha1', $msg, $key, true);
    $o = ord($h[19]) & 0x0F;
    $num = ((ord($h[$o]) & 0x7F) << 24) | (ord($h[$o + 1]) << 16) | (ord($h[$o + 2]) << 8) | ord($h[$o + 3]);
    return str_pad((string)($num % (10 ** TOTP_DIGITS)), TOTP_DIGITS, '0', STR_PAD_LEFT);
}

/**
 * Kodu yoxlayır. Uğurda istifadə olunan addımı qaytarır (təkrar istifadənin qarşısı üçün), əks halda 0.
 * $lastStep — bu istifadəçinin sonuncu qəbul olunmuş addımı.
 */
function totp_verify(string $secret, string $code, int $lastStep = 0): int {
    $code = preg_replace('/\D/', '', $code);
    if ($secret === '' || strlen($code) !== TOTP_DIGITS) return 0;
    $now = intdiv(time(), TOTP_STEP);
    foreach ([0, -1, 1] as $d) {
        $s = $now + $d;
        if ($s <= $lastStep) continue;
        if (hash_equals(totp_code($secret, $s), $code)) return $s;
    }
    return 0;
}

function totp_uri(string $secret, string $account): string {
    $issuer = 'xaliqrzayev.com';
    return 'otpauth://totp/' . rawurlencode($issuer . ':' . $account)
         . '?secret=' . $secret . '&issuer=' . rawurlencode($issuer) . '&algorithm=SHA1&digits=' . TOTP_DIGITS . '&period=' . TOTP_STEP;
}
