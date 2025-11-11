<?php
// Lightweight endpoint to reset opcode cache for mod_php/fastcgi during development.
// WARNING: remove this file from production.
if (session_status() === PHP_SESSION_NONE) session_start();
$ok = false;
if (function_exists('opcache_reset')) {
    $ok = opcache_reset();
}
header('Content-Type: application/json');
echo json_encode(['reset' => $ok]);
