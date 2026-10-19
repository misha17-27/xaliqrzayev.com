<?php
declare(strict_types=1);
require_once __DIR__ . '/lib.php';
boot_session();
admin_lang();   // ?lang= cookie-yə çıxışdan əvvəl yazılsın

$section = preg_replace('/[^a-z_]/', '', (string)($_GET['section'] ?? 'overview'));

/* ---------------- quraşdırılmayıbsa — brauzerdə sehrbaz ---------------- */
$installed = false;
if (is_file(CONFIG_FILE)) {
    try { pdo()->query('SELECT 1 FROM admins LIMIT 1'); $installed = true; } catch (Throwable $e) {}
}
if (!$installed) {
    require __DIR__ . '/setup.php';
    setup_wizard(null);
}

/* ---------------- logout (yalnız öz səhifəmizdən: CSRF tokeni ilə) ---------------- */
if ($section === 'logout') {
    if (hash_equals(csrf_token(), (string)($_GET['t'] ?? ''))) {
        admin_log('logout');
        logout();
        redirect('index.php?section=login');
    }
    redirect('index.php');
}

/* ---------------- login (+ 2FA addımı) ---------------- */
if ($section === 'login') {
    if (current_admin()) redirect('index.php');
    $err = '';
    $step = !empty($_SESSION['pending_2fa']) ? '2fa' : 'pass';
    if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
        csrf_check();
        if (($_POST['step'] ?? '') === '2fa') {
            $r = attempt_2fa((string)($_POST['code'] ?? ''));
            if ($r === 'ok') redirect('index.php');
            if ($r === 'expired') { $step = 'pass'; $err = t('Время на ввод кода истекло. Войдите снова.'); }
            else { $step = '2fa'; $err = t('Неверный код.'); }
        } elseif (($_POST['step'] ?? '') === 'cancel') {
            unset($_SESSION['pending_2fa']); $step = 'pass';
        } else {
            $email = (string)($_POST['email'] ?? '');
            $wait = login_blocked($email);
            if ($wait > 0) {
                $err = t('Слишком много попыток входа. Повторите через ') . ceil($wait / 60) . t(' мин.');
            } elseif (!adm_turnstile_verify((string)($_POST['cf-turnstile-response'] ?? ''))) {
                $err = t('Подтвердите, что вы не робот.');
            } else {
                $r = attempt_login($email, (string)($_POST['password'] ?? ''));
                if ($r === 'ok') redirect('index.php');
                if ($r === '2fa') $step = '2fa';
                else $err = t('Неверный e-mail или пароль.');
            }
        }
    }
    head_html(t('Вход'));
    echo '<div class="login"><form class="box" method="post" autocomplete="on">';
    echo '<div class="login-top">' . brand_html() . lang_switch() . '</div>';
    echo t('<h2>Вход в панель</h2><div class="sub">Управление контентом сайта</div>');
    echo flash_render();
    if ($err) echo '<div class="flash err">' . e($err) . '</div>';
    echo csrf_field();
    if ($step === '2fa') {
        echo '<input type="hidden" name="step" value="2fa">';
        echo '<label for="code">' . e(t('Код из приложения-аутентификатора')) . '</label>';
        echo '<input id="code" type="text" name="code" required autofocus inputmode="numeric" pattern="[0-9 ]{6,7}" maxlength="7" autocomplete="one-time-code" style="font-size:22px;letter-spacing:.3em;text-align:center">';
        echo '<div class="hint">' . e(t('Откройте Google Authenticator (или другое приложение) и введите 6 цифр для xaliqrzayev.com.')) . '</div>';
        echo '<button class="btn" style="width:100%;margin-top:18px" type="submit">' . e(t('Подтвердить')) . '</button>';
        echo '<button class="btn ghost" style="width:100%;margin-top:8px" type="submit" name="step" value="cancel" formnovalidate>' . e(t('Назад')) . '</button>';
    } else {
        echo '<label>E-mail</label><input type="email" name="email" required autofocus autocomplete="username">';
        echo t('<label>Пароль</label><input type="password" name="password" required autocomplete="current-password">');
        echo '<div style="margin-top:14px;display:flex;justify-content:center">'; adm_turnstile_widget(); echo '</div>';
        echo t('<button class="btn" style="width:100%;margin-top:18px" type="submit">Войти</button>');
    }
    echo '</form></div></body></html>';
    exit;
}

/* ---------------- auth ---------------- */
require_login();
if (!isset(SECTIONS[$section])) $section = 'overview';
if (in_array($section, ADMIN_ONLY, true) && !is_admin()) {
    flash(t('Недостаточно прав (нужна роль Администратор).'), 'err');
    redirect('index.php?section=overview');
}
csrf_check();
$db = pdo();

/* Hər dəyişiklik jurnala yazılır (parollar və məzmun yox — yalnız bölmə və əməliyyat) */
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    admin_log('save', $section . '/' . preg_replace('/[^a-z_]/', '', (string)($_POST['action'] ?? '')) . (isset($_POST['id']) ? ' #' . (int)$_POST['id'] : ''));
}

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
