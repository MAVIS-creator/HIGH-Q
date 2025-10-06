<?php
// admin/cli/get_csrf.php - outputs the generated CSRF token used by admin pages
chdir(__DIR__ . '/..');
require_once __DIR__ . '/../includes/csrf.php';
if (session_status() === PHP_SESSION_NONE) session_start();
$token = generateToken();
echo $token . PHP_EOL;
