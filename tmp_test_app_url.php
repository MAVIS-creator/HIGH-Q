<?php
// Test app_url function
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Simulate being called from public page
$_SERVER['HTTP_HOST'] = '127.0.0.1';
$_SERVER['REQUEST_URI'] = '/HIGH-Q/public/home.php';
$_SERVER['SCRIPT_NAME'] = '/HIGH-Q/public/home.php';
$_SERVER['HTTPS'] = 'off';

require_once 'public/config/functions.php';

echo "=== Testing app_url() function ===\n\n";

echo "Environment variables:\n";
echo "APP_URL from \$_ENV: " . ($_ENV['APP_URL'] ?? 'NOT SET') . "\n";
echo "APP_URL from getenv: " . (getenv('APP_URL') ?: 'NOT SET') . "\n";
echo "\n";

echo "Server variables:\n";
echo "HTTP_HOST: " . $_SERVER['HTTP_HOST'] . "\n";
echo "REQUEST_URI: " . $_SERVER['REQUEST_URI'] . "\n";
echo "SCRIPT_NAME: " . $_SERVER['SCRIPT_NAME'] . "\n";
echo "\n";

echo "Testing app_url() output:\n";
echo "app_url(''): " . app_url('') . "\n";
echo "app_url('assets/css/public.css'): " . app_url('assets/css/public.css') . "\n";
echo "app_url('assets/images/logo.png'): " . app_url('assets/images/logo.png') . "\n";
