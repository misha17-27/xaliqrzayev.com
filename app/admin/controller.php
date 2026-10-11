<?php
declare(strict_types=1);
require_once __DIR__ . '/lib.php';
boot_session();

$section = preg_replace('/[^a-z_]/', '', (string)($_GET['section'] ?? 'overview'));

/* ---------------- baza qurulmayıbsa ---------------- */
try {
    pdo()->query('SELECT 1 FROM admins LIMIT 1');
} catch (Throwable $e) {
    header('Content-Type: text/html; charset=utf-8');
    echo '<meta charset="utf-8"><meta name="robots" content="noindex"><style>body{font-family:Montserrat,Arial,sans-serif;max-width:640px;margin:80px auto;padding:0 20px;line-height:1.6;color:#14262c}'
       . 'code{background:#e3f4f1;padding:2px 8px;border-radius:6px}</style>'
       . '<h2>Панель ещё не установлена</h2>'
       . '<p>1. Скопируйте <code>config/config.sample.php</code> → <code>config/config.php</code> и заполните его.</p>'
       . '<p>2. Откройте один раз <code>/admin/install.php?token=…</code> (токен — в <code>config/config.php</code>).</p>';
    exit;
}

/* ---------------- logout ---------------- */
if ($section === 'logout') { logout(); redirect('index.php?section=login'); }

/* ---------------- login ---------------- */
if ($section === 'login') {
    if (current_admin()) redirect('index.php');
    $err = '';
    if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
        csrf_check();
        $wait = login_blocked();
        if ($wait > 0) {
            $err = 'Слишком много попыток входа. Повторите через ' . ceil($wait / 60) . ' мин.';
        } elseif (!adm_turnstile_verify((string)($_POST['cf-turnstile-response'] ?? ''))) {
            $err = 'Подтвердите, что вы не робот.';
        } elseif (attempt_login((string)($_POST['email'] ?? ''), (string)($_POST['password'] ?? ''))) {
            redirect('index.php');
        } else {
            $err = 'Неверный e-mail или пароль.';
        }
    }
    head_html('Вход');
    echo '<div class="login"><form class="box" method="post">';
    echo brand_html();
    echo '<h2>Вход в панель</h2><div class="sub">Управление контентом сайта</div>';
    if ($err) echo '<div class="flash err">' . e($err) . '</div>';
    echo csrf_field();
    echo '<label>E-mail</label><input type="email" name="email" required autofocus autocomplete="username">';
    echo '<label>Пароль</label><input type="password" name="password" required autocomplete="current-password">';
    echo '<div style="margin-top:14px;display:flex;justify-content:center">'; adm_turnstile_widget(); echo '</div>';
    echo '<button class="btn" style="width:100%;margin-top:18px" type="submit">Войти</button>';
    echo '</form></div></body></html>';
    exit;
}

/* ---------------- auth ---------------- */
require_login();
if (!isset(SECTIONS[$section])) $section = 'overview';
if (in_array($section, ADMIN_ONLY, true) && !is_admin()) {
    flash('Недостаточно прав (нужна роль Администратор).', 'err');
    redirect('index.php?section=overview');
}
csrf_check();
$db = pdo();

require __DIR__ . '/fields.php';
require __DIR__ . '/helpers.php';

/* POST və eksport (cavab redirect/fayl ilə bitir) */
require __DIR__ . '/actions.php';

/* ================== RENDER ================== */
layout_top($section, SECTIONS[$section][0]);

$count = function (string $sql) use ($db): int { try { return (int)$db->query($sql)->fetchColumn(); } catch (Throwable $e) { return 0; } };
$imgUrl = function (string $p): string { return '../assets/img/' . $p; };

/* Bölmənin görünüşü: app/admin/sections/<section>.php */
$view = __DIR__ . '/sections/' . $section . '.php';
if (is_file($view)) require $view;

layout_bottom();
