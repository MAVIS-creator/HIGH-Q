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

// Determine site URL to check (prefer app_url()/admin_url() when available, then env, then fallback)
$siteUrl = rtrim(app_url(''), '/') . '/';

// Run quick checks
$dbStatus = checkDatabase($pdo);
$dbServer = null;
try {
    $dbServer = $pdo->getAttribute(PDO::ATTR_SERVER_VERSION);
} catch (Exception $e) {
    $dbServer = null;
}
$siteStatus = $siteUrl ? checkUrl($siteUrl) : ['ok' => false, 'message' => 'Unknown'];

// For admin URL, use the same base URL but add admin path
$adminUrl = rtrim(app_url(''), '/') . '/admin/';
$adminStatus = $adminUrl ? checkUrl($adminUrl) : ['ok' => false, 'message' => 'Unknown'];

// Get recent activity
$recentPayments = [];
try {
    $stmt = $pdo->query("SELECT p.*, u.name as user_name FROM payments p LEFT JOIN users u ON p.user_id = u.id ORDER BY p.created_at DESC LIMIT 5");
    $recentPayments = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}

$recentUsers = [];
try {
    $stmt = $pdo->query("SELECT * FROM users ORDER BY created_at DESC LIMIT 5");
    $recentUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}
?>

<div class="dashboard-container">
    <!-- Hero Section -->
    <div class="page-hero">
        <div class="page-hero-content">
            <div>
                <span class="page-hero-badge"><i class='bx bx-home-circle'></i> Overview</span>
                <h1 class="page-hero-title">Welcome back, <?= htmlspecialchars($_SESSION['user']['name'] ?? 'Admin') ?>!</h1>
                <p class="page-hero-subtitle">Here's what's happening with your academy today.</p>
            </div>
            <div class="page-hero-actions">
                <span class="inline-flex items-center gap-2 px-4 py-2 bg-white/20 backdrop-blur-md border border-white/30 rounded-full text-sm font-medium text-slate-900">
                    <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                    All Systems Operational
                </span>
            </div>
        </div>
    </div>

    <!-- Stats Grid -->
    <div class="stat-grid">
        <?php if (in_array('users', $permissions)): ?>
            <a href="index.php?pages=users" class="stat-card animate-fadeIn" style="animation-delay: 0ms">
                <div class="stat-icon stat-icon--blue">
                    <i class='bx bxs-user-detail'></i>
                </div>
                <div class="stat-content">
                    <div class="stat-value"><?= safeCount($pdo, "SELECT COUNT(*) FROM users") ?></div>
                    <div class="stat-label">Total Users</div>
                </div>
            </a>
        <?php endif; ?>

        <?php if (in_array('courses', $permissions)): ?>
            <a href="index.php?pages=courses" class="stat-card animate-fadeIn" style="animation-delay: 50ms">
                <div class="stat-icon stat-icon--amber">
                    <i class='bx bxs-book-content'></i>
                </div>
                <div class="stat-content">
                    <div class="stat-value"><?= safeCount($pdo, "SELECT COUNT(*) FROM courses") ?></div>
                    <div class="stat-label">Active Courses</div>
                </div>
            </a>
        <?php endif; ?>

        <?php if (in_array('students', $permissions)): ?>
            <a href="index.php?pages=students" class="stat-card animate-fadeIn" style="animation-delay: 100ms">
                <div class="stat-icon stat-icon--green">
                    <i class='bx bxs-graduation'></i>
                </div>
                <div class="stat-content">
                    <?php
                    $studentsCount = safeCount($pdo, "SELECT COUNT(*) FROM students");
                    if ($studentsCount === 0) {
                        $studentsCount = safeCount($pdo, "SELECT COUNT(*) FROM users u LEFT JOIN roles r ON r.id=u.role_id WHERE r.slug='student' OR u.role_id IS NULL");
                    }
                    ?>
                    <div class="stat-value"><?= $studentsCount ?></div>
                    <div class="stat-label">Enrolled Students</div>
                </div>
            </a>
        <?php endif; ?>

        <?php if (in_array('posts', $permissions)): ?>
            <a href="index.php?pages=posts" class="stat-card animate-fadeIn" style="animation-delay: 150ms">
                <div class="stat-icon stat-icon--purple">
                    <i class='bx bxs-news'></i>
                </div>
                <div class="stat-content">
                    <div class="stat-value"><?= safeCount($pdo, "SELECT COUNT(*) FROM posts") ?></div>
                    <div class="stat-label">Published Posts</div>
                </div>
            </a>
        <?php endif; ?>

        <?php if (in_array('payments', $permissions)): ?>
            <a href="index.php?pages=payments" class="stat-card animate-fadeIn" style="animation-delay: 200ms">
                <div class="stat-icon stat-icon--primary">
                    <i class='bx bxs-credit-card'></i>
                </div>
                <div class="stat-content">
                    <div class="stat-value"><?= safeCount($pdo, "SELECT COUNT(*) FROM payments WHERE status='pending'") ?></div>
                    <div class="stat-label">Pending Payments</div>
                </div>
            </a>
        <?php endif; ?>

        <?php if (in_array('comments', $permissions)): ?>
            <a href="index.php?pages=comments" class="stat-card animate-fadeIn" style="animation-delay: 250ms">
                <div class="stat-icon" style="background: rgba(100, 116, 139, 0.1); color: #64748b;">
                    <i class='bx bxs-comment-detail'></i>
                </div>
                <div class="stat-content">
                    <div class="stat-value"><?= safeCount($pdo, "SELECT COUNT(*) FROM comments WHERE status='pending'") ?></div>
                    <div class="stat-label">Pending Comments</div>
                </div>
            </a>
        <?php endif; ?>

        <?php if (in_array('tutors', $permissions)): ?>
            <a href="index.php?pages=tutors" class="stat-card animate-fadeIn" style="animation-delay: 300ms">
                <div class="stat-icon stat-icon--red">
                    <i class='bx bxs-user-voice'></i>
                </div>
                <div class="stat-content">
                    <div class="stat-value"><?= safeCount($pdo, "SELECT COUNT(*) FROM tutors") ?></div>
                    <div class="stat-label">Tutors</div>
                </div>
            </a>
        <?php endif; ?>

        <?php if (in_array('settings', $permissions)): ?>
            <a href="index.php?pages=settings" class="stat-card animate-fadeIn" style="animation-delay: 350ms">
                <div class="stat-icon" style="background: var(--hq-gray-800); color: #fff;">
                    <i class='bx bxs-cog'></i>
                </div>
                <div class="stat-content">
                    <div class="stat-value" style="font-size: 1.25rem;">Settings</div>
                    <div class="stat-label">Manage Site</div>
                </div>
            </a>
        <?php endif; ?>
    </div>

    <!-- Two Column Layout -->
    <div class="dashboard-grid">
        <!-- System Status Card -->
        <?php if (in_array('settings', $permissions)): ?>
        <div class="admin-card animate-fadeIn" style="animation-delay: 400ms">
            <div class="admin-card-header">
                <h3 class="admin-card-title">
                    <i class='bx bxs-dashboard'></i>
                    System Status
                </h3>
                <span class="badge badge-success"><i class='bx bx-check-circle'></i> All Systems Go</span>
            </div>
            <div class="admin-card-body">
                <div class="status-grid">
                    <div class="status-item">
                        <div class="status-indicator <?= $dbStatus['ok'] ? 'status-indicator--success' : 'status-indicator--danger' ?>"></div>
                        <div class="status-info">
                            <span class="status-label">Database</span>
                            <span class="status-value"><?= htmlspecialchars($dbStatus['message']) ?></span>
                        </div>
                    </div>
                    <div class="status-item">
                        <div class="status-indicator <?= $siteStatus['ok'] ? 'status-indicator--success' : 'status-indicator--danger' ?>"></div>
                        <div class="status-info">
                            <span class="status-label">Website</span>
                            <span class="status-value"><?= htmlspecialchars($siteStatus['message']) ?></span>
                        </div>
                    </div>
                    <div class="status-item">
                        <div class="status-indicator <?= $adminStatus['ok'] ? 'status-indicator--success' : 'status-indicator--danger' ?>"></div>
                        <div class="status-info">
                            <span class="status-label">Admin Panel</span>
                            <span class="status-value"><?= htmlspecialchars($adminStatus['message']) ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Quick Actions Card -->
        <div class="admin-card animate-fadeIn" style="animation-delay: 450ms">
            <div class="admin-card-header">
                <h3 class="admin-card-title">
                    <i class='bx bxs-zap'></i>
                    Quick Actions
                </h3>
            </div>
            <div class="admin-card-body">
                <div class="quick-actions-grid">
                    <?php if (in_array('users', $permissions)): ?>
                    <a href="index.php?pages=users&action=new" class="quick-action-btn">
                        <i class='bx bx-user-plus'></i>
                        <span>Add User</span>
                    </a>
                    <?php endif; ?>
                    <?php if (in_array('posts', $permissions)): ?>
                    <a href="index.php?pages=post_edit" class="quick-action-btn">
                        <i class='bx bx-edit'></i>
                        <span>New Post</span>
                    </a>
                    <?php endif; ?>
                    <?php if (in_array('courses', $permissions)): ?>
                    <a href="index.php?pages=courses&action=new" class="quick-action-btn">
                        <i class='bx bx-book-add'></i>
                        <span>Add Course</span>
                    </a>
                    <?php endif; ?>
                    <?php if (in_array('payments', $permissions)): ?>
                    <a href="index.php?pages=payments" class="quick-action-btn">
                        <i class='bx bx-receipt'></i>
                        <span>View Payments</span>
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Activity Section -->
    <div class="dashboard-grid">
        <!-- Recent Users -->
        <?php if (in_array('users', $permissions) && !empty($recentUsers)): ?>
        <div class="admin-card animate-fadeIn" style="animation-delay: 500ms">
            <div class="admin-card-header">
                <h3 class="admin-card-title">
                    <i class='bx bxs-user-account'></i>
                    Recent Users
                </h3>
                <a href="index.php?pages=users" class="btn btn-sm btn-secondary">View All</a>
            </div>
            <div class="admin-card-body" style="padding: 0;">
                <div class="recent-list">
                    <?php foreach ($recentUsers as $user): ?>
                    <div class="recent-item">
                        <div class="recent-avatar">
                            <img src="<?= !empty($user['avatar']) ? htmlspecialchars($user['avatar']) : app_url('assets/images/hq-logo.jpeg') ?>" alt="">
                        </div>
                        <div class="recent-info">
                            <span class="recent-name"><?= htmlspecialchars($user['name'] ?? 'Unknown') ?></span>
                            <span class="recent-meta"><?= htmlspecialchars($user['email'] ?? '') ?></span>
                        </div>
                        <span class="recent-time"><?= date('M d', strtotime($user['created_at'])) ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Recent Payments -->
        <?php if (in_array('payments', $permissions) && !empty($recentPayments)): ?>
        <div class="admin-card animate-fadeIn" style="animation-delay: 550ms">
            <div class="admin-card-header">
                <h3 class="admin-card-title">
                    <i class='bx bxs-wallet'></i>
                    Recent Payments
                </h3>
                <a href="index.php?pages=payments" class="btn btn-sm btn-secondary">View All</a>
            </div>
            <div class="admin-card-body" style="padding: 0;">
                <div class="recent-list">
                    <?php foreach ($recentPayments as $payment): ?>
                    <div class="recent-item">
                        <div class="recent-icon <?= $payment['status'] === 'success' ? 'recent-icon--success' : 'recent-icon--pending' ?>">
                            <i class='bx <?= $payment['status'] === 'success' ? 'bx-check' : 'bx-time' ?>'></i>
                        </div>
                        <div class="recent-info">
                            <span class="recent-name">₦<?= number_format($payment['amount'] ?? 0, 2) ?></span>
                            <span class="recent-meta"><?= htmlspecialchars($payment['user_name'] ?? 'Unknown') ?></span>
                        </div>
                        <span class="badge <?= $payment['status'] === 'success' ? 'badge-success' : 'badge-warning' ?>">
                            <?= ucfirst($payment['status'] ?? 'pending') ?>
                        </span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<style>
