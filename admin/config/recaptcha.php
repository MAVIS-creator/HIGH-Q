<?php
// Admin-side reCAPTCHA config wrapper. Allows admin to use separate env vars.
// Look for ADMIN_RECAPTCHA_SITE_KEY and ADMIN_RECAPTCHA_SECRET first.
$site = getenv('ADMIN_RECAPTCHA_SITE_KEY');
$secret = getenv('ADMIN_RECAPTCHA_SECRET');
if ($site || $secret) {
    return ['site_key' => $site ?: '', 'secret' => $secret ?: ''];
}

// Fallback to project config
if (file_exists(__DIR__ . '/../../config/recaptcha.php')) {
    return require __DIR__ . '/../../config/recaptcha.php';
}

return ['site_key'=>'','secret'=>''];
