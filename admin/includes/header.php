<?php
// admin/includes/header.php
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title><?= isset($pageTitle) ? $pageTitle : 'Admin Panel'; ?> - HIGH Q SOLID ACADEMY</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <?php
    // Build a reliable path to the admin assets directory by locating the 'admin' segment
    $script = $_SERVER['SCRIPT_NAME'] ?? '';
    $parts = explode('/', trim($script, '/'));
    $adminBase = '';
    $idx = array_search('admin', $parts, true);
    if ($idx !== false) {
        $adminBase = '/' . implode('/', array_slice($parts, 0, $idx + 1));
    } else {
        // fallback to dirname
        $adminBase = rtrim(dirname($script), '/');
        if ($adminBase === '') $adminBase = '/';
    }

    // Try several candidate hrefs and pick the first that exists on disk (DOCUMENT_ROOT)
    $candidates = [
        $adminBase . '/assets/css/admin.css',
        '/admin/assets/css/admin.css',
        dirname($script) . '/assets/css/admin.css',
        $adminBase . '/includes/../assets/css/admin.css',
        '/assets/css/admin.css'
    ];

    $chosen = null;
    $docRoot = rtrim($_SERVER['DOCUMENT_ROOT'] ?? '', '/');
    foreach ($candidates as $cand) {
        // normalize
        $candNorm = '/' . ltrim($cand, '/');
        $fs = $docRoot . $candNorm;
        if ($docRoot !== '' && is_readable($fs)) {
            $chosen = $candNorm;
            break;
        }
    }

    if ($chosen === null) {
        // fallback to adminBase path (best-effort)
        $chosen = $adminBase . '/assets/css/admin.css';
    }

    // Output only the correct admin CSS link for XAMPP
    echo "<link rel=\"stylesheet\" href=\"/HIGH-Q/admin/assets/css/admin.css\">\n";
    if (!empty($pageCss)) echo $pageCss;

    // Minimal critical inline fallback CSS (keeps UI readable if external CSS fails)
    // NOTE: keep these conservative and avoid overriding admin layout variables (no body padding or zero margin-left on .admin-main)
    echo "<style>\n" .
        "body{background:#f3f4f6;font-family:Arial,Helvetica,sans-serif;color:#111;margin:0;padding:0;}\n" .
        ".admin-main{margin-left:260px;padding-top:84px;padding-bottom:68px;}\n" .
        ".page-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:1rem;}\n" .
        ".card{background:#fff;padding:18px;border-radius:8px;border:1px solid #eee;box-shadow:0 4px 10px rgba(0,0,0,0.04);margin-bottom:12px;}\n" .
        ".header-cta{background:#ffd600;color:#111;padding:8px 12px;border-radius:8px;text-decoration:none;border:none;cursor:pointer;}\n" .
        ".tabs{display:flex;gap:6px;margin-bottom:8px;}\n" .
        ".tab-btn{padding:6px 8px;border:1px solid #ccc;background:#fff;border-radius:4px;cursor:pointer;}\n" .
        "label{display:block;margin:6px 0;font-weight:600;}input.input, textarea.input{width:100%;padding:8px;border:1px solid #ccc;border-radius:6px;}\n" .
        "</style>\n";
    ?>
</head>

<body>
    <header class="admin-header">
        <div class="header-left">
            <i class='bx bx-menu' id="menuToggle"></i>
            <div>
                <span class="header-title"><?= isset($pageTitle) ? $pageTitle : 'Dashboard'; ?></span>
                <?php if (!empty($pageSubtitle)): ?>
                    <p class="header-subtitle"><?= htmlspecialchars($pageSubtitle) ?></p>
                <?php endif; ?>
            </div>
        </div>
        <div class="header-right">
            <?php if (empty($_SESSION['user'])): ?>
                <a href="../signup.php" class="header-cta">Sign up</a>
            <?php else: ?>
                <div class="header-avatar">
                    <img src="<?= $_SESSION['user']['avatar'] ?? '../public/assets/images/avatar-placeholder.png'; ?>" alt="Avatar">
                </div>
            <?php endif; ?>
        </div>
    </header>
    <?php
    // Render flash messages (if any)
    if (function_exists('getFlash')) {
        $flash = getFlash();
        if (!empty($flash)) {
            $type = $flash['type'] ?? 'info';
            $msg  = $flash['message'] ?? '';
            echo "<div class=\"admin-flash admin-flash-{$type}\">" . htmlspecialchars($msg) . "</div>";
        }
    }
    ?>
    <main class="admin-main">