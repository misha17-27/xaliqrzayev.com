<?php
/*
 * Admin panelinin tərcümələrini yoxlayır:
 *   php tools/i18n-check.php
 * Koddakı bütün t('...') açarlarını və sabitlərdəki etiketləri (SECTIONS, TEXT_FIELDS, …)
 * app/admin/lang/az.php ilə tutuşdurur. Tərcüməsi olmayanları və artıq istifadə olunmayanları göstərir.
 */
$root = dirname(__DIR__);
define('APP_DIR', $root . '/app');
$keys = [];

/* 1) t('...') çağırışları */
foreach (array_merge(glob("$root/app/admin/*.php"), glob("$root/app/admin/sections/*.php")) as $f) {
    $toks = token_get_all(file_get_contents($f));
    $n = count($toks);
    for ($i = 0; $i < $n; $i++) {
        if (!is_array($toks[$i]) || $toks[$i][0] !== T_STRING || $toks[$i][1] !== 't') continue;
        $j = $i + 1;
        while (is_array($toks[$j]) && $toks[$j][0] === T_WHITESPACE) $j++;
        if ($toks[$j] !== '(') continue;
        $j++;
        while (is_array($toks[$j]) && $toks[$j][0] === T_WHITESPACE) $j++;
        if (is_array($toks[$j]) && $toks[$j][0] === T_CONSTANT_ENCAPSED_STRING) {
            $keys[eval('return ' . $toks[$j][1] . ';')] = basename($f);
        }
    }
}

/* 2) Sabitlərdəki etiketlər (istifadə yerində t() ilə tərcümə olunur) */
require "$root/app/admin/fields.php";
foreach (TEXT_FIELDS as $g => $fs) { $keys[$g] = 'fields.php'; foreach ($fs as $d) { $keys[$d[0]] = 'fields.php'; if (!empty($d[2])) $keys[$d[2]] = 'fields.php'; } }
foreach (CONTACT_FIELDS as $d) { $keys[$d[0]] = 'fields.php'; if ($d[1] !== '') $keys[$d[1]] = 'fields.php'; }
foreach (SVC_GROUPS as $v) $keys[$v] = 'fields.php';
foreach (IMAGE_SLOTS as $d) { $keys[$d[0]] = 'fields.php'; $keys[$d[1]] = 'fields.php'; }
foreach (SUB_STATUS as $v) $keys[$v] = 'fields.php';
$lib = file_get_contents("$root/app/admin/lib.php");
if (preg_match('/const SECTIONS = \[(.*?)\];/s', $lib, $m) && preg_match_all("/=> \['([^']+)'/u", $m[1], $mm)) foreach ($mm[1] as $v) $keys[$v] = 'lib.php';
if (preg_match('/const GROUPS = \[(.*?)\];/s', $lib, $m) && preg_match_all("/'([^']+)'\s*=>/u", $m[1], $mm)) foreach ($mm[1] as $v) $keys[$v] = 'lib.php';

/* Yalnız kiril olanlar tərcümə tələb edir (URL, nümunə nömrələr və s. olduğu kimi qalır) */
$keys = array_filter($keys, function ($k) { return preg_match('/[\x{0400}-\x{04FF}]/u', (string)$k); }, ARRAY_FILTER_USE_KEY);

$az = require "$root/app/admin/lang/az.php";
$missing = array_diff_key($keys, $az);
$unused  = array_diff_key($az, $keys);

/* sprintf yer tutanlarının sayı uyğun olmalıdır */
$bad = [];
foreach ($az as $k => $v) {
    if (preg_match_all('/%[sd]/', $k) !== preg_match_all('/%[sd]/', $v)) $bad[] = $k;
}

echo 'Açarlar: ' . count($keys) . ', lüğətdə: ' . count($az) . "\n";
echo 'Tərcüməsi yoxdur: ' . count($missing) . "\n";
foreach ($missing as $k => $f) echo "  [$f] $k\n";
echo 'İstifadə olunmur: ' . count($unused) . "\n";
foreach ($unused as $k => $_) echo "  $k\n";
echo '%s/%d uyğunsuzluğu: ' . count($bad) . "\n";
foreach ($bad as $k) echo "  $k\n";
exit(($missing || $bad) ? 1 : 0);
