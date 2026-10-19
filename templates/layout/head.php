<?php
/*
 * <head> və <body> başlanğıcı.
 * Gözlənilən dəyişən: $page = ['title', 'descr', 'robots', 'canonical', 'schema' => bool]
 */
$ogImage = SITE_URL . img($IMAGES['og_image']);
?><!doctype html>
<html lang="az">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($page['title']) ?></title>
<?php if (($page['descr'] ?? '') !== ''): ?><meta name="description" content="<?= e($page['descr']) ?>">
<?php endif; ?>
<meta name="robots" content="<?= e($page['robots'] ?? 'index,follow') ?>">
<?php if (!empty($page['canonical'])): ?>
<link rel="canonical" href="<?= e($page['canonical']) ?>">
<meta property="og:type" content="website">
<meta property="og:locale" content="az_AZ">
<meta property="og:title" content="<?= e($page['title']) ?>">
<meta property="og:description" content="<?= e($page['descr'] ?? '') ?>">
<meta property="og:url" content="<?= e($page['canonical']) ?>">
<meta property="og:image" content="<?= e($ogImage) ?>">
<meta name="twitter:card" content="summary_large_image">
<?php endif; ?>
<meta name="theme-color" content="#0e1a2b">
<link rel="icon" href="<?= e(img('favicon.svg')) ?>" type="image/svg+xml">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Lora:ital,wght@0,500;0,600;0,700;1,500&family=Manrope:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= e(asset('css/style.css')) ?>">
<?php if (!empty($page['schema'])): ?>
<script type="application/ld+json" nonce="<?= csp_nonce() ?>"><?= json_encode([
    '@context'  => 'https://schema.org',
    '@type'     => 'Physician',
    'name'      => $T['site_name'],
    'medicalSpecialty' => 'Urologic',
    'description' => $page['descr'] ?? '',
    'image'     => $ogImage,
    'url'       => $page['canonical'],
    'telephone' => $C['phone_raw'],
] + ($C['address'] !== '' ? ['address' => $C['address']] : []), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?></script>
<?php endif; ?>
<?php if (($ga = cms_setting('ga_id')) !== ''): ?>
<script async src="https://www.googletagmanager.com/gtag/js?id=<?= e($ga) ?>"></script>
<script nonce="<?= csp_nonce() ?>">window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments)}gtag('js',new Date());gtag('config',<?= json_encode($ga) ?>);</script>
<?php endif; ?>
</head>
<body>
