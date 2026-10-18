<?php
/* Bölmə: seo — controller.php-dən yüklənir ($db, $count, $imgUrl mövcuddur). */
declare(strict_types=1);

$st = $db->prepare('SELECT * FROM seo_pages WHERE slug=?'); $st->execute(['home']);
$p = $st->fetch() ?: ['title' => '', 'descr' => '', 'robots' => 'index,follow'];
echo '<form method="post" class="panel">' . csrf_field() . '<input type="hidden" name="action" value="save">';
echo '<h3>Главная страница</h3>';
echo '<label>Title</label><input type="text" name="title" value="' . e($p['title']) . '" data-max="60" data-min="30">';
echo '<label>Description</label><textarea name="descr" data-max="160" data-min="120">' . e($p['descr']) . '</textarea>';
echo '<label>Индексация</label><select name="robots">';
foreach (['index,follow' => 'Разрешена (index, follow)', 'noindex,nofollow' => 'Запрещена (noindex) — для теста'] as $k => $l)
    echo '<option value="' . $k . '"' . ($p['robots'] === $k ? ' selected' : '') . '>' . e($l) . '</option>';
echo '</select>';
echo '<h4>Предпросмотр в Google</h4><div style="border:1px solid #e3ecec;border-radius:10px;padding:14px 16px;max-width:600px">'
   . '<div style="color:#1a0dab;font-size:18px">' . e($p['title']) . '</div><div style="color:#006621;font-size:13px">xaliqrzayev.com</div>'
   . '<div style="color:#545454;font-size:13px">' . e(mb_strimwidth((string)$p['descr'], 0, 160, '…')) . '</div></div>';
echo '<p><button class="btn">Сохранить</button></p></form>';

echo '<form method="post" class="panel">' . csrf_field() . '<input type="hidden" name="action" value="save_ga">';
echo '<h3>Google Analytics</h3><label>Measurement ID</label><input type="text" name="ga_id" value="' . e(setting('ga_id')) . '" placeholder="G-XXXXXXXXXX">';
echo '<div class="hint">Пусто — аналитика отключена.</div><p><button class="btn">Сохранить</button></p></form>';
