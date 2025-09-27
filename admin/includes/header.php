<?php
// admin/includes/header.php
// Start output buffering so downstream header() calls succeed even if this file emits HTML
if (!headers_sent()) {
    ob_start();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title><?= isset($pageTitle) ? $pageTitle : 'Admin Panel'; ?> - HIGH Q SOLID ACADEMY</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" href="../assets/img/favicon.ico" type="image/x-icon">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="../assets/css/notifications.css">
    <script src="../assets/js/notifications.js"></script>
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
                    <div class="header-notifications">
                        <button id="notifBtn" class="header-cta" title="Notifications">
                            <i class='bx bx-bell'></i>
                            <span id="notifBadge" style="display:none;background:#ff3b30;color:#fff;border-radius:999px;padding:2px 6px;margin-left:8px;font-size:0.8rem;">0</span>
                        </button>
                    </div>
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
        <style>
        /* Minimal header dropdown styles (kept inline for reliability) */
        .notif-dropdown{position:relative;display:inline-block}
        .notif-panel{position:absolute;right:0;top:40px;width:360px;max-height:420px;overflow:auto;background:#fff;border:1px solid #e6e6e6;border-radius:8px;box-shadow:0 8px 24px rgba(0,0,0,0.12);display:none;z-index:1000}
        .notif-item{padding:10px;border-bottom:1px solid #f1f1f1}
        .notif-item:last-child{border-bottom:none}
        .notif-empty{padding:20px;text-align:center;color:#666}
        </style>
        <script>
        (function initNotifications(){
            const btn = document.getElementById('notifBtn');
            if (!btn) return;

            // Create dropdown container
            const wrap = document.createElement('div');
            wrap.className = 'notif-dropdown';
            
            const panel = document.createElement('div');
            panel.className = 'notif-panel notification-list';
            panel.id = 'notifPanel';
            
            // Clone button and add to wrapper
            wrap.appendChild(btn.cloneNode(true));
            wrap.appendChild(panel);
            
            // Replace original button with wrapper
            const orig = btn.parentNode;
            orig.replaceChild(wrap, btn);

            const badge = wrap.querySelector('#notifBadge');

            async function loadNotifications(){
                try {
                    const res = await fetch('/HIGH-Q/admin/api/notifications.php');
                    if(!res.ok) return;
                    const j = await res.json();
                    const list = j.notifications || [];
                    
                    // Update badge
                    if(list.length>0) { 
                        badge.style.display = 'inline-block'; 
                        badge.textContent = list.length; 
                    } else { 
                        badge.style.display = 'none'; 
                    }
                    
                    // Clear and render notifications
                    panel.innerHTML = '';
                    if(list.length === 0) {
                        panel.innerHTML = '<div class="notif-empty">No notifications</div>';
                        return;
                    }
                    
                    list.forEach(n => {
                        const it = document.createElement('a'); 
                        it.className = 'notification-item ' + (n.is_read ? 'read' : '');
                        it.setAttribute('data-notification-id', n.id);
                        it.setAttribute('data-notification-type', n.type);
                        
                        // Set the href based on notification type
                        const baseUrl = '/HIGH-Q/admin/index.php?pages=';
                        let href = '';
                        if(n.type === 'chat') href = baseUrl + 'chat&thread_id=' + (n.meta.thread_id || n.id);
                        else if(n.type === 'comment') href = baseUrl + 'comments&id=' + n.id;
                        else if(n.type === 'student_application') href = baseUrl + 'students&id=' + n.id;
                        else if(n.type === 'payment') href = baseUrl + 'payments&id=' + n.id;
                        else if(n.type === 'user') href = baseUrl + 'users&id=' + n.id;
                        it.href = href;
                        
                        const title = document.createElement('div'); 
                        title.className = 'notification-title';
                        title.textContent = n.title || '';
                        
                        const msg = document.createElement('div'); 
                        msg.className = 'notification-message';
                        msg.textContent = n.message || '';
                        
                        const time = document.createElement('div'); 
                        time.className = 'notification-time';
                        time.textContent = n.created_at || '';
                        
                        it.appendChild(title);
                        it.appendChild(msg);
                        it.appendChild(time);
                        
                        it.addEventListener('click', function(e) {
                            e.preventDefault();
                            e.stopPropagation();
                            panel.style.display = 'none';
                            // The click handler is now in notifications.js
                        });
                            else if(n.type==='comment') window.location = '/HIGH-Q/admin/index.php?pages=comments&highlight=' + (n.id);
                            else if(n.type==='student_application') window.location = '/HIGH-Q/admin/index.php?pages=students&highlight=' + (n.id);
                            else if(n.type==='payment') window.location = '/HIGH-Q/admin/index.php?pages=payments&highlight=' + (n.id);
                        });
                        // Panel append is now handled in the forEach loop
                    });
                }catch(e){ /* ignore */ }
            }

            // toggle panel
            wrap.querySelector('button').addEventListener('click', function(e){
                e.stopPropagation(); panel.style.display = panel.style.display==='block' ? 'none' : 'block';
                if(panel.style.display==='block') loadNotifications();
            });

            // close on outside click
            document.addEventListener('click', function(e){ if(!wrap.contains(e.target)) panel.style.display='none'; });

            // poll badge count in background
            async function pollCount(){
                try{
                    const res = await fetch('/HIGH-Q/admin/api/notifications.php'); if(!res.ok) return;
                    const j = await res.json(); const c = j.count || 0; if(c>0) badge.style.display='inline-block', badge.textContent=c; else badge.style.display='none';
                }catch(e){}
            }
            pollCount(); setInterval(pollCount,5000);
        })();
        </script>