<?php
/*
 * Bu faylı  config.php  adı ilə kopyalayın və məlumatlarınızı yazın.
 * config.php git-ə düşmür — parollar repozitoriyada saxlanılmır.
 *
 * driver: 'sqlite'  — heç bir quraşdırma tələb etmir (lokal iş üçün ideal)
 *         'mysql'   — cPanel hostinqi üçün (MySQL Databases bölməsindən)
 */
return [
    'driver' => 'sqlite',

    // --- sqlite üçün ---
    'sqlite_path' => __DIR__ . '/../storage/admin.sqlite',

    // --- mysql üçün ---
    'db_host'    => 'localhost',
    'db_name'    => 'cpaneluser_xaliq',
    'db_user'    => 'cpaneluser_xaliq',
    'db_pass'    => 'CHANGE_ME',
    'db_charset' => 'utf8mb4',

    // install.php ilk admin hesabını bu məlumatlarla yaradır (min. 8 simvol):
    'install_admin_email' => 'admin@xaliqrzayev.com',
    'install_admin_pass'  => 'CHANGE_ME',

    // install.php-i brauzerdə işə salmaq üçün gizli açar
    'install_token' => 'CHANGE_ME',
];
