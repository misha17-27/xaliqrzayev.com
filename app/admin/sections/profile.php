<?php
/* Bölmə: profile — controller.php-dən yüklənir ($db, $count, $imgUrl mövcuddur). */
declare(strict_types=1);

$a = current_admin();
echo '<div class="row"><form method="post" class="panel">' . csrf_field() . '<input type="hidden" name="action" value="save">';
echo t('<h3>Профиль</h3><label>Имя</label><input type="text" name="name" value="') . e($a['name']) . t('"><label>E-mail (логин)</label><input type="email" name="email" value="') . e($a['email']) . '" required>';
echo t('<p><button class="btn">Сохранить</button></p></form>');
echo '<form method="post" class="panel">' . csrf_field() . '<input type="hidden" name="action" value="passwd">';
echo t('<h3>Смена пароля</h3><label>Текущий пароль</label><input type="password" name="old" required autocomplete="current-password">');
echo t('<label>Новый пароль (мин. 8)</label><input type="password" name="new" required minlength="8" autocomplete="new-password"><label>Повторите</label><input type="password" name="new2" required autocomplete="new-password">');
echo t('<p><button class="btn">Изменить пароль</button></p></form></div>');
