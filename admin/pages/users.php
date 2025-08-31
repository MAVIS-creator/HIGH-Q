<?php
// admin/pages/users.php

require '../includes/auth.php';
require '../admin/includes/db.php';
require './includes/functions.php';
require './includes/csrf.php';

// Only Admins & Sub-Admins
if (!in_array($_SESSION['user']['role_slug'], ['admin','sub-admin'])) {
    header("Location: index.php");
    exit;
}

// Generate CSRF token
$csrf = generateToken('users_form');

// Action handling
if (isset($_GET['action'], $_GET['id'])) {
    $id = (int) $_GET['id'];
    $action = $_GET['action'];

    // CSRF check for POST actions
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !verifyToken('users_form', $_POST['csrf_token'] ?? '')) {
        die("Invalid CSRF token.");
    }

    switch ($action) {
        case 'approve':
            if ($_SESSION['user']['role_slug'] !== 'admin') break;
            $role_id = (int) $_POST['role_id'];
            $stmt = $pdo->prepare("UPDATE users SET is_active=1, role_id=? WHERE id=?");
            $stmt->execute([$role_id, $id]);

            $user = $pdo->prepare("SELECT name,email FROM users WHERE id=?");
            $user->execute([$id]);
            $user = $user->fetch();

            sendEmail($user['email'], "Account Approved", "Hi {$user['name']},<br>Your account has been approved.");
            logAction($pdo, $_SESSION['user']['id'], "Approved user", ['user_id'=>$id]);
            break;

        case 'banish':
            if ($_SESSION['user']['role_slug'] !== 'admin') break;
            $stmt = $pdo->prepare("UPDATE users SET is_active=2 WHERE id=?");
            $stmt->execute([$id]);

            $user = $pdo->prepare("SELECT name,email FROM users WHERE id=?");
            $user->execute([$id]);
            $user = $user->fetch();

            sendEmail($user['email'], "Account Banned", "Hi {$user['name']},<br>Your account has been banned.");
            logAction($pdo, $_SESSION['user']['id'], "Banned user", ['user_id'=>$id]);
            break;

        case 'reactivate':
            if ($_SESSION['user']['role_slug'] !== 'admin') break;
            $stmt = $pdo->prepare("UPDATE users SET is_active=1 WHERE id=?");
            $stmt->execute([$id]);

            $user = $pdo->prepare("SELECT name,email FROM users WHERE id=?");
            $user->execute([$id]);
            $user = $user->fetch();

            sendEmail($user['email'], "Account Reactivated", "Hi {$user['name']},<br>Your account has been reactivated.");
            logAction($pdo, $_SESSION['user']['id'], "Reactivated user", ['user_id'=>$id]);
            break;

        case 'edit':
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $name    = trim($_POST['name']);
                $email   = trim($_POST['email']);
                $role_id = (int) $_POST['role_id'];
                $status  = (int) $_POST['is_active'];

                // Prevent non-admins from escalating privileges
                if ($_SESSION['user']['role_slug'] !== 'admin') {
                    $status = $status === 2 ? 1 : $status; // no ban
                    $isAdminRole = (bool)$pdo->prepare("SELECT 1 FROM roles WHERE id=? AND slug='admin'")
                        ->execute([$role_id]);
                    if ($isAdminRole) {
                        $role_id = (int)$pdo->prepare("SELECT role_id FROM users WHERE id=?")
                                    ->execute([$id]);
                    }
                }

                $stmt = $pdo->prepare("UPDATE users SET name=?, email=?, role_id=?, is_active=? WHERE id=?");
                $stmt->execute([$name, $email, $role_id, $status, $id]);
                logAction($pdo, $_SESSION['user']['id'], "Edited user", ['user_id'=>$id]);
            }
            break;
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

    $id = (int) $_GET['id'];

    $stmt = $pdo->prepare("
        SELECT u.id, u.name, u.email, u.avatar, u.is_active, u.last_login, u.created_at, u.updated_at,
               r.name AS role_name, r.slug AS role_slug, r.id AS role_id
        FROM users u
        LEFT JOIN roles r ON r.id = u.role_id
        WHERE u.id = ?
    ");
    $stmt->execute([$id]);
    $user = $stmt->fetch();

    if (!$user) {
        echo json_encode(['error' => 'User not found']);
        exit;
    }

    // Use prepared statements for counts
    $posts_count = (int)$pdo->prepare("SELECT COUNT(*) FROM posts WHERE author_id=?")->execute([$id])->fetchColumn();
    $comments_count = (int)$pdo->prepare("SELECT COUNT(*) FROM comments WHERE user_id=?")->execute([$id])->fetchColumn();

    $status_map = [0=>'Pending', 1=>'Active', 2=>'Banned'];

    echo json_encode([
        'id'           => $user['id'],
        'name'         => $user['name'],
        'email'        => $user['email'],
        'avatar'       => $user['avatar'],
        'role_id'      => $user['role_id'] ?? null,
        'role_name'    => $user['role_name'] ?? 'Student',
        'role_slug'    => $user['role_slug'] ?? 'student',
        'status'       => $status_map[(int)$user['is_active']] ?? 'Unknown',
        'status_value' => (int)$user['is_active'],
        'last_login'   => $user['last_login'],
        'created_at'   => $user['created_at'],
        'updated_at'   => $user['updated_at'],
        'posts_count'  => $posts_count,
        'comments_count'=> $comments_count
    ]);
    exit;
}

