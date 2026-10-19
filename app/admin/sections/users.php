<?php
/* Bölmə: users — controller.php-dən yüklənir ($db, $count, $imgUrl mövcuddur). */
declare(strict_types=1);

$me = (int)current_admin()['id'];
$users = $db->query('SELECT * FROM admins ORDER BY id')->fetchAll();
echo '<form method="post" class="panel">' . csrf_field() . '<input type="hidden" name="action" value="save">';
echo t('<h3>Пользователи панели</h3><p class="muted">Редактор может менять контент и заявки, но не видит настройки почты, безопасности и пользователей.</p>');
echo t('<div class="tablewrap"><table><tr><th>E-mail</th><th>Имя</th><th>Роль</th><th class="c">Активен</th><th>Новый пароль</th><th>Последний вход</th></tr>');
foreach ($users as $u) {
    $id = (int)$u['id']; $self = $id === $me;
    echo '<tr><td>' . e($u['email']) . ($self ? t(' <span class="pill">вы</span>') : '') . '</td>'
       . '<td><input type="text" name="u[' . $id . '][name]" value="' . e($u['name']) . '"></td>'
       . '<td><select name="u[' . $id . '][role]"' . ($self ? ' disabled' : '') . '><option value="admin"' . ($u['role'] === 'admin' ? ' selected' : '') . t('>Администратор</option><option value="editor"') . ($u['role'] !== 'admin' ? ' selected' : '') . t('>Редактор</option></select></td>')
       . '<td class="c"><input type="checkbox" name="u[' . $id . '][active]" value="1"' . ((int)$u['active'] ? ' checked' : '') . ($self ? ' disabled' : '') . '></td>'
       . '<td><input type="password" name="u[' . $id . t('][password]" placeholder="не менять" autocomplete="new-password"></td>')
       . '<td class="muted">' . e((string)$u['last_login']) . '</td></tr>';
}
echo t('</table></div><p><button class="btn">Сохранить</button></p></form>');

foreach ($users as $u) {
    if ((int)$u['id'] === $me) continue;
    echo t('<form method="post" style="display:inline-block;margin:0 8px 8px 0" data-confirm="Удалить пользователя ') . e($u['email']) . '?">' . csrf_field()
       . '<input type="hidden" name="action" value="del"><input type="hidden" name="id" value="' . (int)$u['id'] . t('"><button class="btn sm red">Удалить ') . e($u['email']) . '</button></form>';
}

echo '<form method="post" class="panel" style="margin-top:12px">' . csrf_field() . '<input type="hidden" name="action" value="add">';
echo t('<h3>Добавить пользователя</h3><div class="row"><div><label>E-mail</label><input type="email" name="email" required></div><div><label>Имя</label><input type="text" name="name"></div></div>');
echo t('<div class="row"><div><label>Пароль (мин. 8)</label><input type="password" name="password" required minlength="8" autocomplete="new-password"></div><div><label>Роль</label><select name="role"><option value="editor">Редактор</option><option value="admin">Администратор</option></select></div></div>');
echo t('<p><button class="btn">Добавить</button></p></form>');
