<?php
/* Bölmə: education — controller.php-dən yüklənir ($db, $count, $imgUrl mövcuddur). */
declare(strict_types=1);

$rows = $db->query('SELECT * FROM education ORDER BY sort, id')->fetchAll();
echo '<form method="post" class="panel">' . csrf_field() . '<input type="hidden" name="action" value="save">';
echo '<h3>Образование и квалификация</h3><p class="muted">Можно добавлять курсы, стажировки, сертификаты. В описании — перенос строки сохраняется.</p>';
rows_editor($rows, ['years' => ['Годы', 'input'], 'title' => ['Учреждение', 'input'], 'descr' => ['Описание', 'textarea'], 'label' => ['Метка', 'input']], 1);
echo '<div class="sticky-save"><button class="btn">Сохранить</button></div></form>';
