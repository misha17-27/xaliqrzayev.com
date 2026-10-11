<?php
/* /sitemap.xml — .htaccess bu fayla yönləndirir */
declare(strict_types=1);
require __DIR__ . '/_init.php';

header('Content-Type: application/xml; charset=utf-8');
$lastmod = date('Y-m-d', (int)max(filemtime(__DIR__ . '/index.php'), filemtime(APP_DIR . '/defaults.php')));
echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
  <url><loc><?= htmlspecialchars(SITE_URL . '/') ?></loc><lastmod><?= $lastmod ?></lastmod><priority>1.0</priority></url>
</urlset>
