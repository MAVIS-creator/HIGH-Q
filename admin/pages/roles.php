<?php

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/functions.php';

requirePermission('roles'); // where 'roles' matches the menu slug

// Generate CSRF token
$csrf = generateToken('default_form'); // token for default_form


// Page title and subtitle for header
$pageTitle = 'Roles Management';
$pageSubtitle = 'Manage roles, permissions, and access control';
require_once '../includes/header.php';
require_once '../includes/sidebar.php';

$errors = [];
$flash  = [];

// --- CSRF wrapper for simplicity ---
function verifyCsrfToken(string $token): bool {
    return verifyToken('default_form', $token);
}

// Define all menu items (matches sidebar.php)
$allMenus = [
    'dashboard' => 'Dashboard',
    'users'     => 'Manage Users',
    'roles'     => 'Roles',
    'settings'  => 'Site Settings',
    'courses'   => 'Courses',
    'tutors'    => 'Tutors',
    'students'  => 'Students',
    'payments'  => 'Payments',
    'post'     => 'News / Blog',
    'comments'  => 'Comments',
    'chat'      => 'Chat Support',
];

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action'])) {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors[] = "Invalid CSRF token.";
    } else {
        $action = $_GET['action'];
        $id     = isset($_GET['id']) ? (int)$_GET['id'] : null;

        // Sanitize inputs
        $name      = trim($_POST['name'] ?? '');
        $slug      = trim($_POST['slug'] ?? '');
        $max_count = (int)($_POST['max_count'] ?? 0) ?: null;
        $menus     = $_POST['menus'] ?? [];

        if ($action === 'create') {
            if (!$name || !$slug) {
                $errors[] = "Name and slug are required.";
            } else {
                $stmt = $pdo->prepare("INSERT INTO roles (name, slug, max_count) VALUES (?,?,?)");
                $stmt->execute([$name, $slug, $max_count]);
                $roleId = $pdo->lastInsertId();

                // Save menu permissions
                $stmtPerm = $pdo->prepare("INSERT INTO role_permissions (role_id, menu_slug) VALUES (?, ?)");
                foreach ($menus as $menu) {
                    $stmtPerm->execute([$roleId, $menu]);
                }

                logAction($pdo, $_SESSION['user']['id'], 'role_created', ['slug' => $slug]);
                $flash[] = "Role '{$name}' created.";
            }
        }

        if ($action === 'edit' && $id) {
            if (!$name || !$slug) {
                $errors[] = "Name and slug are required.";
            } else {
                $stmt = $pdo->prepare("UPDATE roles SET name=?, slug=?, max_count=? WHERE id=?");
                $stmt->execute([$name, $slug, $max_count, $id]);

                // Update menu permissions
                $pdo->prepare("DELETE FROM role_permissions WHERE role_id=?")->execute([$id]);
                $stmtPerm = $pdo->prepare("INSERT INTO role_permissions (role_id, menu_slug) VALUES (?, ?)");
                foreach ($menus as $menu) {
                    $stmtPerm->execute([$id, $menu]);
                }

                logAction($pdo, $_SESSION['user']['id'], 'role_updated', ['role_id' => $id]);
                $flash[] = "Role '{$name}' updated.";
            }
        }

        if ($action === 'delete' && $id) {
            // Optional: prevent deletion if users assigned
            $stmt = $pdo->prepare("DELETE FROM roles WHERE id=?");
            $stmt->execute([$id]);
            // role_permissions cascade delete
            logAction($pdo, $_SESSION['user']['id'], 'role_deleted', ['role_id' => $id]);
            $flash[] = "Role deleted.";
        }
    }

    // Redirect to avoid form resubmission
    header("Location: index.php?pages=roles");
    exit;
}

// Fetch roles with permissions
$roles = $pdo->query("SELECT * FROM roles ORDER BY id ASC")->fetchAll();

