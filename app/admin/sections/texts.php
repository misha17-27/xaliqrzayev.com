<?php
/* Bölmə: texts — controller.php-dən yüklənir ($db, $count, $imgUrl mövcuddur). */
declare(strict_types=1);

echo '<form method="post" class="panel">' . csrf_field() . '<input type="hidden" name="action" value="save">';
echo '<p class="muted">Все тексты главной страницы. Списки услуг — в разделе «Услуги и операции».</p>';
foreach (TEXT_FIELDS as $group => $fields) {
    echo '<fieldset><legend>' . e($group) . '</legend>';
    foreach ($fields as $k => $def) {
        [$label, $type] = $def;
        $v = kv_get('texts', $k);
        echo '<label for="t-' . e($k) . '">' . e($label) . '</label>';
        if ($type === 'textarea') echo '<textarea id="t-' . e($k) . '" name="t[' . e($k) . ']"' . ($k === 'about_body' ? ' style="min-height:220px"' : '') . '>' . e($v) . '</textarea>';
        else echo '<input id="t-' . e($k) . '" type="text" name="t[' . e($k) . ']" value="' . e($v) . '">';
        if (!empty($def[2])) echo '<div class="hint">' . e($def[2]) . '</div>';
    }
    echo '</fieldset>';
}
echo '<div class="sticky-save"><button class="btn">Сохранить тексты</button></div></form>';
