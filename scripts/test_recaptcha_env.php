<?php
// Test reCAPTCHA env loading for admin and public
// Usage: php scripts/test_recaptcha_env.php

$adminCfg = file_exists(__DIR__ . '/../admin/config/recaptcha.php') ? require __DIR__ . '/../admin/config/recaptcha.php' : null;
$publicCfg = file_exists(__DIR__ . '/../public/config/recaptcha.php') ? require __DIR__ . '/../public/config/recaptcha.php' : null;

echo "Admin reCAPTCHA config:\n";
var_export($adminCfg);
echo "\n\nPublic reCAPTCHA config:\n";
var_export($publicCfg);
echo "\n";
