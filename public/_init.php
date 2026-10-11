<?php
/*
 * Bütün veb giriş nöqtələri (index.php, muraciet.php, admin/…) bu faylla başlayır.
 *
 * Layihənin kökünü (app/, config/, storage/, templates/) tapır:
 *   · lokal və ya bütöv yükləmədə — public/-in valideyn qovluğu;
 *   · cPanel-də — public_html-in yanındakı xaliqrzayev.com/ qovluğu (.cpanel.yml belə yerləşdirir).
 */
declare(strict_types=1);

define('PUBLIC_DIR', __DIR__);

foreach ([dirname(__DIR__), dirname(__DIR__) . '/xaliqrzayev.com'] as $root) {
    if (is_file($root . '/app/bootstrap.php')) { define('ROOT', realpath($root)); break; }
}
if (!defined('ROOT')) {
    http_response_code(500);
    header('Content-Type: text/plain; charset=utf-8');
    exit('Application root not found (app/bootstrap.php).');
}

require ROOT . '/app/bootstrap.php';
