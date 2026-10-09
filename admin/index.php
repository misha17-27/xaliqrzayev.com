<?php
declare(strict_types=1);
require __DIR__ . '/lib.php';
boot_session();

$section = preg_replace('/[^a-z_]/', '', (string)($_GET['section'] ?? 'overview'));

/* ---------------- baza qurulmayıbsa ---------------- */
try {
    pdo()->query('SELECT 1 FROM admins LIMIT 1');
} catch (Throwable $e) {
    header('Content-Type: text/html; charset=utf-8');
    echo '<meta charset="utf-8"><meta name="robots" content="noindex"><style>body{font-family:Montserrat,Arial,sans-serif;max-width:640px;margin:80px auto;padding:0 20px;line-height:1.6;color:#14262c}'
       . 'code{background:#e3f4f1;padding:2px 8px;border-radius:6px}</style>'
       . '<h2>Панель ещё не установлена</h2>'
       . '<p>1. Скопируйте <code>admin/config.sample.php</code> → <code>admin/config.php</code> и заполните его.</p>'
       . '<p>2. Откройте один раз <code>/admin/install.php?token=…</code> (токен — в <code>admin/config.php</code>).</p>';
    exit;
}

/* ---------------- logout ---------------- */
if ($section === 'logout') { logout(); redirect('index.php?section=login'); }

/* ---------------- login ---------------- */
if ($section === 'login') {
    if (current_admin()) redirect('index.php');
    $err = '';
    if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
        csrf_check();
        $wait = login_blocked();
        if ($wait > 0) {
            $err = 'Слишком много попыток входа. Повторите через ' . ceil($wait / 60) . ' мин.';
        } elseif (!adm_turnstile_verify((string)($_POST['cf-turnstile-response'] ?? ''))) {
            $err = 'Подтвердите, что вы не робот.';
        } elseif (attempt_login((string)($_POST['email'] ?? ''), (string)($_POST['password'] ?? ''))) {
            redirect('index.php');
        } else {
            $err = 'Неверный e-mail или пароль.';
        }
    }
    head_html('Вход');
    echo '<div class="login"><form class="box" method="post">';
    echo brand_html();
    echo '<h2>Вход в панель</h2><div class="sub">Управление контентом сайта</div>';
    if ($err) echo '<div class="flash err">' . e($err) . '</div>';
    echo csrf_field();
    echo '<label>E-mail</label><input type="email" name="email" required autofocus autocomplete="username">';
    echo '<label>Пароль</label><input type="password" name="password" required autocomplete="current-password">';
    echo '<div style="margin-top:14px;display:flex;justify-content:center">'; adm_turnstile_widget(); echo '</div>';
    echo '<button class="btn" style="width:100%;margin-top:18px" type="submit">Войти</button>';
    echo '</form></div></body></html>';
    exit;
}

/* ---------------- auth ---------------- */
require_login();
if (!isset(SECTIONS[$section])) $section = 'overview';
if (in_array($section, ADMIN_ONLY, true) && !is_admin()) {
    flash('Недостаточно прав (нужна роль Администратор).', 'err');
    redirect('index.php?section=overview');
}
csrf_check();
$db = pdo();

/* Mətn sahələrinin təsviri: açar => [etiket, tip, ipucu] */
const TEXT_FIELDS = [
    'Общее' => [
        'site_name'   => ['Имя на сайте (логотип, футер)', 'input'],
        'site_role'   => ['Специальность', 'input'],
        'footer_text' => ['Текст в футере', 'input'],
    ],
    'Первый экран' => [
        'hero_badge'   => ['Плашка над заголовком', 'input'],
        'hero_title_1' => ['Заголовок — первая часть (тёмная)', 'input'],
        'hero_title_2' => ['Заголовок — вторая часть (цветная)', 'input'],
        'hero_lead'    => ['Подзаголовок', 'textarea'],
        'hero_desc'    => ['Описание', 'textarea'],
        'hero_chip_1'  => ['Плашка на фото №1', 'input', 'Пусто — не показывать'],
        'hero_chip_2'  => ['Плашка на фото №2', 'input', 'Пусто — не показывать'],
    ],
    'Обо мне' => [
        'about_tag'   => ['Метка раздела', 'input'],
        'about_title' => ['Заголовок', 'input'],
        'about_body'  => ['Текст', 'textarea', 'Абзацы разделяйте пустой строкой. Теги выводятся после 2-го абзаца.'],
        'about_tags'  => ['Теги (направления)', 'textarea', 'Каждый тег — с новой строки'],
        'award_text'  => ['Награда', 'textarea', 'Пусто — блок не показывается'],
    ],
    'Обследование и диагностика' => [
        'diag_tag'         => ['Метка раздела', 'input'],
        'diag_title'       => ['Заголовок', 'input'],
        'diag_sub'         => ['Подзаголовок', 'input'],
        'diag_adult_title' => ['Карточка «взрослые» — заголовок', 'input'],
        'diag_adult_sub'   => ['Карточка «взрослые» — подпись', 'input'],
        'diag_child_title' => ['Карточка «дети» — заголовок', 'input'],
        'diag_child_sub'   => ['Карточка «дети» — подпись', 'input'],
    ],
    'Операции' => [
        'surg_tag'         => ['Метка раздела', 'input'],
        'surg_title'       => ['Заголовок', 'input'],
        'surg_sub'         => ['Подзаголовок', 'textarea'],
        'surg_adult_title' => ['Блок «взрослые» — заголовок', 'input'],
        'surg_child_title' => ['Блок «дети» — заголовок', 'input'],
    ],
    'Образование' => [
        'edu_tag'   => ['Метка раздела', 'input'],
        'edu_title' => ['Заголовок', 'input'],
    ],
    'Запись и форма' => [
        'cta_title'  => ['Заголовок блока', 'input'],
        'cta_text'   => ['Текст блока', 'textarea'],
        'form_title' => ['Заголовок формы', 'input'],
        'form_ok'    => ['Сообщение после отправки', 'textarea'],
    ],
];

