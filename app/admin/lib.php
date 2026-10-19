<?php
/* Admin panelinin ortaq köməkçiləri: PDO, auth, CSRF, yükləmə, layout.
   app/bootstrap.php artıq yüklənib (e(), sabitlər). */
declare(strict_types=1);

require_once dirname(__DIR__) . '/schema.php';
require_once __DIR__ . '/totp.php';

function pdo(): PDO {
    static $p = null;
    if (!$p) { $p = require APP_DIR . '/db.php'; }
    return $p;
}
function cfg(string $k, $default = null) {
    static $c = null;
    if ($c === null) { $c = is_file(CONFIG_FILE) ? require CONFIG_FILE : []; }
    return $c[$k] ?? $default;
}
function redirect(string $to): void { header('Location: ' . $to); exit; }

/* ---------------- Panelin dili: AZ / RU ---------------- */
const ADMIN_LANGS = ['az' => 'AZ', 'ru' => 'RU'];
const ADMIN_LANG_DEFAULT = 'az';

/** Cari dil: ?lang= (cookie-yə yazılır) → cookie → defolt */
function admin_lang(): string {
    static $lang = null;
    if ($lang !== null) return $lang;
    $q = (string)($_GET['lang'] ?? '');
    if (isset(ADMIN_LANGS[$q])) {
        $lang = $q;
        if (!headers_sent()) {
            setcookie('xradm_lang', $q, ['expires' => time() + 31536000, 'path' => '/', 'httponly' => true, 'samesite' => 'Lax', 'secure' => is_https()]);
        }
        return $lang;
    }
    $c = (string)($_COOKIE['xradm_lang'] ?? '');
    return $lang = isset(ADMIN_LANGS[$c]) ? $c : ADMIN_LANG_DEFAULT;
}

/**
 * Tərcümə. Açar — rus mətni (kodda yazılan), lüğət: app/admin/lang/az.php.
 * Dəyişənlər sprintf ilə: t('Добавлено: %d.', $n)
 */
function t(string $s, ...$args): string {
    static $dict = null;
    if (admin_lang() !== 'ru') {
        if ($dict === null) {
            $f = __DIR__ . '/lang/' . admin_lang() . '.php';
            $dict = is_file($f) ? (array)require $f : [];
        }
        if (isset($dict[$s]) && $dict[$s] !== '') $s = $dict[$s];
    }
    return $args ? vsprintf($s, $args) : $s;
}

/** AZ | RU keçidi — cari səhifənin ünvanını saxlayır */
function lang_switch(): string {
    $qs = $_GET;
    $out = '<span class="langsw" title="Dil / Язык">';
    foreach (ADMIN_LANGS as $code => $label) {
        $qs['lang'] = $code;
        $out .= '<a' . ($code === admin_lang() ? ' class="on"' : '') . ' href="index.php?' . e(http_build_query($qs)) . '">' . $label . '</a>';
    }
    return $out . '</span>';
}

/* ---------------- sessiya / auth ---------------- */
function is_https(): bool {
    $h = $_SERVER['HTTPS'] ?? '';
    return ($h !== '' && strtolower((string)$h) !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https')
        || (int)($_SERVER['SERVER_PORT'] ?? 0) === 443;
}

const SESSION_IDLE_MAX = 7200;      // 2 saat boşdayanma
const SESSION_ABS_MAX  = 43200;     // 12 saat mütləq ömür

function boot_session(): void {
    if (session_status() !== PHP_SESSION_NONE) return;
    session_name('xradm');
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Strict',
        'secure'   => is_https(),
    ]);
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    session_start();

    $now = time();
    if (isset($_SESSION['admin'])) {
        $last  = (int)($_SESSION['seen']  ?? $now);
        $start = (int)($_SESSION['start'] ?? $now);
        if ($now - $last > SESSION_IDLE_MAX || $now - $start > SESSION_ABS_MAX) {
            $_SESSION = [];
            session_regenerate_id(true);
        }
    }
    $_SESSION['seen'] = $now;
}

function current_admin(): ?array { boot_session(); return $_SESSION['admin'] ?? null; }

/**
 * Hər sorğuda hesab bazada yoxlanılır: silinib, söndürülüb və ya parolu/2FA dəyişibsə (session_ver),
 * köhnə sessiya dərhal bağlanır. Rol da bazadan götürülür.
 */