/* Dashboard Specific Styles */
.dashboard-container {
    max-width: 1600px;
    margin: 0 auto;
}

.dashboard-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 1.5rem;
    margin-top: 1.5rem;
}

/* Status Grid */
.status-grid {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.status-item {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem;
    background: var(--hq-gray-50);
    border-radius: var(--radius);
    border: 1px solid var(--hq-gray-100);
}

.status-indicator {
    width: 12px;
    height: 12px;
    border-radius: 50%;
    flex-shrink: 0;
}

.status-indicator--success { background: var(--hq-success); box-shadow: 0 0 8px rgba(34, 197, 94, 0.4); }
.status-indicator--danger { background: var(--hq-danger); box-shadow: 0 0 8px rgba(239, 68, 68, 0.4); }
.status-indicator--warning { background: var(--hq-warning); box-shadow: 0 0 8px rgba(245, 158, 11, 0.4); }

.status-info {
    display: flex;
    flex-direction: column;
}

.status-label {
    font-size: 0.75rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: var(--hq-gray-500);
    font-weight: 600;
}

.status-value {
    font-weight: 600;
    color: var(--hq-gray-800);
}

/* Quick Actions */
.quick-actions-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 0.75rem;
}

.quick-action-btn {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 1.25rem 1rem;
    background: var(--hq-gray-50);
    border: 1px solid var(--hq-gray-200);
    border-radius: var(--radius);
    text-decoration: none;
    color: var(--hq-gray-700);
    transition: all var(--transition);
}