const CONTACT_FIELDS = [
    'phone'     => ['Телефон (как показывать)', '+994 55 622 54 53'],
    'phone_raw' => ['Телефон для ссылки tel:', '+994556225453'],
    'whatsapp'  => ['WhatsApp (только цифры)', '994556225453'],
    'email'     => ['E-mail', ''],
    'address'   => ['Адрес приёма', 'Пусто — не показывать'],
    'hours'     => ['Часы приёма', 'Напр.: B.e.–Cümə 10:00–18:00'],
    'map'       => ['Google Maps — ссылка для встраивания (src из iframe)', 'https://www.google.com/maps/embed?pb=...'],
    'instagram' => ['Instagram', 'https://instagram.com/...'],
    'facebook'  => ['Facebook', 'https://facebook.com/...'],
    'youtube'   => ['YouTube', ''],
    'tiktok'    => ['TikTok', ''],
];

const SVC_GROUPS = [
    'diag_adult' => 'Обследование — взрослые',
    'diag_child' => 'Обследование — дети',
    'surg_adult' => 'Операции — взрослые',
    'surg_child' => 'Операции — дети',
];

const IMAGE_SLOTS = [
    'photo_hero'  => ['Фото на первом экране', 'Вертикальное, 4:5 (напр. 960×1200)'],
    'photo_about' => ['Фото в разделе «Обо мне»', 'Вертикальное, 2:3 (напр. 1000×1500)'],
    'og_image'    => ['Картинка для соцсетей (OG)', 'Показывается при отправке ссылки в WhatsApp / Facebook'],
];

const SUB_STATUS = ['new' => 'Новая', 'called' => 'Связались', 'done' => 'Записан', 'spam' => 'Спам'];

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

