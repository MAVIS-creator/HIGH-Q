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

/**
 * Ensure the visitor is authenticated. If not, redirect to signup (if no users exist)
 * or to the login page. Call this at the top of pages that require a logged-in user.
 */
function ensureAuthenticated(): void {
    global $pdo;

    $userId = $_SESSION['user']['id'] ?? null;
    if ($userId) return; // already logged in

    // Determine if any users exist in the DB. If none, redirect to signup for first admin creation.
    try {
        $stmt = $pdo->query('SELECT COUNT(*) FROM users');
        $total = (int)$stmt->fetchColumn();
    } catch (Exception $e) {
        // On DB error, fallback to sending to login page
        $total = 1;
    }

    if ($total === 0) {
        header('Location: ../signup.php');
        exit;
    }

    header('Location: ../login.php');
    exit;
}

// -- Simple flash helpers --
function setFlash(string $type, string $message): void {
    if (session_status() === PHP_SESSION_NONE) session_start();
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function getFlash(): ?array {
    if (session_status() === PHP_SESSION_NONE) session_start();
    $f = $_SESSION['flash'] ?? null;
    if ($f) unset($_SESSION['flash']);
    return $f;
}
