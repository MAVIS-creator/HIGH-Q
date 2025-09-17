<?php
// admin/pages/users.php
require_once '../includes/auth.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/csrf.php';

// Only Admins & Sub-Admins
if (!in_array($_SESSION['user']['role_slug'], ['admin','sub-admin'])) {
    header("Location: index.php");
    exit;
}

// Generate CSRF token
$csrf = generateToken('users_form');

// ACTION HANDLING
if (isset($_GET['action'], $_GET['id'])) {
    $id = (int) $_GET['id'];
    $action = $_GET['action'];

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !verifyToken('users_form', $_POST['csrf_token'] ?? '')) {
        die("Invalid CSRF token.");
    }

    switch ($action) {
        case 'approve':
            if ($_SESSION['user']['role_slug'] !== 'admin') break;
            $role_id = (int) $_POST['role_id'];
            // enforce max_count
            $max_count = $pdo->prepare("SELECT max_count FROM roles WHERE id=?");
            $max_count->execute([$role_id]);
            $max = $max_count->fetchColumn();
            if ($max && $max > 0) {
                $current_count = $pdo->prepare("SELECT COUNT(*) FROM users WHERE role_id=? AND is_active=1");
                $current_count->execute([$role_id]);
                if ($current_count->fetchColumn() >= $max) {
                    die("Maximum number of users for this role reached.");
                }
            }
            $stmt = $pdo->prepare("UPDATE users SET is_active=1, role_id=? WHERE id=?");
            $stmt->execute([$role_id, $id]);
            break;

        case 'banish':
            if ($_SESSION['user']['role_slug'] !== 'admin') break;
            if ($id == $_SESSION['user']['id']) break; // prevent self
            if ($id == 1) break; // prevent banishing main admin
            $stmt = $pdo->prepare("UPDATE users SET is_active=2 WHERE id=?");
            $stmt->execute([$id]);
            break;

        case 'reactivate':
            if ($_SESSION['user']['role_slug'] !== 'admin') break;
            $stmt = $pdo->prepare("UPDATE users SET is_active=1 WHERE id=?");
            $stmt->execute([$id]);
            break;

        case 'edit':
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $name    = trim($_POST['name']);
                $email   = trim($_POST['email']);
                $role_id = (int) $_POST['role_id'];
                $status  = (int) $_POST['is_active'];

                if ($id == 1 && $_SESSION['user']['id'] != 1) break; // main admin locked
                if ($id == $_SESSION['user']['id'] && $status == 2) $status = 1; // cannot self-banish

                if ($_SESSION['user']['role_slug'] !== 'admin') {
                    $status = $status === 2 ? 1 : $status;
                    $isAdminRole = $pdo->prepare("SELECT slug FROM roles WHERE id=?");
                    $isAdminRole->execute([$role_id]);
                    if ($isAdminRole->fetchColumn() === 'admin') {
                        $role_id = $pdo->prepare("SELECT role_id FROM users WHERE id=?");
                        $role_id->execute([$id]);
                        $role_id = $role_id->fetchColumn();
                    }
                }

                $stmt = $pdo->prepare("UPDATE users SET name=?, email=?, role_id=?, is_active=? WHERE id=?");
                $stmt->execute([$name, $email, $role_id, $status, $id]);
            }
            break;
    }

    header("Location: index.php?pages=users");
    exit;
}

// Fetch all roles
$all_roles = $pdo->query("SELECT id,name,slug FROM roles")->fetchAll(PDO::FETCH_ASSOC);