/* ================== POST ================== */
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $act = (string)($_POST['action'] ?? '');

    if ($section === 'texts' && $act === 'save') {
        $allowed = [];
        foreach (TEXT_FIELDS as $fields) $allowed += $fields;
        foreach (($_POST['t'] ?? []) as $k => $v) {
            if (isset($allowed[$k])) kv_set('texts', (string)$k, trim((string)$v));
        }
        flash('Тексты сохранены.'); redirect('index.php?section=texts');
    }

    if ($section === 'contacts' && $act === 'save') {
        foreach (CONTACT_FIELDS as $k => $_) kv_set('contacts', $k, trim((string)($_POST[$k] ?? '')));
        $map = trim((string)($_POST['map'] ?? ''));
        /* Bütöv <iframe> yapışdırılıbsa — src-ni çıxarırıq */
        if (preg_match('~src="([^"]+)"~', $map, $m)) { $map = html_entity_decode($m[1]); kv_set('contacts', 'map', $map); }
        if ($map !== '' && !preg_match('~^https://(www\.)?google\.[a-z.]+/maps/embed~', $map))
            flash('Карта не будет показана: нужна ссылка вида https://www.google.com/maps/embed?pb=…', 'err');
        flash('Контакты сохранены.'); redirect('index.php?section=contacts');
    }

    if ($section === 'services') {
        $grp = (string)($_POST['grp'] ?? '');
        if (!isset(SVC_GROUPS[$grp])) redirect('index.php?section=services');
        if ($act === 'save') {
            $r = save_rows($db, 'services', ['title'], 'title', ['grp' => $grp]);
            flash(rows_msg($r));
        } elseif ($act === 'bulk_add') {
            $max = (int)$db->query("SELECT COALESCE(MAX(sort),0) FROM services WHERE grp=" . $db->quote($grp))->fetchColumn();
            $st = $db->prepare('INSERT INTO services (grp, title, sort, visible) VALUES (?,?,?,1)');
            $n = 0;
            foreach (preg_split('/\R/', (string)($_POST['lines'] ?? '')) as $line) {
                $line = trim(preg_replace('/^[\s*•\-–]+/u', '', $line));
                if ($line === '') continue;
                $max += 10; $st->execute([$grp, mb_substr($line, 0, 255), $max]); $n++;
            }
            flash("Добавлено пунктов: $n.");
        } elseif ($act === 'renumber') {
            $st = $db->prepare('SELECT id FROM services WHERE grp=? ORDER BY sort, id'); $st->execute([$grp]);
            $up = $db->prepare('UPDATE services SET sort=? WHERE id=?');
            foreach ($st->fetchAll() as $i => $r) $up->execute([($i + 1) * 10, $r['id']]);
            flash('Порядок пересчитан (10, 20, 30…).');
        }
        redirect('index.php?section=services&grp=' . $grp);
    }

    if ($section === 'highlights' && $act === 'save') {
        flash(rows_msg(save_rows($db, 'highlights', ['icon', 'title', 'descr'], 'title')));
        redirect('index.php?section=highlights');
    }

    if ($section === 'education' && $act === 'save') {
        flash(rows_msg(save_rows($db, 'education', ['years', 'title', 'descr', 'label'], 'title')));
        redirect('index.php?section=education');
    }

    if ($section === 'images') {
        if ($act === 'slot') {
            $slot = (string)($_POST['slot'] ?? '');
            if (isset(IMAGE_SLOTS[$slot])) {
                $err = '';
                if ($p = admin_upload('file', $err)) { kv_set('settings', $slot, $p); flash('Изображение загружено и установлено.'); }
                elseif ($err !== '') flash($err, 'err');
                elseif (($p = safe_img_path((string)($_POST['path'] ?? ''))) !== '') { kv_set('settings', $slot, $p); flash('Изображение выбрано.'); }
                else flash('Выберите файл или изображение из библиотеки.', 'err');
            }
        } elseif ($act === 'upload') {
            $err = '';
            if ($p = admin_upload('file', $err)) flash('Загружено: ' . $p);
            else flash($err ?: 'Файл не выбран.', 'err');
        } elseif ($act === 'del') {
            $p = safe_img_path((string)($_POST['path'] ?? ''));
            $used = false;
            foreach (IMAGE_SLOTS as $k => $_) if (setting($k) === $p) $used = true;
            if ($p === '' || !str_starts_with($p, 'uploads/')) flash('Удалять можно только загруженные через панель файлы.', 'err');
            elseif ($used) flash('Изображение используется на сайте — сначала замените его.', 'err');
            else {
                $full = dirname(__DIR__) . '/assets/img/' . $p;
                @unlink($full);
                @unlink((string)preg_replace('/\.(jpe?g|png)$/i', '.webp', $full));
                flash('Файл удалён.');
            }
        }
        redirect('index.php?section=images');
    }

    if ($section === 'submissions') {
        $id = (int)($_POST['id'] ?? 0);
        if ($act === 'update' && $id) {
            $status = (string)($_POST['status'] ?? 'new');
            if (!isset(SUB_STATUS[$status])) $status = 'new';
            $db->prepare('UPDATE submissions SET status=?, note=?, is_read=1 WHERE id=?')->execute([$status, trim((string)($_POST['note'] ?? '')), $id]);
            flash('Заявка обновлена.');
        } elseif ($act === 'read' && $id) {
            $db->prepare('UPDATE submissions SET is_read=1 WHERE id=?')->execute([$id]);
        } elseif ($act === 'del' && $id) {
            $db->prepare('DELETE FROM submissions WHERE id=?')->execute([$id]); flash('Заявка удалена.');
        } elseif ($act === 'allread') {
            $db->exec('UPDATE submissions SET is_read=1'); flash('Все отмечены прочитанными.');
        }
        $back = 'index.php?section=submissions';
        if (!empty($_POST['f'])) $back .= '&f=' . rawurlencode((string)$_POST['f']);
        redirect($back);
    }

    if ($section === 'seo') {
        if ($act === 'save') {
            $robots = in_array($_POST['robots'] ?? '', ['index,follow', 'noindex,nofollow'], true) ? $_POST['robots'] : 'index,follow';
            $st = $db->prepare('SELECT 1 FROM seo_pages WHERE slug=?'); $st->execute(['home']);
            if ($st->fetch()) $db->prepare('UPDATE seo_pages SET title=?, descr=?, robots=? WHERE slug=?')->execute([trim((string)$_POST['title']), trim((string)$_POST['descr']), $robots, 'home']);
            else $db->prepare('INSERT INTO seo_pages (slug,title,descr,robots) VALUES (?,?,?,?)')->execute(['home', trim((string)$_POST['title']), trim((string)$_POST['descr']), $robots]);
            flash('SEO сохранён.');
        } elseif ($act === 'save_ga') {
            $ga = trim((string)($_POST['ga_id'] ?? ''));
            if ($ga !== '' && !preg_match('/^(G|GT|UA|AW)-[A-Z0-9\-]{4,20}$/i', $ga)) flash('Неверный формат. Пример: G-XXXXXXXXXX', 'err');
            else { kv_set('settings', 'ga_id', $ga); flash($ga === '' ? 'Аналитика отключена.' : 'Номер аналитики сохранён.'); }
        }
        redirect('index.php?section=seo');
    }

    if ($section === 'security' && $act === 'save') {
        kv_set('settings', 'turnstile_site', trim((string)($_POST['turnstile_site'] ?? '')));
        if (($_POST['turnstile_secret'] ?? '') !== '') kv_set('settings', 'turnstile_secret', trim((string)$_POST['turnstile_secret']));
        if (!empty($_POST['turnstile_off'])) { kv_set('settings', 'turnstile_site', ''); kv_set('settings', 'turnstile_secret', ''); }
        flash('Настройки безопасности сохранены.'); redirect('index.php?section=security');
    }

    if ($section === 'smtp') {
        if ($act === 'save') {
            foreach (['smtp_host', 'smtp_port', 'smtp_user', 'smtp_from', 'smtp_from_name', 'smtp_secure'] as $k)
                kv_set('settings', $k, trim((string)($_POST[$k] ?? '')));
            if (($_POST['smtp_pass'] ?? '') !== '') kv_set('settings', 'smtp_pass', (string)$_POST['smtp_pass']);
            $to = trim((string)($_POST['mail_to'] ?? ''));
            if ($to !== '' && !filter_var($to, FILTER_VALIDATE_EMAIL)) flash('Некорректный e-mail получателя.', 'err');
            else kv_set('settings', 'mail_to', $to);
            flash('Настройки почты сохранены.');
        } elseif ($act === 'test') {
            $err = ''; $to = trim((string)($_POST['test_to'] ?? ''));
            if (!filter_var($to, FILTER_VALIDATE_EMAIL)) flash('Укажите корректный e-mail.', 'err');
            elseif (smtp_send($to, 'Тест SMTP — xaliqrzayev.com', "Это тестовое письмо из админ-панели.\nЕсли вы его получили — SMTP настроен верно.", $err))
                flash('Тестовое письмо отправлено на ' . $to);
            else flash('Ошибка отправки: ' . $err, 'err');
        }
        redirect('index.php?section=smtp');
    }

    if ($section === 'profile') {
        $a = current_admin();
        if ($act === 'save') {
            $em = trim((string)($_POST['email'] ?? ''));
            if (!filter_var($em, FILTER_VALIDATE_EMAIL)) flash('Некорректный e-mail.', 'err');
            else {
                $chk = $db->prepare('SELECT id FROM admins WHERE email=? AND id<>?'); $chk->execute([$em, $a['id']]);
                if ($chk->fetch()) flash('Этот e-mail уже занят.', 'err');
                else {
                    $db->prepare('UPDATE admins SET name=?, email=? WHERE id=?')->execute([trim((string)($_POST['name'] ?? '')), $em, $a['id']]);
                    $_SESSION['admin']['name']  = trim((string)($_POST['name'] ?? ''));
                    $_SESSION['admin']['email'] = $em;
                    flash('Профиль сохранён.');
                }
            }
        } elseif ($act === 'passwd') {
            $st = $db->prepare('SELECT pass_hash FROM admins WHERE id=?'); $st->execute([$a['id']]); $row = $st->fetch();
            if (!password_verify((string)($_POST['old'] ?? ''), $row['pass_hash'] ?? '')) flash('Текущий пароль неверный.', 'err');
            elseif (strlen((string)($_POST['new'] ?? '')) < 8)                        flash('Новый пароль слишком короткий (мин. 8).', 'err');
            elseif (($_POST['new'] ?? '') !== ($_POST['new2'] ?? ''))                 flash('Пароли не совпадают.', 'err');
            else {
                $db->prepare('UPDATE admins SET pass_hash=? WHERE id=?')->execute([password_hash((string)$_POST['new'], PASSWORD_DEFAULT), $a['id']]);
                session_regenerate_id(true);
                flash('Пароль изменён.');
            }
        }
        redirect('index.php?section=profile');
    }

    if ($section === 'users') {
        $me = (int)current_admin()['id'];
        if ($act === 'add') {
            $em = trim((string)($_POST['email'] ?? ''));
            if (!filter_var($em, FILTER_VALIDATE_EMAIL))           flash('Некорректный e-mail.', 'err');
            elseif (strlen((string)($_POST['password'] ?? '')) < 8) flash('Пароль минимум 8 символов.', 'err');
            else {
                $chk = $db->prepare('SELECT id FROM admins WHERE email=?'); $chk->execute([$em]);
                if ($chk->fetch()) flash('Пользователь с таким e-mail уже есть.', 'err');
                else {
                    $db->prepare('INSERT INTO admins (name,email,pass_hash,role,active) VALUES (?,?,?,?,1)')
                       ->execute([trim((string)($_POST['name'] ?? '')), $em, password_hash((string)$_POST['password'], PASSWORD_DEFAULT), ($_POST['role'] ?? 'editor') === 'admin' ? 'admin' : 'editor']);
                    flash('Пользователь добавлен.');
                }
            }
        } elseif ($act === 'save') {
            foreach (($_POST['u'] ?? []) as $id => $row) {
                $id = (int)$id;
                $role   = ($row['role'] ?? 'editor') === 'admin' ? 'admin' : 'editor';
                $active = isset($row['active']) ? 1 : 0;
                if ($id === $me) { $active = 1; $role = 'admin'; }
                $db->prepare('UPDATE admins SET name=?, role=?, active=? WHERE id=?')->execute([trim((string)($row['name'] ?? '')), $role, $active, $id]);
                if (($row['password'] ?? '') !== '') {
                    if (strlen((string)$row['password']) >= 8)
                        $db->prepare('UPDATE admins SET pass_hash=? WHERE id=?')->execute([password_hash((string)$row['password'], PASSWORD_DEFAULT), $id]);
                    else flash('Пароль не изменён: минимум 8 символов.', 'err');
                }
            }
            flash('Изменения сохранены.');
        } elseif ($act === 'del') {
            $id = (int)($_POST['id'] ?? 0);
            if ($id !== $me) { $db->prepare('DELETE FROM admins WHERE id=?')->execute([$id]); flash('Пользователь удалён.'); }
            else flash('Нельзя удалить самого себя.', 'err');
        }
        redirect('index.php?section=users');
    }

    redirect('index.php?section=' . $section);
}

