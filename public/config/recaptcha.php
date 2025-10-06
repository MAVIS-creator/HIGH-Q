<?php
// Public wrapper to provide site-specific reCAPTCHA keys.
// Prefer PUBLIC_RECAPTCHA_SITE_KEY and PUBLIC_RECAPTCHA_SECRET environment variables.
// If vlucas/phpdotenv is available and a .env exists in project root, it may already be loaded by other bootstrap code.
$site = getenv('PUBLIC_RECAPTCHA_SITE_KEY');
$secret = getenv('PUBLIC_RECAPTCHA_SECRET');
if ($site || $secret) {
    return ['site_key' => $site ?: '', 'secret' => $secret ?: ''];
}

// fallback to project config
if (file_exists(__DIR__ . '/../../config/recaptcha.php')) {
    return require __DIR__ . '/../../config/recaptcha.php';
}

return ['site_key'=>'','secret'=>''];
