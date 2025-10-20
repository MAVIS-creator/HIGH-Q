<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function requirePermission($menuSlug) {
    // Accept either string or array of allowed menu slugs
    global $pdo;

    // Helper to detect AJAX/XHR requests
    $isAjax = function() {
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') return true;
        $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
        if (strpos($accept, 'application/json') !== false) return true;
        return false;
    };

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
            if ($isAjax()) {
                header('Content-Type: application/json'); http_response_code(200);
                echo json_encode(['message' => 'No users exist, redirect to signup']);
                exit;
            }
            header("Location: ../signup.php");
            exit;
        }

        if ($isAjax()) {
            header('Content-Type: application/json'); http_response_code(401);
            echo json_encode(['error' => 'Unauthenticated']);
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
        if ($isAjax()) { header('Content-Type: application/json'); http_response_code(403); echo json_encode(['error'=>'Access denied: no role assigned']); exit; }
        die("Access denied: no role assigned.");
    }

    // Check permission - allow $menuSlug to be a string or an array of allowed slugs
    if (is_array($menuSlug)) {
        // build placeholders
        $placeholders = implode(',', array_fill(0, count($menuSlug), '?'));
        $params = array_merge([$roleId], $menuSlug);
        $sql = "SELECT 1 FROM role_permissions WHERE role_id = ? AND menu_slug IN ($placeholders) LIMIT 1";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        if (!$stmt->fetch()) {
            if ($isAjax()) { header('Content-Type: application/json'); http_response_code(403); echo json_encode(['error'=>'Access denied: insufficient permission']); exit; }
            die("Access denied: insufficient permission.");
        }
    } else {
        $stmt = $pdo->prepare("SELECT 1 FROM role_permissions WHERE role_id=? AND menu_slug=?");
        $stmt->execute([$roleId, $menuSlug]);
        if (!$stmt->fetch()) {
            if ($isAjax()) { header('Content-Type: application/json'); http_response_code(403); echo json_encode(['error'=>'Access denied: insufficient permission']); exit; }
            die("Access denied: insufficient permission.");
        }
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
        if ($isAjax()) { header('Content-Type: application/json'); http_response_code(200); echo json_encode(['message'=>'no-users']); exit; }
        header('Location: ../signup.php');
        exit;
    }
    if ($isAjax()) { header('Content-Type: application/json'); http_response_code(401); echo json_encode(['error'=>'Unauthenticated']); exit; }
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
