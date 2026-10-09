<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';
http_response_code(404);
?><!doctype html>
<html lang="az">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex">
<title>Səhifə tapılmadı — <?= e($TEXTS['site_name']) ?></title>
<link rel="icon" href="<?= e(img('favicon.svg')) ?>" type="image/svg+xml">
<link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;700;900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= e(asset('css/style.css')) ?>">
</head>
<body>
<section style="min-height:100vh;display:grid;place-items:center;text-align:center">
  <div class="container">
    <div style="font-size:96px;font-weight:900;color:var(--teal);line-height:1">404</div>
    <h1 style="font-size:28px;margin:12px 0 8px">Səhifə tapılmadı</h1>
    <p style="color:var(--muted);margin-bottom:24px">Axtardığınız səhifə mövcud deyil və ya köçürülüb.</p>
    <div style="display:flex;gap:12px;justify-content:center;flex-wrap:wrap">
      <a class="btn btn-teal" href="<?= e(url('/')) ?>">Ana səhifə</a>
      <a class="btn btn-soft" href="<?= e(tel_link($CONTACT)) ?>"><?= e($CONTACT['phone']) ?></a>
    </div>
  </div>
</section>
</body>
</html>
