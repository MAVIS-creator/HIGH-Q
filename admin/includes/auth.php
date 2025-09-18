<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function requirePermission(string $menuSlug) {
    global $pdo;

    $userId = $_SESSION['user']['id'] ?? null;
    if (!$userId) {
        // If there are no users in the system yet, redirect to signup for initial setup.
        try {
            $stmt = $pdo->query('SELECT COUNT(*) FROM users');
            $total = (int)$stmt->fetchColumn();
        } catch (Exception $e) {
            // If DB not available, fall back to login
            $total = 1;
        }

        if ($total === 0) {
            header("Location: ../signup.php");
            exit;
        }

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