/* ---------------- CSV eksport (GET) ---------------- */
if ($section === 'submissions' && ($_GET['export'] ?? '') === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="zayavki-' . date('Y-m-d') . '.csv"');
    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF");   // Excel üçün UTF-8 BOM
    fputcsv($out, ['ID', 'Дата', 'Имя', 'Телефон', 'Сообщение', 'Статус', 'Заметка'], ';');
    foreach ($db->query('SELECT * FROM submissions ORDER BY id DESC') as $r) {
        $row = [$r['id'], $r['created_at'], $r['name'], $r['phone'], $r['message'], SUB_STATUS[$r['status']] ?? $r['status'], $r['note']];
        /* Excel düstur inyeksiyasının qarşısı */
        $row = array_map(function ($v) { $v = (string)$v; return preg_match('/^[=+\-@]/', $v) ? "'" . $v : $v; }, $row);
        fputcsv($out, $row, ';');
    }
    exit;
}

/* ================== RENDER ================== */
layout_top($section, SECTIONS[$section][0]);

$count = function (string $sql) use ($db): int { try { return (int)$db->query($sql)->fetchColumn(); } catch (Throwable $e) { return 0; } };
$imgUrl = function (string $p): string { return '../assets/img/' . $p; };

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

/* ---------------- OVERVIEW ---------------- */
if ($section === 'overview') {
    $unread = unread_count();
    $total  = $count('SELECT COUNT(*) FROM submissions');
    $today  = $count("SELECT COUNT(*) FROM submissions WHERE created_at >= " . $db->quote(date('Y-m-d 00:00:00')));
    $svc    = $count('SELECT COUNT(*) FROM services WHERE visible=1');
    echo '<div class="cards">';
    echo '<a class="card" href="index.php?section=submissions&f=unread"><div class="n">' . $unread . '</div><div class="l">Новых заявок</div></a>';
    echo '<a class="card" href="index.php?section=submissions"><div class="n">' . $today . '</div><div class="l">Заявок сегодня</div></a>';
    echo '<a class="card" href="index.php?section=submissions"><div class="n">' . $total . '</div><div class="l">Всего заявок</div></a>';
    echo '<a class="card" href="index.php?section=services"><div class="n">' . $svc . '</div><div class="l">Услуг и операций</div></a>';
    echo '</div>';

    $last = $db->query('SELECT * FROM submissions ORDER BY id DESC LIMIT 6')->fetchAll();
    echo '<div class="panel"><h3>Последние заявки</h3>';
    if (!$last) echo '<p class="muted">Заявок пока нет. Они появятся здесь после отправки формы на сайте.</p>';
    else {
        echo '<div class="tablewrap"><table><tr><th>Дата</th><th>Имя</th><th>Телефон</th><th>Статус</th></tr>';
        foreach ($last as $r) {
            echo '<tr' . (!$r['is_read'] ? ' class="unread"' : '') . '><td>' . e($r['created_at']) . '</td><td>' . e($r['name']) . '</td>'
               . '<td><a href="tel:' . e(preg_replace('/[^0-9+]/', '', $r['phone'])) . '">' . e($r['phone']) . '</a></td>'
               . '<td><span class="pill' . ($r['status'] === 'new' ? ' warn' : '') . '">' . e(SUB_STATUS[$r['status']] ?? $r['status']) . '</span></td></tr>';
        }
        echo '</table></div><p><a class="btn sm ghost" href="index.php?section=submissions">Все заявки →</a></p>';
    }
    echo '</div>';

    /* Sistem vəziyyəti */
    $checks = [];
    $checks[] = ['PHP ' . PHP_VERSION, version_compare(PHP_VERSION, '8.0', '>=') ? 'ok' : 'warn', version_compare(PHP_VERSION, '8.0', '>=') ? '' : 'Рекомендуется PHP 8.1+ (cPanel → MultiPHP Manager)'];
    $checks[] = ['База: ' . $db->getAttribute(PDO::ATTR_DRIVER_NAME), 'ok', ''];
    $up = uploads_dir();
    $checks[] = ['Папка загрузок', (is_dir($up) && is_writable($up)) ? 'ok' : 'warn', 'assets/img/uploads должна быть доступна для записи'];
    $checks[] = ['Получатель заявок (e-mail)', setting('mail_to') !== '' ? 'ok' : 'warn', 'Укажите в разделе «Почта (SMTP)» — иначе заявки будут только в панели'];
    $checks[] = ['Защита от ботов (Turnstile)', adm_turnstile_on() ? 'ok' : 'warn', 'Рекомендуется включить в разделе «Безопасность»'];
    $tok = (string)cfg('install_token', '');
    $checks[] = ['Токен install.php', (!is_file(__DIR__ . '/install.php') || (strlen($tok) >= 12 && $tok !== 'CHANGE_ME')) ? 'ok' : 'warn', 'Задайте длинный install_token в admin/config.php или удалите install.php'];
    $checks[] = ['GD (обработка изображений)', function_exists('imagecreatetruecolor') ? 'ok' : 'warn', 'Без GD фото не уменьшаются и WebP не создаётся'];
    echo '<div class="panel"><h3>Состояние системы</h3><div class="tablewrap"><table>';
    foreach ($checks as [$label, $st, $hint]) {
        echo '<tr><td>' . e($label) . '</td><td class="c"><span class="pill' . ($st === 'ok' ? '">OK' : ' warn">Внимание') . '</span></td><td class="muted">' . ($st === 'ok' ? '' : e($hint)) . '</td></tr>';
    }
    echo '</table></div></div>';
}

