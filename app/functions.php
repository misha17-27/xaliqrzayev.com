<?php
/* Saytın ortaq köməkçiləri. */
declare(strict_types=1);

function e($s): string { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

/** Saytın daxili ünvanı: url('/muraciet.php') */
function url(string $path = '/'): string { return BASE_URL . '/' . ltrim($path, '/'); }

/** assets/ daxilindəki fayl + dəyişiklikdə keşi yeniləmək üçün ?v= */
function asset(string $path): string {
    $file = PUBLIC_DIR . '/assets/' . ltrim($path, '/');
    $v = is_file($file) ? '?v=' . filemtime($file) : '';
    return url('assets/' . ltrim($path, '/')) . $v;
}

/** assets/img daxilindəki şəkil (paneldən yüklənənlər: uploads/...) */
function img(string $path): string { return url('assets/img/' . ltrim($path, '/')); }

/** Eyni adlı .webp varsa onun yolu, yoxdursa null */
function img_webp(string $path): ?string {
    $webp = preg_replace('/\.(jpe?g|png)$/i', '.webp', $path);
    if ($webp === $path) return null;
    return is_file(PUBLIC_DIR . '/assets/img/' . $webp) ? img($webp) : null;
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
    $f = STORAGE_DIR . '/.form_secret';
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

/* ---------------- Şablon köməkçiləri ---------------- */

/** İnline SVG ikon: icon('phone') */
function icon(string $name): string {
    static $icons = null;
    if ($icons === null) $icons = [
        'phone' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1.9.4 1.8.7 2.7a2 2 0 0 1-.5 2.1L8 9.8a16 16 0 0 0 6 6l1.3-1.3a2 2 0 0 1 2.1-.4c.9.3 1.8.6 2.7.7a2 2 0 0 1 1.7 2z"/></svg>',
        'wa'    => '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M17.5 14.4c-.3-.1-1.8-.9-2-1-.3-.1-.5-.1-.7.1-.2.3-.8 1-.9 1.2-.2.2-.3.2-.6.1-.3-.1-1.3-.5-2.4-1.5-.9-.8-1.5-1.8-1.7-2.1-.2-.3 0-.5.1-.6l.4-.5c.2-.2.2-.3.3-.5.1-.2 0-.4 0-.5l-.9-2.2c-.2-.6-.5-.5-.7-.5h-.6c-.2 0-.5.1-.8.4-.3.3-1 1-1 2.5s1.1 2.9 1.2 3.1c.1.2 2.1 3.2 5.1 4.5 2.5 1 3 .8 3.6.7.6-.1 1.8-.7 2-1.4.2-.7.2-1.3.2-1.4-.1-.2-.3-.3-.6-.4zM12 2a10 10 0 0 0-8.6 15.1L2 22l5-1.3A10 10 0 1 0 12 2z"/></svg>',
        'instagram' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.5" cy="6.5" r="1" fill="currentColor"/></svg>',
        'facebook'  => '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M14 8h3V4h-3a4 4 0 0 0-4 4v2H8v4h2v8h4v-8h3l1-4h-4V8z"/></svg>',
        'youtube'   => '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M23 7.5a3 3 0 0 0-2.1-2.1C19 5 12 5 12 5s-7 0-8.9.4A3 3 0 0 0 1 7.5 31 31 0 0 0 .6 12 31 31 0 0 0 1 16.5a3 3 0 0 0 2.1 2.1C5 19 12 19 12 19s7 0 8.9-.4a3 3 0 0 0 2.1-2.1 31 31 0 0 0 .4-4.5 31 31 0 0 0-.4-4.5zM9.8 15.3V8.7l5.7 3.3-5.7 3.3z"/></svg>',
        'tiktok'    => '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M16.6 5.8A4.3 4.3 0 0 1 15.5 3h-3.3v12.4a2.6 2.6 0 1 1-2.6-2.6c.3 0 .5 0 .8.1V9.5a6 6 0 1 0 5.1 5.9V9.1a7.6 7.6 0 0 0 4.5 1.4V7.2a4.3 4.3 0 0 1-3.4-1.4z"/></svg>',
    ];
    return $icons[$name] ?? '';
}


/**
 * Səhifəni yığır: layout/head + layout/header + sections/* + layout/footer.
 * $sections — templates/sections/ daxilindəki faylların adları (sıra ilə).
 */
function render_page(array $page, array $sections, array $vars = []): void {
    global $TEXTS, $CONTACT, $IMAGES, $HIGHLIGHTS, $SERVICES, $EDUCATION;
    $T   = $TEXTS;
    $C   = $CONTACT;
    $tel = tel_link($C);
    $wa  = wa_link($C);
    $sent = false; $formErr = '';
    /* Menyu linkləri ana səhifədə lövbər, digər səhifələrdə /#lövbər */
    $navBase = in_array('hero', $sections, true) ? '' : url('/');
    extract($vars, EXTR_OVERWRITE);

    require TPL_DIR . '/layout/head.php';
    require TPL_DIR . '/layout/header.php';
    echo "
<main>
";
    foreach ($sections as $s) {
        require TPL_DIR . '/sections/' . basename($s) . '.php';
    }
    echo "</main>
";
    require TPL_DIR . '/layout/footer.php';
}
