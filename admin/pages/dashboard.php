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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard — Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
</head>
<body class="bg-slate-50">
<?php include '../includes/header.php'; ?>
<?php include '../includes/sidebar.php'; ?>

<div class="min-h-screen w-full mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6 ml-[var(--sidebar-width)] transition-all duration-300">
    <!-- Header Section -->
    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-indigo-600 via-purple-600 to-indigo-700 p-8 shadow-xl text-white">
        <div class="absolute inset-0 bg-[radial-gradient(circle_at_20%_20%,rgba(255,255,255,0.1),transparent_35%),radial-gradient(circle_at_80%_0%,rgba(255,255,255,0.1),transparent_25%)]"></div>
        <div class="relative flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <p class="text-xs uppercase tracking-[0.2em] text-indigo-100/80">Overview</p>
                <h1 class="mt-2 text-3xl sm:text-4xl font-bold leading-tight">Dashboard</h1>
                <p class="mt-2 text-indigo-100/90 max-w-2xl">Welcome back, <?= htmlspecialchars($_SESSION['user']['name'] ?? 'Admin') ?></p>
            </div>
            <div class="flex items-center gap-2 text-sm bg-white/10 backdrop-blur-md border border-white/20 rounded-full px-4 py-2">
                <span class="h-2 w-2 rounded-full bg-emerald-400"></span>
                <span class="text-white font-medium">System Online</span>
            </div>
        </div>
    </div>

    <!-- Widgets Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6">
        <?php if (in_array('users', $permissions)): ?>
            <a href="index.php?pages=users" class="group relative bg-white rounded-xl border border-slate-200 p-6 hover:shadow-lg transition-all duration-200 hover:border-indigo-300 flex items-center gap-4">
                <div class="h-14 w-14 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center text-3xl group-hover:scale-110 transition-transform">
                    <i class='bx bxs-user-detail'></i>
                </div>
                <div>
                    <h3 class="text-2xl font-bold text-slate-800"><?= safeCount($pdo, "SELECT COUNT(*) FROM users") ?></h3>
                    <p class="text-sm text-slate-500 font-medium">Total Users</p>
                </div>
            </a>
        <?php endif; ?>

        <?php if (in_array('courses', $permissions)): ?>
            <a href="index.php?pages=courses" class="group relative bg-white rounded-xl border border-slate-200 p-6 hover:shadow-lg transition-all duration-200 hover:border-amber-300 flex items-center gap-4">
                <div class="h-14 w-14 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center text-3xl group-hover:scale-110 transition-transform">
                    <i class='bx bxs-book'></i>
                </div>
                <div>
                    <h3 class="text-2xl font-bold text-slate-800"><?= safeCount($pdo, "SELECT COUNT(*) FROM courses") ?></h3>
                    <p class="text-sm text-slate-500 font-medium">Courses</p>
                </div>
            </a>
        <?php endif; ?>

        <?php if (in_array('students', $permissions)): ?>
            <a href="index.php?pages=students" class="group relative bg-white rounded-xl border border-slate-200 p-6 hover:shadow-lg transition-all duration-200 hover:border-rose-300 flex items-center gap-4">
                <div class="h-14 w-14 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center text-3xl group-hover:scale-110 transition-transform">
                    <i class='bx bxs-graduation'></i>
                </div>
                <div>
                    <?php
                    $studentsCount = safeCount($pdo, "SELECT COUNT(*) FROM students");
                    if ($studentsCount === 0) {
                        $studentsCount = safeCount($pdo, "SELECT COUNT(*) FROM users u LEFT JOIN roles r ON r.id=u.role_id WHERE r.slug='student' OR u.role_id IS NULL");
                    }
                    ?>
                    <h3 class="text-2xl font-bold text-slate-800"><?= $studentsCount ?></h3>
                    <p class="text-sm text-slate-500 font-medium">Students</p>
                </div>
            </a>
        <?php endif; ?>

        <?php if (in_array('posts', $permissions)): ?>
            <a href="index.php?pages=posts" class="group relative bg-white rounded-xl border border-slate-200 p-6 hover:shadow-lg transition-all duration-200 hover:border-emerald-300 flex items-center gap-4">
                <div class="h-14 w-14 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-3xl group-hover:scale-110 transition-transform">
                    <i class='bx bxs-news'></i>
                </div>
                <div>
                    <h3 class="text-2xl font-bold text-slate-800"><?= safeCount($pdo, "SELECT COUNT(*) FROM posts") ?></h3>
                    <p class="text-sm text-slate-500 font-medium">Posts</p>
                </div>
            </a>
        <?php endif; ?>

        <?php if (in_array('comments', $permissions)): ?>
            <a href="index.php?pages=comments" class="group relative bg-white rounded-xl border border-slate-200 p-6 hover:shadow-lg transition-all duration-200 hover:border-slate-300 flex items-center gap-4">
                <div class="h-14 w-14 rounded-xl bg-slate-100 text-slate-600 flex items-center justify-center text-3xl group-hover:scale-110 transition-transform">
                    <i class='bx bxs-comment-detail'></i>
                </div>
                <div>
                    <h3 class="text-2xl font-bold text-slate-800"><?= safeCount($pdo, "SELECT COUNT(*) FROM comments WHERE status='pending'") ?></h3>
                    <p class="text-sm text-slate-500 font-medium">Pending Comments</p>
                </div>
            </a>
        <?php endif; ?>

        <?php if (in_array('payments', $permissions)): ?>
            <a href="index.php?pages=payments" class="group relative bg-white rounded-xl border border-slate-200 p-6 hover:shadow-lg transition-all duration-200 hover:border-yellow-300 flex items-center gap-4">
                <div class="h-14 w-14 rounded-xl bg-yellow-50 text-yellow-600 flex items-center justify-center text-3xl group-hover:scale-110 transition-transform">
                    <i class='bx bxs-credit-card'></i>
                </div>
                <div>
                    <h3 class="text-2xl font-bold text-slate-800"><?= safeCount($pdo, "SELECT COUNT(*) FROM payments WHERE status='pending'") ?></h3>
                    <p class="text-sm text-slate-500 font-medium">Pending Payments</p>
                </div>
            </a>
        <?php endif; ?>
        
        <?php if (in_array('settings', $permissions)): ?>
            <a href="index.php?pages=settings" class="group relative bg-white rounded-xl border border-slate-200 p-6 hover:shadow-lg transition-all duration-200 hover:border-slate-400 flex items-center gap-4">
                <div class="h-14 w-14 rounded-xl bg-slate-800 text-white flex items-center justify-center text-3xl group-hover:scale-110 transition-transform">
                    <i class='bx bxs-cog'></i>
                </div>
                <div>
                    <h3 class="text-xl font-bold text-slate-800">Settings</h3>
                    <p class="text-sm text-slate-500 font-medium">Manage Site</p>
                </div>
            </a>
        <?php endif; ?>
    </div>

    <!-- System Status -->
    <?php if (in_array('settings', $permissions)): ?>
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
        <div class="flex items-center gap-3 mb-4">
            <div class="h-10 w-10 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center">
                <i class='bx bxs-dashboard text-xl'></i>
            </div>
            <h2 class="text-lg font-bold text-slate-800">System Status</h2>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="p-4 rounded-xl bg-slate-50 border border-slate-100">
                <p class="text-xs text-slate-500 uppercase tracking-wider font-semibold">Database</p>
                <div class="flex items-center gap-2 mt-1">
                    <span class="h-2.5 w-2.5 rounded-full <?= $dbStatus['ok'] ? 'bg-emerald-500' : 'bg-rose-500' ?>"></span>
                    <span class="font-medium text-slate-700"><?= htmlspecialchars($dbStatus['message']) ?></span>
                </div>
            </div>
            <div class="p-4 rounded-xl bg-slate-50 border border-slate-100">
                <p class="text-xs text-slate-500 uppercase tracking-wider font-semibold">Website</p>
                <div class="flex items-center gap-2 mt-1">
                    <span class="h-2.5 w-2.5 rounded-full <?= $siteStatus['ok'] ? 'bg-emerald-500' : 'bg-rose-500' ?>"></span>
                    <span class="font-medium text-slate-700"><?= htmlspecialchars($siteStatus['message']) ?></span>
                </div>
            </div>
            <div class="p-4 rounded-xl bg-slate-50 border border-slate-100">
                <p class="text-xs text-slate-500 uppercase tracking-wider font-semibold">Admin Panel</p>
                <div class="flex items-center gap-2 mt-1">
                    <span class="h-2.5 w-2.5 rounded-full <?= $adminStatus['ok'] ? 'bg-emerald-500' : 'bg-rose-500' ?>"></span>
                    <span class="font-medium text-slate-700"><?= htmlspecialchars($adminStatus['message']) ?></span>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>
</body>
</html>

