<?php
/* Bölmə: contacts — controller.php-dən yüklənir ($db, $count, $imgUrl mövcuddur). */
declare(strict_types=1);

echo '<form method="post" class="panel">' . csrf_field() . '<input type="hidden" name="action" value="save">';
echo '<div class="row">';
foreach (CONTACT_FIELDS as $k => [$label, $ph]) {
    $v = kv_get('contacts', $k);
    $wide = in_array($k, ['address', 'hours', 'map'], true);
    if ($wide) echo '</div>';
    echo '<div><label for="c-' . $k . '">' . e($label) . '</label>';
    if ($k === 'address' || $k === 'hours') echo '<textarea id="c-' . $k . '" name="' . $k . '" placeholder="' . e($ph) . '" style="min-height:64px">' . e($v) . '</textarea>';
    else echo '<input id="c-' . $k . '" type="text" name="' . $k . '" value="' . e($v) . '" placeholder="' . e($ph) . '">';
    if ($k === 'map') echo '<div class="hint">Google Maps → «Поделиться» → «Встраивание карт» → скопируйте код целиком или только ссылку из src.</div>';
    echo '</div>';
    if ($wide) echo '<div class="row">';
}
echo '</div><div class="sticky-save"><button class="btn">Сохранить контакты</button></div></form>';
