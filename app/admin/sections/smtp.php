<?php
/* Bölmə: smtp — controller.php-dən yüklənir ($db, $count, $imgUrl mövcuddur). */
declare(strict_types=1);

echo '<form method="post" class="panel">' . csrf_field() . '<input type="hidden" name="action" value="save">';
echo t('<h3>Куда отправлять заявки</h3><label>E-mail получателя</label><input type="email" name="mail_to" value="') . e(setting('mail_to')) . '" placeholder="doctor@example.com">';
echo t('<div class="hint">Все заявки сохраняются в панели в любом случае. E-mail — дополнительное уведомление.</div>');
echo t('<h4>SMTP-сервер</h4><p class="muted">Если не заполнено — используется встроенная функция mail() хостинга. Для cPanel: сервер <code>mail.xaliqrzayev.com</code>, порт 465 (SSL) или 587 (TLS), логин — полный адрес ящика.</p>');
echo t('<div class="row3"><div><label>Сервер</label><input type="text" name="smtp_host" value="') . e(setting('smtp_host')) . '"></div>'
   . t('<div><label>Порт</label><input type="text" name="smtp_port" value="') . e(setting('smtp_port') ?: '587') . '"></div>'
   . t('<div><label>Шифрование</label><select name="smtp_secure">');
foreach (['tls' => 'TLS (587)', 'ssl' => 'SSL (465)', 'none' => t('Нет')] as $k => $l) echo '<option value="' . $k . '"' . (setting('smtp_secure') === $k ? ' selected' : '') . '>' . $l . '</option>';
echo '</select></div></div>';
echo t('<div class="row"><div><label>Логин</label><input type="text" name="smtp_user" value="') . e(setting('smtp_user')) . '" autocomplete="off"></div>'
   . t('<div><label>Пароль</label><input type="password" name="smtp_pass" value="" placeholder="') . (setting('smtp_pass') !== '' ? t('•••••••• (сохранён)') : '') . '" autocomplete="new-password"></div></div>';
echo t('<div class="row"><div><label>Адрес отправителя</label><input type="text" name="smtp_from" value="') . e(setting('smtp_from')) . '"></div>'
   . t('<div><label>Имя отправителя</label><input type="text" name="smtp_from_name" value="') . e(setting('smtp_from_name')) . '" placeholder="xaliqrzayev.com"></div></div>';
echo t('<p><button class="btn">Сохранить</button></p></form>');

echo '<form method="post" class="panel">' . csrf_field() . '<input type="hidden" name="action" value="test">';
echo t('<h3>Проверка</h3><label>Отправить тестовое письмо на</label><input type="email" name="test_to" value="') . e(setting('mail_to')) . '">';
echo t('<p><button class="btn navy">Отправить тест</button></p></form>');
