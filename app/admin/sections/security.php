<?php
/* Bölmə: security — controller.php-dən yüklənir ($db, $count, $imgUrl mövcuddur). */
declare(strict_types=1);

echo '<form method="post" class="panel">' . csrf_field() . '<input type="hidden" name="action" value="save">';
echo t('<h3>Cloudflare Turnstile</h3><p class="muted">Защищает форму записи на сайте и вход в панель от ботов. Ключи: <a href="https://dash.cloudflare.com" target="_blank" rel="noopener">dash.cloudflare.com</a> → Turnstile → Add site (домен xaliqrzayev.com).</p>');
echo t('<p>Статус: ') . (adm_turnstile_on() ? t('<span class="pill">Включено</span>') : t('<span class="pill warn">Выключено</span>')) . '</p>';
echo '<label>Site key</label><input type="text" name="turnstile_site" value="' . e(setting('turnstile_site')) . '">';
echo '<label>Secret key</label><input type="password" name="turnstile_secret" placeholder="' . (setting('turnstile_secret') !== '' ? t('•••••••• (сохранён)') : '') . '" autocomplete="new-password">';
echo t('<label class="inline" style="margin-top:14px"><input type="checkbox" name="turnstile_off" value="1"> Отключить Turnstile (удалить ключи)</label>');
echo t('<div class="hint">Внимание: при неверных ключах вход в панель станет невозможен. Сначала проверьте форму на сайте.</div>');
echo t('<p><button class="btn">Сохранить</button></p></form>');

echo t('<div class="panel"><h3>Встроенная защита</h3><ul class="muted" style="line-height:1.9">')
   . t('<li>Вход: не более ') . LOGIN_MAX_TRIES . t(' неудачных попыток за 15 минут с одного IP</li>')
   . t('<li>Сессия: завершается после 2 часов бездействия, максимум 12 часов</li>')
   . '<li>' . e(t('Двухфакторный вход (2FA) — включается в «Мой профиль»')) . '</li>'
   . '<li>' . e(t('Смена пароля, отключение пользователя или сброс 2FA сразу завершают его входы на всех устройствах')) . '</li>'
   . '<li>' . e(t('Ограничение попыток: 6 с одного IP и 10 на один аккаунт за 15 минут')) . '</li>'
   . '<li>' . e(t('Заголовки безопасности (CSP, HSTS), реальный IP проверяется по сетям Cloudflare')) . '</li>'
   . t('<li>CSRF-токен во всех формах панели</li>')
   . t('<li>Форма на сайте: скрытое поле-ловушка, подписанный токен времени, не более 3 заявок за 10 минут с одного IP</li>')
   . t('<li>Загрузка: только изображения, проверка содержимого файла, случайные имена</li></ul></div>');

/* ---------- Əməliyyat jurnalı ---------- */
$log = [];
try { $log = $db->query('SELECT * FROM admin_log ORDER BY id DESC LIMIT 100')->fetchAll(); } catch (Throwable $e) {}
$fails = 0;
try { $fails = (int)$db->query('SELECT COUNT(*) FROM login_fails WHERE at > ' . (time() - 86400))->fetchColumn(); } catch (Throwable $e) {}
$labels = [
    'login' => t('Вход'), 'login_fail' => t('Неудачный вход'), '2fa_fail' => t('Неверный код 2FA'), 'logout' => t('Выход'),
    'save' => t('Изменение'), 'password_changed' => t('Смена пароля'), '2fa_on' => t('2FA включена'), '2fa_off' => t('2FA отключена'), '2fa_reset' => t('2FA сброшена'),
];
echo '<div class="panel"><h3>' . e(t('Журнал действий')) . '</h3>';
echo '<p class="muted">' . e(t('Последние 100 событий: входы, неудачные попытки, изменения. Хранится 180 дней.')) . ' '
   . e(t('Неудачных попыток входа за сутки:')) . ' <b>' . $fails . '</b></p>';
if (!$log) echo '<p class="muted">—</p>';
else {
    echo '<div class="tablewrap"><table><tr><th>' . e(t('Дата')) . '</th><th>' . e(t('Пользователь')) . '</th><th>' . e(t('Событие')) . '</th><th>IP</th></tr>';
    foreach ($log as $r) {
        $warn = in_array($r['action'], ['login_fail', '2fa_fail'], true);
        echo '<tr' . ($warn ? ' class="unread"' : '') . '><td style="white-space:nowrap">' . e($r['at']) . '</td><td>' . e($r['admin']) . '</td>'
           . '<td>' . e($labels[$r['action']] ?? $r['action']) . ($r['detail'] !== '' ? ' <span class="muted">' . e($r['detail']) . '</span>' : '') . '</td>'
           . '<td class="muted">' . e($r['ip']) . '</td></tr>';
    }
    echo '</table></div>';
}
echo '</div>';
