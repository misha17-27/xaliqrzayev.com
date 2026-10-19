<?php
/*
 * Bir dəfəlik quraşdırma: cədvəlləri yaradır, saytın məzmununu bazaya köçürür
 * və ilk admin hesabını açır.
 *
 *   /admin/install.php?token=<install_token>
 *
 * Təkrar işə salmaq təhlükəsizdir — doldurulmuş cədvəllərə toxunmur.
 * Quraşdırma bitəndən sonra bu faylı serverdən silmək tövsiyə olunur.
 */
declare(strict_types=1);
require dirname(__DIR__) . '/_init.php';
require APP_DIR . '/admin/lib.php';
send_security_headers('admin');
header('Content-Type: text/plain; charset=utf-8');

$token = (string)cfg('install_token', '');
if ($token === '' || $token === 'CHANGE_ME' || !hash_equals($token, (string)($_GET['token'] ?? ''))) {
    http_response_code(403);
    exit("Forbidden.\nconfig/config.php-də install_token təyin edin və ?token=... əlavə edin.");
}

$db = pdo();
echo 'Driver: ' . $db->getAttribute(PDO::ATTR_DRIVER_NAME) . "\n\n";

echo "1) Cədvəllər yaradılır...\n";
foreach (cms_create_tables($db) as $t) echo "   + $t\n";

echo "\n2) Saytın məzmunu köçürülür...\n";
foreach (cms_seed($db) as $line) echo "   + $line\n";

echo "\n3) İlk admin hesabı...\n";
$email = (string)cfg('install_admin_email', 'admin@xaliqrzayev.com');
$pass  = (string)cfg('install_admin_pass', '');
$st = $db->prepare('SELECT id FROM admins WHERE email = ?');
$st->execute([$email]);
if ($st->fetch()) {
    echo "   · $email artıq mövcuddur — toxunulmadı\n";
} elseif (strlen($pass) < 8 || $pass === 'CHANGE_ME') {
    echo "   ! install_admin_pass ən azı 8 simvol olmalıdır — hesab yaradılmadı\n";
} else {
    $db->prepare('INSERT INTO admins (name,email,pass_hash,role,active) VALUES (?,?,?,?,1)')
       ->execute(['Administrator', $email, password_hash($pass, PASSWORD_DEFAULT), 'admin']);
    echo "   + $email yaradıldı (parol config/config.php-dədir)\n";
}

echo "\nHAZIRDIR.\nPanelə giriş:  /admin/\nTəhlükəsizlik üçün install.php-ni serverdən silin.\n";