.quick-action-btn:hover {
    background: var(--hq-yellow-100);
    border-color: var(--hq-yellow);
    color: var(--hq-black);
    transform: translateY(-2px);
}

.quick-action-btn i {
    font-size: 1.5rem;
    color: var(--hq-yellow-dark);
}

.quick-action-btn span {
    font-size: 0.85rem;
    font-weight: 600;
}

/* Recent Lists */
.recent-list {
    display: flex;
    flex-direction: column;
}

.recent-item {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem 1.5rem;
    border-bottom: 1px solid var(--hq-gray-100);
    transition: background var(--transition);
}

.recent-item:last-child {
    border-bottom: none;
}

.recent-item:hover {
    background: var(--hq-gray-50);
}

.recent-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    overflow: hidden;
    flex-shrink: 0;
}

.recent-avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.recent-icon {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.recent-icon--success {
    background: rgba(34, 197, 94, 0.1);
    color: var(--hq-success);
}

.recent-icon--pending {
    background: rgba(245, 158, 11, 0.1);
    color: var(--hq-warning);
}

.recent-info {
    flex: 1;
    min-width: 0;
    display: flex;
    flex-direction: column;
}

.recent-name {
    font-weight: 600;
    color: var(--hq-gray-800);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.recent-meta {
    font-size: 0.8rem;
    color: var(--hq-gray-500);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.recent-time {
    font-size: 0.75rem;
    color: var(--hq-gray-400);
    flex-shrink: 0;
}

/* Responsive */
@media (max-width: 1024px) {
    .dashboard-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 768px) {
    .quick-actions-grid {
        grid-template-columns: 1fr;
    }
}
</style>

