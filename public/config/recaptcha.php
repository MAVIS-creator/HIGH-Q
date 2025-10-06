<?php
// Public wrapper to provide site-specific reCAPTCHA keys.
// Prefer PUBLIC_RECAPTCHA_SITE_KEY and PUBLIC_RECAPTCHA_SECRET environment variables.
// If vlucas/phpdotenv is available and a .env exists in project root, it may already be loaded by other bootstrap code.
// Always load root .env first, then public/.env if present
if (file_exists(__DIR__ . '/../.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
    $dotenv->safeLoad();
}
if (file_exists(__DIR__ . '/.env')) {
    $dotenv2 = Dotenv\Dotenv::createImmutable(__DIR__);
    $dotenv2->safeLoad();
}
$site = $_ENV['PUBLIC_RECAPTCHA_SITE_KEY'] ?? getenv('PUBLIC_RECAPTCHA_SITE_KEY');
$secret = $_ENV['PUBLIC_RECAPTCHA_SECRET'] ?? getenv('PUBLIC_RECAPTCHA_SECRET');
if ($site || $secret) {
    return ['site_key' => $site ?: '', 'secret' => $secret ?: ''];
}

// fallback to project config
if (file_exists(__DIR__ . '/../../config/recaptcha.php')) {
    return require __DIR__ . '/../../config/recaptcha.php';
}

return ['site_key'=>'','secret'=>''];
