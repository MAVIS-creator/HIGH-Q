<?php
require '../includes/auth.php';
require '../includes/db.php';
require '../includes/functions.php';
require '../includes/csrf.php';

// Only allow Admins and Sub‑Admins
if (!in_array($_SESSION['user']['role_slug'], ['admin','sub-admin'])) {
    header("Location: index.php");
    exit;
}

// Handle actions
if (isset($_GET['action'], $_GET['id'])) {
    $id = (int) $_GET['id'];

    // Approve
    if ($_GET['action'] === 'approve' && $_SESSION['user']['role_slug'] === 'admin') {
        if (!verifyToken($_POST['csrf_token'])) die("Invalid CSRF token");
        $role_id = (int) $_POST['role_id'];
        $pdo->prepare("UPDATE users SET is_active=1, role_id=? WHERE id=?")->execute([$role_id, $id]);
        $user = $pdo->query("SELECT name,email FROM users WHERE id=$id")->fetch();
        sendEmail($user['email'], "Account Approved", "Hi {$user['name']},<br>Your account has been approved.");
        logAction($pdo, $_SESSION['user']['id'], "Approved user", ['user_id'=>$id]);
    }

    // Banish
    if ($_GET['action'] === 'banish' && $_SESSION['user']['role_slug'] === 'admin') {
        if (!verifyToken($_POST['csrf_token'])) die("Invalid CSRF token");
        $pdo->prepare("UPDATE users SET is_active=2 WHERE id=?")->execute([$id]);
        $user = $pdo->query("SELECT name,email FROM users WHERE id=$id")->fetch();
        sendEmail($user['email'], "Account Banned", "Hi {$user['name']},<br>Your account has been banned.");
        logAction($pdo, $_SESSION['user']['id'], "Banned user", ['user_id'=>$id]);
    }

    // Reactivate
    if ($_GET['action'] === 'reactivate' && $_SESSION['user']['role_slug'] === 'admin') {
        if (!verifyToken($_POST['csrf_token'])) die("Invalid CSRF token");
        $pdo->prepare("UPDATE users SET is_active=1 WHERE id=?")->execute([$id]);
        $user = $pdo->query("SELECT name,email FROM users WHERE id=$id")->fetch();
        sendEmail($user['email'], "Account Reactivated", "Hi {$user['name']},<br>Your account has been reactivated.");
        logAction($pdo, $_SESSION['user']['id'], "Reactivated user", ['user_id'=>$id]);
    }

    // Edit user
    if ($_GET['action'] === 'edit' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!verifyToken($_POST['csrf_token'])) die("Invalid CSRF token");
        $name = trim($_POST['name']);
        $email = trim($_POST['email']);
        $role_id = (int) $_POST['role_id'];
        $status = (int) $_POST['is_active'];
        $pdo->prepare("UPDATE users SET name=?, email=?, role_id=?, is_active=? WHERE id=?")
            ->execute([$name, $email, $role_id, $status, $id]);
        logAction($pdo, $_SESSION['user']['id'], "Edited user", ['user_id'=>$id]);
    }

    header("Location: index.php?page=users");
    exit;
}

// Search/filter
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$where = $search ? "WHERE name LIKE :search OR email LIKE :search" : '';
$params = $search ? [':search' => "%$search%"] : [];

$pending_users = $pdo->prepare("SELECT * FROM users WHERE is_active=0");
$pending_users->execute();
$pending_users = $pending_users->fetchAll();

$active_users = $pdo->prepare("SELECT * FROM users WHERE is_active=1");
$active_users->execute();
$active_users = $active_users->fetchAll();

$banned_users = $pdo->prepare("SELECT * FROM users WHERE is_active=2");
$banned_users->execute();
$banned_users = $banned_users->fetchAll();

// Badge helpers
function getStatusBadge($status) {
    return match($status) {
        0 => ['status-pending', 'Pending'],
        1 => ['status-active', 'Active'],
        2 => ['status-banned', 'Banned'],
    };
}
function getRoleBadge($pdo, $role_id) {
    if (!$role_id) return ['role-student', 'Student'];
    $role = $pdo->query("SELECT name, slug FROM roles WHERE id={$role_id}")->fetch();
    return ['role-' . strtolower($role['slug']), $role['name']];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>User Management - HIGH Q SOLID ACADEMY</title>
<link rel="stylesheet" href="../public/assets/css/admin.css">
</head>
<body>
<?php include '../includes/header.php'; ?>

<div class="container">
    <h1>User Management</h1>

    <!-- Search -->
    <form method="get" action="index.php">
        <input type="hidden" name="page" value="users">
        <input type="text" name="search" placeholder="Search name or email..." value="<?= htmlspecialchars($search) ?>">
        <button type="submit">Search</button>
    </form>

    <!-- Pending -->
    <h2>Pending Approval</h2>
    <table>
        <tr><th>Name</th><th>Email</th><th>Actions</th></tr>
        <?php foreach ($pending_users as $u): ?>
        <tr>
            <td><?= htmlspecialchars($u['name']) ?></td>
            <td><?= htmlspecialchars($u['email']) ?></td>
            <td>
                <form method="post" action="index.php?page=users&action=approve&id=<?= $u['id'] ?>" style="display:inline;">
                    <input type="hidden" name="csrf_token" value="<?= generateToken(); ?>">
                    <select name="role_id" required>
                        <option value="">Assign Role</option>
                        <?php foreach ($pdo->query("SELECT id,name FROM roles") as $r): ?>
                            <option value="<?= $r['id'] ?>"><?= $r['name'] ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" class="btn-approve">Approve</button>
                </form>
                <form method="post" action="index.php?page=users&action=banish&id=<?= $u['id'] ?>" style="display:inline;">
                    <input type="hidden" name="csrf_token" value="<?= generateToken(); ?>">
                    <button type="submit" class="btn-banish">Banish</button>
                </form>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>

    <!-- Active -->
    <h2>Active Users</h2>
    <table>
        <tr><th>Name</th><th>Email</th><th>Role & Status</th><th>Actions</th></tr>
        <?php foreach ($active_users as $u): ?>
        <?php [$statusClass, $statusText] = getStatusBadge($u['is_active']); ?>
        <?php [$roleClass, $roleText] = getRoleBadge($pdo, $u['role_id']); ?>
        <tr>
            <td><?= htmlspecialchars($u['name']) ?></td>
            <td><?= htmlspecialchars($u['email']) ?></td>
            <td>
                <span class="role-badge <?= $roleClass; ?>"><?= htmlspecialchars($roleText); ?></span>
                <span class="status-badge <?= $statusClass; ?>"><?= $statusText; ?></span>
            </td>
            <td>
                <!-- Edit -->
                <form method="post" action="index.php?page=users&action=edit&id=<?= $u['id'] ?>" style="display:inline;">
                    <input type="hidden" name="csrf_token" value="<?= generateToken(); ?>">
                    <input type="text" name="name" value="<?= htmlspecialchars($u['name']) ?>" required>
                    <input type="email" name="email" value="<?= htmlspecialchars($u['email']) ?>" required>
                    <select name="role_id">
                        <?php foreach ($pdo->query("SELECT id,name FROM roles") as $r): ?>
                            <option value="<?= $r['id'] ?>" <?= $u['role_id']==$r['id']?'selected':'' ?>><?= $r['name'] ?></option>
                        <?php endforeach; ?>
                    </select>
                    <select name="is_active">
                        <option value="1" <?= $u['is_active']==1?'selected':'' ?>>Active</option>
                        <option value="0" <?= $u['is_active']==0?'selected':'' ?>>Pending</option>
                        <option value="2" <?=