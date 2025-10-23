<?php
// Simple in-process test harness for public/api/comments.php (GET)
chdir(__DIR__ . '/..');
ini_set('display_errors',1);
error_reporting(E_ALL);

// simulate GET
$_SERVER['REQUEST_METHOD'] = 'GET';
$_GET['post_id'] = $argv[1] ?? 1;

// ensure session available
if (session_status() !== PHP_SESSION_ACTIVE) session_start();

// include the API file which prints JSON and exits
include __DIR__ . '/../public/api/comments.php';

// shouldn't reach here normally
echo "\n--- done ---\n";
