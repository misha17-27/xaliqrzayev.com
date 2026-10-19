<?php
/* Bölmə: contacts — controller.php-dən yüklənir ($db, $count, $imgUrl mövcuddur). */
declare(strict_types=1);

echo '<form method="post" class="panel">' . csrf_field() . '<input type="hidden" name="action" value="save">';
echo '<div class="row">';
foreach (CONTACT_FIELDS as $k => [$label, $ph]) {
    $v = (string)($CONTACT[$k] ?? kv_get('contacts', $k));   // bazada yoxdursa — saytdakı cari dəyər
    $wide = in_array($k, ['address', 'hours', 'map'], true);
    if ($wide) echo '</div>';
    echo '<div><label for="c-' . $k . '">' . e(t($label)) . '</label>';
    if ($k === 'address' || $k === 'hours') echo '<textarea id="c-' . $k . '" name="' . $k . '" placeholder="' . e(t($ph)) . '" style="min-height:64px">' . e($v) . '</textarea>';
    else echo '<input id="c-' . $k . '" type="text" name="' . $k . '" value="' . e($v) . '" placeholder="' . e(t($ph)) . '">';
    if ($k === 'map') echo t('<div class="hint">Google Maps → «Поделиться» → «Встраивание карт» → скопируйте код целиком или только ссылку из src.</div>');
    echo '</div>';
    if ($wide) echo '<div class="row">';
}
echo t('</div><div class="sticky-save"><button class="btn">Сохранить контакты</button></div></form>');
