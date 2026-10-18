<?php
/* Bölmə: backup — məzmunun JSON nüsxəsi (yükləmə və bərpa). */
declare(strict_types=1);

$stats = [];
foreach (BACKUP_TABLES as $t => $_) $stats[] = $t . ': ' . $count("SELECT COUNT(*) FROM `$t`");

echo '<div class="panel"><h3>Скачать резервную копию</h3>';
echo '<p class="muted">Все тексты, услуги, образование, контакты, SEO и выбранные фото — одним JSON-файлом. Пароли, пользователи и заявки в копию не попадают.</p>';
echo '<p class="muted">Сейчас в базе — ' . e(implode(', ', $stats)) . '.</p>';
echo '<p><a class="btn" href="index.php?section=backup&export=json">Скачать копию</a></p></div>';

echo '<form method="post" enctype="multipart/form-data" class="panel" data-confirm="Текущий контент будет заменён содержимым файла. Продолжить?" data-nodirty>' . csrf_field()
   . '<input type="hidden" name="action" value="import">';
echo '<h3>Восстановить из копии</h3>';
echo '<p class="muted">Заменяет тексты, списки услуг, образование, контакты и SEO содержимым файла. Перед восстановлением рекомендуется скачать текущую копию. Сами файлы изображений не входят в копию — только пути к ним.</p>';
echo '<input type="file" name="file" accept="application/json,.json" required>';
echo '<p><button class="btn red">Восстановить</button></p></form>';
