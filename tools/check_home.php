<?php
// quick check runner for home.php include
chdir(__DIR__ . '/..');
error_reporting(E_ALL);
ini_set('display_errors', 1);
$pageTitle = 'Test Home';
include __DIR__ . '/../public/includes/header.php';
include __DIR__ . '/../public/home.php';
include __DIR__ . '/../public/includes/footer.php';
echo "\n-- done --\n";
