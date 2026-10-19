<?php
/*
 * Tətbiqin girişi: sabitlər, köməkçilər və məzmun.
 *
 * public/_init.php ROOT və PUBLIC_DIR-i təyin edib bu faylı yükləyir.
 * Məzmun: app/defaults.php (ilkin) → app/content.php (bazadan, varsa).
 * Baza yoxdursa və ya sıradan çıxıbsa sayt ilkin məzmunla işləməyə davam edir.
 */
declare(strict_types=1);

if (!defined('ROOT'))       define('ROOT', dirname(__DIR__));
if (!defined('PUBLIC_DIR')) define('PUBLIC_DIR', ROOT . '/public');

define('APP_DIR',     ROOT . '/app');
define('TPL_DIR',     ROOT . '/templates');
define('STORAGE_DIR', ROOT . '/storage');
define('CONFIG_FILE', ROOT . '/config/config.php');

/* Sayt alt qovluqdadırsa: define('BASE_URL', '/qovluq'); */
if (!defined('BASE_URL')) define('BASE_URL', '');
define('SITE_URL', 'https://xaliqrzayev.com');

/* Cloudflare Turnstile — açarlar paneldə (Безопасность). Boşdursa kapça sönülüdür. */
define('TURNSTILE_SITE', '');
define('TURNSTILE_SECRET', '');

require_once APP_DIR . '/compat.php';
require_once APP_DIR . '/security.php';
security_error_setup();
require_once APP_DIR . '/functions.php';

require APP_DIR . '/defaults.php';

/* Bazadakı dəyərlər yuxarıdakıların üzərinə yazılır */
require APP_DIR . '/content.php';
