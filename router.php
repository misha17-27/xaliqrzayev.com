<?php
/*
 * PHP daxili serveri üçün marşrutlayıcı (yalnız lokal iş üçün, serverə yüklənmir):
 *   php -S localhost:8020 -t D:\xaliqrzayev.com D:\xaliqrzayev.com\router.php
 * .htaccess-dəki qadağaları təkrarlayır.
 */
$path = rawurldecode((string)parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

if (preg_match('~^/(includes|storage|tools|_designs|\.git|\.claude)(/|$)~', $path)
    || preg_match('~(^|/)(README\.md|router\.php|\.gitignore|\.htaccess)$~', $path)
    || preg_match('~\.(sqlite|log|sql|bak|md)$~i', $path)
    || preg_match('~^/admin/(config|config\.sample|db|schema|lib)\.php$~', $path)) {
    http_response_code(404);
    require __DIR__ . '/404.php';
    return true;
}

if ($path === '/sitemap.xml') { require __DIR__ . '/sitemap.php'; return true; }
if ($path === '/admin' ) { header('Location: /admin/'); return true; }
if ($path === '/admin/') { require __DIR__ . '/admin/index.php'; return true; }

$file = __DIR__ . $path;
if ($path !== '/' && is_file($file)) return false;   // statik fayl və ya .php

if ($path === '/' || $path === '/index.php') { require __DIR__ . '/index.php'; return true; }

http_response_code(404);
require __DIR__ . '/404.php';
return true;
