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

$csrf = generateToken();

// Actions
if (isset($_GET['action'], $_GET['id'])) {
    $id = (int) $_GET['id'];

    // Approve (Admin only)
    if ($_GET['action'] === 'approve' && $_SESSION['user']['role_slug'] === 'admin') {
        if (!verifyToken($_POST['csrf_token'])) die("Invalid CSRF token");
        $role_id = (int) $_POST['role_id'];
        $pdo->prepare("UPDATE users SET is_active=1, role_id=? WHERE id=?")->execute([$role_id, $id]);
        $user = $pdo->query("SELECT name,email FROM users WHERE id=$id")->fetch();
        sendEmail($user['email'], "Account Approved", "Hi {$user['name']},<br>Your account has been approved.");
        logAction($pdo, $_SESSION['user']['id'], "Approved user", ['user_id'=>$id]);
    }

    // Banish (Admin only)
    if ($_GET['action'] === 'banish' && $_SESSION['user']['role_slug'] === 'admin') {
        if (!verifyToken($_POST['csrf_token'])) die("Invalid CSRF token");
        $pdo->prepare("UPDATE users SET is_active=2 WHERE id=?")->execute([$id]);
        $user = $pdo->query("SELECT name,email FROM users WHERE id=$id")->fetch();
        sendEmail($user['email'], "Account Banned", "Hi {$user['name']},<br>Your account has been banned.");
        logAction($pdo, $_SESSION['user']['id'], "Banned user", ['user_id'=>$id]);
    }

    // Reactivate (Admin only)
    if ($_GET['action'] === 'reactivate' && $_SESSION['user']['role_slug'] === 'admin') {
        if (!verifyToken($_POST['csrf_token'])) die("Invalid CSRF token");
        $pdo->prepare("UPDATE users SET is_active=1 WHERE id=?")->execute([$id]);
        $user = $pdo->query("SELECT name,email FROM users WHERE id=$id")->fetch();
        sendEmail($user['email'], "Account Reactivated", "Hi {$user['name']},<br>Your account has been reactivated.");
        logAction($pdo, $_SESSION['user']['id'], "Reactivated user", ['user_id'=>$id]);
    }

    // Edit (Admin + Sub-Admin can edit, but only Admin can change status to banned or assign Admin role)
    if ($_GET['action'] === 'edit' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!verifyToken($_POST['csrf_token'])) die("Invalid CSRF token");

        $name    = trim($_POST['name']);
        $email   = trim($_POST['email']);
        $role_id = (int) $_POST['role_id'];
        $status  = (int) $_POST['is_active'];

        // Safety gates: prevent non-admins from escalating roles or banning
        if ($_SESSION['user']['role_slug'] !== 'admin') {
            // Force non-admins to keep non-admin roles and not ban
            $status = $status === 2 ? 1 : $status;
            // Prevent assigning admin role
            $isAdminRole = (bool)$pdo->query("SELECT 1 FROM roles WHERE id={$role_id} AND slug='admin'")->fetchColumn();
            if ($isAdminRole) {
                // fallback to existing role
                $role_id = (int)$pdo->query("SELECT role_id FROM users WHERE id={$id}")->fetchColumn();
            }
        }

        $stmt = $pdo->prepare("UPDATE users SET name=?, email=?, role_id=?, is_active=? WHERE id=?");
        $stmt->execute([$name, $email, $role_id, $status, $id]);

        logAction($pdo, $_SESSION['user']['id'], "Edited user", ['user_id'=>$id]);
    }

    header("Location: index.php?page=users");
    exit;
}

