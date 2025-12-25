<?php
// admin/pages/users.php
require_once '../includes/auth.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/csrf.php';

// Only Admins & Sub-Admins
requirePermission('users');

// Generate CSRF token
$csrf = generateToken('users_form');

// Fetch roles for dropdowns
$all_roles = $pdo->query("SELECT id, name, slug FROM roles ORDER BY name ASC")->fetchAll();

// Ensure users.css is loaded after admin.css by providing $pageCss for header
$pageCss = '';
// Page title and subtitle for header
$pageTitle = 'User Management';
$pageSubtitle = 'Manage user accounts, roles, and permissions';

// Server-side action handling (POST) and AJAX view (GET)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action'])) {
  $action = $_GET['action'];
  $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

  // CSRF
  $token = $_POST['csrf_token'] ?? '';
  if (!verifyToken('users_form', $token)) {
    header('Location: index.php?pages=users'); exit;
  }

  // Basic protections
  $currentUserId = $_SESSION['user']['id'];
  $isMainAdmin = ($id === 1);

  if ($action === 'approve') {
    // Approve a pending user and assign role (respect roles.max_count)
    $role_id = isset($_POST['role_id']) ? (int)$_POST['role_id'] : 0;
    if (!$role_id) { header('Location: index.php?pages=users'); exit; }

    // Check role max_count
    $stmt = $pdo->prepare('SELECT max_count FROM roles WHERE id = ?');
    $stmt->execute([$role_id]);
    $max = $stmt->fetchColumn();
    if ($max !== false && $max !== null && (int)$max > 0) {
      $stmt = $pdo->prepare('SELECT COUNT(*) FROM users WHERE role_id = ? AND is_active = 1');
      $stmt->execute([$role_id]);
      $count = (int)$stmt->fetchColumn();
      if ($count >= (int)$max) {
        // Role is full
        header('Location: index.php?pages=users'); exit;
      }

        if ($action === 'resend_verification') {
          // Resend verification email to user (admin action)
          $u = $pdo->prepare('SELECT id, email, name, is_active, email_verification_sent_at FROM users WHERE id = ? LIMIT 1');
          $u->execute([$id]); $usr = $u->fetch(PDO::FETCH_ASSOC);
          if (!$usr) { header('Location: index.php?pages=users'); exit; }

          // Only resend if user is not active
          if ((int)$usr['is_active'] === 0) {
            // Rate limit: allow resend every 10 minutes by default (env override)
            $wait = getenv('VERIFICATION_RESEND_WAIT_MIN') ? intval(getenv('VERIFICATION_RESEND_WAIT_MIN')) : 10;
            $canSend = true;
            if (!empty($usr['email_verification_sent_at'])) {
              $sentTs = strtotime($usr['email_verification_sent_at']);
              if ($sentTs !== false && (time() - $sentTs) < ($wait * 60)) $canSend = false;
            }

            if ($canSend) {
              // generate new token
              $token = bin2hex(random_bytes(32));
              $upd = $pdo->prepare('UPDATE users SET email_verification_token = ?, email_verification_sent_at = NOW() WHERE id = ?');
              $upd->execute([$token, $id]);

              // Use admin_url() so ADMIN_URL from .env (if set) is honoured and fallbacks apply consistently
              $verifyUrl = admin_url('verify_email.php?token=' . urlencode($token));

              $subject = 'Please verify your email';
              $html = '<p>Hi ' . htmlspecialchars($usr['name'] ?? '') . ',</p>' .
                      '<p>Please verify your email by clicking <a href="' . htmlspecialchars($verifyUrl) . '">this link</a>.</p>';
              @sendEmail($usr['email'], $subject, $html);
              logAction($pdo, $_SESSION['user']['id'], 'resend_verification', ['user_id'=>$id]);
            }
          }

          header('Location: index.php?pages=users'); exit;
        }
    }

    $stmt = $pdo->prepare('UPDATE users SET role_id = ?, is_active = 1, updated_at = NOW() WHERE id = ?');
    $stmt->execute([$role_id, $id]);
    logAction($pdo, $currentUserId, 'approve_user', ['user_id' => $id, 'role_id' => $role_id]);
    // Send approval email to the user if email present
    try {
      $u = $pdo->prepare('SELECT email, name FROM users WHERE id = ? LIMIT 1'); $u->execute([$id]); $usr = $u->fetch(PDO::FETCH_ASSOC);
      if ($usr && filter_var($usr['email'] ?? '', FILTER_VALIDATE_EMAIL) && function_exists('sendEmail')) {
        $sub = 'Your account has been approved';
        $body = '<p>Hi ' . htmlspecialchars($usr['name'] ?? '') . ',</p><p>Your account has been approved and activated. You can now login.</p>';
        @sendEmail($usr['email'], $sub, $body);
      }
    } catch(Throwable $e){}
    header('Location: index.php?pages=users'); exit;
  }

  if ($action === 'banish') {
    // Prevent banishing main admin or yourself
    if ($id === $currentUserId || $id === 1) { header('Location: index.php?pages=users'); exit; }
    $stmt = $pdo->prepare('UPDATE users SET is_active = 2, updated_at = NOW() WHERE id = ?');
    $stmt->execute([$id]);
    logAction($pdo, $currentUserId, 'banish_user', ['user_id' => $id]);
    header('Location: index.php?pages=users'); exit;
  }

  if ($action === 'reactivate') {
    // Reactivate a banned/pending user
    $stmt = $pdo->prepare('UPDATE users SET is_active = 1, updated_at = NOW() WHERE id = ?');
    $stmt->execute([$id]);
    logAction($pdo, $currentUserId, 'reactivate_user', ['user_id' => $id]);
    header('Location: index.php?pages=users'); exit;
  }

  if ($action === 'edit') {
    // Edit user details from modal
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $role_id = isset($_POST['role_id']) ? (int)$_POST['role_id'] : null;
    $is_active = isset($_POST['is_active']) ? (int)$_POST['is_active'] : null;

    // Don't allow editing main admin by non-main admin
    if ($id === 1 && $_SESSION['user']['id'] !== 1) { header('Location: index.php?pages=users'); exit; }

    $stmt = $pdo->prepare('UPDATE users SET name = ?, email = ?, role_id = ?, is_active = ?, updated_at = NOW() WHERE id = ?');
    $stmt->execute([$name, $email, $role_id, $is_active, $id]);
    logAction($pdo, $currentUserId, 'edit_user', ['user_id' => $id]);
    header('Location: index.php?pages=users'); exit;
  }
}

