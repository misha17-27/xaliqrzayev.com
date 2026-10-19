<?php
/* Bölmə: submissions — controller.php-dən yüklənir ($db, $count, $imgUrl mövcuddur). */
declare(strict_types=1);

$f = (string)($_GET['f'] ?? '');
$where = '1=1'; $args = [];
if ($f === 'unread') $where = 'is_read=0';
elseif (isset(SUB_STATUS[$f])) { $where = 'status=?'; $args[] = $f; }
$st = $db->prepare("SELECT * FROM submissions WHERE $where ORDER BY id DESC LIMIT 300"); $st->execute($args);
$rows = $st->fetchAll();

echo '<div class="tabs"><a' . ($f === '' ? ' class="on"' : '') . t(' href="index.php?section=submissions">Все</a>')
   . '<a' . ($f === 'unread' ? ' class="on"' : '') . t(' href="index.php?section=submissions&f=unread">Непрочитанные<b>') . unread_count() . '</b></a>';
foreach (SUB_STATUS as $k => $l) echo '<a' . ($f === $k ? ' class="on"' : '') . ' href="index.php?section=submissions&f=' . $k . '">' . e(t($l)) . '</a>';
echo '</div>';

echo '<div class="panel"><div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:12px">';
echo '<form method="post">' . csrf_field() . t('<input type="hidden" name="action" value="allread"><button class="btn sm ghost">Отметить все прочитанными</button></form>');
echo t('<a class="btn sm ghost" href="index.php?section=submissions&export=csv">Скачать CSV (Excel)</a></div>');
if (!$rows) echo t('<p class="muted">Заявок нет.</p>');
else {
    echo t('<div class="tablewrap"><table><tr><th>Дата</th><th>Клиент</th><th>Сообщение</th><th>Статус и заметка</th><th></th></tr>');
    foreach ($rows as $r) {
        $tel = preg_replace('/[^0-9+]/', '', $r['phone']);
        $wa  = preg_replace('/\D/', '', $r['phone']);
        echo '<tr' . (!$r['is_read'] ? ' class="unread"' : '') . '>';
        echo '<td style="white-space:nowrap">' . e($r['created_at']) . (!$r['is_read'] ? t('<br><span class="pill warn">новая</span>') : '') . '</td>';
        echo '<td><b>' . e($r['name']) . '</b><br><a href="tel:' . e($tel) . '">' . e($r['phone']) . '</a>'
           . '<br><a href="https://wa.me/' . e($wa) . '" target="_blank" rel="noopener" class="muted">WhatsApp ↗</a>'
           . (($r['notified'] ?? '') !== '' ? '<div class="hint">' . e(t('Уведомления')) . ': ' . e((string)$r['notified']) . '</div>' : '')
           . '</td>';
        echo '<td><div class="msg-text">' . ($r['message'] !== '' ? e($r['message']) : '<span class="muted">—</span>') . '</div></td>';
        echo '<td><form method="post" style="min-width:200px">' . csrf_field() . '<input type="hidden" name="action" value="update"><input type="hidden" name="id" value="' . (int)$r['id'] . '"><input type="hidden" name="f" value="' . e($f) . '">'
           . '<select name="status">';
        foreach (SUB_STATUS as $k => $l) echo '<option value="' . $k . '"' . ($r['status'] === $k ? ' selected' : '') . '>' . e(t($l)) . '</option>';
        echo t('</select><textarea name="note" placeholder="Заметка" style="min-height:44px;margin-top:6px">') . e((string)$r['note']) . '</textarea>'
           . t('<button class="btn sm" style="margin-top:6px">Сохранить</button></form></td>');
        echo t('<td><form method="post" data-confirm="Удалить заявку?">') . csrf_field() . '<input type="hidden" name="action" value="del"><input type="hidden" name="id" value="' . (int)$r['id'] . '"><input type="hidden" name="f" value="' . e($f) . t('"><button class="btn sm red" title="Удалить">✕</button></form></td>');
        echo '</tr>';
    }
    echo '</table></div>';
}
echo '</div>';
