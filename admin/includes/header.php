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

    // (TEMP) Disabled external stylesheet output to rely on inline fallback while debugging path/link issues
    // Once layout is confirmed restore external link tags here for improved caching/performance.
    if (!empty($pageCss)) echo $pageCss;

    // Full inline fallback CSS (applies core layout if external CSS fails). Kept here intentionally to ensure admin UI renders.
    echo "<style>\n" .
        ":root{--hq-yellow:#ffd600;--hq-yellow-light:#ffe566;--hq-red:#ff4b2b;--hq-black:#0a0a0a;--hq-gray:#f3f4f6;--sidebar-width:260px;--header-height:72px;--footer-height:56px;}\n" .
        "*{box-sizing:border-box}html,body{height:100%}body{margin:0;padding:0;background:var(--hq-gray);font-family:Segoe UI,Arial,sans-serif;color:var(--hq-black);}\n" .
        "/* Sidebar */\n" .
        ".admin-sidebar{position:fixed;top:0;left:0;width:var(--sidebar-width);height:100vh;background:#111;color:#fff;z-index:1000;padding:18px 16px;overflow:auto;}\n" .
        ".sidebar-logo img{max-width:120px;display:block;margin-bottom:8px} .sidebar-logo h3{font-size:1rem;margin:0;color:#fff} .sidebar-nav{margin-top:12px} .sidebar-nav ul{list-style:none;padding:0;margin:0} .sidebar-nav li{margin:6px 0} .sidebar-nav a{display:flex;align-items:center;gap:12px;padding:10px 12px;color:#ddd;text-decoration:none;border-radius:8px} .sidebar-nav a:hover{background:rgba(255,204,0,0.12);color:#111} .sidebar-nav a.active{background:var(--hq-yellow);color:#111;font-weight:700} .logout-link{display:block;margin-top:18px;color:var(--hq-red);font-weight:700}\n" .
        "/* Header */\n" .
        ".admin-header{position:fixed;top:0;left:var(--sidebar-width);right:0;height:var(--header-height);display:flex;align-items:center;justify-content:space-between;padding:0 20px;z-index:900;background:transparent;border-bottom:1px solid rgba(0,0,0,0.04);}\n" .
        ".header-left{display:flex;align-items:center;gap:12px} #menuToggle{font-size:1.6rem;cursor:pointer;color:var(--hq-yellow)} .header-title{font-weight:700} .header-subtitle{margin:0;font-size:0.85rem;color:#666} .header-right{display:flex;align-items:center;gap:12px} .header-cta{background:var(--hq-yellow);color:#111;padding:6px 12px;border-radius:999px;font-weight:700;text-decoration:none} .header-avatar{width:40px;height:40px;border-radius:50%;overflow:hidden;border:2px solid var(--hq-yellow)}\n" .
        "/* Footer */\n" .
        ".admin-footer{position:fixed;bottom:0;left:var(--sidebar-width);right:0;height:var(--footer-height);display:flex;align-items:center;justify-content:center;padding:8px 20px;z-index:880;background:transparent;border-top:1px solid rgba(0,0,0,0.04);} .admin-footer > div{max-width:1200px;width:100%} .admin-footer .card{background:var(--hq-yellow);padding:12px 18px;border-radius:8px;display:flex;align-items:center;justify-content:space-between;color:#111} \n" .
        "/* Main content */\n" .
        ".admin-main{margin-left:var(--sidebar-width);padding-top:calc(var(--header-height) + 12px);padding-bottom:calc(var(--footer-height) + 12px);}\n" .
        "/* small utilities */\n" .
        ".card{background:#fff;padding:18px;border-radius:8px;border:1px solid #eee;box-shadow:0 4px 10px rgba(0,0,0,0.04);margin-bottom:12px} .page-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:1rem} .muted{color:#6b6b6b;font-size:0.95rem;margin-bottom:8px} h1{font-size:2rem;margin-bottom:1rem;color:var(--hq-black)}\n" .
        "</style>\n";
    ?>
</head>

<body>
    <style>
        /* Critical, high-specificity fallbacks to force layout when external CSS missing/cached */
        .admin-sidebar {
            position: fixed !important;
            top: 0;
            left: 0;
            width: 260px;
            height: 100vh;
            background: #111;
            color: #fff;
            z-index: 1000;
            padding: 18px 16px;
            overflow: auto
        }

        .admin-header {
            position: fixed !important;
            top: 0;
            left: 260px;
            right: 0;
            height: 72px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 20px;
            z-index: 900
        }

        .admin-footer {
            position: fixed !important;
            bottom: 0;
            left: 260px;
            right: 0;
            height: 56px;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 8px 20px;
            z-index: 880
        }

        .admin-main {
            margin-left: 260px;
            padding-top: 84px;
            padding-bottom: 68px
        }

        .sidebar-overlay {
            display: none
        }
    </style>
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