// AJAX: return JSON for a single user view
if (isset($_GET['action']) && $_GET['action'] === 'view' && isset($_GET['id'])) {
  $id = (int)$_GET['id'];
  $stmt = $pdo->prepare('SELECT u.*, u.role_id AS role_id, r.name AS role_name, r.slug AS role_slug,
  (SELECT COUNT(*) FROM posts WHERE author_id = u.id) AS posts_count,
    (SELECT COUNT(*) FROM comments WHERE user_id = u.id) AS comments_count
    FROM users u LEFT JOIN roles r ON r.id = u.role_id WHERE u.id = ?');
  $stmt->execute([$id]);
  $u = $stmt->fetch(PDO::FETCH_ASSOC);
  if (!$u) {
    header('Content-Type: application/json'); echo json_encode(['error' => 'User not found']); exit;
  }
  $u['status'] = $u['is_active']==1 ? 'Active' : ($u['is_active']==0 ? 'Pending' : 'Banned');
  $u['status_value'] = (int)$u['is_active'];
  header('Content-Type: application/json'); echo json_encode($u); exit;
}

// Handle receipt upload by admin on user profile (AJAX file upload)
if (isset($_GET['action']) && $_GET['action'] === 'upload_receipt' && isset($_GET['id']) && $_SERVER['REQUEST_METHOD']==='POST') {
  $id = (int)$_GET['id'];
  $token = $_POST['csrf_token'] ?? '';
  if (!verifyToken('users_form', $token)) { header('Content-Type: application/json'); echo json_encode(['error'=>'Invalid CSRF']); exit; }
  if (empty($_FILES['receipt']) || $_FILES['receipt']['error'] !== UPLOAD_ERR_OK) { header('Content-Type: application/json'); echo json_encode(['error'=>'No file uploaded']); exit; }
  $f = $_FILES['receipt'];
  $ext = pathinfo($f['name'], PATHINFO_EXTENSION);
  $allowed = ['jpg','jpeg','png','pdf'];
  // Size limit 5MB
  if ($f['size'] > 5 * 1024 * 1024) { header('Content-Type: application/json'); echo json_encode(['error'=>'File too large (max 5MB)']); exit; }
  // MIME check
  $finfo = finfo_open(FILEINFO_MIME_TYPE);
  $mime = finfo_file($finfo, $f['tmp_name']);
  finfo_close($finfo);
  $mimeAllowed = ['image/jpeg','image/png','application/pdf'];
  if (!in_array($mime, $mimeAllowed) || !in_array(strtolower($ext), $allowed)) { header('Content-Type: application/json'); echo json_encode(['error'=>'Invalid file type']); exit; }
  $uploadDir = __DIR__ . '/../../public/uploads/receipts/'; if (!is_dir($uploadDir)) mkdir($uploadDir,0755,true);
  $fileName = 'receipt_' . $id . '_' . time() . '.' . $ext;
  $target = $uploadDir . $fileName;
  if (!move_uploaded_file($f['tmp_name'], $target)) { header('Content-Type: application/json'); echo json_encode(['error'=>'Failed to move file']); exit; }

  // Create a payments record or attach to existing pending payment for user
  $stmt = $pdo->prepare('SELECT id FROM payments WHERE student_id = ? AND status = "pending" ORDER BY created_at DESC LIMIT 1');
  $stmt->execute([$id]); $p = $stmt->fetch();
  if ($p) {
    $pid = $p['id'];
    $upd = $pdo->prepare('UPDATE payments SET receipt_path = ?, updated_at = NOW() WHERE id = ?');
    $upd->execute(["uploads/receipts/{$fileName}", $pid]);
  } else {
    $ref = 'RCPT-' . date('YmdHis') . '-' . substr(bin2hex(random_bytes(3)),0,6);
    $ins = $pdo->prepare('INSERT INTO payments (student_id, amount, payment_method, reference, status, receipt_path, created_at) VALUES (?, 0, "bank_transfer", ?, "uploaded", ?, NOW())');
    $ins->execute([$id, $ref, "uploads/receipts/{$fileName}"]); $pid = $pdo->lastInsertId();
  }

  // Notify admins via log
  logAction($pdo, $_SESSION['user']['id'], 'upload_receipt', ['user_id'=>$id, 'payment_id'=>$pid, 'path'=>"uploads/receipts/{$fileName}"]);
  header('Content-Type: application/json'); echo json_encode(['ok'=>true,'payment_id'=>$pid,'path'=>"uploads/receipts/{$fileName}"]); exit;
}

// Fetch counts for summary
$total_users   = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$active_users  = $pdo->query("SELECT COUNT(*) FROM users WHERE is_active=1")->fetchColumn();
$pending_users = $pdo->query("SELECT COUNT(*) FROM users WHERE is_active=0")->fetchColumn();
$banned_users  = $pdo->query("SELECT COUNT(*) FROM users WHERE is_active=2")->fetchColumn();

// Fetch users
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
<link rel="stylesheet" href="../assets/css/users.css">
<link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
</head>
<body>
<?php include '../includes/header.php'; ?>
<?php include '../includes/sidebar.php'; ?>

<div class="users-page">
  <!-- Page Hero -->
  <div class="page-hero">
    <div class="page-hero-content">
      <div>
        <span class="page-hero-badge"><i class='bx bxs-user-detail'></i> User Management</span>
        <h1 class="page-hero-title">Manage Users</h1>
        <p class="page-hero-subtitle">View and manage user accounts, roles, and permissions</p>
      </div>
      <div class="page-hero-actions">
        <span class="inline-flex items-center gap-2 px-4 py-2 bg-white/20 backdrop-blur-md border border-white/30 rounded-full text-sm font-medium text-slate-900">
          <i class='bx bxs-group'></i>
          <?= $total_users ?> Total Users
        </span>
      </div>
    </div>
  </div>

  <!-- User Statistics -->
  <div class="summary-cards">
    <div class="card animate-fadeIn" style="animation-delay: 0ms">
      <div>
        <h3><?= $total_users ?></h3>
        <p>Total Users</p>
      </div>
    </div>
    <div class="card animate-fadeIn" style="animation-delay: 50ms">
      <div>
        <h3><?= $active_users ?></h3>
        <p>Active</p>
      </div>
    </div>
    <div class="card animate-fadeIn" style="animation-delay: 100ms">
      <div>
        <h3><?= $pending_users ?></h3>
        <p>Pending</p>
      </div>
    </div>
    <div class="card animate-fadeIn" style="animation-delay: 150ms">
      <div>
        <h3><?= $banned_users ?></h3>
        <p>Banned</p>
      </div>
    </div>
  </div>

  <!-- Search + Filter -->
  <div class="user-filters animate-fadeIn" style="animation-delay: 200ms">
    <div class="filter-controls">
      <input type="text" id="searchInput" placeholder="Search by name or email...">
      <select id="statusFilter">
        <option value="">All Status</option>
        <option value="active">Active</option>
        <option value="pending">Pending</option>
        <option value="banned">Banned</option>
      </select>
      <select id="roleFilter">
        <option value="">All Roles</option>
        <?php foreach ($all_roles as $r): ?>
          <option value="<?= htmlspecialchars($r['slug']) ?>"><?= htmlspecialchars($r['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
  </div>

  <!-- User List -->
  <div class="user-list" id="userTableBody">
    <?php foreach ($users as $u): 
      $status = $u['is_active']==1 ? 'Active' : ($u['is_active']==0 ? 'Pending' : 'Banned');
      $roleClass = 'role-' . strtolower($u['role_slug'] ?? 'student');
    ?>
    <div class="user-card" data-status="<?= $u['is_active']==1?'active':($u['is_active']==0?'pending':'banned') ?>" data-role="<?= strtolower($u['role_slug'] ?? 'student') ?>">
      <div class="user-avatar">
        <img src="<?= htmlspecialchars($u['avatar'] ? app_url($u['avatar']) : app_url('public/assets/images/hq-logo.jpeg')) ?>" alt="Avatar">
      </div>
      <div class="user-info">
        <div class="user-name"><?= htmlspecialchars($u['name']) ?></div>
        <div class="user-email"><?= htmlspecialchars($u['email']) ?></div>
        <div class="user-meta">
          <span class="user-role <?= strtolower($u['role_slug'] ?? 'student') ?>"><?= htmlspecialchars($u['role_name'] ?? 'Student') ?></span>
          <span class="user-status <?= strtolower($status) ?>"><?= $status ?></span>
        </div>
      </div>
      <div class="user-actions">
        <div class="card-actions">
          <button class="btn-secondary btn-view" data-user-id="<?= $u['id'] ?>" title="View"><i class='bx bx-show'></i></button>
          <button class="btn-primary btn-edit" data-user-id="<?= $u['id'] ?>" title="Edit"><i class='bx bx-edit'></i></button>
          <?php if ($_SESSION['user']['role_slug']==='admin'): ?>
              <?php if($u['is_active']===0): ?>
              <form method="post" action="index.php?pages=users&action=approve&id=<?= $u['id'] ?>" class="inline-form">
                <input type="hidden" name="csrf_token" value="<?= $csrf; ?>">
                <select name="role_id" required>
                  <option value="">Assign Role</option>
                  <?php foreach ($all_roles as $r): ?>
                    <option value="<?= $r['id'] ?>"><?= htmlspecialchars($r['name']) ?></option>
                  <?php endforeach; ?>
                </select>
                <button type="submit" class="btn-approve">Approve</button>
              </form>
              <form method="post" action="index.php?pages=users&action=resend_verification&id=<?= $u['id'] ?>" class="inline-form">
                <input type="hidden" name="csrf_token" value="<?= $csrf; ?>">
                <button type="submit" class="btn">Resend Verification</button>
              </form>
              <?php if ($u['id'] != 1 && $u['id'] != $_SESSION['user']['id']): ?>
              <form method="post" action="index.php?pages=users&action=banish&id=<?= $u['id'] ?>" class="inline-form">
                <input type="hidden" name="csrf_token" value="<?= $csrf; ?>">
                <button type="submit" class="btn-banish">Reject</button>
              </form>
              <?php endif; ?>
            <?php elseif($u['is_active']===1): ?>
              <?php if ($u['id'] != 1 && $u['id'] != $_SESSION['user']['id']): ?>
              <form method="post" action="index.php?pages=users&action=banish&id=<?= $u['id'] ?>" class="inline-form">
                <input type="hidden" name="csrf_token" value="<?= $csrf; ?>">
                <button type="submit" class="btn-banish">Deactivate</button>
              </form>
              <?php endif; ?>
            <?php else: ?>
              <form method="post" action="index.php?pages=users&action=reactivate&id=<?= $u['id'] ?>" class="inline-form">
                <input type="hidden" name="csrf_token" value="<?= $csrf; ?>">
                <button type="submit" class="btn-approve">Reactivate</button>
              </form>
            <?php endif; ?>
          <?php endif; ?>
        </div>
        <?php if ($u['id'] === 1): ?>
          <div class="main-admin-badge">Main Admin</div>
        <?php endif; ?>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
</div>

<!-- User Modal -->
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
  <img id="mAvatar" src="<?= htmlspecialchars(app_url('public/assets/images/hq-logo.jpeg')) ?>" alt="Avatar">
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
          <div class="sticky-actions">
              <button type="submit" class="btn-primary">Save Changes</button>
          </div>
        </div>
      </form>
    </div>
  </div>
</div>
<div id="modalOverlay"></div>
</div>

<?php include '../includes/footer.php'; ?>

<!-- Inline JS -->
<script>
// Modal control
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
tabButtons.forEach(btn=>btn.addEventListener('click', ()=>activateTab(btn.dataset.tab)));

// AJAX: Load user data
async function loadUser(id, mode='view'){
  const res = await fetch((window.HQ_ADMIN_BASE || '') + '/index.php?pages=users&action=view&id=' + encodeURIComponent(id), { credentials: 'same-origin' });
  let data = null;
  try {
    data = await res.json();
  } catch (e) {
    // Probably an auth redirect or HTML response; show friendly message and redirect to login
    const text = await res.text();
    if (typeof Swal !== 'undefined') {
      Swal.fire({ title: 'Session expired', text: 'Your session may have expired. Please login again.', icon: 'warning' }).then(()=> window.location = (window.HQ_ADMIN_BASE || '') + '/login.php');
    } else {
      alert('Session expired. Please login again.'); window.location = (window.HQ_ADMIN_BASE || '') + '/login.php';
    }
    return;
  }
  if(data.error) {
    if (typeof Swal !== 'undefined') Swal.fire('Error', data.error, 'error'); else alert(data.error);
    return;
  }

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
  document.getElementById('mAvatar').src = data.avatar ? ((window.HQ_APP_BASE || '') + '/' + data.avatar.replace(/^\/+/, '')) : ((window.HQ_APP_BASE || '') + '/public/assets/images/hq-logo.jpeg');

  // Fill edit form
  const form = document.getElementById('editForm');
  form.action = `index.php?pages=users&action=edit&id=${data.id}`;
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

// Search + filter
const searchInput = document.getElementById('searchInput');
const statusFilter = document.getElementById('statusFilter');
const roleFilter = document.getElementById('roleFilter');
const rows = document.querySelectorAll('#userTableBody .user-card');

function filterTable(){
  const search = searchInput.value.toLowerCase();
  const status = statusFilter.value;
  const role   = roleFilter.value;

  rows.forEach(row=>{
    const nameEl  = row.querySelector('.card-name');
    const emailEl = row.querySelector('.card-email');
    const name  = nameEl ? nameEl.textContent.toLowerCase() : '';
    const email = emailEl ? emailEl.textContent.toLowerCase() : '';
    const rowStatus = row.dataset.status;
    const rowRole   = row.dataset.role;

    let match = true;
    if(search && !(name.includes(search) || email.includes(search))) match = false;
    if(status && rowStatus!==status) match = false;
    if(role && rowRole!==role) match = false;

    row.style.display = match ? '' : 'none';
  });
}

searchInput.addEventListener('input', filterTable);
statusFilter.addEventListener('change', filterTable);
roleFilter.addEventListener('change', filterTable);
</script>
</body>
</html>
