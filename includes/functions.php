<?php
/* Saytın ortaq köməkçiləri. */
declare(strict_types=1);

function e($s): string { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

/** Saytın daxili ünvanı: url('/muraciet.php') */
function url(string $path = '/'): string { return BASE_URL . '/' . ltrim($path, '/'); }

/** assets/ daxilindəki fayl + dəyişiklikdə keşi yeniləmək üçün ?v= */
function asset(string $path): string {
    $file = dirname(__DIR__) . '/assets/' . ltrim($path, '/');
    $v = is_file($file) ? '?v=' . filemtime($file) : '';
    return url('assets/' . ltrim($path, '/')) . $v;
}

/** assets/img daxilindəki şəkil (paneldən yüklənənlər: uploads/...) */
function img(string $path): string { return url('assets/img/' . ltrim($path, '/')); }

/** Eyni adlı .webp varsa onun yolu, yoxdursa null */
function img_webp(string $path): ?string {
    $webp = preg_replace('/\.(jpe?g|png)$/i', '.webp', $path);
    if ($webp === $path) return null;
    return is_file(dirname(__DIR__) . '/assets/img/' . $webp) ? img($webp) : null;
}

/** <picture> — webp varsa onu da verir */
function picture(string $path, string $alt, int $w, int $h, bool $lazy = false): string {
    $out  = '<picture>';
    if ($webp = img_webp($path)) $out .= '<source srcset="' . e($webp) . '" type="image/webp">';
    $out .= '<img src="' . e(img($path)) . '" alt="' . e($alt) . '" width="' . $w . '" height="' . $h . '"'
          . ($lazy ? ' loading="lazy" decoding="async"' : '') . '>';
    return $out . '</picture>';
}

function tel_link(array $c): string { return 'tel:' . preg_replace('/[^0-9+]/', '', $c['phone_raw'] ?: $c['phone']); }
function wa_link(array $c): string  { return 'https://wa.me/' . preg_replace('/\D/', '', $c['whatsapp'] ?: $c['phone_raw']); }

/** Boş sətirlə ayrılmış mətni <p>-lərə bölür */
function paragraphs(string $text): string {
    $out = '';
    foreach (preg_split('/\R{2,}/', trim($text)) as $p) {
        if (trim($p) !== '') $out .= '<p>' . nl2br(e(trim($p)), false) . '</p>';
    }
    return $out;
}

/** Sətir-sətir siyahı (boşlar atılır) */
function lines(string $text): array {
    return array_values(array_filter(array_map('trim', preg_split('/\R/', $text)), 'strlen'));
}

/* ---------------- Turnstile ---------------- */
function turnstile_site(): string {
    $v = function_exists('cms_setting') ? cms_setting('turnstile_site') : '';
    return $v !== '' ? $v : TURNSTILE_SITE;
}
function turnstile_secret(): string {
    $v = function_exists('cms_setting') ? cms_setting('turnstile_secret') : '';
    return $v !== '' ? $v : TURNSTILE_SECRET;
}
function turnstile_on(): bool { return turnstile_site() !== '' && turnstile_secret() !== ''; }

function turnstile_verify(string $token): bool {
    if (!turnstile_on()) return true;
    if ($token === '') return false;
    $data = http_build_query(['secret' => turnstile_secret(), 'response' => $token, 'remoteip' => client_ip()]);
    $ctx  = stream_context_create(['http' => [
        'method' => 'POST', 'timeout' => 8,
        'header' => "Content-Type: application/x-www-form-urlencoded\r\n",
        'content' => $data,
    ]]);
    $res = @file_get_contents('https://challenges.cloudflare.com/turnstile/v0/siteverify', false, $ctx);
    if ($res === false) return false;
    $j = json_decode($res, true);
    return !empty($j['success']);
}

function client_ip(): string {
    $ip = $_SERVER['HTTP_CF_CONNECTING_IP'] ?? $_SERVER['REMOTE_ADDR'] ?? '';
    return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : '';
}

/* ---------------- Forma tokeni (sessiyasız) ----------------
   Forma göstərilən vaxt imzalanır: bot dərhal göndərirsə və ya
   token saxtadırsa müraciət qəbul olunmur. */
function form_secret(): string {
    $f = dirname(__DIR__) . '/storage/.form_secret';
    if (is_file($f)) return (string)file_get_contents($f);
    $s = bin2hex(random_bytes(24));
    if (!is_dir(dirname($f))) @mkdir(dirname($f), 0775, true);
    @file_put_contents($f, $s);
    return $s;
}
function form_token(): string {
    $t = (string)time();
    return $t . '.' . hash_hmac('sha256', $t, form_secret());
}
/** 0 — düzgün; əks halda səbəb kodu */
function form_token_check(string $tok): int {
    $parts = explode('.', $tok, 2);
    if (count($parts) !== 2) return 1;
    [$t, $sig] = $parts;
    if (!hash_equals(hash_hmac('sha256', $t, form_secret()), $sig)) return 2;
    $age = time() - (int)$t;
    if ($age < 3) return 3;          // çox tez — bot
    if ($age > 86400) return 4;      // köhnə səhifə
    return 0;
}
