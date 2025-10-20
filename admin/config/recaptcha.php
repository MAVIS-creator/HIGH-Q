<?php
// Admin-side reCAPTCHA config wrapper. Allows admin to use separate env vars.
// Look for ADMIN_RECAPTCHA_SITE_KEY and ADMIN_RECAPTCHA_SECRET first.
// Always load admin/.env first
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
}
if (file_exists(__DIR__ . '/../.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
    $dotenv->safeLoad();
}
$site = $_ENV['ADMIN_RECAPTCHA_SITE_KEY'] ?? getenv('ADMIN_RECAPTCHA_SITE_KEY');
$secret = $_ENV['ADMIN_RECAPTCHA_SECRET'] ?? getenv('ADMIN_RECAPTCHA_SECRET');
$enabled = isset($_ENV['ADMIN_RECAPTCHA_ENABLED']) ? (strtolower($_ENV['ADMIN_RECAPTCHA_ENABLED']) === '1' || strtolower($_ENV['ADMIN_RECAPTCHA_ENABLED']) === 'true') : (getenv('ADMIN_RECAPTCHA_ENABLED') ? (strtolower(getenv('ADMIN_RECAPTCHA_ENABLED')) === '1' || strtolower(getenv('ADMIN_RECAPTCHA_ENABLED')) === 'true') : false);
if ($site || $secret) {
    return ['site_key' => $site ?: '', 'secret' => $secret ?: '', 'enabled' => $enabled];
}

// Fallback to project config
if (file_exists(__DIR__ . '/../../config/recaptcha.php')) {
    return require __DIR__ . '/../../config/recaptcha.php';
}

return ['site_key'=>'','secret'=>'','enabled'=>false];
