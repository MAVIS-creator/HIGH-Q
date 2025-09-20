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

<body class="admin">
    <header class="admin-header">
        <div class="header-top" style="display:flex;align-items:center;justify-content:space-between;padding:8px 20px;height:60px;">
            <div style="display:flex;align-items:center;gap:12px;">
                <i class='bx bx-menu' id="menuToggle" style="font-size:1.6rem;color:var(--hq-yellow)"></i>
                <div style="font-weight:700;color:var(--hq-black);">Admin Panel</div>
            </div>
            <div class="header-right">
                <?php if (empty($_SESSION['user'])): ?>
                    <a href="../signup.php" class="header-cta">Sign up</a>
                <?php else: ?>
                    <div style="display:flex;align-items:center;gap:12px;">
                        <div class="header-avatar"><img src="<?= $_SESSION['user']['avatar'] ?? '../public/assets/images/avatar-placeholder.png'; ?>" alt="Avatar"></div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php if (!empty($pageTitle)): ?>
            <div class="admin-banner" style="background:var(--hq-yellow);padding:18px 28px;border-bottom:1px solid rgba(0,0,0,0.04);">
                <div style="display:flex;align-items:center;gap:12px;max-width:1200px;margin:0 auto;">
                    <div style="width:56px;height:56px;border-radius:50%;background:#111;display:flex;align-items:center;justify-content:center;color:#fff;font-size:1.25rem;">
                        <i class='bx bx-award'></i>
                    </div>
                    <div>
                        <div style="font-weight:700;color:#111;font-size:1.25rem;"><?= htmlspecialchars($pageTitle) ?></div>
                        <?php if (!empty($pageSubtitle)): ?><div style="color:#222;"><?= htmlspecialchars($pageSubtitle) ?></div><?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>
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
        <?php if (!empty($pageTitle)): ?>
            <div class="page-banner card" style="background:var(--hq-yellow);padding:18px;border-radius:8px;margin-bottom:16px;">
                <div style="display:flex;align-items:center;gap:12px;">
                    <div style="width:48px;height:48px;border-radius:50%;background:#000;display:flex;align-items:center;justify-content:center;color:#fff;font-size:1.25rem;">
                        <i class='bx bx-award'></i>
                    </div>
                    <div>
                        <div style="font-weight:700;color:#111;font-size:1.1rem;"><?= htmlspecialchars($pageTitle) ?></div>
                        <?php if (!empty($pageSubtitle)): ?><div style="color:#333;"><?= htmlspecialchars($pageSubtitle) ?></div><?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>