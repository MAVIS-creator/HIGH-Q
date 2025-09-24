<?php
// admin/includes/csrf.php

use Symfony\Component\Security\Csrf\CsrfTokenManager;
use Symfony\Component\Security\Csrf\CsrfToken;

require_once __DIR__ . '/../../vendor/autoload.php';

// Start session only if not started yet
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$csrfTokenManager = new CsrfTokenManager();

/**
 * Generate a CSRF token for a given form ID
 */
function generateToken(string $id = 'default_form'): string {
    global $csrfTokenManager;
    return $csrfTokenManager->getToken($id)->getValue();
}

/**
 * Verify a submitted CSRF token
 */
function verifyToken(string $id, string $token): bool {
    global $csrfTokenManager;
    return $csrfTokenManager->isTokenValid(new CsrfToken($id, $token));
}

/**
 * Backwards-compatible wrapper used by some older pages.
 * Accepts just the token and uses the default form id.
 */
function verifyCsrfToken(string $token, string $id = 'default_form'): bool {
    return verifyToken($id, $token);
}
