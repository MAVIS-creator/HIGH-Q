<?php
// Project-level reCAPTCHA configuration.
// Prefer environment variables so secrets are not stored in code.
return [
    'site_key' => getenv('RECAPTCHA_SITE_KEY') ?: '',
    'secret' => getenv('RECAPTCHA_SECRET') ?: ''
];