/* ---------------- TEXTS ---------------- */
if ($section === 'texts') {
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
}

/* ---------------- SERVICES ---------------- */
if ($section === 'services') {
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
}

/* ---------------- HIGHLIGHTS ---------------- */
if ($section === 'highlights') {
    $rows = $db->query('SELECT * FROM highlights ORDER BY sort, id')->fetchAll();
    echo '<form method="post" class="panel">' . csrf_field() . '<input type="hidden" name="action" value="save">';
    echo '<h3>Карточки под первым экраном</h3><p class="muted">Иконка — эмодзи (🔬 💎 🧸 🩺 …). Лучше всего смотрятся 4 карточки.</p>';
    rows_editor($rows, ['icon' => ['Иконка', 'short'], 'title' => ['Заголовок', 'input'], 'descr' => ['Описание', 'input']], 2);
    echo '<div class="sticky-save"><button class="btn">Сохранить</button></div></form>';
}

/* ---------------- EDUCATION ---------------- */
if ($section === 'education') {
    $rows = $db->query('SELECT * FROM education ORDER BY sort, id')->fetchAll();
    echo '<form method="post" class="panel">' . csrf_field() . '<input type="hidden" name="action" value="save">';
    echo '<h3>Образование и квалификация</h3><p class="muted">Можно добавлять курсы, стажировки, сертификаты. В описании — перенос строки сохраняется.</p>';
    rows_editor($rows, ['years' => ['Годы', 'input'], 'title' => ['Учреждение', 'input'], 'descr' => ['Описание', 'textarea'], 'label' => ['Метка', 'input']], 1);
    echo '<div class="sticky-save"><button class="btn">Сохранить</button></div></form>';
}