function require_login(): array {
    $a = current_admin();
    if (!$a) redirect('index.php?section=login');
    try {
        $st = pdo()->prepare('SELECT id, email, name, role, active, session_ver FROM admins WHERE id = ?');
        $st->execute([(int)$a['id']]);
        $u = $st->fetch();
    } catch (Throwable $e) { $u = null; }
    if (!$u || (int)$u['active'] !== 1 || (int)$u['session_ver'] !== (int)($a['ver'] ?? -1)) {
        logout();
        boot_session();
        flash(t('Сессия завершена. Войдите снова.'), 'err');
        redirect('index.php?section=login');
    }
    $_SESSION['admin']['role']  = $u['role'];
    $_SESSION['admin']['email'] = $u['email'];
    $_SESSION['admin']['name']  = $u['name'];
    return $_SESSION['admin'];
}

/* ---------------- əməliyyat jurnalı ---------------- */
function admin_log(string $action, string $detail = '', ?string $who = null): void {
    try {
        $a = current_admin();
        pdo()->prepare('INSERT INTO admin_log (at, admin, ip, action, detail) VALUES (?,?,?,?,?)')
             ->execute([date('Y-m-d H:i:s'), mb_substr($who ?? ($a['email'] ?? ''), 0, 190), client_ip(), mb_substr($action, 0, 60), mb_substr($detail, 0, 255)]);
        if (random_int(1, 50) === 1) pdo()->prepare('DELETE FROM admin_log WHERE at < ?')->execute([date('Y-m-d H:i:s', time() - 180 * 86400)]);
    } catch (Throwable $e) {}
}

/* ---------------- giriş cəhdlərinin məhdudlaşdırılması ----------------
   IP üzrə (6 cəhd / 15 dəq) və hesab üzrə (10 cəhd / 15 dəq — çox IP-dən parol seçilməsinə qarşı). */
const LOGIN_MAX_TRIES       = 6;
const LOGIN_MAX_PER_ACCOUNT = 10;
const LOGIN_WINDOW          = 900;        // 15 dəqiqə

function client_ip_admin(): string { return client_ip(); }   // app/security.php

/** Bloklanıbsa — qalan saniyə, əks halda 0 */
function login_blocked(string $email = ''): int {
    try {
        $since = time() - LOGIN_WINDOW;
        $st = pdo()->prepare('SELECT COUNT(*) c, MIN(at) m FROM login_fails WHERE ip = ? AND at > ?');
        $st->execute([client_ip(), $since]);
        $r = $st->fetch();
        $wait = (int)$r['c'] >= LOGIN_MAX_TRIES ? (int)$r['m'] + LOGIN_WINDOW - time() : 0;
        if ($email !== '') {
            $st = pdo()->prepare('SELECT COUNT(*) c, MIN(at) m FROM login_fails WHERE email = ? AND at > ?');
            $st->execute([mb_strtolower(trim($email)), $since]);
            $r = $st->fetch();
            if ((int)$r['c'] >= LOGIN_MAX_PER_ACCOUNT) $wait = max($wait, (int)$r['m'] + LOGIN_WINDOW - time());
        }
        return $wait > 0 ? $wait : 0;
    } catch (Throwable $e) { return 0; }
}
function login_fail(string $email = ''): void {
    try {
        $db = pdo();
        $db->prepare('INSERT INTO login_fails (ip, email, at) VALUES (?, ?, ?)')->execute([client_ip(), mb_strtolower(trim($email)), time()]);
        $db->prepare('DELETE FROM login_fails WHERE at < ?')->execute([time() - 86400]);
    } catch (Throwable $e) {}
    usleep(400000);   // hər uğursuz cəhd yavaşıdılır
}

/** Həqiqi bcrypt heşi: mövcud olmayan hesabda da yoxlama eyni vaxt aparsın (istifadəçinin varlığı bilinməsin) */
function dummy_hash(): string {
    static $h = null;
    return $h ?? ($h = password_hash(bin2hex(random_bytes(8)), PASSWORD_DEFAULT));
}

