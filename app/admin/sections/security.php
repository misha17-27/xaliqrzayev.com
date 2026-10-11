<?php
/* Bölmə: security — controller.php-dən yüklənir ($db, $count, $imgUrl mövcuddur). */
declare(strict_types=1);

echo '<form method="post" class="panel">' . csrf_field() . '<input type="hidden" name="action" value="save">';
echo '<h3>Cloudflare Turnstile</h3><p class="muted">Защищает форму записи на сайте и вход в панель от ботов. Ключи: <a href="https://dash.cloudflare.com" target="_blank" rel="noopener">dash.cloudflare.com</a> → Turnstile → Add site (домен xaliqrzayev.com).</p>';
echo '<p>Статус: ' . (adm_turnstile_on() ? '<span class="pill">Включено</span>' : '<span class="pill warn">Выключено</span>') . '</p>';
echo '<label>Site key</label><input type="text" name="turnstile_site" value="' . e(setting('turnstile_site')) . '">';
echo '<label>Secret key</label><input type="password" name="turnstile_secret" placeholder="' . (setting('turnstile_secret') !== '' ? '•••••••• (сохранён)' : '') . '" autocomplete="new-password">';
echo '<label class="inline" style="margin-top:14px"><input type="checkbox" name="turnstile_off" value="1"> Отключить Turnstile (удалить ключи)</label>';
echo '<div class="hint">Внимание: при неверных ключах вход в панель станет невозможен. Сначала проверьте форму на сайте.</div>';
echo '<p><button class="btn">Сохранить</button></p></form>';

echo '<div class="panel"><h3>Встроенная защита</h3><ul class="muted" style="line-height:1.9">'
   . '<li>Вход: не более ' . LOGIN_MAX_TRIES . ' неудачных попыток за 15 минут с одного IP</li>'
   . '<li>Сессия: завершается после 2 часов бездействия, максимум 12 часов</li>'
   . '<li>CSRF-токен во всех формах панели</li>'
   . '<li>Форма на сайте: скрытое поле-ловушка, подписанный токен времени, не более 3 заявок за 10 минут с одного IP</li>'
   . '<li>Загрузка: только изображения, проверка содержимого файла, случайные имена</li></ul></div>';
