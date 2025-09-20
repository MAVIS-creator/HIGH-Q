<?php
// admin/pages/dashboard.php

// Page title and subtitle for header
$pageTitle = 'Dashboard';
$pageSubtitle = 'Overview and quick stats for your site';

// Fetch allowed menus for current role
$userRoleId = $_SESSION['user']['role_id'];
$stmt = $pdo->prepare("SELECT menu_slug FROM role_permissions WHERE role_id = ?");
$stmt->execute([$userRoleId]);
$permissions = $stmt->fetchAll(PDO::FETCH_COLUMN);

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
        set_error_handler(function() {});
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
            <div class="widget-card red">
                <i class='bx bxs-user-detail'></i>
                <div>
                    <h3><?= $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn(); ?></h3>
                    <p>Total Users</p>
                </div>
            </div>
        <?php endif; ?>

        <?php if (in_array('settings', $permissions)): ?>
            <a href="/admin/pages/settings.php" class="widget-card black widget-link">
                <i class='bx bxs-cog'></i>
                <div>
                    <h3>Settings</h3>
                    <p>Manage Site</p>
                </div>
            </a>
        <?php endif; ?>

        <?php if (in_array('courses', $permissions)): ?>
            <div class="widget-card yellow">
                <i class='bx bxs-book'></i>
                <div>
                    <h3><?= $pdo->query("SELECT COUNT(*) FROM courses")->fetchColumn(); ?></h3>
                    <p>Courses</p>
                </div>
            </div>
        <?php endif; ?>

        <?php if (in_array('students', $permissions)): ?>
            <div class="widget-card red">
                <i class='bx bxs-graduation'></i>
                <div>
                    <h3><?= $pdo->query("SELECT COUNT(*) FROM students")->fetchColumn(); ?></h3>
                    <p>Students</p>
                </div>
            </div>
        <?php endif; ?>

        <?php if (in_array('posts', $permissions)): ?>
            <div class="widget-card yellow">
                <i class='bx bxs-news'></i>
                <div>
                    <h3><?= $pdo->query("SELECT COUNT(*) FROM posts")->fetchColumn(); ?></h3>
                    <p>Posts</p>
                </div>
            </div>
        <?php endif; ?>

        <?php if (in_array('comments', $permissions)): ?>
            <div class="widget-card black">
                <i class='bx bxs-comment-detail'></i>
                <div>
                    <h3><?= $pdo->query("SELECT COUNT(*) FROM comments WHERE status='pending'")->fetchColumn(); ?></h3>
                    <p>Pending Comments</p>
                </div>
            </div>
        <?php endif; ?>

        <?php if (in_array('payments', $permissions)): ?>
            <div class="widget-card yellow">
                <i class='bx bxs-credit-card'></i>
                <div>
                    <h3><?= $pdo->query("SELECT COUNT(*) FROM payments WHERE status='pending'")->fetchColumn(); ?></h3>
                    <p>Pending Payments</p>
                </div>
            </div>
        <?php endif; ?>

        <?php if (in_array('settings', $permissions)): ?>
            <div class="widget-card system-status">
                <i class='bx bxs-dashboard'></i>
                <div>
                    <h3>System Status</h3>
                    <ul class="system-list">
                        <li>Database: <strong class="status-<?= $dbStatus['ok'] ? 'ok' : 'bad' ?>"><?= htmlspecialchars($dbStatus['message']) ?></strong></li>
                        <li>Website: <strong class="status-<?= $siteStatus['ok'] ? 'ok' : 'bad' ?>"><?= htmlspecialchars($siteStatus['message']) ?></strong></li>
                        <li>Admin Panel: <strong class="status-<?= $adminStatus['ok'] ? 'ok' : 'bad' ?>"><?= htmlspecialchars($adminStatus['message']) ?></strong></li>
                    </ul>
                    <p><a href="/admin/pages/settings.php">Open settings</a></p>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>
