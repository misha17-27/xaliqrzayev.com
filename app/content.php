<?php
/**
 * Admin panelindən gələn məzmun.
 *
 * app/bootstrap.php-nin sonundan yüklənir. Baza qurulubsa — app/defaults.php-dəki massivləri
 * bazadakı dəyərlərlə əvəz edir. Baza yoxdursa sayt ilkin dəyərlərlə işləyir.
 */

if (defined('CMS_NO_DB')) return;   // seed zamanı dövrəyə girməsin

/** Bazaya bağlantı (yoxdursa null) */
function cms_db(): ?PDO {
    static $db = false;
    if ($db !== false) return $db;
    $db = null;
    if (!is_file(CONFIG_FILE)) return $db;
    try {
        $p = require APP_DIR . '/db.php';
        $p->query('SELECT 1 FROM texts LIMIT 1');       // quraşdırılıb?
        $db = $p;
    } catch (Throwable $e) {
        $db = null;
    }
    return $db;
}

/** key-value cədvəli → massiv */
function cms_kv(string $table): array {
    static $cache = [];
    if (isset($cache[$table])) return $cache[$table];
    $out = [];
    if ($db = cms_db()) {
        try { foreach ($db->query("SELECT k, v FROM `$table`") as $r) $out[$r['k']] = (string)$r['v']; }
        catch (Throwable $e) {}
    }
    return $cache[$table] = $out;
}

function cms_setting(string $k, string $default = ''): string { return cms_kv('settings')[$k] ?? $default; }

if ($db = cms_db()) {
    /* Köhnə bazada sxem yenilənməyibsə — bir dəfə miqrasiya */
    if ((int)(cms_kv('settings')['schema_version'] ?? 0) < 2) {
        try { require_once APP_DIR . '/schema.php'; cms_migrate($db); } catch (Throwable $e) {}
    }
    try {
        foreach (cms_kv('texts') as $k => $v)    if (array_key_exists($k, $TEXTS)) $TEXTS[$k] = $v;
        foreach (cms_kv('contacts') as $k => $v) $CONTACT[$k] = $v;
        foreach (cms_kv('settings') as $k => $v) if (array_key_exists($k, $IMAGES) && $v !== '') $IMAGES[$k] = $v;

        $rows = $db->query('SELECT * FROM highlights WHERE visible=1 ORDER BY sort, id')->fetchAll();
        $HIGHLIGHTS = array_map(static function ($r) { return ['icon' => $r['icon'], 'title' => $r['title'], 'descr' => $r['descr']]; }, $rows);

        $svc = array_fill_keys(array_keys($SERVICE_GROUPS), []);
        foreach ($db->query('SELECT grp, title FROM services WHERE visible=1 ORDER BY sort, id') as $r) {
            if (isset($svc[$r['grp']])) $svc[$r['grp']][] = $r['title'];
        }
        $SERVICES = $svc;

        $rows = $db->query('SELECT * FROM education WHERE visible=1 ORDER BY sort, id')->fetchAll();
        $EDUCATION = array_map(static function ($r) { return ['years' => $r['years'], 'title' => $r['title'], 'descr' => $r['descr'], 'label' => $r['label']]; }, $rows);

        foreach ($db->query('SELECT * FROM seo_pages') as $r) {
            $SEO[$r['slug']] = ['title' => $r['title'], 'descr' => $r['descr'], 'robots' => $r['robots'] ?: 'index,follow'];
        }
    } catch (Throwable $e) {
        // köhnə sxem və ya xəta — ilkin dəyərlər qalır
    }
}
