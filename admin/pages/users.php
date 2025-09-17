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

// Fetch roles for dropdowns
$all_roles = $pdo->query("SELECT id, name, slug FROM roles ORDER BY name ASC")->fetchAll();

// Action handling (approve, banish, reactivate, edit) — same as your original
// ...

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

  <!-- Summary Cards -->
  <div class="summary-cards">
    <div class="card"><h3><?= $total_users ?></h3><p>Total Users</p></div>
    <div class="card"><h3><?= $active_users ?></h3><p>Active</p></div>
    <div class="card"><h3><?= $pending_users ?></h3><p>Pending</p></div>
    <div class="card"><h3><?= $banned_users ?></h3><p>Banned</p></div>
  </div>

  <!-- Search + Filter -->
  <div class="user-filters">
    <input type="text" id="searchInput" placeholder="Search by name or email">
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

  <!-- Users Table -->
  <table class="users-table">
    <thead>
      <tr>
        <th>Avatar</th>
        <th>Name & Email</th>
        <th>Role & Status</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody id="userTableBody">
      <?php foreach ($users as $u): 
        $statusClass = $u['is_active']==1 ? 'status-active' : ($u['is_active']==0 ? 'status-pending' : 'status-banned');
        $roleClass   = 'role-' . strtolower($u['role_slug'] ?? 'student');
      ?>
      <tr data-status="<?= $u['is_active']==1?'active':($u['is_active']==0?'pending':'banned') ?>" data-role="<?= strtolower($u['role_slug'] ?? 'student') ?>">
        <td><img src="<?= $u['avatar'] ?: '../public/assets/images/avatar-placeholder.png' ?>" class="avatar-sm"></td>
        <td>
          <strong><?= htmlspecialchars($u['name']) ?></strong><br>
          <span><?= htmlspecialchars($u['email']) ?></span>
        </td>
        <td>
          <span class="role-badge <?= $roleClass ?>"><?= htmlspecialchars($u['role_name'] ?? 'Student') ?></span>
          <span class="status-badge <?= $statusClass ?>"><?= $statusClass==='status-active' ? 'Active' : ($statusClass==='status-pending' ? 'Pending' : 'Banned') ?></span>
        </td>
        <td>
          <button class="btn-view" data-user-id="<?= $u['id'] ?>"><i class='bx bx-show'></i></button>
          <button class="btn-edit" data-user-id="<?= $u['id'] ?>"><i class='bx bx-edit'></i></button>
          <?php if ($_SESSION['user']['role_slug']==='admin'): ?>
            <?php if($u['is_active']===0): ?>
              <!-- Approve -->
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
              <!-- Reject/Banish -->
              <?php if ($u['id'] != 1 && $u['id'] != $_SESSION['user']['id']): ?>
              <form method="post" action="index.php?pages=users&action=banish&id=<?= $u['id'] ?>" class="inline-form">
                <input type="hidden" name="csrf_token" value="<?= $csrf; ?>">
                <button type="submit" class="btn-banish">Reject</button>
              </form>
              <?php endif; ?>
            <?php elseif($u['is_active']===1): ?>
              <!-- Deactivate/Banish -->
              <?php if ($u['id'] != 1 && $u['id'] != $_SESSION['user']['id']): ?>
              <form method="post" action="index.php?pages=users&action=banish&id=<?= $u['id'] ?>" class="inline-form">
                <input type="hidden" name="csrf_token" value="<?= $csrf; ?>">
                <button type="submit" class="btn-banish">Deactivate</button>
              </form>
              <?php endif; ?>
            <?php else: ?>
              <!-- Reactivate -->
              <form method="post" action="index.php?pages=users&action=reactivate&id=<?= $u['id'] ?>" class="inline-form">
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
  const res = await fetch(`index.php?page=users&action=view&id=${id}`);
  const data = await res.json();
  if(data.error) return alert(data.error);

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

// Search + filter
const searchInput = document.getElementById('searchInput');
const statusFilter = document.getElementById('statusFilter');
const roleFilter = document.getElementById('roleFilter');
const rows = document.querySelectorAll('#userTableBody tr');

function filterTable(){
  const search = searchInput.value.toLowerCase();
  const status = statusFilter.value;
  const role   = roleFilter.value;

  rows.forEach(row=>{
    const name  = row.querySelector('td:nth-child(2) strong').textContent.toLowerCase();
    const email = row.querySelector('td:nth-child(2) span').textContent.toLowerCase();
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
