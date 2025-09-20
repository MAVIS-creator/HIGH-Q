<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function requirePermission(string $menuSlug) {
    global $pdo;

    $userId = $_SESSION['user']['id'] ?? null;
    if (!$userId) {
        header("Location: ../login.php");
        exit;
    }

    // Fetch role_id
    $stmt = $pdo->prepare("SELECT role_id FROM users WHERE id=?");
    $stmt->execute([$userId]);
    $roleId = $stmt->fetchColumn();

    if (!$roleId) {
        die("Access denied: no role assigned.");
    }

    // Check permission
    $stmt = $pdo->prepare("SELECT 1 FROM role_permissions WHERE role_id=? AND menu_slug=?");
    $stmt->execute([$roleId, $menuSlug]);
    if (!$stmt->fetch()) {
        die("Access denied: insufficient permission.");
    }
}
