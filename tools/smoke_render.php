<?php
// Quick smoke renderer for public/tutors.php
chdir(__DIR__ . '/../');
// Ensure $appBase is available (mirrors runtime logic used in public templates)
$appBase = rtrim($_ENV['APP_URL'] ?? '', '/');
if ($appBase === '') {
    $proto = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $appBase = rtrim($proto . '://' . $host, '/');
}
ob_start();
include __DIR__ . '/../public/tutors.php';
$out = ob_get_clean();
if (strlen($out) > 0) {
    echo "RENDERED";
    exit(0);
}
echo "EMPTY";
exit(2);