// Fetch users + roles in one query to avoid N+1
$users = $pdo->query("
    SELECT u.*, r.name AS role_name, r.slug AS role_slug
    FROM users u
    LEFT JOIN roles r ON r.id = u.role_id
    ORDER BY u.created_at DESC
")->fetchAll();

?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>User Management - HIGH Q SOLID ACADEMY</title>
<link rel="stylesheet" href=".assets/css/users.css">
<link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
</head>
<body>
<?php include '../includes/header.php'; ?>
<?php include '../includes/sidebar.php'; ?>

<div class="container" style="margin-left:240px;">
    <h1>User Management</h1>

    <!-- Users Table -->
    <table class="users-table">
        <thead>
            <tr>
                <th>Name</th>
                <th>Email</th>
                <th>Role & Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($users as $u): 
            $statusClass = $u['is_active']==1 ? 'status-active' : ($u['is_active']==0 ? 'status-pending' : 'status-banned');
            $roleClass = 'role-' . strtolower($u['role_slug'] ?? 'student');
        ?>
            <tr>
                <td><?= htmlspecialchars($u['name']) ?></td>
                <td><?= htmlspecialchars($u['email']) ?></td>
                <td>
                    <span class="role-badge <?= $roleClass ?>"><?= htmlspecialchars($u['role_name'] ?? 'Student') ?></span>
                    <span class="status-badge <?= $statusClass ?>"><?= $statusClass==='status-active' ? 'Active' : ($statusClass==='status-pending' ? 'Pending' : 'Banned') ?></span>
                </td>
                <td>
                    <a href="#" class="btn-view" data-user-id="<?= $u['id'] ?>">View</a>
                    <a href="#" class="btn-edit" data-user-id="<?= $u['id'] ?>">Edit</a>
                    <?php if ($_SESSION['user']['role_slug']==='admin'): ?>
                        <?php if($u['is_active']===0): ?>
                        <form method="post" action="index.php?page=users&action=approve&id=<?= $u['id'] ?>" class="inline-form">
                            <input type="hidden" name="csrf_token" value="<?= $csrf; ?>">
                            <select name="role_id" required>
                                <option value="">Assign Role</option>
                                <?php foreach ($all_roles as $r): ?>
                                    <option value="<?= $r['id'] ?>"><?= htmlspecialchars($r['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <button type="submit" class="btn-approve">Approve</button>
                        </form>
                        <form method="post" action="index.php?page=users&action=banish&id=<?= $u['id'] ?>" class="inline-form">
                            <input type="hidden" name="csrf_token" value="<?= $csrf; ?>">
                            <button type="submit" class="btn-banish">Banish</button>
                        </form>
                        <?php elseif($u['is_active']===1): ?>
                        <form method="post" action="index.php?page=users&action=banish&id=<?= $u['id'] ?>" class="inline-form">
                            <input type="hidden" name="csrf_token" value="<?= $csrf; ?>">
                            <button type="submit" class="btn-banish">Banish</button>
                        </form>
                        <?php else: ?>
                        <form method="post" action="index.php?page=users&action=reactivate&id=<?= $u['id'] ?>" class="inline-form">
                            <input type="hidden" name="csrf_token" value="<?= $csrf; ?>">
                            <button type="submit" class="btn-approve">Reactivate</button>
                        </form>
                        <?php endif; ?>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- Modal -->
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
const userModal = document.getElementById('userModal');
const overlay   = document.getElementById('modalOverlay');
const closeBtn  = document.getElementById('userModalClose');

function openModal(){ userModal.classList.add('open'); overlay.classList.add('open'); }
function closeModal(){ userModal.classList.remove('open'); overlay.classList.remove('open'); }

overlay.addEventListener('click', closeModal);
closeBtn.addEventListener('click', closeModal);
document.addEventListener('keydown', e=>{ if(e.key==='Escape') closeModal(); });

// Tabs
const tabButtons = document.querySelectorAll('.tab-btn');
const tabPanes = document.querySelectorAll('.tab-pane');
function activateTab(id){
    tabButtons.forEach(b=>b.classList.toggle('active', b.dataset.tab===id));
    tabPanes.forEach(p=>p.classList.toggle('active', p.id===id));
}

// Load user data via AJAX
async function loadUser(id, mode='view'){
    const res = await fetch(`index.php?page=users&action=view&id=${id}`);
    const data = await res.json();
    if(data.error) return alert(data.error);

    // Fill view fields
    document.getElementById('mName').textContent = data.name;
    document.getElementById('mEmail').textContent = data.email;
    document.getElementById('mRole').textContent = data.role_name;
    document.getElementById('mRole').className = `role-badge role-${data.role_slug}`;
    document.getElementById('mStatus').textContent = data.status;
    document.getElementById('mStatus').className = `status-badge ${data.status_value===1?'status-active':data.status_value===0?'status-pending':'status-banned'}`;
    document.getElementById('mLastLogin').textContent = data.last_login ?? '—';
    document.getElementById('mCreated').textContent = data.created_at ?? '—';
    document.getElementById('mUpdated').textContent = data.updated_at ?? '—';
    document.getElementById('mPosts').textContent = data.posts_count;
    document.getElementById('mComments').textContent = data.comments_count;
    document.getElementById('mAvatar').src = data.avatar ? `../${data.avatar}` : "../public/assets/images/avatar-placeholder.png";

    // Fill edit form
    const form = document.getElementById('editForm');
    form.action = `index.php?page=users&action=edit&id=${data.id}`;
    document.getElementById('fName').value = data.name;
    document.getElementById('fEmail').value = data.email;
    document.getElementById('fRole').value = data.role_id ?? '';
    document.getElementById('fStatus').value = data.status_value;

    activateTab(mode==='edit'?'editTab':'viewTab');
    openModal();
}

// Button handlers
document.querySelectorAll('.btn-view').forEach(btn=>btn.addEventListener('click', e=>{
    e.preventDefault(); loadUser(btn.dataset.userId,'view');
}));
document.querySelectorAll('.btn-edit').forEach(btn=>btn.addEventListener('click', e=>{
    e.preventDefault(); loadUser(btn.dataset.userId,'edit');
}));

</script>
</body>
</html>
