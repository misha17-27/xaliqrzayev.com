<?php
/* Bölmə: images — controller.php-dən yüklənir ($db, $count, $imgUrl mövcuddur). */
declare(strict_types=1);

/* Kitabxana: assets/img (kök) + uploads */
$lib = [];
$base = PUBLIC_DIR . '/assets/img/';
foreach (array_merge(glob($base . '*.{jpg,jpeg,png,webp,gif}', GLOB_BRACE) ?: [], glob($base . 'uploads/*.{jpg,jpeg,png,webp,gif}', GLOB_BRACE) ?: []) as $f) {
    $rel = substr($f, strlen($base));
    /* yanında jpg/png olan .webp — avtomatik nüsxədir, göstərmirik */
    if (preg_match('/\.webp$/i', $rel) && (is_file(substr($f, 0, -5) . '.jpg') || is_file(substr($f, 0, -5) . '.jpeg') || is_file(substr($f, 0, -5) . '.png'))) continue;
    $lib[$rel] = filemtime($f);
}
arsort($lib);

echo t('<div class="panel"><h3>Фотографии на сайте</h3>');
foreach (IMAGE_SLOTS as $slot => [$label, $hint]) {
    $cur = setting($slot);
    echo '<form method="post" enctype="multipart/form-data" class="imgslot">' . csrf_field()
       . '<input type="hidden" name="action" value="slot"><input type="hidden" name="slot" value="' . $slot . '">';
    echo '<img src="' . e($imgUrl($cur)) . '?v=' . (int)@filemtime($base . $cur) . '" alt="">';
    echo '<div><b>' . e(t($label)) . '</b><div class="hint">' . e(t($hint)) . t('</div><div class="hint">Сейчас: <code>') . e($cur) . '</code></div>';
    echo t('<label>Загрузить новое</label><input type="file" name="file" accept="image/jpeg,image/png,image/webp">');
    echo t('<label>…или выбрать из библиотеки</label><select name="path"><option value="">—</option>');
    foreach ($lib as $rel => $_) echo '<option value="' . e($rel) . '"' . ($rel === $cur ? ' selected' : '') . '>' . e($rel) . '</option>';
    echo t('</select><p><button class="btn sm">Применить</button></p></div></form>');
}
echo '</div>';

echo '<form method="post" enctype="multipart/form-data" class="panel">' . csrf_field() . '<input type="hidden" name="action" value="upload">';
echo t('<h3>Библиотека изображений</h3><p class="muted">JPG, PNG, WEBP до 15 МБ. Большие фото автоматически уменьшаются до 2000px, создаётся WebP-версия.</p>');
echo t('<input type="file" name="file" accept="image/jpeg,image/png,image/webp,image/gif"> <button class="btn sm" style="margin-top:10px">Загрузить</button></form>');

echo '<div class="panel"><div class="grid-media">';
foreach ($lib as $rel => $mt) {
    echo '<div class="media"><img src="' . e($imgUrl($rel)) . '" alt="" loading="lazy"><div class="m">'
       . '<input type="text" value="' . e($rel) . t('" readonly data-copy title="Нажмите, чтобы скопировать путь">');
    if (str_starts_with($rel, 'uploads/')) {
        echo t('<form method="post" style="margin-top:6px" data-confirm="Удалить файл?">') . csrf_field()
           . '<input type="hidden" name="action" value="del"><input type="hidden" name="path" value="' . e($rel) . t('"><button class="btn sm red">Удалить</button></form>');
    }
    echo '</div></div>';
}
echo '</div></div>';
