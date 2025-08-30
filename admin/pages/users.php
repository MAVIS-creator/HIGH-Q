<?php
// admin/pages/users.php
require_once '../includes/db.php';
require_once '../includes/csrf.php';
require_once '../includes/functions.php';

// Gate: only Admins and Sub-Admins can view this page
if (!in_array($_SESSION['user']['role_slug'], ['admin','sub-admin'])) {
    echo "<div class='container'><div class='error'>You do not have access to this page.</div></div>";
    return;
}

$flash = [];
$errors = [];

// Helpers
function logAudit(PDO $pdo, $action, $meta = []) {
    $stmt = $pdo->prepare("INSERT INTO audit_logs (user_id, action, ip, user_agent, meta) VALUES (?,?,?,?,JSON_OBJECT())");
    $ip  = $_SERVER['REMOTE_ADDR'] ?? '';
    $ua  = $_SERVER['HTTP_USER_AGENT'] ?? '';
    // We’ll store meta via JSON_SET for key-value pairs if provided
    $pdo->prepare("INSERT INTO audit_logs (user_id, action, ip, user_agent, meta)
                   VALUES (:uid,:act,:ip,:ua,:meta)")
        ->execute([
            ':uid'  => $_SESSION['user']['id'] ?? null,
            ':act'  => $action,
            ':ip'   => $ip,
            ':ua'   => $ua,
            ':meta' => json_encode($meta, JSON_UNESCAPED_UNICODE)
        ]);
}

function roleLimitOk(PDO $pdo, $role_id) {
    if (!$role_id) return true;
    $stmt = $pdo->prepare("SELECT max_count FROM roles WHERE id=?");
    $stmt->execute([$role_id]);
    $max = $stmt->fetchColumn();
    if ($max === null) return true; // no limit
    $stmt2 = $pdo->prepare("SELECT COUNT(*) FROM users WHERE role_id=? AND is_active=1");
    $stmt2->execute([$role_id]);
    $count = (int)$stmt2->fetchColumn();
    return $count < (int)$max;
}

function getRoles(PDO $pdo) {
    static $cache;
    if (!$cache) {
        $cache = $pdo->query("SELECT id, name, slug FROM roles ORDER BY id ASC")->fetchAll();
    }
    return $cache;
}

function fetchUsers(PDO $pdo, $status, $q = '', $roleId = '') {
    $sql = "SELECT u.*, r.name AS role_name, r.slug AS role_slug
            FROM users u
            LEFT JOIN roles r ON u.role_id = r.id
            WHERE u.is_active = :status";
    $params = [':status' => $status];

    if ($q !== '') {
        $sql .= " AND (u.name LIKE :q OR u.email LIKE :q)";
        $params[':q'] = "%{$q}%";
    }
    if ($roleId !== '' && $roleId !== 'all') {
        $sql .= " AND u.role_id = :rid";
        $params[':rid'] = (int)$roleId;
    }
    $sql .= " ORDER BY u.created_at DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function roleBadge($role_slug, $role_name) {
    if (!$role_slug) return "<span class='role-badge role-student'>Student</span>";
    $cls = 'role-' . htmlspecialchars(strtolower($role_slug));
    return "<span class='role-badge {$cls}'>" . htmlspecialchars($role_name) . "</span>";
}

function statusBadge($status) {
    switch ((int)$status) {
        case 0: return "<span class='status-badge status-pending'>Pending</span>";
        case 1: return "<span class='status-badge status-active'>Active</span>";
        case 2: return "<span class='status-badge status-banned'>Banned</span>";
    }
    return "";
}

// Actions (POST only, with CSRF)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action'])) {
    if (!verifyToken($_POST['csrf_token'] ?? '')) {
        $errors[] = "Invalid CSRF token.";
    } else {
        $action = $_GET['action'];
        $targetId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

        // Fetch target user
        $stmtU = $pdo->prepare("SELECT u.*, r.slug AS role_slug FROM users u LEFT JOIN roles r ON u.role_id=r.id WHERE u.id=?");
        $stmtU->execute([$targetId]);
        $target = $stmtU->fetch();

        if (!$target) {
            $errors[] = "User not found.";
        } else {
            // Only Admin can approve/ban/reactivate or change roles
            $isAdmin = ($_SESSION['user']['role_slug'] === 'admin');

            if ($action === 'approve') {
                if (!$isAdmin) {
                    $errors[] = "You do not have permission to approve.";
                } else {
                    $role_id = (int)($_POST['role_id'] ?? 0);
                    if (!$role_id) {
                        $errors[] = "Please select a role.";
                    } elseif (!roleLimitOk($pdo, $role_id)) {
                        $errors[] = "Role limit reached. Cannot assign this role.";
                    } else {
                        $stmt = $pdo->prepare("UPDATE users SET is_active=1, role_id=? WHERE id=?");
                        $stmt->execute([$role_id, $targetId]);

                        sendEmail($target['email'], "Account Approved", "
                            Hi {$target['name']},<br><br>
                            Your account has been approved. You can now log in.
                        ");
                        logAudit($pdo, "user_approved", ['target_id' => $targetId, 'role_id' => $role_id]);
                        $flash[] = "Approved {$target['name']} and assigned role.";
                    }
                }
            }

            if ($action === 'banish') {
                if (!$isAdmin) {
                    $errors[] = "You do not have permission to ban users.";
                } else {
                    $stmt = $pdo->prepare("UPDATE users SET is_active=2 WHERE id=?");
                    $stmt->execute([$targetId]);

                    sendEmail($target['email'], "Account Banned", "
                        Hi {$target['name']},<br><br>
                        Your account has been banned. Contact support for details.
                    ");
                    logAudit($pdo, "user_banned", ['target_id' => $targetId]);
                    $flash[] = "Banned {$target['name']}.";
                }
            }

            if ($action === 'reactivate') {
                if (!$isAdmin) {
                    $errors[] = "You do not have permission to reactivate users.";
                } else {
                    $stmt = $pdo->prepare("UPDATE users SET is_active=1 WHERE id=?");
                    $stmt->execute([$targetId]);

                    sendEmail($target['email'], "Account Reactivated", "
                        Hi {$target['name']},<br><br>
                        Your account has been reactivated. You can now log in.
                    ");
                    logAudit($pdo, "user_reactivated", ['target_id' => $targetId]);
                    $flash[] = "Reactivated {$target['name']}.";
                }
            }

            if ($action === 'edit') {
                // Admin can edit name/email/role; Sub-Admin can edit name/email only (not role)
                $new_name  = trim($_POST['name'] ?? '');
                $new_email = trim($_POST['email'] ?? '');
                $new_role  = isset($_POST['role_id']) ? (int)$_POST['role_id'] : $target['role_id'];

                if (!$new_name || !$new_email) {
                    $errors[] = "Name and Email are required.";
                } else {
                    try {
                        if ($isAdmin) {
                            if ($new_role && !roleLimitOk($pdo, $new_role) && (int)$target['role_id'] !== (int)$new_role) {
                                $errors[] = "Role limit reached. Cannot assign this role.";
                            } else {
                                $stmt = $pdo->prepare("UPDATE users SET name=?, email=?, role_id=? WHERE id=?");
                                $stmt->execute([$new_name, $new_email, $new_role ?: null, $targetId]);
                                $flash[] = "Updated user details.";
                                logAudit($pdo, "user_updated", ['target_id' => $targetId, 'role_id' => $new_role]);
                            }
                        } else {
                            // Sub-Admin path: no role change
                            $stmt = $pdo->prepare("UPDATE users SET name=?, email=? WHERE id=?");
                            $stmt->execute([$new_name, $new_email, $targetId]);
                            $flash[] = "Updated user details.";
                            logAudit($pdo, "user_updated_basic", ['target_id' => $targetId]);
                        }
                    } catch (PDOException $e) {
                        if (str_contains($e->getMessage(), 'Duplicate')) {
                            $errors[] = "Email is already in use.";
                        } else {
                            $errors[] = "Update failed.";
                        }
                    }
                }
            }
        }
    }
}

// Filters
$q = trim($_GET['q'] ?? '');
$roleFilter = $_GET['role'] ?? 'all';

// Fetch roles once for selects
$roles = getRoles($pdo);

// Fetch lists with filters (search/role filter applies to all)
$pending_users = fetchUsers($pdo, 0, $q, $roleFilter);
$active_users  = fetchUsers($pdo, 1, $q, $roleFilter);
$banned_users  = fetchUsers($pdo, 2, $q, $roleFilter);

$csrf = generateToken();
?>

<div class="container">
    <h1>User Management</h1>

    <?php if ($flash): ?>
        <div class="alert success">
            <?php foreach ($flash as $m) echo "<p>".htmlspecialchars($m)."</p>"; ?>
        </div>
    <?php endif; ?>
    <?php if ($errors): ?>
        <div class="alert error">
            <?php foreach ($errors as $e) echo "<p>".htmlspecialchars($e)."</p>"; ?>
        </div>
    <?php endif; ?>

    <form class="filter-bar" method="get" action="index.php">
        <input type="hidden" name="page" value="users">
        <input type="text" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="Search name or email...">
        <select name="role">
            <option value="all" <?= $roleFilter==='all'?'selected':''; ?>>All roles</option>
            <?php foreach ($roles as $r): ?>
                <option value="<?= $r['id'] ?>" <?= ($roleFilter==$r['id'] ? 'selected' : '') ?>>
                    <?= htmlspecialchars($r['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="btn-approve">Filter</button>
    </form>

    <h2>Pending Approval</h2>
    <table>
        <tr>
            <th>Name</th><th>Email</th><th>Assign Role & Actions</th>
        </tr>
        <?php if (!$pending_users): ?>
            <tr><td colspan="3">No pending users<?= $q ? ' for this search/filter' : '' ?>.</td></tr>
        <?php endif; ?>
        <?php foreach ($pending_users as $u): ?>
        <tr>
            <td><?= htmlspecialchars($u['name']) ?></td>
            <td><?= htmlspecialchars($u['email']) ?></td>
            <td>
                <?php if ($_SESSION['user']['role_slug'] === 'admin'): ?>
                <form method="post" action="index.php?page=users&action=approve&id=<?= (int)$u['id'] ?>" style="display:inline;">
                    <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                    <select name="role_id" required>
                        <option value="">Assign Role</option>
                        <?php foreach ($roles as $r) echo "<option value='{$r['id']}'>".htmlspecialchars($r['name'])."</option>"; ?>
                    </select>
                    <button type="submit" class="btn-approve">Approve</button>
                </form>
                <form method="post" action="index.php?page=users&action=banish&id=<?= (int)$u['id'] ?>" style="display:inline; margin-left:6px;">
                    <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                    <button type="submit" class="btn-banish">Banish</button>
                </form>
                <?php else: ?>
                    <em>Admin only</em>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>

    <h2>Active Users</h2>
    <table>
        <tr>
            <th>Name</th><th>Email</th><th>Role & Status</th><th>Actions</th>
        </tr>
        <?php if (!$active_users): ?>
            <tr><td colspan="4">No active users<?= $q ? ' for this search/filter' : '' ?>.</td></tr>
        <?php endif; ?>
        <?php foreach ($active_users as $u): ?>
        <tr>
            <td><?= htmlspecialchars($u['name']) ?></td>
            <td><?= htmlspecialchars($u['email']) ?></td>
            <td>
                <?= roleBadge($u['role_slug'] ?? '', $u['role_name'] ?? '') ?>
                <?= statusBadge($u['is_active']) ?>
            </td>
            <td>
                <!-- Edit button triggers modal -->
                <button class="btn-approve btn-edit"
                    data-id="<?= (int)$u['id'] ?>"
                    data-name="<?= htmlspecialchars($u['name']) ?>"
                    data-email="<?= htmlspecialchars($u['email']) ?>"
                    data-role="<?= (int)$u['role_id'] ?>">Edit</button>

                <?php if ($_SESSION['user']['role_slug'] === 'admin'): ?>
                <form method="post" action="index.php?page=users&action=banish&id=<?= (int)$u['id'] ?>" style="display:inline; margin-left:6px;">
                    <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                    <button type="submit" class="btn-banish">Banish</button>
                </form>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>

    <h2>Banned Users</h2>
    <table>
        <tr>
            <th>Name</th><th>Email</th><th>Role & Status</th><th>Actions</th>
        </tr>
        <?php if (!$banned_users): ?>
            <tr><td colspan="4">No banned users<?= $q ? ' for this search/filter' : '' ?>.</td></tr>
        <?php endif; ?>
        <?php foreach ($banned_users as $u): ?>
        <tr>
            <td><?= htmlspecialchars($u['name']) ?></td>
            <td><?= htmlspecialchars($u['email']) ?></td>
            <td>
                <?= roleBadge($u['role_slug'] ?? '', $u['role_name'] ?? '') ?>
                <?= statusBadge($u['is_active']) ?>
            </td>
            <td>
                <?php if ($_SESSION['user']['role_slug'] === 'admin'): ?>
                <form method="post" action="index.php?page=users&action=reactivate&id=<?= (int)$u['id'] ?>" style="display:inline;">
                    <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                    <button type="submit" class="btn-approve">Reactivate</button>
                </form>
                <?php else: ?>
                    <em>Admin only</em>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
</div>

<!-- Edit Modal -->
<div class="modal" id="editModal" aria-hidden="true">
  <div class="modal-content">
    <h3>Edit User</h3>
    <form method="post" action="index.php?page=users&action=edit" id="editForm">
        <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
        <input type="hidden" name="id" id="edit_id">
        <label>Name</label>
        <input type="text" name="name" id="edit_name" required>
        <label>Email</label>
        <input type="email" name="email" id="edit_email" required>

        <?php if ($_SESSION['user']['role_slug'] === 'admin'): ?>
        <label>Role</label>
        <select name="role_id" id="edit_role">
            <option value="">Student (no role)</option>
            <?php foreach ($roles as $r): ?>
                <option value="<?= $r['id'] ?>"><?= htmlspecialchars($r['name']) ?></option>
            <?php endforeach; ?>
        </select>
        <?php endif; ?>

        <div class="modal-actions">
            <button type="button" class="btn-banish" id="closeModal">Cancel</button>
            <button type="submit" class="btn-approve">Save Changes</button>
        </div>
    </form>
  </div>
</div>

<script>
// Edit modal logic
const modal = document.getElementById('editModal');
const closeModalBtn = document.getElementById('closeModal');
const editForm = document.getElementById('editForm');

document.querySelectorAll('.btn-edit').forEach(btn => {
    btn.addEventListener('click', () => {
        const id    = btn.getAttribute('data-id');
        const name  = btn.getAttribute('data-name');
        const email = btn.getAttribute('data-email');
        const role  = btn.getAttribute('data-role');

        document.getElementById('edit_id').value = id;
        document.getElementById('edit_name').value = name;
        document.getElementById('edit_email').value = email;
        const roleSelect = document.getElementById('edit_role');
        if (roleSelect) roleSelect.value = role || '';

        modal.classList.add('open');
        modal.setAttribute('aria-hidden', 'false');
    });
});

function closeModal() {
    modal.classList.remove('open');
    modal.setAttribute('aria-hidden', 'true');
}
closeModalBtn.addEventListener('click', closeModal);
modal.addEventListener('click', (e) => {
    if (e.target === modal) closeModal();
});
</script>