/** Sessiyanı açır (parol və lazım olsa 2FA yoxlanandan sonra) */
function login_complete(array $u): void {
    boot_session();
    session_regenerate_id(true);
    unset($_SESSION['pending_2fa']);
    $_SESSION['start'] = time();
    $_SESSION['seen']  = time();
    $_SESSION['admin'] = [
        'id' => (int)$u['id'], 'email' => $u['email'], 'name' => $u['name'] ?? '',
        'role' => $u['role'] ?? 'editor', 'ver' => (int)($u['session_ver'] ?? 0),
    ];
    try {
        pdo()->prepare('UPDATE admins SET last_login=? WHERE id=?')->execute([date('Y-m-d H:i'), (int)$u['id']]);
        pdo()->prepare('DELETE FROM login_fails WHERE ip = ? OR email = ?')->execute([client_ip(), mb_strtolower((string)$u['email'])]);
    } catch (Throwable $e) {}
    admin_log('login', '', (string)$u['email']);
}

/**
 * Parol yoxlanışı. Qaytarır: 'ok' — daxil oldu; '2fa' — kod lazımdır; 'fail' — uğursuz.
 */
function attempt_login(string $email, string $pass): string {
    if (login_blocked($email) > 0) return 'fail';

    $st = pdo()->prepare('SELECT * FROM admins WHERE email = ? LIMIT 1');
    $st->execute([trim($email)]);
    $u = $st->fetch();

    $hash = $u['pass_hash'] ?? dummy_hash();
    $ok   = password_verify($pass, $hash);

    if ($u && $ok && (int)($u['active'] ?? 1) === 1) {
        if (password_needs_rehash($hash, PASSWORD_DEFAULT)) {
            try { pdo()->prepare('UPDATE admins SET pass_hash=? WHERE id=?')->execute([password_hash($pass, PASSWORD_DEFAULT), (int)$u['id']]); } catch (Throwable $e) {}
        }
        if ((string)($u['totp_secret'] ?? '') !== '') {
            boot_session();
            session_regenerate_id(true);
            $_SESSION['pending_2fa'] = ['id' => (int)$u['id'], 'at' => time()];
            return '2fa';
        }
        login_complete($u);
        return 'ok';
    }
    login_fail($email);
    admin_log('login_fail', '', mb_substr(trim($email), 0, 190));
    return 'fail';
}

/** 2FA kodu. Qaytarır: 'ok' | 'fail' | 'expired' */
function attempt_2fa(string $code): string {
    boot_session();
    $p = $_SESSION['pending_2fa'] ?? null;
    if (!$p || time() - (int)$p['at'] > 300) { unset($_SESSION['pending_2fa']); return 'expired'; }
    $st = pdo()->prepare('SELECT * FROM admins WHERE id = ? AND active = 1');
    $st->execute([(int)$p['id']]);
    $u = $st->fetch();
    if (!$u || login_blocked((string)$u['email']) > 0) { unset($_SESSION['pending_2fa']); return 'expired'; }
    $step = totp_verify((string)$u['totp_secret'], $code, (int)$u['totp_last']);
    if ($step > 0) {
        pdo()->prepare('UPDATE admins SET totp_last = ? WHERE id = ?')->execute([$step, (int)$u['id']]);
        login_complete($u);
        return 'ok';
    }
    login_fail((string)$u['email']);
    admin_log('2fa_fail', '', (string)$u['email']);
    return 'fail';
}

/** Parol/2FA dəyişəndə və ya istifadəçi söndürüləndə onun bütün sessiyaları etibarsız olur */
function bump_session_ver(int $id): void {
    pdo()->prepare('UPDATE admins SET session_ver = session_ver + 1 WHERE id = ?')->execute([$id]);
    $a = current_admin();
    if ($a && (int)$a['id'] === $id) {
        /* özümüz — cari sessiyanı yeni versiyada saxlayırıq */
        $st = pdo()->prepare('SELECT session_ver FROM admins WHERE id = ?'); $st->execute([$id]);
        $_SESSION['admin']['ver'] = (int)$st->fetchColumn();
        session_regenerate_id(true);
    }
}

function is_admin(): bool {
    $a = current_admin();
    return is_array($a) && ($a['role'] ?? '') === 'admin';
}
function logout(): void {
    boot_session();
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}

