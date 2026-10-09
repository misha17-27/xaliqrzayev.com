<?php
/* PHP 7.4 üçün PHP 8 funksiyaları. PHP 8-də heç nə etmir. */
if (!function_exists('str_starts_with')) {
    function str_starts_with(string $h, string $n): bool { return $n === '' || strncmp($h, $n, strlen($n)) === 0; }
}
if (!function_exists('str_ends_with')) {
    function str_ends_with(string $h, string $n): bool { return $n === '' || substr($h, -strlen($n)) === $n; }
}
if (!function_exists('str_contains')) {
    function str_contains(string $h, string $n): bool { return $n === '' || strpos($h, $n) !== false; }
}
if (!ini_get('date.timezone') || date_default_timezone_get() === 'UTC') date_default_timezone_set('Asia/Baku');
