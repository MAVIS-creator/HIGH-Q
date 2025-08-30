<?php
// admin/includes/auth.php
session_start();

function isLoggedIn() {
    return isset($_SESSION['user']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: /admin/login.php");
        exit;
    }
}

function userRole() {
    return $_SESSION['user']['role_slug'] ?? null;
}

// Restrict by role
function requireRole($roles = []) {
    if (!isLoggedIn() || !in_array(userRole(), $roles)) {
        header("Location: /admin/pages/index.php?error=unauthorized");
        exit;
    }
}
