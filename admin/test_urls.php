<?php
// Quick debug script to see what URLs are being generated
require_once 'includes/auth.php';
require_once 'includes/functions.php';

header('Content-Type: text/plain');

echo "=== URL DIAGNOSTICS ===\n\n";
echo "app_url(): " . app_url() . "\n";
echo "admin_url(): " . admin_url() . "\n";
echo "admin_url('api/notifications.php'): " . admin_url('api/notifications.php') . "\n\n";

echo "=== SERVER VARS ===\n";
echo "HTTP_HOST: " . ($_SERVER['HTTP_HOST'] ?? 'NOT SET') . "\n";
echo "SCRIPT_NAME: " . ($_SERVER['SCRIPT_NAME'] ?? 'NOT SET') . "\n";
echo "REQUEST_URI: " . ($_SERVER['REQUEST_URI'] ?? 'NOT SET') . "\n";
echo "HTTPS: " . ($_SERVER['HTTPS'] ?? 'NOT SET') . "\n\n";

echo "=== ENV VARS ===\n";
echo "APP_URL: " . ($_ENV['APP_URL'] ?? 'NOT SET') . "\n";
echo "ADMIN_URL: " . ($_ENV['ADMIN_URL'] ?? 'NOT SET') . "\n";
