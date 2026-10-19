<?php
/* POST əməliyyatları və CSV eksport. controller.php-dən yüklənir: $db, $section mövcuddur. */
declare(strict_types=1);

/* ================== POST ================== */
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $act = (string)($_POST['action'] ?? '');

    if ($section === 'texts' && $act === 'save') {
        $allowed = [];
        foreach (TEXT_FIELDS as $fields) $allowed += $fields;
        foreach (($_POST['t'] ?? []) as $k => $v) {
            if (isset($allowed[$k])) kv_set('texts', (string)$k, trim((string)$v));
        }
        flash(t('Тексты сохранены.')); redirect('index.php?section=texts');
    }

    if ($section === 'contacts' && $act === 'save') {
        foreach (CONTACT_FIELDS as $k => $_) kv_set('contacts', $k, trim((string)($_POST[$k] ?? '')));
        $map = trim((string)($_POST['map'] ?? ''));
        /* Bütöv <iframe> yapışdırılıbsa — src-ni çıxarırıq */
        if (preg_match('~src="([^"]+)"~', $map, $m)) { $map = html_entity_decode($m[1]); kv_set('contacts', 'map', $map); }
        if ($map !== '' && !preg_match('~^https://(www\.)?google\.[a-z.]+/maps/embed~', $map))
            flash(t('Карта не будет показана: нужна ссылка вида https://www.google.com/maps/embed?pb=…'), 'err');
        flash(t('Контакты сохранены.')); redirect('index.php?section=contacts');
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
            flash(t('Добавлено пунктов: %d.', $n));
        }
        redirect('index.php?section=services&grp=' . $grp);
    }

    if ($section === 'highlights' && $act === 'save') {
        flash(rows_msg(save_rows($db, 'highlights', ['title', 'descr'], 'title')));
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
                if ($p = admin_upload('file', $err)) { kv_set('settings', $slot, $p); flash(t('Изображение загружено и установлено.')); }
                elseif ($err !== '') flash($err, 'err');
                elseif (($p = safe_img_path((string)($_POST['path'] ?? ''))) !== '') { kv_set('settings', $slot, $p); flash(t('Изображение выбрано.')); }
                else flash(t('Выберите файл или изображение из библиотеки.'), 'err');
            }
        } elseif ($act === 'upload') {
            $err = '';
            if ($p = admin_upload('file', $err)) flash(t('Загружено: ') . $p);
            else flash($err ?: t('Файл не выбран.'), 'err');
        } elseif ($act === 'del') {
            $p = safe_img_path((string)($_POST['path'] ?? ''));
            $used = false;
            foreach (IMAGE_SLOTS as $k => $_) if (setting($k) === $p) $used = true;
            if ($p === '' || !str_starts_with($p, 'uploads/')) flash(t('Удалять можно только загруженные через панель файлы.'), 'err');
            elseif ($used) flash(t('Изображение используется на сайте — сначала замените его.'), 'err');
            else {
                $full = PUBLIC_DIR . '/assets/img/' . $p;
                @unlink($full);
                @unlink((string)preg_replace('/\.(jpe?g|png)$/i', '.webp', $full));
                flash(t('Файл удалён.'));
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
            flash(t('Заявка обновлена.'));
        } elseif ($act === 'read' && $id) {
            $db->prepare('UPDATE submissions SET is_read=1 WHERE id=?')->execute([$id]);
        } elseif ($act === 'del' && $id) {
            $db->prepare('DELETE FROM submissions WHERE id=?')->execute([$id]); flash(t('Заявка удалена.'));
        } elseif ($act === 'allread') {
            $db->exec('UPDATE submissions SET is_read=1'); flash(t('Все отмечены прочитанными.'));
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
            flash(t('SEO сохранён.'));
        } elseif ($act === 'save_ga') {
            $ga = trim((string)($_POST['ga_id'] ?? ''));
            if ($ga !== '' && !preg_match('/^(G|GT|UA|AW)-[A-Z0-9\-]{4,20}$/i', $ga)) flash(t('Неверный формат. Пример: G-XXXXXXXXXX'), 'err');
            else { kv_set('settings', 'ga_id', $ga); flash($ga === '' ? t('Аналитика отключена.') : t('Номер аналитики сохранён.')); }
        }
        redirect('index.php?section=seo');
    }

    if ($section === 'backup' && $act === 'import') {
        $err = '';
        $raw = '';
        if (!empty($_FILES['file']['tmp_name']) && is_uploaded_file($_FILES['file']['tmp_name']) && $_FILES['file']['size'] < 5 * 1024 * 1024) {
            $raw = (string)file_get_contents($_FILES['file']['tmp_name']);
        }
        $data = $raw !== '' ? json_decode($raw, true) : null;
        if (!is_array($data) || ($data['_format'] ?? '') !== BACKUP_FORMAT) {
            flash(t('Файл не похож на резервную копию этого сайта.'), 'err');
        } else {
            try {
                $n = backup_restore($db, $data);
                flash(t('Восстановлено из копии от %s. Записей: %d.', (string)($data['_created'] ?? '?'), $n));
            } catch (Throwable $e) {
                flash(t('Ошибка восстановления, данные не изменены: ') . $e->getMessage(), 'err');
            }
        }
        redirect('index.php?section=backup');
    }

    if ($section === 'security' && $act === 'save') {
        kv_set('settings', 'turnstile_site', trim((string)($_POST['turnstile_site'] ?? '')));
        if (($_POST['turnstile_secret'] ?? '') !== '') kv_set('settings', 'turnstile_secret', trim((string)$_POST['turnstile_secret']));
        if (!empty($_POST['turnstile_off'])) { kv_set('settings', 'turnstile_site', ''); kv_set('settings', 'turnstile_secret', ''); }
        flash(t('Настройки безопасности сохранены.')); redirect('index.php?section=security');
    }

    if ($section === 'smtp') {
        if ($act === 'save') {
            foreach (['smtp_host', 'smtp_port', 'smtp_user', 'smtp_from', 'smtp_from_name', 'smtp_secure'] as $k)
                kv_set('settings', $k, trim((string)($_POST[$k] ?? '')));
            if (($_POST['smtp_pass'] ?? '') !== '') kv_set('settings', 'smtp_pass', (string)$_POST['smtp_pass']);
            $to = trim((string)($_POST['mail_to'] ?? ''));
            if ($to !== '' && !filter_var($to, FILTER_VALIDATE_EMAIL)) flash(t('Некорректный e-mail получателя.'), 'err');
            else kv_set('settings', 'mail_to', $to);
            flash(t('Настройки почты сохранены.'));
        } elseif ($act === 'test') {
            $err = ''; $to = trim((string)($_POST['test_to'] ?? ''));
            if (!filter_var($to, FILTER_VALIDATE_EMAIL)) flash(t('Укажите корректный e-mail.'), 'err');
            elseif (smtp_send($to, t('Тест SMTP — xaliqrzayev.com'), t("Это тестовое письмо из админ-панели.\nЕсли вы его получили — SMTP настроен верно."), $err))
                flash(t('Тестовое письмо отправлено на ') . $to);
            else flash(t('Ошибка отправки: ') . $err, 'err');
        }
        redirect('index.php?section=smtp');
    }

    if ($section === 'profile') {
        $a = current_admin();
        if ($act === 'save') {
            $em = trim((string)($_POST['email'] ?? ''));
            if (!filter_var($em, FILTER_VALIDATE_EMAIL)) flash(t('Некорректный e-mail.'), 'err');
            else {
                $chk = $db->prepare('SELECT id FROM admins WHERE email=? AND id<>?'); $chk->execute([$em, $a['id']]);
                if ($chk->fetch()) flash(t('Этот e-mail уже занят.'), 'err');
                else {
                    $db->prepare('UPDATE admins SET name=?, email=? WHERE id=?')->execute([trim((string)($_POST['name'] ?? '')), $em, $a['id']]);
                    $_SESSION['admin']['name']  = trim((string)($_POST['name'] ?? ''));
                    $_SESSION['admin']['email'] = $em;
                    flash(t('Профиль сохранён.'));
                }
            }
        } elseif ($act === 'passwd') {
            $st = $db->prepare('SELECT pass_hash FROM admins WHERE id=?'); $st->execute([$a['id']]); $row = $st->fetch();
            if (!password_verify((string)($_POST['old'] ?? ''), $row['pass_hash'] ?? '')) flash(t('Текущий пароль неверный.'), 'err');
            elseif (strlen((string)($_POST['new'] ?? '')) < 8)                        flash(t('Новый пароль слишком короткий (мин. 8).'), 'err');
            elseif (($_POST['new'] ?? '') !== ($_POST['new2'] ?? ''))                 flash(t('Пароли не совпадают.'), 'err');
            else {
                $db->prepare('UPDATE admins SET pass_hash=? WHERE id=?')->execute([password_hash((string)$_POST['new'], PASSWORD_DEFAULT), $a['id']]);
                session_regenerate_id(true);
                flash(t('Пароль изменён.'));
            }
        }
        redirect('index.php?section=profile');
    }

    if ($section === 'users') {
        $me = (int)current_admin()['id'];
        if ($act === 'add') {
            $em = trim((string)($_POST['email'] ?? ''));
            if (!filter_var($em, FILTER_VALIDATE_EMAIL))           flash(t('Некорректный e-mail.'), 'err');
            elseif (strlen((string)($_POST['password'] ?? '')) < 8) flash(t('Пароль минимум 8 символов.'), 'err');
            else {
                $chk = $db->prepare('SELECT id FROM admins WHERE email=?'); $chk->execute([$em]);
                if ($chk->fetch()) flash(t('Пользователь с таким e-mail уже есть.'), 'err');
                else {
                    $db->prepare('INSERT INTO admins (name,email,pass_hash,role,active) VALUES (?,?,?,?,1)')
                       ->execute([trim((string)($_POST['name'] ?? '')), $em, password_hash((string)$_POST['password'], PASSWORD_DEFAULT), ($_POST['role'] ?? 'editor') === 'admin' ? 'admin' : 'editor']);
                    flash(t('Пользователь добавлен.'));
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
                    else flash(t('Пароль не изменён: минимум 8 символов.'), 'err');
                }
            }
            flash(t('Изменения сохранены.'));
        } elseif ($act === 'del') {
            $id = (int)($_POST['id'] ?? 0);
            if ($id !== $me) { $db->prepare('DELETE FROM admins WHERE id=?')->execute([$id]); flash(t('Пользователь удалён.')); }
            else flash(t('Нельзя удалить самого себя.'), 'err');
        }
        redirect('index.php?section=users');
    }

    redirect('index.php?section=' . $section);
}

