<?php
/*
 * Ümumi təhlükəsizlik: real IP, HTTP başlıqları (CSP, HSTS…), xətaların gizlədilməsi.
 * app/bootstrap.php tərəfindən hər sorğuda yüklənir.
 */
declare(strict_types=1);

/* ---------------- Xətalar istifadəçiyə göstərilmir, storage/php-errors.log-a yazılır ---------------- */
function security_error_setup(): void {
    $local = in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1'], true) && PHP_SAPI === 'cli-server';
    ini_set('display_errors', $local ? '1' : '0');
    ini_set('log_errors', '1');
    if (defined('STORAGE_DIR') && is_dir(STORAGE_DIR) && is_writable(STORAGE_DIR)) {
        ini_set('error_log', STORAGE_DIR . '/php-errors.log');
    }
    /* PHP versiyası başlıqda göstərilməsin */
    if (!headers_sent()) header_remove('X-Powered-By');
}

/* ---------------- Real IP ----------------
   CF-Connecting-IP başlığına yalnız sorğu həqiqətən Cloudflare-dən gəlibsə inanırıq.
   Əks halda kimsə serverə birbaşa müraciət edib istənilən IP «göstərə» və limitləri keçə bilərdi.
   Mənbə: https://www.cloudflare.com/ips/ */
const CLOUDFLARE_RANGES = [
    '173.245.48.0/20', '103.21.244.0/22', '103.22.200.0/22', '103.31.4.0/22', '141.101.64.0/18',
    '108.162.192.0/18', '190.93.240.0/20', '188.114.96.0/20', '197.234.240.0/22', '198.41.128.0/17',
    '162.158.0.0/15', '104.16.0.0/13', '104.24.0.0/14', '172.64.0.0/13', '131.0.72.0/22',
    '2400:cb00::/32', '2606:4700::/32', '2803:f800::/32', '2405:b500::/32', '2405:8100::/32',
    '2a06:98c0::/29', '2c0f:f248::/32',
];

function ip_in_cidr(string $ip, string $cidr): bool {
    [$net, $bits] = explode('/', $cidr) + [1 => ''];
    $ipb = @inet_pton($ip); $netb = @inet_pton($net);
    if ($ipb === false || $netb === false || strlen($ipb) !== strlen($netb)) return false;
    $bits = (int)$bits;
    $bytes = intdiv($bits, 8); $rem = $bits % 8;
    if (strncmp($ipb, $netb, $bytes) !== 0) return false;
    if ($rem === 0) return true;
    $mask = chr((0xFF << (8 - $rem)) & 0xFF);
    return (($ipb[$bytes] & $mask) === ($netb[$bytes] & $mask));
}

function from_cloudflare(string $ip): bool {
    foreach (CLOUDFLARE_RANGES as $c) if (ip_in_cidr($ip, $c)) return true;
    return false;
}

function client_ip(): string {
    static $ip = null;
    if ($ip !== null) return $ip;
    $remote = (string)($_SERVER['REMOTE_ADDR'] ?? '');
    $cf     = (string)($_SERVER['HTTP_CF_CONNECTING_IP'] ?? '');
    if ($cf !== '' && filter_var($cf, FILTER_VALIDATE_IP) && from_cloudflare($remote)) return $ip = $cf;
    return $ip = filter_var($remote, FILTER_VALIDATE_IP) ? $remote : '';
}

/* ---------------- HTTP başlıqları ---------------- */
function csp_nonce(): string {
    static $n = null;
    if ($n === null) $n = rtrim(strtr(base64_encode(random_bytes(16)), '+/', '-_'), '=');
    return $n;
}

function is_https_request(): bool {
    $h = $_SERVER['HTTPS'] ?? '';
    return ($h !== '' && strtolower((string)$h) !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https')
        || (int)($_SERVER['SERVER_PORT'] ?? 0) === 443;
}

/**
 * $mode: 'site' — sayt (iframe-də yalnız öz domenimiz, Google xəritə və Turnstile icazəli);
 *        'admin' — panel (heç bir iframe-də açılmır, keşlənmir).
 */
function send_security_headers(string $mode = 'site'): void {
    if (headers_sent()) return;
    $n = csp_nonce();
    $csp = [
        "default-src 'self'",
        "script-src 'self' 'nonce-$n' https://challenges.cloudflare.com https://www.googletagmanager.com",
        "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com",
        "font-src 'self' https://fonts.gstatic.com",
        "img-src 'self' data: https://www.googletagmanager.com https://www.google-analytics.com",
        "connect-src 'self' https://*.google-analytics.com https://*.analytics.google.com https://www.googletagmanager.com",
        "frame-src https://challenges.cloudflare.com" . ($mode === 'site' ? ' https://www.google.com https://maps.google.com' : ''),
        "form-action 'self'" . ($mode === 'site' ? ' https://wa.me https://api.whatsapp.com' : ''),
        "base-uri 'self'",
        "object-src 'none'",
        "frame-ancestors " . ($mode === 'admin' ? "'none'" : "'self'"),
    ];
    header('Content-Security-Policy: ' . implode('; ', $csp));
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: ' . ($mode === 'admin' ? 'DENY' : 'SAMEORIGIN'));
    header('Referrer-Policy: ' . ($mode === 'admin' ? 'same-origin' : 'strict-origin-when-cross-origin'));
    header('Permissions-Policy: camera=(), microphone=(), geolocation=(), payment=()');
    header('Cross-Origin-Opener-Policy: same-origin');
    if (is_https_request()) header('Strict-Transport-Security: max-age=31536000');
    if ($mode === 'admin') {
        header('Cache-Control: no-store, max-age=0');
        header('X-Robots-Tag: noindex, nofollow');
    }
}