/* ---------------- IMAGES ---------------- */
if ($section === 'images') {
    /* Kitabxana: assets/img (kök) + uploads */
    $lib = [];
    $base = dirname(__DIR__) . '/assets/img/';
    foreach (array_merge(glob($base . '*.{jpg,jpeg,png,webp,gif}', GLOB_BRACE) ?: [], glob($base . 'uploads/*.{jpg,jpeg,png,webp,gif}', GLOB_BRACE) ?: []) as $f) {
        $rel = substr($f, strlen($base));
        /* yanında jpg/png olan .webp — avtomatik nüsxədir, göstərmirik */
        if (preg_match('/\.webp$/i', $rel) && (is_file(substr($f, 0, -5) . '.jpg') || is_file(substr($f, 0, -5) . '.jpeg') || is_file(substr($f, 0, -5) . '.png'))) continue;
        $lib[$rel] = filemtime($f);
    }
    arsort($lib);

    echo '<div class="panel"><h3>Фотографии на сайте</h3>';
    foreach (IMAGE_SLOTS as $slot => [$label, $hint]) {
        $cur = setting($slot);
        echo '<form method="post" enctype="multipart/form-data" class="imgslot">' . csrf_field()
           . '<input type="hidden" name="action" value="slot"><input type="hidden" name="slot" value="' . $slot . '">';
        echo '<img src="' . e($imgUrl($cur)) . '?v=' . (int)@filemtime($base . $cur) . '" alt="">';
        echo '<div><b>' . e($label) . '</b><div class="hint">' . e($hint) . '</div><div class="hint">Сейчас: <code>' . e($cur) . '</code></div>';
        echo '<label>Загрузить новое</label><input type="file" name="file" accept="image/jpeg,image/png,image/webp">';
        echo '<label>…или выбрать из библиотеки</label><select name="path"><option value="">—</option>';
        foreach ($lib as $rel => $_) echo '<option value="' . e($rel) . '"' . ($rel === $cur ? ' selected' : '') . '>' . e($rel) . '</option>';
        echo '</select><p><button class="btn sm">Применить</button></p></div></form>';
    }
    echo '</div>';

    echo '<form method="post" enctype="multipart/form-data" class="panel">' . csrf_field() . '<input type="hidden" name="action" value="upload">';
    echo '<h3>Библиотека изображений</h3><p class="muted">JPG, PNG, WEBP до 15 МБ. Большие фото автоматически уменьшаются до 2000px, создаётся WebP-версия.</p>';
    echo '<input type="file" name="file" accept="image/jpeg,image/png,image/webp,image/gif"> <button class="btn sm" style="margin-top:10px">Загрузить</button></form>';

    echo '<div class="panel"><div class="grid-media">';
    foreach ($lib as $rel => $mt) {
        echo '<div class="media"><img src="' . e($imgUrl($rel)) . '" alt="" loading="lazy"><div class="m">'
           . '<input type="text" value="' . e($rel) . '" readonly data-copy title="Нажмите, чтобы скопировать путь">';
        if (str_starts_with($rel, 'uploads/')) {
            echo '<form method="post" style="margin-top:6px" data-confirm="Удалить файл?">' . csrf_field()
               . '<input type="hidden" name="action" value="del"><input type="hidden" name="path" value="' . e($rel) . '"><button class="btn sm red">Удалить</button></form>';
        }
        echo '</div></div>';
    }
    echo '</div></div>';
}

/* ---------------- CONTACTS ---------------- */
if ($section === 'contacts') {
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
}

/* ---------------- SUBMISSIONS ---------------- */
if ($section === 'submissions') {
    $f = (string)($_GET['f'] ?? '');
    $where = '1=1'; $args = [];
    if ($f === 'unread') $where = 'is_read=0';
    elseif (isset(SUB_STATUS[$f])) { $where = 'status=?'; $args[] = $f; }
    $st = $db->prepare("SELECT * FROM submissions WHERE $where ORDER BY id DESC LIMIT 300"); $st->execute($args);
    $rows = $st->fetchAll();

    echo '<div class="tabs"><a' . ($f === '' ? ' class="on"' : '') . ' href="index.php?section=submissions">Все</a>'
       . '<a' . ($f === 'unread' ? ' class="on"' : '') . ' href="index.php?section=submissions&f=unread">Непрочитанные<b>' . unread_count() . '</b></a>';
    foreach (SUB_STATUS as $k => $l) echo '<a' . ($f === $k ? ' class="on"' : '') . ' href="index.php?section=submissions&f=' . $k . '">' . e($l) . '</a>';
    echo '</div>';

    echo '<div class="panel"><div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:12px">';
    echo '<form method="post">' . csrf_field() . '<input type="hidden" name="action" value="allread"><button class="btn sm ghost">Отметить все прочитанными</button></form>';
    echo '<a class="btn sm ghost" href="index.php?section=submissions&export=csv">Скачать CSV (Excel)</a></div>';
    if (!$rows) echo '<p class="muted">Заявок нет.</p>';
    else {
        echo '<div class="tablewrap"><table><tr><th>Дата</th><th>Клиент</th><th>Сообщение</th><th>Статус и заметка</th><th></th></tr>';
        foreach ($rows as $r) {
            $tel = preg_replace('/[^0-9+]/', '', $r['phone']);
            $wa  = preg_replace('/\D/', '', $r['phone']);
            echo '<tr' . (!$r['is_read'] ? ' class="unread"' : '') . '>';
            echo '<td style="white-space:nowrap">' . e($r['created_at']) . (!$r['is_read'] ? '<br><span class="pill warn">новая</span>' : '') . '</td>';
            echo '<td><b>' . e($r['name']) . '</b><br><a href="tel:' . e($tel) . '">' . e($r['phone']) . '</a><br><a href="https://wa.me/' . e($wa) . '" target="_blank" rel="noopener" class="muted">WhatsApp ↗</a></td>';
            echo '<td><div class="msg-text">' . ($r['message'] !== '' ? e($r['message']) : '<span class="muted">—</span>') . '</div></td>';
            echo '<td><form method="post" style="min-width:200px">' . csrf_field() . '<input type="hidden" name="action" value="update"><input type="hidden" name="id" value="' . (int)$r['id'] . '"><input type="hidden" name="f" value="' . e($f) . '">'
               . '<select name="status">';
            foreach (SUB_STATUS as $k => $l) echo '<option value="' . $k . '"' . ($r['status'] === $k ? ' selected' : '') . '>' . e($l) . '</option>';
            echo '</select><textarea name="note" placeholder="Заметка" style="min-height:44px;margin-top:6px">' . e((string)$r['note']) . '</textarea>'
               . '<button class="btn sm" style="margin-top:6px">Сохранить</button></form></td>';
            echo '<td><form method="post" data-confirm="Удалить заявку?">' . csrf_field() . '<input type="hidden" name="action" value="del"><input type="hidden" name="id" value="' . (int)$r['id'] . '"><input type="hidden" name="f" value="' . e($f) . '"><button class="btn sm red" title="Удалить">✕</button></form></td>';
            echo '</tr>';
        }
        echo '</table></div>';
    }
    echo '</div>';
}

