<?php
// admin/includes/csrf.php

use Symfony\Component\Security\Csrf\CsrfTokenManager;
use Symfony\Component\Security\Csrf\CsrfToken;

require_once __DIR__ . '/../../vendor/autoload.php';

// Start session only if not started yet
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Initialize token manager once (idempotent) and store on $GLOBALS to avoid duplicate instantiation
if (empty($GLOBALS['csrfTokenManager'])) {
    $GLOBALS['csrfTokenManager'] = new CsrfTokenManager();
}
$csrfTokenManager = $GLOBALS['csrfTokenManager'];

/**
 * Generate a CSRF token for a given form ID
 */
if (!function_exists('generateToken')) {
    function generateToken(string $id = 'default_form'): string {
        global $csrfTokenManager;
        return $csrfTokenManager->getToken($id)->getValue();
    }
}

/**
 * Verify a submitted CSRF token
 */
if (!function_exists('verifyToken')) {
    function verifyToken(string $id, string $token): bool {
        global $csrfTokenManager;
        return $csrfTokenManager->isTokenValid(new CsrfToken($id, $token));
    }
}

/**
 * Backwards-compatible wrapper used by some older pages.
 * Accepts just the token and uses the default form id.
 */
if (!function_exists('verifyCsrfToken')) {
    function verifyCsrfToken(string $token, string $id = 'default_form'): bool {
        return verifyToken($id, $token);
    }
}
