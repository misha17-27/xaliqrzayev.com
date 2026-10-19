<?php
/*
 * Brauzerdən quraşdırma (config/config.php yoxdursa və ya baza qurulmayıbsa).
 *
 * Təhlükəsizlik:
 *  · Yalnız localhost-dakı MySQL qəbul olunur — onun istifadəçisini yalnız cPanel sahibi yarada bilər,
 *    yəni kənar şəxs bazanı öz serverinə yönləndirib paneli ələ keçirə bilməz.
 *  · Cəhdlər IP üzrə məhdudlaşdırılır (MySQL parolunun seçilməsinə qarşı).
 *  · config.php yaradılandan sonra sehrbaz bağlanır; baza boşdursa, quraşdırma install_token tələb edir.
 */
declare(strict_types=1);

const SETUP_MAX_TRIES = 10;
const SETUP_WINDOW    = 3600;

function setup_attempts_file(): string { return STORAGE_DIR . '/setup_attempts.json'; }

/** Son bir saatda bu IP-dən neçə uğursuz cəhd olub */
function setup_tries(bool $add = false): int {
    $f = setup_attempts_file();
    $all = is_file($f) ? (json_decode((string)file_get_contents($f), true) ?: []) : [];
    $ip = client_ip_admin() ?: 'unknown';
    $now = time();
    $all[$ip] = array_values(array_filter((array)($all[$ip] ?? []), function ($t) use ($now) { return $t > $now - SETUP_WINDOW; }));
    if ($add) $all[$ip][] = $now;
    if (!is_dir(STORAGE_DIR)) @mkdir(STORAGE_DIR, 0775, true);
    @file_put_contents($f, json_encode($all), LOCK_EX);
    return count($all[$ip]);
}

function setup_page(string $title, string $body): void {
    head_html($title);
    echo '<div class="login"><div class="box setup"><div class="login-top">' . brand_html() . lang_switch() . '</div>' . '<h2>' . e($title) . '</h2>' . $body . '</div></div>';
    echo '<script src="' . e(admin_asset('js/admin.js')) . '"></script></body></html>';
    exit;
}

function setup_field(string $label, string $name, string $type = 'text', string $value = '', string $hint = '', string $attrs = ''): string {
    return '<label for="s-' . $name . '">' . e($label) . '</label>'
         . '<input id="s-' . $name . '" type="' . $type . '" name="' . $name . '" value="' . e($value) . '" ' . $attrs . '>'
         . ($hint !== '' ? '<div class="hint">' . $hint . '</div>' : '');
}

/** Config faylının məzmunu */
function setup_config_php(array $c): string {
    $out = "<?php\n/*\n * Yaradılıb: " . date('Y-m-d H:i') . " — admin paneldəki quraşdırma sehrbazı ilə.\n"
         . " * Bu fayl git-ə düşmür. Parolları dəyişsəniz, burada da yeniləyin.\n */\nreturn " . var_export($c, true) . ";\n";
    return $out;
}

/** Bazanı qurur: cədvəllər + məzmun + ilk admin (admin artıq varsa toxunmur). Qaytarır: yaradılan admin email-i və ya '' */
function setup_install(PDO $db, string $name, string $email, string $pass): string {
    cms_create_tables($db);
    cms_seed($db);
    $has = (int)$db->query('SELECT COUNT(*) FROM admins')->fetchColumn();
    if ($has > 0) return '';
    $db->prepare('INSERT INTO admins (name,email,pass_hash,role,active) VALUES (?,?,?,?,1)')
       ->execute([$name !== '' ? $name : 'Administrator', $email, password_hash($pass, PASSWORD_DEFAULT), 'admin']);
    return $email;
}

/** Admin məlumatlarının yoxlanması; xəta mətni və ya '' */
function setup_check_admin(string $email, string $pass, string $pass2): string {
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) return t('Укажите корректный e-mail администратора.');
    if (strlen($pass) < 8) return t('Пароль администратора — минимум 8 символов.');
    if ($pass !== $pass2) return t('Пароли администратора не совпадают.');
    return '';
}

/* ================================================================ */

