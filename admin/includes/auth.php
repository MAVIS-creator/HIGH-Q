<?php
function hqAdminBasePathFromRequest(): string {
    $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
    $scriptFilename = $_SERVER['SCRIPT_FILENAME'] ?? '';
    $adminRoot = realpath(__DIR__ . '/..') ?: '';

    if (is_string($scriptName) && trim($scriptName) !== '' && is_string($scriptFilename) && trim($scriptFilename) !== '' && $adminRoot !== '') {
        $scriptName = str_replace('\\', '/', $scriptName);
        $scriptFilename = str_replace('\\', '/', realpath($scriptFilename) ?: $scriptFilename);
        $adminRoot = str_replace('\\', '/', $adminRoot);

        if (strpos(strtolower($scriptFilename), strtolower(rtrim($adminRoot, '/'))) === 0) {
            $relativeScript = str_replace('\\', '/', substr($scriptFilename, strlen($adminRoot)));
            $relativeScript = '/' . ltrim($relativeScript, '/');

            if ($relativeScript !== '/' && str_ends_with(strtolower($scriptName), strtolower($relativeScript))) {
                $basePath = substr($scriptName, 0, strlen($scriptName) - strlen($relativeScript));
                $basePath = rtrim(str_replace('\\', '/', $basePath), '/');
                return ($basePath === '/' || $basePath === '.') ? '' : $basePath;
            }
        }
    }

    $dir = rtrim(str_replace('\\', '/', dirname((string)$scriptName)), '/');
    if ($dir === '/' || $dir === '.') {
        return '';
    }

    return $dir;
}

function hqAdminClientIp(): string {
    $candidates = [
        $_SERVER['HTTP_CF_CONNECTING_IP'] ?? null,
        $_SERVER['HTTP_X_FORWARDED_FOR'] ?? null,
        $_SERVER['HTTP_X_REAL_IP'] ?? null,
        $_SERVER['REMOTE_ADDR'] ?? null,
    ];

    foreach ($candidates as $candidate) {
        if (!is_string($candidate) || trim($candidate) === '') {
            continue;
        }
        foreach (explode(',', $candidate) as $part) {
            $ip = trim($part);
            if ($ip !== '' && filter_var($ip, FILTER_VALIDATE_IP)) {
                return $ip;
            }
        }
    }

    return '';
}

// Secure session configuration
if (session_status() === PHP_SESSION_NONE) {
    // 1. Prevent Javascript from accessing cookies (Stops XSS stealing)
    ini_set('session.cookie_httponly', 1);
    
    // 2. Only send cookie over HTTPS in production (Stops network sniffing)
    // Enable this in production: ini_set('session.cookie_secure', 1);
    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
        ini_set('session.cookie_secure', 1);
    }
    
    // 3. Strict Mode (Prevents using session ID from a link)
    ini_set('session.use_strict_mode', 1);
    
    // 4. SameSite cookie protection
    ini_set('session.cookie_samesite', 'Lax');
    
    session_start();
    
    // 5. IP Binding (Security lock - kills session if IP changes)
    $clientIp = hqAdminClientIp();
    if (isset($_SESSION['user_ip']) && $clientIp !== '' && $_SESSION['user_ip'] !== $clientIp) {
        session_unset();
        session_destroy();
        header('Location: ../login.php?error=session_invalid');
        exit('Session invalid (IP Change Detected). Please log in again.');
    }
    
    // Set the IP when they first log in
    if (!empty($_SESSION['user']) && !isset($_SESSION['user_ip']) && $clientIp !== '') {
        $_SESSION['user_ip'] = $clientIp;
    }
}

function requirePermission($menuSlug) {
    // Accept either string or array of allowed menu slugs
    global $pdo;

    // Detect AJAX/JSON requests so we can emit JSON instead of HTML redirects
    $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH'])
        || (isset($_GET['ajax']) || isset($_POST['ajax']))
        || (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false));

    $userId = $_SESSION['user']['id'] ?? null;
    if (!$userId) {
        // Compute admin base path for safe redirects
        $adminBasePath = hqAdminBasePathFromRequest();

        // If there are no users in the system yet, redirect to signup for initial setup.
        try {
            $stmt = $pdo->query('SELECT COUNT(*) FROM users');
            $total = (int)$stmt->fetchColumn();
        } catch (Exception $e) {
            // If DB not available, fall back to login
            $total = 1;
        }

        if ($isAjax) {
            header('Content-Type: application/json');
            http_response_code(401);
            echo json_encode(['status' => 'error', 'message' => 'Unauthenticated']);
            exit;
        } else {
            if ($total === 0) {
                header("Location: " . $adminBasePath . "/signup.php");
                exit;
            }
            header("Location: " . $adminBasePath . "/login.php");
            exit;
        }
    }

    // Fetch role_id
    $stmt = $pdo->prepare("SELECT role_id FROM users WHERE id=?");
    $stmt->execute([$userId]);
    $roleId = $stmt->fetchColumn();

    if (!$roleId) {
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
            if ($isAjax) {
                header('Content-Type: application/json');
                http_response_code(403);
                echo json_encode(['status' => 'error', 'message' => 'Access denied: insufficient permission.']);
                exit;
            }
            die("Access denied: insufficient permission.");
        }
    } else {
        $stmt = $pdo->prepare("SELECT 1 FROM role_permissions WHERE role_id=? AND menu_slug=?");
        $stmt->execute([$roleId, $menuSlug]);
        if (!$stmt->fetch()) {
            if ($isAjax) {
                header('Content-Type: application/json');
                http_response_code(403);
                echo json_encode(['status' => 'error', 'message' => 'Access denied: insufficient permission.']);
                exit;
            }
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

    $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH'])
        || (isset($_GET['ajax']) || isset($_POST['ajax']))
        || (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false));

    // Determine if any users exist in the DB. If none, redirect to signup for first admin creation.
    try {
        $stmt = $pdo->query('SELECT COUNT(*) FROM users');
        $total = (int)$stmt->fetchColumn();
    } catch (Exception $e) {
        // On DB error, fallback to sending to login page
        $total = 1;
    }

    if ($isAjax) {
        header('Content-Type: application/json');
        http_response_code(401);
        echo json_encode(['status' => 'error', 'message' => 'Unauthenticated']);
        exit;
    } else {
        $adminBasePath = hqAdminBasePathFromRequest();

        if ($total === 0) {
            header('Location: ' . $adminBasePath . '/signup.php');
            exit;
        }
        header('Location: ' . $adminBasePath . '/login.php');
        exit;
    }
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