/* ---------------- CSRF ---------------- */
function csrf_token(): string {
    boot_session();
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16));
    return $_SESSION['csrf'];
}
function csrf_field(): string { return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">'; }
function csrf_check(): void {
    boot_session();
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') return;
    $have = (string)($_SESSION['csrf'] ?? '');
    $sent = (string)($_POST['_csrf'] ?? '');
    if ($have === '' || $sent === '' || !hash_equals($have, $sent)) {
        http_response_code(400);
        header('Content-Type: text/plain; charset=utf-8');
        exit('CSRF token səhvdir. Səhifəni yeniləyin.');
    }
}

/* ---------------- flash ---------------- */
function flash(string $msg, string $type = 'ok'): void { boot_session(); $_SESSION['flash'][] = [$type, $msg]; }
function flash_render(): string {
    boot_session(); $out = '';
    foreach ($_SESSION['flash'] ?? [] as [$t, $m]) {
        $out .= '<div class="flash ' . ($t === 'err' ? 'err' : 'ok') . '">' . e($m) . '</div>';
    }
    $_SESSION['flash'] = [];
    return $out;
}

/* ---------------- key-value ---------------- */
function kv_get(string $table, string $key, $default = ''): string {
    try {
        $st = pdo()->prepare("SELECT v FROM `$table` WHERE k = ? LIMIT 1");
        $st->execute([$key]);
        $r = $st->fetch();
        return $r ? (string)$r['v'] : (string)$default;
    } catch (Throwable $e) { return (string)$default; }
}
function kv_set(string $table, string $key, string $val): void {
    $db = pdo();
    $st = $db->prepare("SELECT 1 FROM `$table` WHERE k = ? LIMIT 1");
    $st->execute([$key]);
    if ($st->fetch()) $db->prepare("UPDATE `$table` SET v = ? WHERE k = ?")->execute([$val, $key]);
    else              $db->prepare("INSERT INTO `$table` (k, v) VALUES (?, ?)")->execute([$key, $val]);
}
function setting(string $k): string { return kv_get('settings', $k); }


/* ---------------- fayl yükləmə ---------------- */
const UPLOAD_TYPES = [
    'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png',
    'webp' => 'image/webp', 'gif' => 'image/gif',
];
const UPLOAD_MAX_BYTES = 15 * 1024 * 1024;   // 15 MB
const UPLOAD_MAX_SIDE  = 2000;               // daha böyük şəkillər kiçildilir
/* SVG qəsdən yoxdur: içində <script> ola bilər. */

function uploads_dir(): string { return PUBLIC_DIR . '/assets/img/uploads'; }

/** Yüklənmiş faylı yoxlayır, saxlayır; nisbi yolu qaytarır (uploads/...) */
function admin_upload(string $field, string &$err = ''): ?string {
    if (empty($_FILES[$field]['name'])) return null;
    $f = $_FILES[$field];
    if (($f['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) { $err = t('Ошибка загрузки (код ') . (int)$f['error'] . ')'; return null; }
    if (!is_uploaded_file($f['tmp_name']))  { $err = t('Файл не получен'); return null; }
    if ($f['size'] > UPLOAD_MAX_BYTES)      { $err = t('Файл больше 15 МБ'); return null; }

    $name = preg_replace('/[^A-Za-z0-9._-]/', '_', basename((string)$f['name']));
    $ext  = strtolower(pathinfo((string)$name, PATHINFO_EXTENSION));
    if (!isset(UPLOAD_TYPES[$ext])) { $err = t('Разрешены только JPG, PNG, WEBP, GIF'); return null; }

    $info = @getimagesize($f['tmp_name']);
    if ($info === false || !str_starts_with((string)$info['mime'], 'image/')) { $err = t('Файл не является изображением'); return null; }
    if ((int)$info[0] * (int)$info[1] > 40000000) { $err = t('Изображение слишком большое (максимум 40 мегапикселей)'); return null; }
    if (class_exists('finfo')) {
        $mime = (string)(new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']);
        if (!str_starts_with($mime, 'image/')) { $err = t('Файл не является изображением'); return null; }
    }

    $dir = uploads_dir();
    if (!is_dir($dir)) @mkdir($dir, 0755, true);
    $base = mb_substr(pathinfo((string)$name, PATHINFO_FILENAME), 0, 50);
    $safe = date('Ymd') . '_' . bin2hex(random_bytes(4)) . '_' . $base . '.' . $ext;
    if (!move_uploaded_file($f['tmp_name'], "$dir/$safe")) { $err = t('Не удалось сохранить файл'); return null; }
    @chmod("$dir/$safe", 0644);

    image_optimize("$dir/$safe", $ext);
    return 'uploads/' . $safe;
}

/** GD varsa: böyük şəkli kiçildir və yanında .webp versiyasını yaradır */
function image_optimize(string $path, string $ext): void {
    if (!function_exists('imagecreatetruecolor') || $ext === 'gif') return;
    $info = @getimagesize($path);
    if (!$info) return;
    [$w, $h] = $info;
    switch ($ext) {
        case 'png':  $im = @imagecreatefrompng($path); break;
        case 'webp': $im = function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($path) : false; break;
        default:     $im = @imagecreatefromjpeg($path);
    }
    if (!$im) return;

    /* Telefon şəkillərində EXIF oriyentasiyası */
    if ($ext !== 'png' && $ext !== 'webp' && function_exists('exif_read_data')) {
        $exif = @exif_read_data($path);
        $o = (int)($exif['Orientation'] ?? 1);
        if ($o === 3) $im = imagerotate($im, 180, 0);
        elseif ($o === 6) { $im = imagerotate($im, -90, 0); [$w, $h] = [$h, $w]; }
        elseif ($o === 8) { $im = imagerotate($im, 90, 0);  [$w, $h] = [$h, $w]; }
    }

    $scale = min(1, UPLOAD_MAX_SIDE / max($w, $h));
    if ($scale < 1) {
        $nw = (int)round($w * $scale); $nh = (int)round($h * $scale);
        $dst = imagecreatetruecolor($nw, $nh);
        if ($ext === 'png' || $ext === 'webp') { imagealphablending($dst, false); imagesavealpha($dst, true); }
        imagecopyresampled($dst, $im, 0, 0, 0, 0, $nw, $nh, $w, $h);
        imagedestroy($im); $im = $dst;
    }
    if ($ext === 'png') imagepng($im, $path, 7);
    elseif ($ext === 'webp') imagewebp($im, $path, 82);
    else imagejpeg($im, $path, 84);

    if ($ext !== 'webp' && function_exists('imagewebp')) {
        @imagewebp($im, preg_replace('/\.(jpe?g|png)$/i', '.webp', $path), 80);
    }
    imagedestroy($im);
}

/** Yalnız assets/img daxilindəki şəkil yolunu qəbul edir */
function safe_img_path(string $p): string {
    $p = ltrim(str_replace('\\', '/', trim($p)), '/');
    if ($p === '' || str_contains($p, '..') || !preg_match('~^[A-Za-z0-9._/-]{1,200}$~', $p)) return '';
    $ext = strtolower((string)pathinfo($p, PATHINFO_EXTENSION));
    if (!isset(UPLOAD_TYPES[$ext])) return '';
    return is_file(PUBLIC_DIR . '/assets/img/' . $p) ? $p : '';
}

/* ---------------- Turnstile (giriş formu) ---------------- */
function adm_turnstile_on(): bool { return setting('turnstile_site') !== '' && setting('turnstile_secret') !== ''; }
function adm_turnstile_widget(): void {
    if (!adm_turnstile_on()) return;
    echo '<div class="cf-turnstile" data-sitekey="' . e(setting('turnstile_site')) . '"></div>';
    echo '<script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>';
}
function adm_turnstile_verify(string $token): bool {
    if (!adm_turnstile_on()) return true;
    if ($token === '') return false;
    $ctx = stream_context_create(['http' => [
        'method' => 'POST', 'timeout' => 8,
        'header' => "Content-Type: application/x-www-form-urlencoded\r\n",
        'content' => http_build_query(['secret' => setting('turnstile_secret'), 'response' => $token, 'remoteip' => client_ip_admin()]),
    ]]);
    $res = @file_get_contents('https://challenges.cloudflare.com/turnstile/v0/siteverify', false, $ctx);
    $j = $res ? json_decode($res, true) : null;
    return !empty($j['success']);
}

/* ---------------- naviqasiya ---------------- */
const SECTIONS = [
    'overview'    => ['Обзор', '▤'],
    'texts'       => ['Тексты сайта', '¶'],
    'services'    => ['Услуги и операции', '✦'],
    'highlights'  => ['Полоса под экраном', '◧'],
    'education'   => ['Образование', '🎓'],
    'images'      => ['Изображения', '❏'],
    'contacts'    => ['Контакты и соцсети', '☏'],
    'submissions' => ['Заявки с сайта', '✉'],
    'seo'         => ['SEO и аналитика', '☌'],
    'notify'      => ['WhatsApp-уведомления', '✆'],
    'security'    => ['Безопасность', '⚿'],
    'backup'      => ['Резервная копия', '⤓'],
    'users'       => ['Пользователи', '☺'],
    'profile'     => ['Мой профиль', '☺'],
];
const GROUPS = [
    'ОСНОВНОЕ'  => ['overview', 'submissions'],
    'КОНТЕНТ'   => ['texts', 'services', 'highlights', 'education', 'images', 'contacts'],
    'НАСТРОЙКИ' => ['seo', 'notify', 'security', 'backup', 'users', 'profile'],
];
const ADMIN_ONLY = ['users', 'notify', 'security', 'backup'];

/* «Открыть на сайте» — bölməyə uyğun lövbər */
const SITE_ANCHORS = ['services' => '#diaqnostika', 'education' => '#tehsil', 'contacts' => '#elaqe', 'submissions' => '#elaqe'];

function require_admin(): void {
    require_login();
    if (!is_admin()) { flash(t('Недостаточно прав (нужна роль Администратор).'), 'err'); redirect('index.php?section=overview'); }
}

function unread_count(): int {
    try { return (int)pdo()->query('SELECT COUNT(*) c FROM submissions WHERE is_read=0')->fetch()['c']; }
    catch (Throwable $e) { return 0; }
}

/** Paneldən public/assets faylına keçid (+ dəyişiklikdə keş yenilənsin deyə ?v=) */
function admin_asset(string $path): string {
    $f = PUBLIC_DIR . '/assets/' . $path;
    return '../assets/' . $path . (is_file($f) ? '?v=' . filemtime($f) : '');
}

function head_html(string $title): void {
    send_security_headers('admin');
    echo '<!doctype html><html lang="' . admin_lang() . '"><head><meta charset="utf-8">';
    echo '<meta name="viewport" content="width=device-width, initial-scale=1">';
    echo '<meta name="robots" content="noindex,nofollow">';
    echo '<title>' . e(t($title)) . ' — xaliqrzayev.com admin</title>';
    echo '<link rel="icon" href="../favicon.ico" sizes="48x48"><link rel="icon" href="' . e(admin_asset('img/favicon.svg')) . '" type="image/svg+xml">';
    echo '<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>';
    echo '<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&display=swap" rel="stylesheet">';
    echo '<link rel="stylesheet" href="' . e(admin_asset('css/admin.css')) . '"></head><body>';
}

function brand_html(): string {
    return t('<div class="logo"><i>XR</i><span>Dr. Xaliq Rzayev<small>панель управления</small></span></div>');
}

function layout_top(string $active, string $title): void {
    $admin  = current_admin();
    $unread = unread_count();
    head_html($title);
    echo '<input type="checkbox" id="navtoggle" hidden><div class="wrap">';

    echo '<aside class="side"><div class="brand">' . brand_html() . '</div><nav>';
    foreach (GROUPS as $glabel => $keys) {
        echo '<div class="navgroup">' . e(t($glabel)) . '</div>';
        foreach ($keys as $key) {
            if (in_array($key, ADMIN_ONLY, true) && !is_admin()) continue;
            [$label, $ic] = SECTIONS[$key];
            $cls = $key === $active ? ' class="on"' : '';
            $badge = ($key === 'submissions' && $unread) ? '<b class="badge">' . $unread . '</b>' : '';
            echo '<a' . $cls . ' href="index.php?section=' . $key . '"><i>' . $ic . '</i><span>' . e(t($label)) . '</span>' . $badge . '</a>';
        }
    }
    echo t('</nav><div class="who">Вы вошли как<br><b>') . e($admin['name'] ?: ($admin['email'] ?? '')) . '</b><br>'
       . '<span class="role">' . (($admin['role'] ?? '') === 'admin' ? t('Администратор') : t('Редактор')) . '</span></div></aside>';

    echo '<main><header class="bar">';
    echo t('<label for="navtoggle" class="burger" title="Меню"><span></span><span></span><span></span></label>');
    echo '<h1>' . e(t($title)) . '</h1><div class="bar-r">' . lang_switch() . ' ';
    echo '<a class="btn ghost site" href="../' . (SITE_ANCHORS[$active] ?? '') . t('" target="_blank" rel="noopener">Открыть сайт ↗</a> ');
    echo '<a class="btn" href="index.php?section=logout&amp;t=' . e(csrf_token()) . '">' . t('Выйти') . '</a></div></header><div class="body">';
    echo flash_render();
}
function layout_bottom(): void {
    echo '</div></main><label for="navtoggle" class="scrim"></label></div>';
    echo '<script src="' . e(admin_asset('js/admin.js')) . '"></script></body></html>';
}

