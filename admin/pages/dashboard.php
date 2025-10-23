<?php
// admin./pages/dashboard.php

// Page title and subtitle for header
$pageTitle = 'Dashboard';
$pageSubtitle = 'Overview and quick stats for your site';

// Fetch allowed menus for current role
$userRoleId = $_SESSION['user']['role_id'];
$stmt = $pdo->prepare("SELECT menu_slug FROM role_permissions WHERE role_id = ?");
$stmt->execute([$userRoleId]);
$permissions = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Helper: safe count query wrapper
function safeCount(PDO $pdo, string $sql)
{
    try {
        $val = $pdo->query($sql)->fetchColumn();
        return (int)$val;
    } catch (Exception $e) {
        return 0;
    }
}

// Helper: check database connectivity
function checkDatabase(PDO $pdo)
{
    try {
        $pdo->query('SELECT 1');
        return ['ok' => true, 'message' => 'Online'];
    } catch (Exception $e) {
        return ['ok' => false, 'message' => 'Down: ' . $e->getMessage()];
    }
}

// Helper: check a URL is reachable (HEAD request, short timeout)
function checkUrl(string $url)
{
    $result = ['ok' => false, 'message' => 'Unknown'];
    if (!function_exists('curl_init')) {
        // fallback to get_headers
        set_error_handler(function () {});
        $headers = @get_headers($url);
        restore_error_handler();
        if ($headers && strpos($headers[0], '200') !== false) {
            $result = ['ok' => true, 'message' => 'Online'];
        } else {
            $result = ['ok' => false, 'message' => 'Unreachable'];
        }
        return $result;
    }

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_NOBODY, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 3);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_errno($ch);
    curl_close($ch);
    if ($err === 0 && $httpCode >= 200 && $httpCode < 400) {
        return ['ok' => true, 'message' => 'Online (' . $httpCode . ')'];
    }
    return ['ok' => false, 'message' => 'Unreachable'];
}

// Determine site URL to check (try environment/config or fallback)
$siteUrl = null;
$envUrl = getenv('APP_URL') ?: ($_ENV['APP_URL'] ?? null);
if (!empty($envUrl)) {
    $siteUrl = rtrim($envUrl, '/') . '/';
} elseif (!empty($_SERVER['HTTP_HOST'])) {
    $proto = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $siteUrl = $proto . '://' . $_SERVER['HTTP_HOST'] . '/';
}

// Run quick checks
$dbStatus = checkDatabase($pdo);
$dbServer = null;
try {
    $dbServer = $pdo->getAttribute(PDO::ATTR_SERVER_VERSION);
} catch (Exception $e) {
    $dbServer = null;
}
$siteStatus = $siteUrl ? checkUrl($siteUrl) : ['ok' => false, 'message' => 'Unknown'];
$adminUrl = $siteUrl ? rtrim($siteUrl, '/') . '/admin/' : null;
$adminStatus = $adminUrl ? checkUrl($adminUrl) : ['ok' => false, 'message' => 'Unknown'];
?>
<div class="dashboard">
    <div class="dashboard-intro">
        <h1><?= htmlspecialchars($pageTitle) ?></h1>
        <p class="role-label">Role: <?= htmlspecialchars($_SESSION['user']['role_name']); ?></p>
    </div>

    <div class="dashboard-widgets">
        <?php if (in_array('users', $permissions)): ?>
            <a href="index.php?pages=users" class="widget-card red widget-link">
                <i class='bx bxs-user-detail'></i>
                <div>
                    <h3><?= safeCount($pdo, "SELECT COUNT(*) FROM users") ?></h3>
                    <p>Total Users</p>
                </div>
            </a>
        <?php endif; ?>

        <?php if (in_array('settings', $permissions)): ?>
            <a href="index.php?pages=settings" class="widget-card black widget-link">
                <i class='bx bxs-cog'></i>
                <div>
                    <h3>Settings</h3>
                    <p>Manage Site</p>
                </div>
            </a>
        <?php endif; ?>

        <?php if (in_array('courses', $permissions)): ?>
            <a href="index.php?pages=courses" class="widget-card yellow widget-link">
                <i class='bx bxs-book'></i>
                <div>
                    <h3><?= safeCount($pdo, "SELECT COUNT(*) FROM courses") ?></h3>
                    <p>Courses</p>
                </div>
            </a>
        <?php endif; ?>

        <?php if (in_array('students', $permissions)): ?>
            <a href="index.php?pages=students" class="widget-card red widget-link">
                <i class='bx bxs-graduation'></i>
                <div>
                    <?php
                    // Some installs store students in `users` with a student role; try students table then fallback
                    $studentsCount = safeCount($pdo, "SELECT COUNT(*) FROM students");
                    if ($studentsCount === 0) {
                        $studentsCount = safeCount($pdo, "SELECT COUNT(*) FROM users u LEFT JOIN roles r ON r.id=u.role_id WHERE r.slug='student' OR u.role_id IS NULL");
                    }
                    ?>
                    <h3><?= $studentsCount ?></h3>
                    <p>Students</p>
                </div>
            </a>
        <?php endif; ?>

        <?php if (in_array('posts', $permissions)): ?>
            <a href="index.php?pages=posts" class="widget-card yellow widget-link">
                <i class='bx bxs-news'></i>
                <div>
                    <h3><?= safeCount($pdo, "SELECT COUNT(*) FROM posts") ?></h3>
                    <p>Posts</p>
                </div>
            </a>
        <?php endif; ?>

        <?php if (in_array('comments', $permissions)): ?>
            <a href="index.php?pages=comments" class="widget-card black widget-link">
                <i class='bx bxs-comment-detail'></i>
                <div>
                    <h3><?= safeCount($pdo, "SELECT COUNT(*) FROM comments WHERE status='pending'") ?></h3>
                    <p>Pending Comments</p>
                </div>
            </a>
        <?php endif; ?>

        <?php if (in_array('payments', $permissions)): ?>
            <a href="index.php?pages=payments" class="widget-card yellow widget-link">
                <i class='bx bxs-credit-card'></i>
                <div>
                    <h3><?= safeCount($pdo, "SELECT COUNT(*) FROM payments WHERE status='pending'") ?></h3>
                    <p>Pending Payments</p>
                </div>
            </a>
        <?php endif; ?>

        <?php if (in_array('settings', $permissions)): ?>
            <div class="widget-card system-status">
                <i class='bx bxs-dashboard'></i>
                <div class="system-content">
                    <h3>System Status</h3>
                    <ul class="system-list">
                        <li>Database: <strong class="status-<?= $dbStatus['ok'] ? 'ok' : 'bad' ?>"><?= htmlspecialchars($dbStatus['message']) ?></strong></li>
                        <li>Website: <strong class="status-<?= $siteStatus['ok'] ? 'ok' : 'bad' ?>"><?= htmlspecialchars($siteStatus['message']) ?></strong></li>
                        <li>Admin Panel: <strong class="status-<?= $adminStatus['ok'] ? 'ok' : 'bad' ?>"><?= htmlspecialchars($adminStatus['message']) ?></strong></li>
                    </ul>
                    <p><a href="/admin./pages/settings.php">Open settings</a></p>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>