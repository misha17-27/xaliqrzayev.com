<?php
/* Cədvəl redaktorunun köməkçiləri: saxlama və göstərmə. */
declare(strict_types=1);

/* Sadə cədvəl redaktorunun saxlanması: sort/visible/del + sahələr */
function save_rows(PDO $db, string $table, array $cols, string $keyCol, array $extraInsert = []): array {
    $add = $upd = $del = 0;
    foreach (($_POST['r'] ?? []) as $row) {
        if (!is_array($row)) continue;
        $id  = (int)($row['id'] ?? 0);
        $key = trim((string)($row[$keyCol] ?? ''));
        if ($id && (!empty($row['del']) || $key === '')) {
            $db->prepare("DELETE FROM `$table` WHERE id=?")->execute([$id]); $del++; continue;
        }
        if ($key === '') continue;
        $vals = [];
        foreach ($cols as $c) $vals[] = trim((string)($row[$c] ?? ''));
        $vals[] = (int)($row['sort'] ?? 0);
        $vals[] = !empty($row['visible']) ? 1 : 0;
        $all = array_merge($cols, ['sort', 'visible']);
        if ($id) {
            $set = implode(',', array_map(function ($c) { return "`$c`=?"; }, $all));
            $vals[] = $id;
            $db->prepare("UPDATE `$table` SET $set WHERE id=?")->execute($vals); $upd++;
        } else {
            $all  = array_merge($all, array_keys($extraInsert));
            $vals = array_merge($vals, array_values($extraInsert));
            $cl = '`' . implode('`,`', $all) . '`';
            $ph = implode(',', array_fill(0, count($all), '?'));
            $db->prepare("INSERT INTO `$table` ($cl) VALUES ($ph)")->execute($vals); $add++;
        }
    }
    return [$add, $upd, $del];
}
function rows_msg(array $r): string {
    [$a, , $d] = $r;
    return 'Сохранено.' . ($a ? " Добавлено: $a." : '') . ($d ? " Удалено: $d." : '');
}

/** Sətir redaktoru: $fields = [ad => [etiket, tip]] */
function rows_editor(array $rows, array $fields, int $extra): void {
    for ($i = 0; $i < $extra; $i++) $rows[] = ['visible' => 1, 'sort' => ''];
    echo '<div class="tablewrap"><table><tr><th class="c">№</th>';
    foreach ($fields as [$lbl]) echo '<th>' . e($lbl) . '</th>';
    echo '<th class="c">Показ</th><th class="c">Удалить</th></tr>';
    foreach ($rows as $i => $r) {
        $new = empty($r['id']);
        echo '<tr' . (!$new && empty($r['visible']) ? ' class="hidden"' : '') . '>';
        echo '<td class="c"><input type="hidden" name="r[' . $i . '][id]" value="' . e($r['id'] ?? '') . '">'
           . '<input type="number" name="r[' . $i . '][sort]" value="' . e($r['sort'] ?? '') . '" placeholder="' . ($new ? 'новый' : '') . '"></td>';
        foreach ($fields as $name => [$lbl, $type]) {
            $v = $r[$name] ?? '';
            if ($type === 'textarea') echo '<td><textarea name="r[' . $i . '][' . $name . ']" style="min-height:58px">' . e($v) . '</textarea></td>';
            else echo '<td><input type="text" name="r[' . $i . '][' . $name . ']" value="' . e($v) . '"' . ($type === 'short' ? ' style="width:70px"' : '') . '></td>';
        }
        echo '<td class="c"><input type="checkbox" name="r[' . $i . '][visible]" value="1"' . (!empty($r['visible']) ? ' checked' : '') . '></td>';
        echo '<td class="c">' . ($new ? '' : '<input type="checkbox" name="r[' . $i . '][del]" value="1">') . '</td></tr>';
    }
    echo '</table></div>';
}
