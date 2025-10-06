<?php
// Public wrapper to project config (so public pages don't need to reach into project root)
if (file_exists(__DIR__ . '/../../config/recaptcha.php')) {
    return require __DIR__ . '/../../config/recaptcha.php';
}

// Fallback: empty values (safe default)
return [
    'site_key' => '',
    'secret' => ''
];