function setup_wizard(?Throwable $dbError): void {
    $isPost = ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST';
    if ($isPost) csrf_check();
    $err = '';
    $P = function (string $k) { return trim((string)($_POST[$k] ?? '')); };

    /* -------- A) config.php yoxdur: tam sehrbaz -------- */
    if (!is_file(CONFIG_FILE)) {
        if ($isPost) {
            if (setup_tries() >= SETUP_MAX_TRIES) {
                $err = t('Слишком много попыток. Повторите через час.');
            } else {
                $host = $P('db_host') ?: 'localhost';
                $port = 0;
                if (preg_match('/^(.+):(\d{2,5})$/', $host, $hm)) { $host = $hm[1]; $port = (int)$hm[2]; }
                $err  = setup_check_admin($P('admin_email'), (string)($_POST['admin_pass'] ?? ''), (string)($_POST['admin_pass2'] ?? ''));
                if ($err === '' && !in_array($host, ['localhost', '127.0.0.1'], true)) $err = t('Разрешён только сервер базы localhost.');
                if ($err === '' && ($P('db_name') === '' || $P('db_user') === '')) $err = t('Заполните имя базы и пользователя.');
                if ($err === '') {
                    try {
                        $db = new PDO("mysql:host=$host;" . ($port ? "port=$port;" : '') . 'dbname=' . $P('db_name') . ';charset=utf8mb4', $P('db_user'), (string)($_POST['db_pass'] ?? ''), [
                            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, PDO::ATTR_EMULATE_PREPARES => false,
                        ]);
                    } catch (Throwable $e) {
                        setup_tries(true);
                        $err = t('Не удалось подключиться к базе: проверьте имя базы, пользователя и пароль (в cPanel имена начинаются с префикса аккаунта, например xaliqrzayev_…). Также пользователь должен быть добавлен к базе с ALL PRIVILEGES.');
                    }
                }
                if ($err === '' && isset($db)) {
                    $cfg = [
                        'driver'      => 'mysql',
                        'db_host'     => $host,
                        'db_port'     => $port ?: 3306,
                        'db_name'     => $P('db_name'),
                        'db_user'     => $P('db_user'),
                        'db_pass'     => (string)($_POST['db_pass'] ?? ''),
                        'db_charset'  => 'utf8mb4',
                        'install_admin_email' => $P('admin_email'),
                        'install_token' => bin2hex(random_bytes(16)),
                    ];
                    $php = setup_config_php($cfg);
                    $dir = dirname(CONFIG_FILE);
                    if (!is_dir($dir)) @mkdir($dir, 0750, true);
                    $ok = @file_put_contents(CONFIG_FILE . '.tmp', $php, LOCK_EX) !== false && @rename(CONFIG_FILE . '.tmp', CONFIG_FILE);
                    if (!$ok) {
                        setup_page(t('Не удалось сохранить конфиг'),
                            t('<p class="sub">Папка <code>config/</code> недоступна для записи. Создайте файл <code>config/config.php</code> в File Manager и вставьте в него:</p>')
                            . '<textarea readonly style="min-height:260px;font-family:monospace;font-size:12px" data-copy>' . e($php) . '</textarea>'
                            . t('<p><a class="btn" href="index.php">Готово, продолжить</a></p>'));
                    }
                    @chmod(CONFIG_FILE, 0640);
                    try {
                        $made = setup_install($db, $P('admin_name'), $P('admin_email'), (string)$_POST['admin_pass']);
                    } catch (Throwable $e) {
                        setup_page(t('Ошибка установки'), '<div class="flash err">' . e($e->getMessage()) . t('</div><p><a class="btn" href="index.php">Повторить</a></p>'));
                    }
                    flash($made !== '' ? t('Панель установлена. Войдите как ') . $made . '.' : t('Конфиг сохранён. В базе уже есть администраторы — войдите своим логином.'));
                    redirect('index.php?section=login');
                }
            }
        }

        $body = t('<div class="sub">Установка за один шаг. Нужна пустая база MySQL: cPanel → MySQL® Databases → создать базу и пользователя, добавить пользователя к базе с ALL PRIVILEGES.</div>');
        if ($err) $body .= '<div class="flash err">' . e($err) . '</div>';
        $body .= '<form method="post" autocomplete="off">' . csrf_field();
        $body .= t('<fieldset><legend>База данных MySQL</legend>')
            . setup_field(t('Имя базы'), 'db_name', 'text', $P('db_name'), t('Полное имя, например <code>xaliqrzayev_site</code>'), 'required')
            . setup_field(t('Пользователь базы'), 'db_user', 'text', $P('db_user'), '', 'required')
            . setup_field(t('Пароль пользователя базы'), 'db_pass', 'password', '', '', 'autocomplete="new-password"')
            . setup_field(t('Сервер'), 'db_host', 'text', $P('db_host') ?: 'localhost', t('Обычно <code>localhost</code>'))
            . '</fieldset>';
        $body .= t('<fieldset><legend>Администратор панели</legend>')
            . setup_field(t('Имя'), 'admin_name', 'text', $P('admin_name') ?: 'Administrator')
            . setup_field(t('E-mail (логин)'), 'admin_email', 'email', $P('admin_email'), '', 'required')
            . setup_field(t('Пароль (мин. 8 символов)'), 'admin_pass', 'password', '', '', 'required minlength="8" autocomplete="new-password"')
            . setup_field(t('Повторите пароль'), 'admin_pass2', 'password', '', '', 'required minlength="8" autocomplete="new-password"')
            . '</fieldset>';
        $body .= t('<button class="btn" style="width:100%;margin-top:18px">Установить</button></form>');
        setup_page(t('Установка панели'), $body);
    }

    /* -------- B) config.php var, amma baza işləmir və ya boşdur -------- */
    $connErr = '';
    $db = null;
    try { $db = pdo(); $db->query('SELECT 1'); } catch (Throwable $e) { $connErr = $e->getMessage(); $db = null; }

    if (!$db) {
        setup_page(t('Нет подключения к базе'),
            t('<div class="sub">Файл <code>config/config.php</code> найден, но подключиться к базе не удалось. Проверьте в нём <code>db_name</code>, <code>db_user</code>, <code>db_pass</code> и что пользователь добавлен к базе с ALL PRIVILEGES.</div>')
            . '<div class="flash err" style="word-break:break-word">' . e(preg_replace('/\(using password: \w+\)/', '', $connErr)) . '</div>'
            . t('<p class="hint">Если хотите пройти установку заново — удалите <code>config/config.php</code> и обновите страницу.</p>')
            . t('<p><a class="btn" href="index.php">Проверить снова</a></p>'));
    }

    /* Bağlantı var, cədvəllər yoxdur: install_token ilə quraşdırma */
    if ($isPost) {
        $tok = (string)cfg('install_token', '');
        if (setup_tries() >= SETUP_MAX_TRIES) $err = t('Слишком много попыток. Повторите через час.');
        elseif ($tok === '' || $tok === 'CHANGE_ME' || !hash_equals($tok, $P('token'))) { setup_tries(true); $err = t('Неверный install_token (он в config/config.php).'); }
        else $err = setup_check_admin($P('admin_email'), (string)($_POST['admin_pass'] ?? ''), (string)($_POST['admin_pass2'] ?? ''));
        if ($err === '') {
            try {
                $made = setup_install($db, $P('admin_name'), $P('admin_email'), (string)$_POST['admin_pass']);
                flash($made !== '' ? t('Панель установлена. Войдите как ') . $made . '.' : t('Таблицы созданы. Администраторы уже есть — войдите своим логином.'));
                redirect('index.php?section=login');
            } catch (Throwable $e) { $err = t('Ошибка установки: ') . $e->getMessage(); }
        }
    }
    $body = t('<div class="sub">Подключение к базе есть, но таблицы ещё не созданы.</div>');
    if ($err) $body .= '<div class="flash err">' . e($err) . '</div>';
    $body .= '<form method="post" autocomplete="off">' . csrf_field()
        . setup_field('install_token', 'token', 'text', '', t('Значение <code>install_token</code> из <code>config/config.php</code>'), 'required')
        . t('<fieldset><legend>Администратор панели</legend>')
        . setup_field(t('Имя'), 'admin_name', 'text', $P('admin_name') ?: 'Administrator')
        . setup_field(t('E-mail (логин)'), 'admin_email', 'email', $P('admin_email') ?: (string)cfg('install_admin_email', ''), '', 'required')
        . setup_field(t('Пароль (мин. 8 символов)'), 'admin_pass', 'password', '', '', 'required minlength="8" autocomplete="new-password"')
        . setup_field(t('Повторите пароль'), 'admin_pass2', 'password', '', '', 'required minlength="8" autocomplete="new-password"')
        . t('</fieldset><button class="btn" style="width:100%;margin-top:18px">Создать таблицы и администратора</button></form>');
    setup_page(t('Завершение установки'), $body);
}
