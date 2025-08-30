<?php
require '../includes/auth.php'; // checks login + role
require '../includes/db.php';
require '../includes/functions.php'; // sendEmail()

// Only allow Admins and Sub‑Admins
if (!in_array($_SESSION['user']['role_slug'], ['admin','sub-admin'])) {
    header("Location: index.php");
    exit;
}

// Handle Approve / Banish actions
if (isset($_GET['action'], $_GET['id'])) {
    $id = (int) $_GET['id'];

    // Approve user
    if ($_GET['action'] === 'approve' && $_SESSION['user']['role_slug'] === 'admin') {
        $role_id = (int) $_POST['role_id']; // from form
        $stmt = $pdo->prepare("UPDATE users SET is_active=1, role_id=? WHERE id=?");
        $stmt->execute([$role_id, $id]);

        $user = $pdo->query("SELECT name,email FROM users WHERE id=$id")->fetch();
        sendEmail($user['email'], "Account Approved", "
            Hi {$user['name']},<br><br>
            Your account has been approved. You can now log in.
        ");
    }

    // Banish user
    if ($_GET['action'] === 'banish' && $_SESSION['user']['role_slug'] === 'admin') {
        $stmt = $pdo->prepare("UPDATE users SET is_active=2 WHERE id=?");
        $stmt->execute([$id]);

        $user = $pdo->query("SELECT name,email FROM users WHERE id=$id")->fetch();
        sendEmail($user['email'], "Account Banned", "
            Hi {$user['name']},<br><br>
            Your account has been banned. Contact support for details.
        ");
    }

    header("Location: users.php");
    exit;
}

// Fetch users
$pending_users = $pdo->query("SELECT * FROM users WHERE is_active=0")->fetchAll();
$active_users  = $pdo->query("SELECT * FROM users WHERE is_active=1")->fetchAll();
$banned_users  = $pdo->query("SELECT * FROM users WHERE is_active=2")->fetchAll();

// Helper to get badges
function getStatusBadge($status) {
    switch ($status) {
        case 0: return ['status-pending', 'Pending'];
        case 1: return ['status-active', 'Active'];
        case 2: return ['status-banned', 'Banned'];
    }
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

    <h2>Pending Approval</h2>
    <table>
        <tr>
            <th>Name</th><th>Email</th><th>Actions</th>
        </tr>
        <?php foreach ($pending_users as $u): ?>
        <tr>
            <td><?= htmlspecialchars($u['name']) ?></td>
            <td><?= htmlspecialchars($u['email']) ?></td>
            <td>
                <form method="post" action="users.php?action=approve&id=<?= $u['id'] ?>" style="display:inline;">
                    <select name="role_id" required>
                        <option value="">Assign Role</option>
                        <?php
                        $roles = $pdo->query("SELECT id,name FROM roles")->fetchAll();
                        foreach ($roles as $r) {
                            echo "<option value='{$r['id']}'>{$r['name']}</option>";
                        }
                        ?>
                    </select>
                    <button type="submit" class="btn-approve">Approve</button>
                </form>
                <a href="users.php?action=banish&id=<?= $u['id'] ?>" class="btn-banish">Banish</a>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>

    <h2>Active Users</h2>
    <table>
        <tr>
            <th>Name</th><th>Email</th><th>Role & Status</th>
        </tr>
        <?php foreach ($active_users as $u): ?>
        <?php list($statusClass, $statusText) = getStatusBadge($u['is_active']); ?>
        <?php list($roleClass, $roleText) = getRoleBadge($pdo, $u['role_id']); ?>
        <tr>
            <td><?= htmlspecialchars($u['name']) ?></td>
            <td><?= htmlspecialchars($u['email']) ?></td>
            <td>
                <span class="role-badge <?= $roleClass; ?>"><?= htmlspecialchars($roleText); ?></span>
                <span class="status-badge <?= $statusClass; ?>"><?= $statusText; ?></span>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>

    <h2>Banned Users</h2>
    <table>
        <tr>
            <th>Name</th><th>Email</th><th>Role & Status</th>
        </tr>
        <?php foreach ($banned_users as $u): ?>
        <?php list($statusClass, $statusText) = getStatusBadge($u['is_active']); ?>
        <?php list($roleClass, $roleText) = getRoleBadge($pdo, $u['role_id']); ?>
        <tr>
            <td><?= htmlspecialchars($u['name']) ?></td>
            <td><?= htmlspecialchars($u['email']) ?></td>
            <td>
                <span class="role-badge <?= $roleClass; ?>"><?= htmlspecialchars($roleText); ?></span>
                <span class="status-badge <?= $statusClass; ?>"><?= $statusText; ?></span>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
</div>

<?php include '../includes/footer.php'; ?>
</body>
</html>