// Fetch all users with roles
$users = $pdo->query("
    SELECT u.*, r.name AS role_name, r.slug AS role_slug
    FROM users u
    LEFT JOIN roles r ON r.id = u.role_id
    ORDER BY u.created_at DESC
")->fetchAll(PDO::FETCH_ASSOC);

$total_users = count($users);
$active_users = count(array_filter($users, fn($u)=>$u['is_active']==1));
$pending_users = count(array_filter($users, fn($u)=>$u['is_active']==0));
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>User Management - HIGH Q SOLID ACADEMY</title>
<link rel="stylesheet" href="../assets/css/users.css">
<link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
<style>
body { font-family:sans-serif; background:#fafafa; }
.container { margin-left:240px; padding:20px; }
h1 { margin-bottom:20px; }

.dashboard-summary { display:flex; gap:1rem; margin-bottom:1rem; }
.dashboard-summary .card {
  flex:1; background:#fff; padding:1rem; border-radius:8px;
  box-shadow:0 2px 5px rgba(0,0,0,0.05); text-align:center;
}
.dashboard-summary h2 { margin:0; font-size:1.5rem; }

.filters { display:flex; gap:1rem; margin-bottom:1rem; }
.filters input, .filters select {
  padding:6px; border:1px solid #ccc; border-radius:4px;
}

.user-cards { display:flex; flex-direction:column; gap:1rem; }
.user-card {
  display:flex; justify-content:space-between; align-items:center;
  background:#fff; padding:1rem; border-radius:8px;
  box-shadow:0 2px 5px rgba(0,0,0,0.05);
}
.user-info { display:flex; gap:1rem; align-items:center; }
.avatar { width:50px; height:50px; border-radius:50%; background:#eee; }
.role, .status { display:inline-block; padding:2px 6px; border-radius:4px; font-size:12px; margin-right:5px; }
.role.admin { background:gold; }
.role.sub-admin { background:black; color:white; }
.role.student { background:#ddd; }
.status.active { background:lightgreen; }
.status.pending { background:orange; }
.status.banned { background:red; color:white; }
.actions form { display:inline-block; margin-left:5px; }
.actions .btn { padding:6px 12px; border:none; border-radius:4px; cursor:pointer; }
.actions .btn.danger { background:red; color:#fff; }
.actions .btn.approve { background:green; color:#fff; }
</style>
</head>
<body>
<?php include '../includes/header.php'; ?>
<?php include '../includes/sidebar.php'; ?>

<div class="container">
    <h1>User Management</h1>

    <!-- Dashboard summary -->
    <div class="dashboard-summary">
        <div class="card">Total Users <h2><?= $total_users ?></h2></div>
        <div class="card">Active Users <h2><?= $active_users ?></h2></div>
        <div class="card">Pending Approval <h2><?= $pending_users ?></h2></div>
    </div>

    <!-- Filters -->
    <div class="filters">
        <input type="text" placeholder="Search users..." id="searchBox">
        <select id="statusFilter">
            <option value="">All Status</option>
            <option value="1">Active</option>
            <option value="0">Pending</option>
            <option value="2">Banned</option>
        </select>
        <select id="roleFilter">
            <option value="">All Roles</option>
            <?php foreach ($all_roles as $r): ?>
                <option value="<?= $r['slug'] ?>"><?= htmlspecialchars($r['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <!-- User cards -->
    <div class="user-cards" id="userCards">
    <?php foreach ($users as $u): ?>
        <div class="user-card" 
             data-name="<?= strtolower($u['name']) ?>" 
             data-email="<?= strtolower($u['email']) ?>"
             data-status="<?= $u['is_active'] ?>" 
             data-role="<?= $u['role_slug'] ?>">
            <div class="user-info">
                <img src="<?= $u['avatar'] ?: '../public/assets/images/avatar-placeholder.png' ?>" class="avatar">
                <div>
                    <h3><?= htmlspecialchars($u['name']) ?></h3>
                    <p><?= htmlspecialchars($u['email']) ?></p>
                    <span class="role <?= $u['role_slug'] ?>"><?= htmlspecialchars($u['role_name']) ?></span>
                    <?php if ($u['id']==1): ?>
                        <span class="status" style="background:gold;">Main Admin</span>
                    <?php endif; ?>
                    <span class="status <?= $u['is_active']==1?'active':($u['is_active']==0?'pending':'banned') ?>">
                        <?= $u['is_active']==1?'Active':($u['is_active']==0?'Pending':'Banned') ?>
                    </span>
                </div>
            </div>
            <div class="actions">
                <!-- Approve -->
                <?php if ($u['is_active']==0 && $_SESSION['user']['role_slug']==='admin'): ?>
                    <form method="post" action="index.php?pages=users&action=approve&id=<?= $u['id'] ?>">
                        <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                        <select name="role_id" required>
                            <option value="">Assign Role</option>
                            <?php foreach ($all_roles as $r): ?>
                                <option value="<?= $r['id'] ?>"><?= htmlspecialchars($r['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" class="btn approve">Approve</button>
                    </form>
                <?php endif; ?>

                <!-- Banish -->
                <?php if ($u['is_active']==1 && $_SESSION['user']['role_slug']==='admin' && $u['id']!=1 && $u['id']!=$_SESSION['user']['id']): ?>
                    <form method="post" action="index.php?pages=users&action=banish&id=<?= $u['id'] ?>">
                        <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                        <button type="submit" class="btn danger">Deactivate</button>
                    </form>
                <?php endif; ?>

                <!-- Reactivate -->
                <?php if ($u['is_active']==2 && $_SESSION['user']['role_slug']==='admin'): ?>
                    <form method="post" action="index.php?pages=users&action=reactivate&id=<?= $u['id'] ?>">
                        <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                        <button type="submit" class="btn approve">Reactivate</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>
    </div>
</div>

<script>
// simple search/filter
const searchBox = document.getElementById('searchBox');
const statusFilter = document.getElementById('statusFilter');
const roleFilter = document.getElementById('roleFilter');
const cards = document.querySelectorAll('.user-card');

function filterUsers(){
    const term = searchBox.value.toLowerCase();
    const status = statusFilter.value;
    const role = roleFilter.value;
    cards.forEach(c=>{
        const name = c.dataset.name;
        const email = c.dataset.email;
        const st = c.dataset.status;
        const rl = c.dataset.role;
        let show = true;
        if(term && !name.includes(term) && !email.includes(term)) show=false;
        if(status && st!==status) show=false;
        if(role && rl!==role) show=false;
        c.style.display = show?'flex':'none';
    });
}

[searchBox, statusFilter, roleFilter].forEach(el=>el.addEventListener('input', filterUsers));
</script>

<?php include '../includes/footer.php'; ?>
</body>
</html>
