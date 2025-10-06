<?php
// CLI test for admin reCAPTCHA env loading
require_once __DIR__ . '/../vendor/autoload.php';
if (file_exists(__DIR__ . '/../.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
    $dotenv->safeLoad();
}
$site = $_ENV['ADMIN_RECAPTCHA_SITE_KEY'] ?? getenv('ADMIN_RECAPTCHA_SITE_KEY');
$secret = $_ENV['ADMIN_RECAPTCHA_SECRET'] ?? getenv('ADMIN_RECAPTCHA_SECRET');
echo "ADMIN_RECAPTCHA_SITE_KEY: " . ($site ?: '[not set]') . "\n";
echo "ADMIN_RECAPTCHA_SECRET: " . ($secret ?: '[not set]') . "\n";