/* ---------------- SEO ---------------- */
if ($section === 'seo') {
    $st = $db->prepare('SELECT * FROM seo_pages WHERE slug=?'); $st->execute(['home']);
    $p = $st->fetch() ?: ['title' => '', 'descr' => '', 'robots' => 'index,follow'];
    echo '<form method="post" class="panel">' . csrf_field() . '<input type="hidden" name="action" value="save">';
    echo '<h3>Главная страница</h3>';
    echo '<label>Title <span class="muted">(рекомендуется до 60 символов, сейчас: ' . mb_strlen($p['title']) . ')</span></label><input type="text" name="title" value="' . e($p['title']) . '">';
    echo '<label>Description <span class="muted">(рекомендуется 120–160 символов, сейчас: ' . mb_strlen((string)$p['descr']) . ')</span></label><textarea name="descr">' . e($p['descr']) . '</textarea>';
    echo '<label>Индексация</label><select name="robots">';
    foreach (['index,follow' => 'Разрешена (index, follow)', 'noindex,nofollow' => 'Запрещена (noindex) — для теста'] as $k => $l)
        echo '<option value="' . $k . '"' . ($p['robots'] === $k ? ' selected' : '') . '>' . e($l) . '</option>';
    echo '</select>';
    echo '<h4>Предпросмотр в Google</h4><div style="border:1px solid #e3ecec;border-radius:10px;padding:14px 16px;max-width:600px">'
       . '<div style="color:#1a0dab;font-size:18px">' . e($p['title']) . '</div><div style="color:#006621;font-size:13px">xaliqrzayev.com</div>'
       . '<div style="color:#545454;font-size:13px">' . e(mb_strimwidth((string)$p['descr'], 0, 160, '…')) . '</div></div>';
    echo '<p><button class="btn">Сохранить</button></p></form>';

    echo '<form method="post" class="panel">' . csrf_field() . '<input type="hidden" name="action" value="save_ga">';
    echo '<h3>Google Analytics</h3><label>Measurement ID</label><input type="text" name="ga_id" value="' . e(setting('ga_id')) . '" placeholder="G-XXXXXXXXXX">';
    echo '<div class="hint">Пусто — аналитика отключена.</div><p><button class="btn">Сохранить</button></p></form>';
}

/* ---------------- SMTP ---------------- */
if ($section === 'smtp') {
    echo '<form method="post" class="panel">' . csrf_field() . '<input type="hidden" name="action" value="save">';
    echo '<h3>Куда отправлять заявки</h3><label>E-mail получателя</label><input type="email" name="mail_to" value="' . e(setting('mail_to')) . '" placeholder="doctor@example.com">';
    echo '<div class="hint">Все заявки сохраняются в панели в любом случае. E-mail — дополнительное уведомление.</div>';
    echo '<h4>SMTP-сервер</h4><p class="muted">Если не заполнено — используется встроенная функция mail() хостинга. Для cPanel: сервер <code>mail.xaliqrzayev.com</code>, порт 465 (SSL) или 587 (TLS), логин — полный адрес ящика.</p>';
    echo '<div class="row3"><div><label>Сервер</label><input type="text" name="smtp_host" value="' . e(setting('smtp_host')) . '"></div>'
       . '<div><label>Порт</label><input type="text" name="smtp_port" value="' . e(setting('smtp_port') ?: '587') . '"></div>'
       . '<div><label>Шифрование</label><select name="smtp_secure">';
    foreach (['tls' => 'TLS (587)', 'ssl' => 'SSL (465)', 'none' => 'Нет'] as $k => $l) echo '<option value="' . $k . '"' . (setting('smtp_secure') === $k ? ' selected' : '') . '>' . $l . '</option>';
    echo '</select></div></div>';
    echo '<div class="row"><div><label>Логин</label><input type="text" name="smtp_user" value="' . e(setting('smtp_user')) . '" autocomplete="off"></div>'
       . '<div><label>Пароль</label><input type="password" name="smtp_pass" value="" placeholder="' . (setting('smtp_pass') !== '' ? '•••••••• (сохранён)' : '') . '" autocomplete="new-password"></div></div>';
    echo '<div class="row"><div><label>Адрес отправителя</label><input type="text" name="smtp_from" value="' . e(setting('smtp_from')) . '"></div>'
       . '<div><label>Имя отправителя</label><input type="text" name="smtp_from_name" value="' . e(setting('smtp_from_name')) . '" placeholder="xaliqrzayev.com"></div></div>';
    echo '<p><button class="btn">Сохранить</button></p></form>';

    echo '<form method="post" class="panel">' . csrf_field() . '<input type="hidden" name="action" value="test">';
    echo '<h3>Проверка</h3><label>Отправить тестовое письмо на</label><input type="email" name="test_to" value="' . e(setting('mail_to')) . '">';
    echo '<p><button class="btn navy">Отправить тест</button></p></form>';
}

