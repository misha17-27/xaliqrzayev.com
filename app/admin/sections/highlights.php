<?php
/* Bölmə: highlights — controller.php-dən yüklənir ($db, $count, $imgUrl mövcuddur). */
declare(strict_types=1);

$rows = $db->query('SELECT * FROM highlights ORDER BY sort, id')->fetchAll();
echo '<form method="post" class="panel">' . csrf_field() . '<input type="hidden" name="action" value="save">';
echo t('<h3>Полоса под первым экраном</h3><p class="muted">Тёмная полоса из 4 ячеек: золотой заголовок и подпись. Лучше всего смотрятся 4 пункта.</p>');
rows_editor($rows, ['title' => [t('Заголовок (золотой)'), 'input'], 'descr' => [t('Подпись'), 'input']], 2);
echo t('<div class="sticky-save"><button class="btn">Сохранить</button></div></form>');
