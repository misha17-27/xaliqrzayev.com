<?php
/* Bölmə: profile — ad, e-poçt (loqin), parol və iki mərhələli giriş (2FA). */
declare(strict_types=1);

$a = current_admin();
$st = $db->prepare('SELECT totp_secret FROM admins WHERE id = ?'); $st->execute([(int)$a['id']]);
$has2fa = (string)$st->fetchColumn() !== '';

echo '<div class="row"><form method="post" class="panel">' . csrf_field() . '<input type="hidden" name="action" value="save">';
echo t('<h3>Профиль</h3><label>Имя</label><input type="text" name="name" value="') . e($a['name']) . t('"><label>E-mail (логин)</label><input type="email" name="email" value="') . e($a['email']) . '" required>';
echo '<label>' . e(t('Текущий пароль (нужен для смены e-mail)')) . '</label><input type="password" name="cur" autocomplete="current-password">';
echo t('<p><button class="btn">Сохранить</button></p></form>');
echo '<form method="post" class="panel">' . csrf_field() . '<input type="hidden" name="action" value="passwd">';
echo t('<h3>Смена пароля</h3><label>Текущий пароль</label><input type="password" name="old" required autocomplete="current-password">');
echo t('<label>Новый пароль (мин. 8)</label><input type="password" name="new" required minlength="8" autocomplete="new-password"><label>Повторите</label><input type="password" name="new2" required autocomplete="new-password">');
echo '<div class="hint">' . e(t('После смены пароля все другие входы в ваш аккаунт будут завершены.')) . '</div>';
echo t('<p><button class="btn">Изменить пароль</button></p></form></div>');

/* ---------- 2FA ---------- */
echo '<div class="panel"><h3>' . e(t('Двухфакторная защита (2FA)')) . '</h3>';
echo '<p class="muted">' . e(t('При входе кроме пароля понадобится 6-значный код из приложения на телефоне (Google Authenticator, Microsoft Authenticator и др.). Даже если пароль украдут, без телефона войти не получится.')) . '</p>';

if ($has2fa) {
    echo '<p>' . e(t('Статус:')) . ' <span class="pill">' . e(t('Включено')) . '</span></p>';
    echo '<form method="post" data-confirm="' . e(t('Отключить двухфакторную защиту?')) . '">' . csrf_field() . '<input type="hidden" name="action" value="totp_off">';
    echo '<div class="row"><div><label>' . e(t('Текущий пароль')) . '</label><input type="password" name="cur" required autocomplete="current-password"></div>'
       . '<div><label>' . e(t('Код из приложения')) . '</label><input type="text" name="code" required inputmode="numeric" maxlength="7" autocomplete="one-time-code"></div></div>';
    echo '<p><button class="btn red">' . e(t('Отключить 2FA')) . '</button></p></form>';
} else {
    $pending = $_SESSION['totp_setup'] ?? '';
    if ($pending === '') {
        echo '<p>' . e(t('Статус:')) . ' <span class="pill warn">' . e(t('Выключено')) . '</span></p>';
        echo '<form method="post">' . csrf_field() . '<input type="hidden" name="action" value="totp_start"><button class="btn">' . e(t('Включить 2FA')) . '</button></form>';
    } else {
        $uri = totp_uri($pending, (string)$a['email']);
        echo '<ol class="muted" style="line-height:1.9;padding-left:20px">'
           . '<li>' . e(t('Установите на телефон Google Authenticator (или Microsoft Authenticator).')) . '</li>'
           . '<li>' . e(t('В приложении нажмите «+» и отсканируйте QR-код (или введите ключ вручную).')) . '</li>'
           . '<li>' . e(t('Введите 6-значный код из приложения и нажмите «Подтвердить».')) . '</li></ol>';
        echo '<div class="totp-setup"><div id="totp-qr" data-uri="' . e($uri) . '"></div><div>'
           . '<label>' . e(t('Ключ для ручного ввода')) . '</label><input type="text" value="' . e(trim(chunk_split($pending, 4, ' '))) . '" readonly data-copy style="font-family:monospace;letter-spacing:.08em">'
           . '<p class="hint"><a href="' . e($uri) . '">' . e(t('Открыть в приложении (на телефоне)')) . '</a></p>';
        echo '<form method="post">' . csrf_field() . '<input type="hidden" name="action" value="totp_confirm">'
           . '<label>' . e(t('Код из приложения')) . '</label><input type="text" name="code" required inputmode="numeric" maxlength="7" autocomplete="one-time-code" style="font-size:20px;letter-spacing:.25em">'
           . '<p><button class="btn">' . e(t('Подтвердить')) . '</button> '
           . '<button class="btn ghost" name="action" value="totp_cancel" formnovalidate>' . e(t('Отмена')) . '</button></p></form>';
        echo '</div></div>';
        echo '<script src="' . e(admin_asset('js/vendor/qrcode.min.js')) . '"></script>';
    }
}
echo '</div>';
