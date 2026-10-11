<?php
/* Bölmə: highlights — controller.php-dən yüklənir ($db, $count, $imgUrl mövcuddur). */
declare(strict_types=1);

$rows = $db->query('SELECT * FROM highlights ORDER BY sort, id')->fetchAll();
echo '<form method="post" class="panel">' . csrf_field() . '<input type="hidden" name="action" value="save">';
echo '<h3>Карточки под первым экраном</h3><p class="muted">Иконка — эмодзи (🔬 💎 🧸 🩺 …). Лучше всего смотрятся 4 карточки.</p>';
rows_editor($rows, ['icon' => ['Иконка', 'short'], 'title' => ['Заголовок', 'input'], 'descr' => ['Описание', 'input']], 2);
echo '<div class="sticky-save"><button class="btn">Сохранить</button></div></form>';
