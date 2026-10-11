<?php
/* Bölmə: services — controller.php-dən yüklənir ($db, $count, $imgUrl mövcuddur). */
declare(strict_types=1);

$grp = (string)($_GET['grp'] ?? 'diag_adult');
if (!isset(SVC_GROUPS[$grp])) $grp = 'diag_adult';
echo '<div class="tabs">';
foreach (SVC_GROUPS as $g => $label) {
    $n = $count('SELECT COUNT(*) FROM services WHERE grp=' . $db->quote($g));
    echo '<a' . ($g === $grp ? ' class="on"' : '') . ' href="index.php?section=services&grp=' . $g . '">' . e($label) . '<b>' . $n . '</b></a>';
}
echo '</div>';

$st = $db->prepare('SELECT * FROM services WHERE grp=? ORDER BY sort, id'); $st->execute([$grp]);
echo '<form method="post" class="panel">' . csrf_field() . '<input type="hidden" name="action" value="save"><input type="hidden" name="grp" value="' . e($grp) . '">';
echo '<h3>' . e(SVC_GROUPS[$grp]) . '</h3><p class="muted">№ — порядок вывода (меньше — выше). Снимите «Показ», чтобы скрыть пункт, не удаляя. Пустые строки внизу — для новых пунктов.</p>';
rows_editor($st->fetchAll(), ['title' => ['Название', 'input']], 3);
echo '<div class="sticky-save"><button class="btn">Сохранить</button></div></form>';

echo '<div class="row">';
echo '<form method="post" class="panel">' . csrf_field() . '<input type="hidden" name="action" value="bulk_add"><input type="hidden" name="grp" value="' . e($grp) . '">';
echo '<h3>Добавить списком</h3><p class="muted">Каждый пункт с новой строки — добавятся в конец списка.</p><textarea name="lines" style="min-height:140px"></textarea><p><button class="btn">Добавить</button></p></form>';
echo '<form method="post" class="panel">' . csrf_field() . '<input type="hidden" name="action" value="renumber"><input type="hidden" name="grp" value="' . e($grp) . '">';
echo '<h3>Порядок</h3><p class="muted">Пересчитать номера по текущему порядку: 10, 20, 30… — удобно, чтобы потом вставлять пункт между другими (например, 15).</p><button class="btn ghost">Пересчитать номера</button></form>';
echo '</div>';
