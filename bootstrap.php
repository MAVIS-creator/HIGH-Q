<?php
// Project bootstrap: load Composer autoload, Dotenv and basic app settings

// Load Composer autoload if available
$autoload = __DIR__ . '/vendor/autoload.php';
if (file_exists($autoload)) {
    require_once $autoload;
}

// Load .env from project root if present
if (class_exists('\Dotenv\Dotenv')) {
    try {
        \Dotenv\Dotenv::createImmutable(__DIR__)->safeLoad();
    } catch (Throwable $e) {
        // ignore - some environments may not have a .env file
    }
}

// Configure error reporting based on APP_DEBUG
$debug = $_ENV['APP_DEBUG'] ?? getenv('APP_DEBUG');
if ($debug === null) $debug = 'false';
if (in_array(strtolower((string)$debug), ['1', 'true', 'on'])) {
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
}

// Common helpers
if (!function_exists('app_env')) {
    function app_env(string $key, $default = null) {
        return $_ENV[$key] ?? getenv($key) ?? $default;
    }
}

if (!function_exists('h')) {
    function h($s) { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }
}
