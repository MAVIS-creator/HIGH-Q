<?php
// admin/bootstrap.php
// Central bootstrap for admin scripts. Loads DB, helpers and auth in a safe, idempotent way.
if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}

if (!defined('HQ_ADMIN_BOOTSTRAPPED')) {
    define('HQ_ADMIN_BOOTSTRAPPED', true);

    // Load Composer autoload if available
    $vendor = __DIR__ . '/../vendor/autoload.php';
    if (file_exists($vendor)) {
        require_once $vendor;
    }

    // Load DB connection, functions and auth (use includes paths)
    $db = __DIR__ . '/includes/db.php';
    if (file_exists($db)) {
        require_once $db;
    }

    $fn = __DIR__ . '/includes/functions.php';
    if (file_exists($fn)) {
        require_once $fn;
    }

    $auth = __DIR__ . '/includes/auth.php';
    if (file_exists($auth)) {
        require_once $auth;
    }

    // Safe defaults: ensure $pdo exists to avoid notices in scripts that assume DB
    if (!isset($pdo)) {
        $pdo = null;
    }
}
