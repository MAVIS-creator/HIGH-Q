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

    // Prepare several href fallbacks for the browser to try.
    $projectSub = '/HIGH-Q';
    $hrefs = array_values(array_unique(array_filter([
        $chosen,
        $adminBase . '/assets/css/admin.css',
        '/admin/assets/css/admin.css',
        $projectSub . '/admin/assets/css/admin.css',
        $projectSub . '/assets/css/admin.css',
        '/assets/css/admin.css',
        dirname($script) . '/assets/css/admin.css',
        '../assets/css/admin.css'
    ])));
    // Ensure a guaranteed absolute path for typical XAMPP setup (project in /HIGH-Q)
    echo '<link rel="stylesheet" href="/HIGH-Q/admin/assets/css/admin.css">\n';
    // Output link tags for each candidate (browser will use the first that 200s)
    $debugParts = [];
    foreach ($hrefs as $h) {
        echo '<link rel="stylesheet" href="' . htmlspecialchars($h, ENT_QUOTES) . '">\n';
        $fsCheck = '';
        $docRoot = rtrim($_SERVER['DOCUMENT_ROOT'] ?? '', '/');
        $candNorm = '/' . ltrim($h, '/');
        if ($docRoot !== '' && is_readable($docRoot . $candNorm)) $fsCheck = 'exists'; else $fsCheck = 'missing';
        $debugParts[] = $h . ' (' . $fsCheck . ')';
    }
    if (!empty($pageCss)) echo $pageCss;
    // Debug comment for quick inspection in View Source
    echo '<!-- admin css candidates: ' . implode(' | ', $debugParts) . ' | chosen: ' . htmlspecialchars($chosen, ENT_QUOTES) . ' -->\n';

    // Minimal critical inline fallback CSS (keeps UI readable if external CSS fails)
    echo "<style>\n" .
        "body{background:#f3f4f6;font-family:Arial,Helvetica,sans-serif;color:#111;margin:0;padding:2rem;}\n" .
        ".admin-main{margin-left:0;padding-top:80px;}\n" .
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
    <header class="admin-header" style="background:transparent;padding:0 1rem 1rem 1rem;">
        <div class="header-card" style="display:flex;align-items:center;gap:1rem;background:var(--hq-yellow);padding:18px;border-radius:8px;">
            <div style="width:60px;height:60px;border-radius:50%;background:#000;display:flex;align-items:center;justify-content:center;color:#ffd;">
                <i class='bx bx-award' style="font-size:1.3rem;color:#fff"></i>
            </div>
            <div style="flex:1;">
                <div style="font-weight:700;color:#111;">HIGH Q SOLID ACADEMY</div>
                <div style="font-size:0.9rem;color:#333;">Always Ahead of Others</div>
            </div>
            <div style="min-width:180px;text-align:right;">
                <?php if (!empty($_SESSION['user'])): ?>
                    <div style="display:inline-flex;align-items:center;gap:8px;">
                        <div class="header-avatar" style="width:40px;height:40px;border-radius:999px;overflow:hidden;border:2px solid rgba(0,0,0,0.06);">
                            <img src="<?= $_SESSION['user']['avatar'] ?? '../public/assets/images/avatar-placeholder.png'; ?>" alt="Avatar" style="width:100%;height:100%;object-fit:cover;">
                        </div>
                        <div style="font-size:0.85rem;color:#222;">Admin</div>
                    </div>
                <?php else: ?>
                    <a href="../signup.php" class="header-cta">Sign up</a>
                <?php endif; ?>
            </div>
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