/* ---------------- SECURITY ---------------- */
if ($section === 'security') {
    echo '<form method="post" class="panel">' . csrf_field() . '<input type="hidden" name="action" value="save">';
    echo '<h3>Cloudflare Turnstile</h3><p class="muted">Защищает форму записи на сайте и вход в панель от ботов. Ключи: <a href="https://dash.cloudflare.com" target="_blank" rel="noopener">dash.cloudflare.com</a> → Turnstile → Add site (домен xaliqrzayev.com).</p>';
    echo '<p>Статус: ' . (adm_turnstile_on() ? '<span class="pill">Включено</span>' : '<span class="pill warn">Выключено</span>') . '</p>';
    echo '<label>Site key</label><input type="text" name="turnstile_site" value="' . e(setting('turnstile_site')) . '">';
    echo '<label>Secret key</label><input type="password" name="turnstile_secret" placeholder="' . (setting('turnstile_secret') !== '' ? '•••••••• (сохранён)' : '') . '" autocomplete="new-password">';
    echo '<label class="inline" style="margin-top:14px"><input type="checkbox" name="turnstile_off" value="1"> Отключить Turnstile (удалить ключи)</label>';
    echo '<div class="hint">Внимание: при неверных ключах вход в панель станет невозможен. Сначала проверьте форму на сайте.</div>';
    echo '<p><button class="btn">Сохранить</button></p></form>';

    echo '<div class="panel"><h3>Встроенная защита</h3><ul class="muted" style="line-height:1.9">'
       . '<li>Вход: не более ' . LOGIN_MAX_TRIES . ' неудачных попыток за 15 минут с одного IP</li>'
       . '<li>Сессия: завершается после 2 часов бездействия, максимум 12 часов</li>'
       . '<li>CSRF-токен во всех формах панели</li>'
       . '<li>Форма на сайте: скрытое поле-ловушка, подписанный токен времени, не более 3 заявок за 10 минут с одного IP</li>'
       . '<li>Загрузка: только изображения, проверка содержимого файла, случайные имена</li></ul></div>';
}

/* ---------------- USERS ---------------- */
if ($section === 'users') {
    $me = (int)current_admin()['id'];
    $users = $db->query('SELECT * FROM admins ORDER BY id')->fetchAll();
    echo '<form method="post" class="panel">' . csrf_field() . '<input type="hidden" name="action" value="save">';
    echo '<h3>Пользователи панели</h3><p class="muted">Редактор может менять контент и заявки, но не видит настройки почты, безопасности и пользователей.</p>';
    echo '<div class="tablewrap"><table><tr><th>E-mail</th><th>Имя</th><th>Роль</th><th class="c">Активен</th><th>Новый пароль</th><th>Последний вход</th></tr>';
    foreach ($users as $u) {
        $id = (int)$u['id']; $self = $id === $me;
        echo '<tr><td>' . e($u['email']) . ($self ? ' <span class="pill">вы</span>' : '') . '</td>'
           . '<td><input type="text" name="u[' . $id . '][name]" value="' . e($u['name']) . '"></td>'
           . '<td><select name="u[' . $id . '][role]"' . ($self ? ' disabled' : '') . '><option value="admin"' . ($u['role'] === 'admin' ? ' selected' : '') . '>Администратор</option><option value="editor"' . ($u['role'] !== 'admin' ? ' selected' : '') . '>Редактор</option></select></td>'
           . '<td class="c"><input type="checkbox" name="u[' . $id . '][active]" value="1"' . ((int)$u['active'] ? ' checked' : '') . ($self ? ' disabled' : '') . '></td>'
           . '<td><input type="password" name="u[' . $id . '][password]" placeholder="не менять" autocomplete="new-password"></td>'
           . '<td class="muted">' . e((string)$u['last_login']) . '</td></tr>';
    }
    echo '</table></div><p><button class="btn">Сохранить</button></p></form>';

    foreach ($users as $u) {
        if ((int)$u['id'] === $me) continue;
        echo '<form method="post" style="display:inline-block;margin:0 8px 8px 0" data-confirm="Удалить пользователя ' . e($u['email']) . '?">' . csrf_field()
           . '<input type="hidden" name="action" value="del"><input type="hidden" name="id" value="' . (int)$u['id'] . '"><button class="btn sm red">Удалить ' . e($u['email']) . '</button></form>';
    }

    echo '<form method="post" class="panel" style="margin-top:12px">' . csrf_field() . '<input type="hidden" name="action" value="add">';
    echo '<h3>Добавить пользователя</h3><div class="row"><div><label>E-mail</label><input type="email" name="email" required></div><div><label>Имя</label><input type="text" name="name"></div></div>';
    echo '<div class="row"><div><label>Пароль (мин. 8)</label><input type="password" name="password" required minlength="8" autocomplete="new-password"></div><div><label>Роль</label><select name="role"><option value="editor">Редактор</option><option value="admin">Администратор</option></select></div></div>';
    echo '<p><button class="btn">Добавить</button></p></form>';
}

/* ---------------- PROFILE ---------------- */
if ($section === 'profile') {
    $a = current_admin();
    echo '<div class="row"><form method="post" class="panel">' . csrf_field() . '<input type="hidden" name="action" value="save">';
    echo '<h3>Профиль</h3><label>Имя</label><input type="text" name="name" value="' . e($a['name']) . '"><label>E-mail (логин)</label><input type="email" name="email" value="' . e($a['email']) . '" required>';
    echo '<p><button class="btn">Сохранить</button></p></form>';
    echo '<form method="post" class="panel">' . csrf_field() . '<input type="hidden" name="action" value="passwd">';
    echo '<h3>Смена пароля</h3><label>Текущий пароль</label><input type="password" name="old" required autocomplete="current-password">';
    echo '<label>Новый пароль (мин. 8)</label><input type="password" name="new" required minlength="8" autocomplete="new-password"><label>Повторите</label><input type="password" name="new2" required autocomplete="new-password">';
    echo '<p><button class="btn">Изменить пароль</button></p></form></div>';
}

layout_bottom();
