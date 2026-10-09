<?php
/*
 * Saytın sabitləri + məzmunun yüklənməsi.
 *
 * Məzmun: includes/defaults.php (ilkin) → includes/content.php (bazadan, varsa).
 * Baza yoxdursa və ya sıradan çıxıbsa sayt ilkin məzmunla işləməyə davam edir.
 */
declare(strict_types=1);

require_once __DIR__ . '/compat.php';

/* Sayt alt qovluqdadırsa: define('BASE_URL', '/qovluq'); */
if (!defined('BASE_URL')) define('BASE_URL', '');
define('SITE_URL', 'https://xaliqrzayev.com');

/* Müraciətlərin göndəriləcəyi ünvan (paneldən dəyişdirilə bilər) */
define('MAIL_TO', '');

/* Cloudflare Turnstile — açarlar paneldə (Безопасность). Boşdursa kapça sönülüdür. */
define('TURNSTILE_SITE', '');
define('TURNSTILE_SECRET', '');

require __DIR__ . '/defaults.php';

/* Bazadakı dəyərlər yuxarıdakıların üzərinə yazılır */
require __DIR__ . '/content.php';
