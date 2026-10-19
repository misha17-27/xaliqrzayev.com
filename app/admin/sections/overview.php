<?php
/* Bölmə: overview — controller.php-dən yüklənir ($db, $count, $imgUrl mövcuddur). */
declare(strict_types=1);

$unread = unread_count();
$total  = $count('SELECT COUNT(*) FROM submissions');
$today  = $count("SELECT COUNT(*) FROM submissions WHERE created_at >= " . $db->quote(date('Y-m-d 00:00:00')));
$svc    = $count('SELECT COUNT(*) FROM services WHERE visible=1');
echo '<div class="cards">';
echo '<a class="card" href="index.php?section=submissions&f=unread"><div class="n">' . $unread . t('</div><div class="l">Новых заявок</div></a>');
echo '<a class="card" href="index.php?section=submissions"><div class="n">' . $today . t('</div><div class="l">Заявок сегодня</div></a>');
echo '<a class="card" href="index.php?section=submissions"><div class="n">' . $total . t('</div><div class="l">Всего заявок</div></a>');
echo '<a class="card" href="index.php?section=services"><div class="n">' . $svc . t('</div><div class="l">Услуг и операций</div></a>');
echo '</div>';

$last = $db->query('SELECT * FROM submissions ORDER BY id DESC LIMIT 6')->fetchAll();
echo t('<div class="panel"><h3>Последние заявки</h3>');
if (!$last) echo t('<p class="muted">Заявок пока нет. Они появятся здесь после отправки формы на сайте.</p>');
else {
    echo t('<div class="tablewrap"><table><tr><th>Дата</th><th>Имя</th><th>Телефон</th><th>Статус</th></tr>');
    foreach ($last as $r) {
        echo '<tr' . (!$r['is_read'] ? ' class="unread"' : '') . '><td>' . e($r['created_at']) . '</td><td>' . e($r['name']) . '</td>'
           . '<td><a href="tel:' . e(preg_replace('/[^0-9+]/', '', $r['phone'])) . '">' . e($r['phone']) . '</a></td>'
           . '<td><span class="pill' . ($r['status'] === 'new' ? ' warn' : '') . '">' . e(t(SUB_STATUS[$r['status']] ?? $r['status'])) . '</span></td></tr>';
    }
    echo t('</table></div><p><a class="btn sm ghost" href="index.php?section=submissions">Все заявки →</a></p>');
}
echo '</div>';

/* Sistem vəziyyəti */
$checks = [];
$checks[] = ['PHP ' . PHP_VERSION, version_compare(PHP_VERSION, '8.0', '>=') ? 'ok' : 'warn', version_compare(PHP_VERSION, '8.0', '>=') ? '' : t('Рекомендуется PHP 8.1+ (cPanel → MultiPHP Manager)')];
$checks[] = [t('База: ') . $db->getAttribute(PDO::ATTR_DRIVER_NAME), 'ok', ''];
$up = uploads_dir();
$checks[] = [t('Папка загрузок'), (is_dir($up) && is_writable($up)) ? 'ok' : 'warn', t('assets/img/uploads должна быть доступна для записи')];
$checks[] = [t('Получатель заявок (e-mail)'), setting('mail_to') !== '' ? 'ok' : 'warn', t('Укажите в разделе «Почта (SMTP)» — иначе заявки будут только в панели')];
$checks[] = [t('Уведомления в WhatsApp'), (setting('wa_api_on') === '1' && setting('wa_api_key') !== '') ? 'ok' : 'warn', t('Настройте в разделе «WhatsApp-уведомления»')];
$checks[] = [t('Защита от ботов (Turnstile)'), adm_turnstile_on() ? 'ok' : 'warn', t('Рекомендуется включить в разделе «Безопасность»')];
$tok = (string)cfg('install_token', '');
$checks[] = [t('Токен install.php'), (!is_file(PUBLIC_DIR . '/admin/install.php') || (strlen($tok) >= 12 && $tok !== 'CHANGE_ME')) ? 'ok' : 'warn', t('Задайте длинный install_token в config/config.php или удалите install.php')];
$checks[] = [t('GD (обработка изображений)'), function_exists('imagecreatetruecolor') ? 'ok' : 'warn', t('Без GD фото не уменьшаются и WebP не создаётся')];
echo t('<div class="panel"><h3>Состояние системы</h3><div class="tablewrap"><table>');
foreach ($checks as [$label, $st, $hint]) {
    echo '<tr><td>' . e($label) . '</td><td class="c"><span class="pill' . ($st === 'ok' ? '">OK' : t(' warn">Внимание')) . '</span></td><td class="muted">' . ($st === 'ok' ? '' : e($hint)) . '</td></tr>';
}
echo '</table></div></div>';
