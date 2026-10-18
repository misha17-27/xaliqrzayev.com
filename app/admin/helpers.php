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

/**
 * Sətir redaktoru: $fields = [ad => [etiket, tip]]
 * Sıra sürükləmə (və ya ↑↓ düymələri) ilə dəyişir; saxlayanda admin.js sort-u 10, 20, 30… yazır.
 * Telefonda cədvəl kartlara çevrilir (admin.css: .rows).
 */
function rows_editor(array $rows, array $fields, int $extra): void {
    for ($i = 0; $i < $extra; $i++) $rows[] = ['visible' => 1, 'sort' => ''];
    echo '<div class="tablewrap"><table class="rows"><thead><tr><th class="c" title="Порядок">⇅</th>';
    foreach ($fields as [$lbl]) echo '<th>' . e($lbl) . '</th>';
    echo '<th class="c">Показ</th><th class="c">Удалить</th></tr></thead><tbody data-sortable>';
    foreach ($rows as $i => $r) {
        $new = empty($r['id']);
        $cls = [];
        if ($new) $cls[] = 'new';
        elseif (empty($r['visible'])) $cls[] = 'hidden';
        echo '<tr' . ($cls ? ' class="' . implode(' ', $cls) . '"' : '') . ($new ? '' : ' draggable="true"') . '>';
        echo '<td class="c handle-cell">'
           . '<input type="hidden" name="r[' . $i . '][id]" value="' . e($r['id'] ?? '') . '">'
           . '<input type="hidden" name="r[' . $i . '][sort]" value="' . e($r['sort'] ?? '') . '" data-sort>'
           . ($new ? '<span class="newmark">+</span>'
                   : '<span class="handle" title="Перетащите">⋮⋮</span><span class="moves"><button type="button" class="mv" data-move="-1" title="Выше">↑</button><button type="button" class="mv" data-move="1" title="Ниже">↓</button></span>')
           . '</td>';
        foreach ($fields as $name => [$lbl, $type]) {
            $v = $r[$name] ?? '';
            $ph = $new ? ' placeholder="Новый пункт…"' : '';
            echo '<td data-label="' . e($lbl) . '">';
            if ($type === 'textarea') echo '<textarea name="r[' . $i . '][' . $name . ']" style="min-height:58px"' . $ph . '>' . e($v) . '</textarea>';
            else echo '<input type="text" name="r[' . $i . '][' . $name . ']" value="' . e($v) . '"' . $ph . '>';
            echo '</td>';
        }
        echo '<td class="c" data-label="Показ"><input type="checkbox" name="r[' . $i . '][visible]" value="1"' . (!empty($r['visible']) ? ' checked' : '') . '></td>';
        echo '<td class="c" data-label="Удалить">' . ($new ? '' : '<input type="checkbox" name="r[' . $i . '][del]" value="1" class="del">') . '</td></tr>';
    }
    echo '</tbody></table></div>';
}

/* ---------------- Ehtiyat nüsxə ---------------- */
const BACKUP_FORMAT = 'xaliqrzayev-content-v1';
/* Məzmun cədvəlləri: [ad => sütunlar]. Parollar, adminlər və müraciətlər nüsxəyə düşmür. */
const BACKUP_TABLES = [
    'texts'      => ['k', 'v'],
    'contacts'   => ['k', 'v'],
    'services'   => ['grp', 'title', 'sort', 'visible'],
    'highlights' => ['icon', 'title', 'descr', 'sort', 'visible'],
    'education'  => ['years', 'title', 'descr', 'label', 'sort', 'visible'],
    'seo_pages'  => ['slug', 'title', 'descr', 'robots'],
];
/* settings-dən yalnız təhlükəsiz açarlar */
const BACKUP_SETTINGS = ['photo_hero', 'og_image', 'ga_id', 'mail_to'];

function backup_export(PDO $db): array {
    $out = ['_format' => BACKUP_FORMAT, '_created' => date('Y-m-d H:i:s')];
    foreach (BACKUP_TABLES as $t => $cols) {
        $out[$t] = $db->query('SELECT `' . implode('`,`', $cols) . "` FROM `$t`" . (in_array('sort', $cols, true) ? ' ORDER BY sort, id' : ''))->fetchAll();
    }
    $st = $db->prepare('SELECT k, v FROM settings WHERE k = ?');
    $out['settings'] = [];
    foreach (BACKUP_SETTINGS as $k) { $st->execute([$k]); if ($r = $st->fetch()) $out['settings'][] = $r; }
    return $out;
}

/** Nüsxədən bərpa — bir tranzaksiyada; xəta olarsa heç nə dəyişmir */
function backup_restore(PDO $db, array $data): int {
    $n = 0;
    $db->beginTransaction();
    try {
        foreach (BACKUP_TABLES as $t => $cols) {
            if (!isset($data[$t]) || !is_array($data[$t])) continue;
            $db->exec("DELETE FROM `$t`");
            $ins = $db->prepare("INSERT INTO `$t` (`" . implode('`,`', $cols) . '`) VALUES (' . implode(',', array_fill(0, count($cols), '?')) . ')');
            foreach ($data[$t] as $row) {
                if (!is_array($row)) continue;
                $vals = [];
                foreach ($cols as $c) $vals[] = is_scalar($row[$c] ?? '') ? (string)($row[$c] ?? '') : '';
                $ins->execute($vals); $n++;
            }
        }
        foreach ((array)($data['settings'] ?? []) as $r) {
            if (is_array($r) && in_array($r['k'] ?? '', BACKUP_SETTINGS, true)) { kv_set('settings', (string)$r['k'], (string)($r['v'] ?? '')); $n++; }
        }
        $db->commit();
    } catch (Throwable $e) {
        $db->rollBack();
        throw $e;
    }
    return $n;
}
