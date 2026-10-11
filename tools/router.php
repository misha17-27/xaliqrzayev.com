<?php
/*
 * PHP daxili serveri üçün marşrutlayıcı (yalnız lokal iş üçün):
 *   php -S localhost:8020 -t public tools/router.php
 * Kök public/ olduğu üçün app/, config/, storage/ onsuz da əlçatmazdır.
 */
$pub  = dirname(__DIR__) . '/public';
$path = rawurldecode((string)parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

if ($path === '/_init.php' || preg_match('~/\.ht~', $path)) { http_response_code(404); require $pub . '/404.php'; return true; }
if ($path === '/sitemap.xml') { require $pub . '/sitemap.php'; return true; }
if ($path === '/admin') { header('Location: /admin/'); return true; }
if ($path === '/admin/') { require $pub . '/admin/index.php'; return true; }
if ($path === '/' || $path === '/index.php') { require $pub . '/index.php'; return true; }

if (is_file($pub . $path)) return false;   // statik fayl və ya .php

http_response_code(404);
require $pub . '/404.php';
return true;