// Fetch all permissions per role
$permissionsByRole = [];
$stmt = $pdo->query("SELECT role_id, menu_slug FROM role_permissions");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $permissionsByRole[$row['role_id']][] = $row['menu_slug'];
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Roles Management - HIGH Q SOLID ACADEMY</title>
  <link rel="stylesheet" href="./assets/css/admin.css">
  <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
  <style>
    /* Roles table responsive tweaks to avoid pushing into the sidebar */
    .roles-table { width: 100%; table-layout: fixed; border-collapse: collapse; }
    .roles-table th, .roles-table td { padding: 10px; vertical-align: middle; text-overflow: ellipsis; overflow: hidden; }
    .roles-table th { background: #111; color: #fff; }
    .role-badge { display:inline-block; margin:2px 4px; padding:4px 8px; background:#ffefc4; border-radius:14px; font-size:13px; max-width:12ch; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
    td.menus-col { max-width: 28ch; }
    td.actions-col { width:1%; white-space:nowrap; }
    @media (max-width: 900px) { td.menus-col { max-width: 40ch; } .role-badge { max-width: 8rem; } }
  </style>
</head>
<body>

<div class="roles-page">
  <div class="page-header">
    <div class="page-title-block">
      <h1>Roles Management</h1>
      <p>Manage roles and permissions</p>
    </div>
    <button id="newRoleBtn" class="btn-approve">
      <i class='bx bx-plus'></i> New Role
    </button>
  </div>

  <?php if ($flash): ?>
    <div class="alert success">
      <?php foreach ($flash as $msg): ?>
        <p><?= htmlspecialchars($msg) ?></p>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <?php if ($errors): ?>
    <div class="alert error">
      <?php foreach ($errors as $err): ?>
        <p><?= htmlspecialchars($err) ?></p>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>


  <table class="roles-table">
    <thead>
      <tr>
        <th>ID</th>
        <th>Name</th>
        <th>Slug</th>
        <th>Max Users</th>
        <th>Menus</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($roles as $r): ?>
      <tr>
        <td><?= $r['id'] ?></td>
        <td><?= htmlspecialchars($r['name']) ?></td>
        <td><?= htmlspecialchars($r['slug']) ?></td>
        <td><?= $r['max_count'] ?? '∞' ?></td>
  <td class="menus-col">
          <?php
            $roleMenus = $permissionsByRole[$r['id']] ?? [];
            foreach ($roleMenus as $menu) {
                echo "<span class='role-badge role-$menu'>" . htmlspecialchars($allMenus[$menu] ?? $menu) . "</span> ";
            }
          ?>
        </td>
  <td class="actions-col">
          <button class="btn-editRole"
                  data-id="<?= $r['id'] ?>"
                  data-name="<?= htmlspecialchars($r['name']) ?>"
                  data-slug="<?= htmlspecialchars($r['slug']) ?>"
                  data-max="<?= $r['max_count'] ?>"
                  data-menus='<?= json_encode($roleMenus) ?>'>
            <i class='bx bx-edit'></i> Edit
          </button>
          <form method="post" action="index.php?pages=roles&action=delete&id=<?= $r['id'] ?>" style="display:inline">
            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
            <button type="submit" class="btn-banish">
              <i class='bx bx-trash'></i> Delete
            </button>
          </form>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<!-- Role Modal -->
<div class="modal" id="roleModal">
  <div class="modal-content">
    <span class="modal-close" id="roleModalClose"><i class='bx bx-x'></i></span>
    <h3 id="roleModalTitle">New Role</h3>

  <form id="roleForm" method="post" action="">
      <!-- CSRF token -->
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">

      <div class="form-row">
        <label>Name</label>
        <input type="text" name="name" id="roleName" required>
      </div>
      <div class="form-row">
        <label>Slug</label>
        <input type="text" name="slug" id="roleSlug" required>
      </div>
      <div class="form-row">
        <label>Max Users (0 for unlimited)</label>
        <input type="number" name="max_count" id="roleMax" min="0">
      </div>
      <div class="form-row">
        <label>Menus</label>
        <div id="menusContainer">
          <?php foreach ($allMenus as $slug => $label): ?>
            <label>
              <input type="checkbox" name="menus[]" value="<?= htmlspecialchars($slug) ?>"> <?= htmlspecialchars($label) ?>
            </label><br>
          <?php endforeach; ?>
        </div>
      </div>
      <div class="form-actions">
        <button type="submit" class="btn-approve">Save Role</button>
      </div>
    </form>
  </div>
</div>
<div id="modalOverlay"></div>

<script>
const roleModal    = document.getElementById('roleModal');
const overlay      = document.getElementById('modalOverlay');
const closeRoleBtn = document.getElementById('roleModalClose');
const newRoleBtn   = document.getElementById('newRoleBtn');
const roleForm     = document.getElementById('roleForm');
const modalTitle   = document.getElementById('roleModalTitle');
const nameInput    = document.getElementById('roleName');
const slugInput    = document.getElementById('roleSlug');
const maxInput     = document.getElementById('roleMax');
const menusContainer = document.getElementById('menusContainer');

function openModal(mode, role = {}) {
  overlay.classList.add('open');
  roleModal.classList.add('open');

  // Reset checkboxes
  menusContainer.querySelectorAll('input[type="checkbox"]').forEach(cb => cb.checked = false);

  if (mode === 'edit') {
    modalTitle.textContent = 'Edit Role';
  roleForm.action = `index.php?pages=roles&action=edit&id=${role.id}`;
    nameInput.value = role.name;
    slugInput.value = role.slug;
    maxInput.value  = role.max_count || '';

    // Check current role menus
    (role.menus || []).forEach(menu => {
      const cb = menusContainer.querySelector(`input[value="${menu}"]`);
      if (cb) cb.checked = true;
    });
  } else {
    modalTitle.textContent = 'New Role';
  roleForm.action = 'index.php?pages=roles&action=create';
    nameInput.value = '';
    slugInput.value = '';
    maxInput.value  = '';
  }
}

function closeModal() {
  overlay.classList.remove('open');
  roleModal.classList.remove('open');
}

newRoleBtn.addEventListener('click', () => openModal('create'));
closeRoleBtn.addEventListener('click', closeModal);
overlay.addEventListener('click', closeModal);
document.addEventListener('keydown', e => { if (e.key === 'Escape') closeModal(); });

// Edit buttons
document.querySelectorAll('.btn-editRole').forEach(btn => {
  btn.addEventListener('click', () => {
    const role = {
      id: btn.dataset.id,
      name: btn.dataset.name,
      slug: btn.dataset.slug,
      max_count: btn.dataset.max,
      menus: JSON.parse(btn.dataset.menus || '[]')
    };
    openModal('edit', role);
  });
});
</script>
</body>
</html>