// AJAX: View profile
if (isset($_GET['action']) && $_GET['action'] === 'view' && isset($_GET['id'])) {
    if (!in_array($_SESSION['user']['role_slug'], ['admin','sub-admin','moderator'])) {
        http_response_code(403);
        echo json_encode(['error' => 'Forbidden']);
        exit;
    }
    header('Content-Type: application/json');
    $id = (int) $_GET['id'];

    $stmt = $pdo->prepare("SELECT u.id, u.name, u.email, u.avatar, u.is_active, u.last_login, u.created_at, u.updated_at,
                                  r.name AS role_name, r.slug AS role_slug, r.id AS role_id
                           FROM users u
                           LEFT JOIN roles r ON r.id = u.role_id
                           WHERE u.id = ?");
    $stmt->execute([$id]);
    $user = $stmt->fetch();

    if (!$user) {
        echo json_encode(['error' => 'User not found']);
        exit;
    }

    $posts_count    = (int)$pdo->query("SELECT COUNT(*) FROM posts WHERE author_id = {$id}")->fetchColumn();
    $comments_count = (int)$pdo->query("SELECT COUNT(*) FROM comments WHERE user_id = {$id}")->fetchColumn();
    $status_map     = [0 => 'Pending', 1 => 'Active', 2 => 'Banned'];

    echo json_encode([
        'id'             => (int)$user['id'],
        'name'           => $user['name'],
        'email'          => $user['email'],
        'avatar'         => $user['avatar'],
        'role_id'        => $user['role_id'] ?? null,
        'role_name'      => $user['role_name'] ?? 'Student',
        'role_slug'      => $user['role_slug'] ?? 'student',
        'status'         => $status_map[(int)$user['is_active']] ?? 'Unknown',
        'status_value'   => (int)$user['is_active'],
        'last_login'     => $user['last_login'],
        'created_at'     => $user['created_at'],
        'updated_at'     => $user['updated_at'],
        'posts_count'    => $posts_count,
        'comments_count' => $comments_count
    ]);
    exit;
}

// Data
$pending_users = $pdo->query("SELECT * FROM users WHERE is_active=0 ORDER BY created_at DESC")->fetchAll();
$active_users  = $pdo->query("SELECT * FROM users WHERE is_active=1 ORDER BY created_at DESC")->fetchAll();
$banned_users  = $pdo->query("SELECT * FROM users WHERE is_active=2 ORDER BY created_at DESC")->fetchAll();
$all_roles     = $pdo->query("SELECT id,name,slug FROM roles ORDER BY id ASC")->fetchAll();

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
<link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
</head>
<body>
<?php include '../includes/header.php'; ?>
<?php include '../includes/sidebar.php'; ?>

<div class="container" style="margin-left:240px;">
    <h1>User Management</h1>

    <!-- Pending -->
    <h2>Pending Approval</h2>
    <table>
        <tr><th>Name</th><th>Email</th><th>Actions</th></tr>
        <?php foreach ($pending_users as $u): ?>
        <tr>
            <td><?= htmlspecialchars($u['name']) ?></td>
            <td><?= htmlspecialchars($u['email']) ?></td>
            <td>
                <?php if ($_SESSION['user']['role_slug'] === 'admin'): ?>
                    <form method="post" action="index.php?page=users&action=approve&id=<?= $u['id'] ?>" style="display:inline;">
                        <input type="hidden" name="csrf_token" value="<?= $csrf; ?>">
                        <select name="role_id" required>
                            <option value="">Assign Role</option>
                            <?php foreach ($all_roles as $r): ?>
                                <option value="<?= $r['id'] ?>"><?= htmlspecialchars($r['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" class="btn-approve">Approve</button>
                    </form>
                    <form method="post" action="index.php?page=users&action=banish&id=<?= $u['id'] ?>" style="display:inline;">
                        <input type="hidden" name="csrf_token" value="<?= $csrf; ?>">
                        <button type="submit" class="btn-banish">Banish</button>
                    </form>
                <?php endif; ?>
                <a href="#" class="btn-view" data-user-id="<?= $u['id'] ?>">View</a>
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
                <a href="#" class="btn-view" data-user-id="<?= $u['id'] ?>">View</a>
                <a href="#" class="btn-edit" data-user-id="<?= $u['id'] ?>">Edit</a>
                <?php if ($_SESSION['user']['role_slug'] === 'admin'): ?>
                    <form method="post" action="index.php?page=users&action=banish&id=<?= $u['id'] ?>" style="display:inline;">
                        <input type="hidden" name="csrf_token" value="<?= $csrf; ?>">
                        <button type="submit" class="btn-banish">Banish</button>
                    </form>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>

    <!-- Banned -->
    <h2>Banned Users</h2>
    <table>
        <tr><th>Name</th><th>Email</th><th>Role & Status</th><th>Actions</th></tr>
        <?php foreach ($banned_users as $u): ?>
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
                <a href="#" class="btn-view" data-user-id="<?= $u['id'] ?>">View</a>
                <?php if ($_SESSION['user']['role_slug'] === 'admin'): ?>
                    <form method="post" action="index.php?page=users&action=reactivate&id=<?= $u['id'] ?>" style="display:inline;">
                        <input type="hidden" name="csrf_token" value="<?= $csrf; ?>">
                        <button type="submit" class="btn-approve">Reactivate</button>
                    </form>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
</div>

<!-- Unified View/Edit Modal -->
<div class="modal" id="userModal">
  <div class="modal-content">
    <span class="modal-close" id="userModalClose"><i class='bx bx-x'></i></span>

    <div class="modal-tabs">
      <button class="tab-btn active" data-tab="viewTab">Profile</button>
      <button class="tab-btn" data-tab="editTab">Edit</button>
    </div>

    <!-- View Tab -->
    <div id="viewTab" class="tab-pane active">
      <div class="profile-header">
        <img id="mAvatar" src="../public/assets/images/avatar-placeholder.png" alt="Avatar">
        <div>
          <h3 id="mName">Name</h3>
          <p id="mRole" class="role-badge role-student">Role</p>
          <p id="mStatus" class="status-badge status-pending">Status</p>
        </div>
      </div>
      <div class="profile-grid">
        <div><span class="label">Email:</span> <span id="mEmail"></span></div>
        <div><span class="label">Last Login:</span> <span id="mLastLogin"></span></div>
        <div><span class="label">Created:</span> <span id="mCreated"></span></div>
        <div><span class="label">Updated:</span> <span id="mUpdated"></span></div>
        <div><span class="label">Posts:</span> <span id="mPosts"></span></div>
        <div><span class="label">Comments:</span> <span id="mComments"></span></div>
      </div>
    </div>

    <!-- Edit Tab -->
    <div id="editTab" class="tab-pane">
      <form id="editForm" method="post">
        <input type="hidden" name="csrf_token" value="<?= $csrf; ?>">
        <div class="form-row">
          <label>Name</label>
          <input type="text" name="name" id="fName" required>
        </div>
        <div class="form-row">
          <label>Email</label>
          <input type="email" name="email" id="fEmail" required>
        </div>
        <div class="form-row">
          <label>Role</label>
          <select name="role_id" id="fRole">
            <?php foreach ($all_roles as $r): ?>
              <option value="<?= $r['id'] ?>"><?= htmlspecialchars($r['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-row">
          <label>Status</label>
          <select name="is_active" id="fStatus">
            <option value="1">Active</option>
            <option value="0">Pending</option>
            <option value="2">Banned</option>
          </select>
        </div>
        <div class="form-actions">
          <button type="submit" class="btn-approve">Save Changes</button>
        </div>
      </form>
    </div>

  </div>
</div>
<div id="modalOverlay"></div>

<?php include '../includes/footer.php'; ?>

<script>
// Open/close modal
const userModal = document.getElementById('userModal');
const overlay   = document.getElementById('modalOverlay');
const closeBtn  = document.getElementById('userModalClose');
function openModal(){ userModal.classList.add('open'); overlay.classList.add('open'); }
function closeModal(){ userModal.classList.remove('open'); overlay.classList.remove('open'); }
overlay.addEventListener('click', closeModal);
closeBtn.addEventListener('click', closeModal);
document.addEventListener('keydown', (e)=>{ if(e.key==='Escape') closeModal(); });

// Tabs switch
const tabButtons = document.querySelectorAll('.tab-btn');
const tabPanes   = document.querySelectorAll('.tab-pane');
function activateTab(id){
  tabButtons.forEach(b=>b.classList.toggle('active', b.dataset.tab===id));
  tabPanes.forEach(p=>p.classList.toggle('active', p.id===id));
}

// Populate modal (view/edit)
async function loadUser(id, mode='view'){
  const res = await fetch(`index.php?page=users&action=view&id=${encodeURIComponent(id)}`);
  const data = await res.json();
  if (data.error) return alert(data.error);

  // View fields
  document.getElementById('mName').textContent = data.name;
  document.getElementById('mEmail').textContent = data.email;
  document.getElementById('mLastLogin').textContent = data.last_login ?? '—';
  document.getElementById('mCreated').textContent = data.created_at ?? '—';
  document.getElementById('mUpdated').textContent = data.updated_at ?? '—';
  document.getElementById('mPosts').textContent = data.posts_count;
  document.getElementById('mComments').textContent = data.comments_count;

  const avatarEl = document.getElementById('mAvatar');
  avatarEl.src = data.avatar ? (data.avatar.startsWith('http') ? data.avatar : `../${data.avatar}`) : "../public/assets/images/avatar-placeholder.png";

  const roleEl = document.getElementById('mRole');
  roleEl.textContent = data.role_name;
  roleEl.className = `role-badge role-${data.role_slug || 'student'}`;

  const statusEl = document.getElementById('mStatus');
  const statusClass = data.status_value === 1 ? 'status-active' : (data.status_value === 0 ? 'status-pending' : 'status-banned');
  statusEl.textContent = data.status;
  statusEl.className = `status-badge ${statusClass}`;

  // Edit fields
  const form = document.getElementById('editForm');
  form.action = `index.php?page=users&action=edit&id=${encodeURIComponent(data.id)}`;
  document.getElementById('fName').value   = data.name;
  document.getElementById('fEmail').value  = data.email;
  document.getElementById('fRole').value   = data.role_id ?? '';
  document.getElementById('fStatus').value = data.status_value;

  // Switch tab based on mode
  activateTab(mode === 'edit' ? 'editTab' : 'viewTab');
  openModal();
}

// Button handlers
document.querySelectorAll('.btn-view').forEach(btn=>{
  btn.addEventListener('click', (e)=>{
    e.preventDefault();
    const id = btn.dataset.userId;
    loadUser(id, 'view');
  });
});
document.querySelectorAll('.btn-edit').forEach(btn=>{
  btn.addEventListener('click', (e)=>{
    e.preventDefault();
    const id = btn.dataset.userId;
    loadUser(id, 'edit');
  });
});
</script>
</body>
</html>
