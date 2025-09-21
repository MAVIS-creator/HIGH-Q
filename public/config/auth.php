<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function requirePermission($menuSlug) {
    // Accept string or array
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

    if (is_array($menuSlug)) {
        $placeholders = implode(',', array_fill(0, count($menuSlug), '?'));
        $params = array_merge([$roleId], $menuSlug);
        $sql = "SELECT 1 FROM role_permissions WHERE role_id = ? AND menu_slug IN ($placeholders) LIMIT 1";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        if (!$stmt->fetch()) die("Access denied: insufficient permission.");
    } else {
        $stmt = $pdo->prepare("SELECT 1 FROM role_permissions WHERE role_id=? AND menu_slug=?");
        $stmt->execute([$roleId, $menuSlug]);
        if (!$stmt->fetch()) die("Access denied: insufficient permission.");
    }
}