/* ---------------- Ehtiyat nüsxə: JSON yükləmə (GET) ---------------- */
if ($section === 'backup' && ($_GET['export'] ?? '') === 'json') {
    header('Content-Type: application/json; charset=utf-8');
    header('Content-Disposition: attachment; filename="xaliqrzayev-backup-' . date('Y-m-d-His') . '.json"');
    echo json_encode(backup_export($db), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

/* ---------------- CSV eksport (GET) ---------------- */
if ($section === 'submissions' && ($_GET['export'] ?? '') === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="zayavki-' . date('Y-m-d') . '.csv"');
    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF");   // Excel üçün UTF-8 BOM
    fputcsv($out, ['ID', t('Дата'), t('Имя'), t('Телефон'), t('Сообщение'), t('Статус'), t('Заметка')], ';');
    foreach ($db->query('SELECT * FROM submissions ORDER BY id DESC') as $r) {
        $row = [$r['id'], $r['created_at'], $r['name'], $r['phone'], $r['message'], t(SUB_STATUS[$r['status']] ?? $r['status']), $r['note']];
        /* Excel düstur inyeksiyasının qarşısı */
        $row = array_map(function ($v) { $v = (string)$v; return preg_match('/^[=+\-@]/', $v) ? "'" . $v : $v; }, $row);
        fputcsv($out, $row, ';');
    }
    exit;
}
