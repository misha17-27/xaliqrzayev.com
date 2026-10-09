<?php
/* Admin panelinin ortaq köməkçiləri: PDO, auth, CSRF, layout. */
declare(strict_types=1);

require_once __DIR__ . '/schema.php';
require_once __DIR__ . '/../includes/mailer.php';

function pdo(): PDO {
    static $p = null;
    if (!$p) { $p = require __DIR__ . '/db.php'; }
    return $p;
}
function cfg(string $k, $default = null) {
    static $c = null;
    if ($c === null) { $c = is_file(__DIR__ . '/config.php') ? require __DIR__ . '/config.php' : []; }
    return $c[$k] ?? $default;
}
function e($s): string { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function redirect(string $to): void { header('Location: ' . $to); exit; }

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
function require_login(): array {
    $a = current_admin();
    if (!$a) redirect('index.php?section=login');
    return $a;
}

/* ---------------- giriş cəhdlərinin məhdudlaşdırılması ----------------
   Həm sessiyada, həm bazada (IP üzrə) sayılır — cookie silməklə keçmək olmasın. */
const LOGIN_MAX_TRIES = 6;
const LOGIN_WINDOW    = 900;        // 15 dəqiqə

function client_ip_admin(): string {
    $ip = $_SERVER['HTTP_CF_CONNECTING_IP'] ?? $_SERVER['REMOTE_ADDR'] ?? '';
    return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : '';
}
function login_blocked(): int {
    try {
        $st = pdo()->prepare('SELECT COUNT(*) c, MIN(at) m FROM login_fails WHERE ip = ? AND at > ?');
        $st->execute([client_ip_admin(), time() - LOGIN_WINDOW]);
        $r = $st->fetch();
        if ((int)$r['c'] < LOGIN_MAX_TRIES) return 0;
        return max(1, (int)$r['m'] + LOGIN_WINDOW - time());
    } catch (Throwable $e) { return 0; }
}
function login_fail(): void {
    try {
        $db = pdo();
        $db->prepare('INSERT INTO login_fails (ip, at) VALUES (?, ?)')->execute([client_ip_admin(), time()]);
        $db->prepare('DELETE FROM login_fails WHERE at < ?')->execute([time() - 86400]);
    } catch (Throwable $e) {}
}

function attempt_login(string $email, string $pass): bool {
    if (login_blocked() > 0) return false;

    $st = pdo()->prepare('SELECT * FROM admins WHERE email = ? LIMIT 1');
    $st->execute([trim($email)]);
    $u = $st->fetch();

    /* Mövcud olmayan hesab üçün də heş yoxlanılır — vaxt fərqi olmasın */
    $hash = $u['pass_hash'] ?? '$2y$10$' . str_repeat('.', 53);
    $ok   = password_verify($pass, $hash);

    if ($u && $ok && (int)($u['active'] ?? 1) === 1) {
        boot_session();
        session_regenerate_id(true);
        $_SESSION['start'] = time();
        $_SESSION['seen']  = time();
        $_SESSION['admin'] = [
            'id' => (int)$u['id'], 'email' => $u['email'],
            'name' => $u['name'] ?? '', 'role' => $u['role'] ?? 'editor',
        ];
        try {
            pdo()->prepare('UPDATE admins SET last_login=? WHERE id=?')->execute([date('Y-m-d H:i'), (int)$u['id']]);
            pdo()->prepare('DELETE FROM login_fails WHERE ip = ?')->execute([client_ip_admin()]);
            if (password_needs_rehash($hash, PASSWORD_DEFAULT)) {
                pdo()->prepare('UPDATE admins SET pass_hash=? WHERE id=?')->execute([password_hash($pass, PASSWORD_DEFAULT), (int)$u['id']]);
            }
        } catch (Throwable $e) {}
        return true;
    }
    login_fail();
    return false;
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

function smtp_send(string $to, string $subject, string $body, string &$err = ''): bool {
    return smtp_send_with('setting', $to, $subject, $body, $err);
}

/* ---------------- fayl yükləmə ---------------- */
const UPLOAD_TYPES = [
    'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png',
    'webp' => 'image/webp', 'gif' => 'image/gif',
];
const UPLOAD_MAX_BYTES = 15 * 1024 * 1024;   // 15 MB
const UPLOAD_MAX_SIDE  = 2000;               // daha böyük şəkillər kiçildilir
/* SVG qəsdən yoxdur: içində <script> ola bilər. */

function uploads_dir(): string { return dirname(__DIR__) . '/assets/img/uploads'; }

/** Yüklənmiş faylı yoxlayır, saxlayır; nisbi yolu qaytarır (uploads/...) */
function admin_upload(string $field, string &$err = ''): ?string {
    if (empty($_FILES[$field]['name'])) return null;
    $f = $_FILES[$field];
    if (($f['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) { $err = 'Ошибка загрузки (код ' . (int)$f['error'] . ')'; return null; }
    if (!is_uploaded_file($f['tmp_name']))  { $err = 'Файл не получен'; return null; }
    if ($f['size'] > UPLOAD_MAX_BYTES)      { $err = 'Файл больше 15 МБ'; return null; }

    $name = preg_replace('/[^A-Za-z0-9._-]/', '_', basename((string)$f['name']));
    $ext  = strtolower(pathinfo((string)$name, PATHINFO_EXTENSION));
    if (!isset(UPLOAD_TYPES[$ext])) { $err = 'Разрешены только JPG, PNG, WEBP, GIF'; return null; }

    $info = @getimagesize($f['tmp_name']);
    if ($info === false || !str_starts_with((string)$info['mime'], 'image/')) { $err = 'Файл не является изображением'; return null; }
    if (class_exists('finfo')) {
        $mime = (string)(new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']);
        if (!str_starts_with($mime, 'image/')) { $err = 'Файл не является изображением'; return null; }
    }

    $dir = uploads_dir();
    if (!is_dir($dir)) @mkdir($dir, 0755, true);
    $base = mb_substr(pathinfo((string)$name, PATHINFO_FILENAME), 0, 50);
    $safe = date('Ymd') . '_' . bin2hex(random_bytes(4)) . '_' . $base . '.' . $ext;
    if (!move_uploaded_file($f['tmp_name'], "$dir/$safe")) { $err = 'Не удалось сохранить файл'; return null; }
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
    return is_file(dirname(__DIR__) . '/assets/img/' . $p) ? $p : '';
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
    'highlights'  => ['Карточки', '◧'],
    'education'   => ['Образование', '🎓'],
    'images'      => ['Изображения', '❏'],
    'contacts'    => ['Контакты и соцсети', '☏'],
    'submissions' => ['Заявки с сайта', '✉'],
    'seo'         => ['SEO и аналитика', '☌'],
    'smtp'        => ['Почта (SMTP)', '✉'],
    'security'    => ['Безопасность', '⚿'],
    'users'       => ['Пользователи', '☺'],
    'profile'     => ['Мой профиль', '☺'],
];
const GROUPS = [
    'ОСНОВНОЕ'  => ['overview', 'submissions'],
    'КОНТЕНТ'   => ['texts', 'services', 'highlights', 'education', 'images', 'contacts'],
    'НАСТРОЙКИ' => ['seo', 'smtp', 'security', 'users', 'profile'],
];
const ADMIN_ONLY = ['users', 'smtp', 'security'];

function require_admin(): void {
    require_login();
    if (!is_admin()) { flash('Недостаточно прав (нужна роль Администратор).', 'err'); redirect('index.php?section=overview'); }
}

function unread_count(): int {
    try { return (int)pdo()->query('SELECT COUNT(*) c FROM submissions WHERE is_read=0')->fetch()['c']; }
    catch (Throwable $e) { return 0; }
}

function head_html(string $title): void {
    echo '<!doctype html><html lang="ru"><head><meta charset="utf-8">';
    echo '<meta name="viewport" content="width=device-width, initial-scale=1">';
    echo '<meta name="robots" content="noindex,nofollow">';
    echo '<title>' . e($title) . ' — xaliqrzayev.com admin</title>';
    echo '<link rel="icon" href="../assets/img/favicon.svg" type="image/svg+xml">';
    echo '<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>';
    echo '<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&display=swap" rel="stylesheet">';
    echo '<style>' . admin_css() . '</style></head><body>';
}

function brand_html(): string {
    return '<div class="logo"><i>XR</i><span>Dr. Xaliq Rzayev<small>панель управления</small></span></div>';
}

function layout_top(string $active, string $title): void {
    $admin  = current_admin();
    $unread = unread_count();
    head_html($title);
    echo '<input type="checkbox" id="navtoggle" hidden><div class="wrap">';

    echo '<aside class="side"><div class="brand">' . brand_html() . '</div><nav>';
    foreach (GROUPS as $glabel => $keys) {
        echo '<div class="navgroup">' . e($glabel) . '</div>';
        foreach ($keys as $key) {
            if (in_array($key, ADMIN_ONLY, true) && !is_admin()) continue;
            [$label, $ic] = SECTIONS[$key];
            $cls = $key === $active ? ' class="on"' : '';
            $badge = ($key === 'submissions' && $unread) ? '<b class="badge">' . $unread . '</b>' : '';
            echo '<a' . $cls . ' href="index.php?section=' . $key . '"><i>' . $ic . '</i><span>' . e($label) . '</span>' . $badge . '</a>';
        }
    }
    echo '</nav><div class="who">Вы вошли как<br><b>' . e($admin['name'] ?: ($admin['email'] ?? '')) . '</b><br>'
       . '<span class="role">' . (($admin['role'] ?? '') === 'admin' ? 'Администратор' : 'Редактор') . '</span></div></aside>';

    echo '<main><header class="bar">';
    echo '<label for="navtoggle" class="burger" title="Меню"><span></span><span></span><span></span></label>';
    echo '<h1>' . e($title) . '</h1><div class="bar-r">';
    echo '<a class="btn ghost" href="../" target="_blank">Открыть сайт</a> ';
    echo '<a class="btn" href="index.php?section=logout">Выйти</a></div></header><div class="body">';
    echo flash_render();
}
function layout_bottom(): void {
    echo '</div></main><label for="navtoggle" class="scrim"></label></div>';
    echo '<script src="admin.js?v=2"></script></body></html>';
}

function admin_css(): string {
    return <<<CSS
*{box-sizing:border-box}
body{margin:0;font-family:Montserrat,-apple-system,Segoe UI,Roboto,Arial,sans-serif;background:#f3f7f7;color:#14262c}
a{text-decoration:none;color:#0a6668}
.wrap{display:flex;min-height:100vh}

.side{width:256px;background:#0f2e33;color:#cfe3e3;display:flex;flex-direction:column;position:sticky;top:0;height:100vh;flex:none;z-index:30}
.brand{padding:18px 18px;border-bottom:1px solid #1b4248}
.logo{display:flex;align-items:center;gap:10px;color:#fff;font-weight:800;font-size:15px;line-height:1.2}
.logo i{width:38px;height:38px;border-radius:11px;background:linear-gradient(135deg,#0f8b8d,#3cb6a6);display:grid;place-items:center;font-style:normal;font-size:14px;flex:none}
.logo small{display:block;font-weight:500;font-size:11px;color:#8fb3b5;letter-spacing:.04em}
.side nav{display:flex;flex-direction:column;padding:4px 0 14px;flex:1;overflow:auto}
.navgroup{color:#6f9a9d;font-size:11px;letter-spacing:.09em;padding:15px 20px 5px;font-weight:700}
.side nav a{color:#b7d2d3;padding:11px 20px;font-size:14px;display:flex;gap:12px;align-items:center;border-left:3px solid transparent}
.side nav a i{width:18px;font-style:normal;opacity:.85;text-align:center}
.side nav a:hover{background:#163c42;color:#fff}
.side nav a.on{background:#0f8b8d;color:#fff;font-weight:700;border-left-color:#f2895e}
.badge{margin-left:auto;background:#f2895e;color:#fff;font-size:11px;font-weight:800;padding:1px 8px;border-radius:20px}
.who{padding:16px 20px;font-size:12px;color:#8fb3b5;border-top:1px solid #1b4248;line-height:1.5}
.who b{color:#fff}
.who .role{display:inline-block;margin-top:4px;background:#1b4248;padding:2px 9px;border-radius:20px;font-size:11px}

main{flex:1;min-width:0}
.bar{display:flex;justify-content:space-between;align-items:center;gap:14px;padding:16px 26px;background:#fff;border-bottom:1px solid #e1eaea;position:sticky;top:0;z-index:20}
.bar h1{margin:0;font-size:21px;letter-spacing:-.02em;flex:1}
.bar-r{display:flex;gap:8px;flex:none}
.burger{display:none;flex-direction:column;gap:5px;cursor:pointer;padding:6px}
.burger span{width:22px;height:2px;background:#0f2e33;border-radius:2px}
.scrim{display:none}
.body{padding:24px 26px 60px;max-width:1120px}

.flash{color:#fff;padding:12px 16px;border-radius:10px;margin-bottom:14px;font-weight:600;font-size:14px}
.flash.ok{background:#0f8a5f}.flash.err{background:#b91c1c}
.btn{display:inline-block;background:#0f8b8d;color:#fff;border:0;padding:10px 18px;border-radius:24px;font-weight:700;cursor:pointer;font-size:14px;font-family:inherit;line-height:1.3}
.btn:hover{background:#0a6668}
.btn.ghost{background:transparent;color:#0f2e33;border:1.5px solid #cddbdb}
.btn.ghost:hover{background:#e3f4f1;border-color:#0f8b8d;color:#0a6668}
.btn.sm{padding:6px 12px;font-size:13px;border-radius:8px}
.btn.red{background:#b91c1c}.btn.red:hover{background:#991717}
.btn.navy{background:#0f2e33}.btn.navy:hover{background:#0a2226}

.cards{display:grid;grid-template-columns:repeat(auto-fit,minmax(190px,1fr));gap:16px;margin-bottom:22px}
.card{background:#fff;border:1px solid #e3ecec;border-radius:14px;padding:20px 22px;color:inherit;display:block}
a.card:hover{border-color:#0f8b8d}
.card .n{font-size:34px;font-weight:800;color:#0f8b8d;line-height:1}
.card .l{color:#5b7378;font-size:12.5px;text-transform:uppercase;letter-spacing:.04em;margin-top:6px}
.panel{background:#fff;border:1px solid #e3ecec;border-radius:14px;padding:22px;margin-bottom:20px}
.panel h3{margin:0 0 6px;font-size:18px}
.panel h4{margin:22px 0 6px;font-size:15px;color:#0f2e33}

table{width:100%;border-collapse:collapse}
th,td{text-align:left;padding:11px 12px;border-bottom:1px solid #edf3f3;font-size:14px;vertical-align:top}
th{color:#5b7378;font-size:11.5px;text-transform:uppercase;letter-spacing:.04em}
td.c,th.c{text-align:center;width:1%;white-space:nowrap}
.tablewrap{overflow-x:auto}
tr.unread td{background:#fff8f3}
tr.hidden td{opacity:.5}

label{display:block;font-weight:600;margin:14px 0 6px;font-size:13.5px}
label.inline{display:inline-flex;gap:8px;align-items:center;margin:0;font-weight:500}
input[type=text],input[type=email],input[type=password],input[type=number],input[type=url],textarea,select{
  width:100%;padding:11px 13px;border:1px solid #cddbdb;border-radius:9px;font-size:14px;font-family:inherit;background:#fbfdfd;color:inherit}
input:focus,textarea:focus,select:focus{outline:0;border-color:#0f8b8d;background:#fff;box-shadow:0 0 0 3px rgba(15,139,141,.13)}
textarea{min-height:90px;resize:vertical}
td input[type=text],td input[type=number]{padding:8px 10px}
td input[type=number]{width:72px}
.row{display:grid;grid-template-columns:1fr 1fr;gap:16px}
.row3{display:grid;grid-template-columns:1fr 1fr 1fr;gap:16px}
fieldset{border:1px solid #e3ecec;border-radius:10px;padding:12px 16px 16px;margin-top:16px}
legend{color:#0f2e33;font-weight:700;font-size:13px;padding:0 6px}
.muted{color:#5b7378;font-size:13px}
.hint{color:#5b7378;font-size:12.5px;margin-top:4px}
.right{text-align:right}
.pill{display:inline-block;font-size:11px;padding:3px 10px;border-radius:20px;background:#e3f4f1;color:#0a6668;font-weight:700}
.pill.warn{background:#fff1e8;color:#b4542a}
.tabs{display:flex;flex-wrap:wrap;gap:6px;margin-bottom:16px}
.tabs a{padding:8px 14px;border-radius:20px;border:1.5px solid #cddbdb;color:#0f2e33;font-weight:600;font-size:13.5px}
.tabs a.on{background:#0f2e33;border-color:#0f2e33;color:#fff}
.tabs a b{font-weight:800;opacity:.6;margin-left:4px}
.grid-media{display:grid;grid-template-columns:repeat(auto-fill,minmax(150px,1fr));gap:14px}
.media{border:1px solid #e3ecec;border-radius:12px;overflow:hidden;background:#fbfdfd;font-size:12px}
.media img{width:100%;height:120px;object-fit:cover;display:block;background:#eef3f3}
.media .m{padding:8px}
.media input{font-size:11px;padding:5px 7px;cursor:copy}
.imgslot{display:grid;grid-template-columns:140px 1fr;gap:18px;align-items:start;padding:16px 0;border-bottom:1px solid #edf3f3}
.imgslot:last-child{border-bottom:0}
.imgslot > div{min-width:0}
.imgslot code{word-break:break-all}
code{background:#e3f4f1;padding:1px 6px;border-radius:5px;font-size:12.5px}
.imgslot img{width:140px;height:175px;object-fit:cover;border-radius:12px;background:#eef3f3}
.sticky-save{position:sticky;bottom:0;background:linear-gradient(transparent,#f3f7f7 30%);padding:18px 0 4px;margin-top:6px}
.msg-text{white-space:pre-wrap;max-width:420px}

.login{min-height:100vh;display:grid;place-items:center;background:linear-gradient(150deg,#0f2e33,#0a2226 55%,#0f8b8d)}
.login .box{background:#fff;padding:36px 32px;border-radius:18px;width:380px;max-width:92vw;box-shadow:0 24px 70px rgba(0,0,0,.35)}
.login .logo{color:#14262c}
.login .logo small{color:#5b7378}
.login h2{margin:18px 0 2px;font-size:22px}
.login .sub{color:#5b7378;margin-bottom:16px;font-size:14px}

@media(max-width:900px){
  .side{position:fixed;left:0;top:0;transform:translateX(-100%);transition:transform .25s;box-shadow:0 0 40px rgba(0,0,0,.3)}
  #navtoggle:checked ~ .wrap .side{transform:none}
  #navtoggle:checked ~ .wrap .scrim{display:block;position:fixed;inset:0;background:rgba(10,34,38,.45);z-index:25}
  .burger{display:flex}
  .body{padding:18px 16px 50px}
  .bar{padding:12px 16px}
  .bar h1{font-size:17px}
  .bar-r .btn.ghost{display:none}
  .row,.row3{grid-template-columns:1fr}
  .imgslot{grid-template-columns:96px 1fr}
  .imgslot img{width:96px;height:120px}
}
CSS;
}
