<?php
/* Ana səhifə. Şablonlar: templates/layout + templates/sections */
declare(strict_types=1);
require __DIR__ . '/_init.php';

$seo  = $SEO['home'] ?? [];
$page = [
    'title'     => $seo['title'] ?? $TEXTS['site_name'],
    'descr'     => $seo['descr'] ?? '',
    'robots'    => $seo['robots'] ?? 'index,follow',
    'canonical' => SITE_URL . '/',
    'schema'    => true,
];

/* Qəbul formunun JS-siz göndərilməsindən sonra nəticə */
$sent    = isset($_GET['sent']);
$errKey  = (string)($_GET['err'] ?? '');
$formErr = (preg_match('/^form_err_[a-z_]+$/', $errKey) && isset($TEXTS[$errKey])) ? $TEXTS[$errKey] : '';   // yalnız öz mətnlərimiz

render_page($page, ['hero', 'about', 'diagnostics', 'surgery', 'education', 'contact'], compact('sent', 'formErr'));
