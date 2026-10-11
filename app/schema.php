<?php
/*
 * Cədvəllərin yaradılması və saytın ilkin məzmununun bazaya köçürülməsi.
 * SQLite və MySQL — hər ikisi ilə işləyir.
 */
declare(strict_types=1);

require_once __DIR__ . '/compat.php';

function cms_is_sqlite(PDO $db): bool { return $db->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite'; }
function cms_pk(PDO $db): string {
    return cms_is_sqlite($db) ? 'id INTEGER PRIMARY KEY AUTOINCREMENT' : 'id INT AUTO_INCREMENT PRIMARY KEY';
}

function cms_create_tables(PDO $db): array {
    $pk  = cms_pk($db);
    $suf = cms_is_sqlite($db) ? '' : ' ENGINE=InnoDB DEFAULT CHARSET=utf8mb4';

    $tables = [
        'admins' => "$pk,
            `name` VARCHAR(120) DEFAULT '',
            `email` VARCHAR(190) NOT NULL UNIQUE,
            `pass_hash` VARCHAR(255) NOT NULL,
            `role` VARCHAR(20) DEFAULT 'editor',
            `active` INT DEFAULT 1,
            `last_login` VARCHAR(40) DEFAULT NULL",

        'settings' => "`k` VARCHAR(80) PRIMARY KEY, `v` TEXT",
        'texts'    => "`k` VARCHAR(80) PRIMARY KEY, `v` TEXT",
        'contacts' => "`k` VARCHAR(80) PRIMARY KEY, `v` TEXT",

        'services' => "$pk,
            `grp` VARCHAR(20) NOT NULL,
            `title` VARCHAR(255) NOT NULL DEFAULT '',
            `sort` INT DEFAULT 0,
            `visible` INT DEFAULT 1",

        'highlights' => "$pk,
            `icon` VARCHAR(16) DEFAULT '',
            `title` VARCHAR(120) DEFAULT '',
            `descr` VARCHAR(255) DEFAULT '',
            `sort` INT DEFAULT 0,
            `visible` INT DEFAULT 1",

        'education' => "$pk,
            `years` VARCHAR(40) DEFAULT '',
            `title` VARCHAR(190) DEFAULT '',
            `descr` TEXT,
            `label` VARCHAR(120) DEFAULT '',
            `sort` INT DEFAULT 0,
            `visible` INT DEFAULT 1",

        'seo_pages' => "`slug` VARCHAR(80) PRIMARY KEY,
            `title` VARCHAR(255) DEFAULT '',
            `descr` TEXT,
            `robots` VARCHAR(60) DEFAULT 'index,follow'",

        'submissions' => "$pk,
            `name` VARCHAR(120) DEFAULT '',
            `phone` VARCHAR(60) DEFAULT '',
            `message` TEXT,
            `note` TEXT,
            `status` VARCHAR(20) DEFAULT 'new',
            `is_read` INT DEFAULT 0,
            `ip` VARCHAR(60) DEFAULT '',
            `user_agent` VARCHAR(255) DEFAULT '',
            `created_at` VARCHAR(30) DEFAULT ''",

        'login_fails' => "$pk, `ip` VARCHAR(60) DEFAULT '', `at` INT DEFAULT 0",
    ];

    $made = [];
    foreach ($tables as $name => $cols) {
        $db->exec("CREATE TABLE IF NOT EXISTS `$name` ($cols)$suf");
        $made[] = $name;
    }
    return $made;
}

/** app/defaults.php-dəki məzmunu boş cədvəllərə köçürür. Doldurulmuş cədvəllərə toxunmur. */
function cms_seed(PDO $db): array {
    require __DIR__ . '/defaults.php';   // $TEXTS, $CONTACT, $IMAGES, ...
    $log = [];

    $empty = function (string $t) use ($db): bool { return (int)$db->query("SELECT COUNT(*) FROM `$t`")->fetchColumn() === 0; };

    /* key-value: yalnız çatışmayan açarlar əlavə olunur (yeni açarlar sonradan da gəlir) */
    $kv = function (string $t, array $rows) use ($db, &$log) {
        $has = $db->prepare("SELECT 1 FROM `$t` WHERE k = ?");
        $ins = $db->prepare("INSERT INTO `$t` (k, v) VALUES (?, ?)");
        $n = 0;
        foreach ($rows as $k => $v) {
            $has->execute([$k]);
            if ($has->fetch()) continue;
            $ins->execute([$k, (string)$v]); $n++;
        }
        $log[] = "$t — $n yeni açar";
    };

    $kv('texts', $TEXTS);
    $kv('contacts', $CONTACT);
    $kv('settings', $IMAGES);

    if ($empty('services')) {
        $st = $db->prepare('INSERT INTO services (grp, title, sort, visible) VALUES (?,?,?,1)');
        $n = 0;
        foreach ($SERVICES as $grp => $items) foreach ($items as $i => $t) { $st->execute([$grp, $t, ($i + 1) * 10]); $n++; }
        $log[] = "services — $n sətir";
    } else $log[] = 'services — artıq doludur';

    if ($empty('highlights')) {
        $st = $db->prepare('INSERT INTO highlights (icon, title, descr, sort, visible) VALUES (?,?,?,?,1)');
        foreach ($HIGHLIGHTS as $i => $h) $st->execute([$h['icon'], $h['title'], $h['descr'], ($i + 1) * 10]);
        $log[] = 'highlights — ' . count($HIGHLIGHTS) . ' sətir';
    } else $log[] = 'highlights — artıq doludur';

    if ($empty('education')) {
        $st = $db->prepare('INSERT INTO education (years, title, descr, label, sort, visible) VALUES (?,?,?,?,?,1)');
        foreach ($EDUCATION as $i => $r) $st->execute([$r['years'], $r['title'], $r['descr'], $r['label'], ($i + 1) * 10]);
        $log[] = 'education — ' . count($EDUCATION) . ' sətir';
    } else $log[] = 'education — artıq doludur';

    if ($empty('seo_pages')) {
        $st = $db->prepare('INSERT INTO seo_pages (slug, title, descr, robots) VALUES (?,?,?,?)');
        foreach ($SEO as $slug => $r) $st->execute([$slug, $r['title'], $r['descr'], $r['robots'] ?? 'index,follow']);
        $log[] = 'seo_pages — ' . count($SEO) . ' sətir';
    } else $log[] = 'seo_pages — artıq doludur';

    return $log;
}
