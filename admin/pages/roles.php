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
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';

$errors = [];
$flash  = [];

// --- CSRF wrapper for simplicity ---
// Avoid redeclaring verifyCsrfToken if it's already provided by includes/csrf.php
if (!function_exists('verifyCsrfToken')) {
  function verifyCsrfToken(string $token): bool {
    return verifyToken('default_form', $token);
  }
}

// Load menus from loader (DB-driven) so new sidebar items automatically appear here
$__menus = require __DIR__ . '/../includes/menu_loader.php';
$allMenus = [];
foreach ($__menus as $slug => $item) {
  $allMenus[$slug] = $item['title'] ?? ucfirst($slug);
}

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

  // Redirect to avoid form resubmission — return to this page (roles.php)
  // Use admin_url() helper so the path isn't hardcoded to /HIGH-Q
  header("Location: " . admin_url('index.php?pages=roles.php'));
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
  <link rel="stylesheet" href="../assets/css/admin.css">
  <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
  <style>
    /* Roles table responsive tweaks to avoid pushing into the sidebar */
    .roles-table {
      width: 100%;
      min-width: 900px;
      table-layout: auto;
      border-collapse: collapse;
      overflow-x: auto;
      font-size: 15px;
    }
    .roles-table th, .roles-table td {
      padding: 12px 14px;
      vertical-align: middle;
      text-overflow: ellipsis;
      overflow: hidden;
      white-space: nowrap;
    }
    .roles-table th { background: #111; color: #fff; font-size: 16px; }
    .role-badge { display:inline-block; margin:2px 4px; padding:4px 8px; background:#ffefc4; border-radius:14px; font-size:13px; max-width:16ch; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
    td.menus-col { max-width: 40ch; }
    td.actions-col { width:1%; white-space:nowrap; }
    @media (max-width: 900px) {
      .roles-table { min-width: 600px; font-size: 14px; }
      td.menus-col { max-width: 60ch; }
      .role-badge { max-width: 12rem; }
    }
  </style>
</head>
<body>

<main class="main-content" style="padding: 2rem; max-width: 1600px; margin: 0 auto;">
<div class="roles-page">
  <div class="page-header" style="background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%); padding: 2.5rem; border-radius: 1rem; margin-bottom: 2rem; box-shadow: 0 4px 20px rgba(251, 191, 36, 0.25); display: flex; justify-content: space-between; align-items: center;">
    <div class="page-title-block">
      <h1 style="font-size: 2.5rem; font-weight: 800; margin: 0 0 0.5rem 0; color: #1e293b;">Roles Management</h1>
      <p style="font-size: 1.1rem; opacity: 0.85; margin: 0; color: #1e293b;">Manage roles and permissions</p>
    </div>
    <button id="newRoleBtn" class="btn-approve" style="background: #1e293b; color: white; font-weight: 700; padding: 0.875rem 1.75rem; border-radius: 0.75rem; border: none; cursor: pointer; transition: all 0.2s; box-shadow: 0 4px 12px rgba(30, 41, 59, 0.3);">
      <i class='bx bx-plus' style="font-size: 1.25rem;"></i> New Role
    </button>
  </div>

  <?php if ($flash): ?>
    <div class="alert success" style="background: #d1fae5; border: 2px solid #86efac; border-radius: 0.75rem; padding: 1rem; margin-bottom: 1.5rem;">
      <?php foreach ($flash as $msg): ?>
        <p style="color: #065f46; margin: 0; font-weight: 600;"><?= htmlspecialchars($msg) ?></p>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <?php if ($errors): ?>
    <div class="alert error" style="background: #fee2e2; border: 2px solid #fecaca; border-radius: 0.75rem; padding: 1rem; margin-bottom: 1.5rem;">
      <?php foreach ($errors as $err): ?>
        <p style="color: #991b1b; margin: 0; font-weight: 600;"><?= htmlspecialchars($err) ?></p>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <!-- Roles Grid -->
  <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(350px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">
    <?php foreach ($roles as $r): ?>
    <div style="background: white; border-radius: 1rem; border: 2px solid #e2e8f0; padding: 1.75rem; transition: all 0.2s; box-shadow: 0 2px 8px rgba(0,0,0,0.08);" onmouseover="this.style.boxShadow='0 8px 24px rgba(251, 191, 36, 0.2)'; this.style.borderColor='#fbbf24'; this.style.transform='translateY(-4px)'" onmouseout="this.style.boxShadow='0 2px 8px rgba(0,0,0,0.08)'; this.style.borderColor='#e2e8f0'; this.style.transform='translateY(0)'">
      <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 1rem;">
        <div>
          <h3 style="font-size: 1.4rem; font-weight: 800; color: #1e293b; margin: 0 0 0.5rem 0;"><?= htmlspecialchars($r['name']) ?></h3>
          <span style="background: #fef3c7; color: #92400e; padding: 0.35rem 0.75rem; border-radius: 9999px; font-size: 0.8rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.025em;"><?= htmlspecialchars($r['slug']) ?></span>
        </div>
        <div style="background: linear-gradient(135deg, #fbbf24, #f59e0b); color: #1e293b; width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 1.25rem; box-shadow: 0 4px 12px rgba(251, 191, 36, 0.3);"><?= $r['id'] ?></div>
      </div>
      
      <div style="margin: 1.25rem 0; padding: 1rem; background: #fef3c7; border-radius: 0.75rem; border: 2px solid #fbbf24;">
        <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 0.5rem;">
          <i class='bx bx-group' style="font-size: 1.25rem; color: #f59e0b;"></i>
          <span style="font-weight: 700; color: #92400e; font-size: 0.9rem;">Max Users:</span>
          <span style="font-weight: 800; color: #1e293b; font-size: 1.1rem;"><?= $r['max_count'] ?? '∞' ?></span>
        </div>
      </div>
      
      <div style="margin-bottom: 1.5rem;">
        <div style="font-weight: 700; color: #475569; font-size: 0.85rem; margin-bottom: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em;">Permissions:</div>
        <div style="display: flex; flex-wrap: wrap; gap: 0.5rem;">
          <?php
            $roleMenus = $permissionsByRole[$r['id']] ?? [];
            $colors = ['#f59e0b', '#fbbf24', '#10b981', '#3b82f6', '#8b5cf6', '#ec4899', '#06b6d4'];
            $colorIndex = 0;
            foreach ($roleMenus as $menu):
              $color = $colors[$colorIndex % count($colors)];
              $colorIndex++;
          ?>
            <span style="background: <?= $color ?>15; color: <?= $color ?>; padding: 0.4rem 0.85rem; border-radius: 0.5rem; font-size: 0.8rem; font-weight: 700; border: 2px solid <?= $color ?>30;">
              <i class='bx bx-check-circle' style="font-size: 0.9rem;"></i> <?= htmlspecialchars($allMenus[$menu] ?? $menu) ?>
            </span>
          <?php endforeach; ?>
        </div>
      </div>
      
      <div style="display: flex; gap: 0.75rem; padding-top: 1rem; border-top: 2px solid #f1f5f9;">
        <button class="btn-editRole" style="flex: 1; padding: 0.75rem; background: #fbbf24; color: #1e293b; border: none; border-radius: 0.75rem; font-weight: 700; cursor: pointer; transition: all 0.2s; display: flex; align-items: center; justify-content: center; gap: 0.5rem;"
                data-id="<?= $r['id'] ?>"
                data-name="<?= htmlspecialchars($r['name']) ?>"
                data-slug="<?= htmlspecialchars($r['slug']) ?>"
                data-max="<?= $r['max_count'] ?>"
                data-menus='<?= json_encode($roleMenus) ?>'
                onmouseover="this.style.background='#f59e0b'; this.style.transform='translateY(-2px)'"
                onmouseout="this.style.background='#fbbf24'; this.style.transform='translateY(0)'">
          <i class='bx bx-edit-alt' style="font-size: 1.1rem;"></i> Edit
        </button>
        <form method="post" action="index.php?pages=roles&action=delete&id=<?= $r['id'] ?>" style="flex: 1;" onsubmit="return confirm('Delete this role?');">
          <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
          <button type="submit" style="width: 100%; padding: 0.75rem; background: #ef4444; color: white; border: none; border-radius: 0.75rem; font-weight: 700; cursor: pointer; transition: all 0.2s; display: flex; align-items: center; justify-content: center; gap: 0.5rem;"
                  onmouseover="this.style.background='#dc2626'; this.style.transform='translateY(-2px)'"
                  onmouseout="this.style.background='#ef4444'; this.style.transform='translateY(0)'">
            <i class='bx bx-trash' style="font-size: 1.1rem;"></i> Delete
          </button>
        </form>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
</div>
</main>

<!-- Role Modal -->
<div class="modal" id="roleModal" aria-hidden="true">
  <div class="modal-content role-modal-content">
    <button class="modal-close" id="roleModalClose" aria-label="Close"><i class='bx bx-x'></i></button>
    <h3 id="roleModalTitle">New Role</h3>

  <form id="roleForm" method="post" action="index.php?pages=roles&action=create">
      <!-- CSRF token -->
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">

      <div class="role-modal-grid">
        <div class="col">
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
        </div>
        <div class="col">
          <div class="form-row">
            <label>Menus</label>
            <div id="menusContainer">
              <?php foreach ($allMenus as $slug => $label): ?>
                <label style="display:inline-flex;align-items:center;gap:8px;width:48%;margin-bottom:6px;">
                  <input type="checkbox" name="menus[]" value="<?= htmlspecialchars($slug) ?>"> <?= htmlspecialchars($label) ?>
                </label>
              <?php endforeach; ?>
            </div>
          </div>
        </div>
      </div>

      <div class="form-actions">
        <button type="submit" class="btn-approve">Save Role</button>
      </div>
    </form>
  </div>
</div>
<div id="modalOverlay"></div>

<style>
/* Role modal: two-column grid and scrollable content */
.modal { display:none; position:fixed; inset:0; z-index:1200; align-items:center; justify-content:center; }
.modal.open { display:flex; }
.modal .modal-content.role-modal-content { background:#fff; padding:18px; border-radius:8px; width:880px; max-width:96%; max-height:86vh; overflow:auto; box-shadow:0 6px 30px rgba(0,0,0,.18); }
.role-modal-grid { display:flex; gap:18px; align-items:flex-start; }
.role-modal-grid .col { flex:1; min-width:220px; }
.role-modal-grid .col .form-row { margin-bottom:10px; }
.role-modal-grid #menusContainer { display:flex; flex-wrap:wrap; gap:6px; }
.modal-close { position:absolute; right:12px; top:8px; background:transparent;border:0;font-size:1.2rem;cursor:pointer;color:#333 }
#modalOverlay { display:none; position:fixed; inset:0; background:rgba(0,0,0,.45); z-index:1199; }
#modalOverlay.open { display:block; }
/* prevent page scroll while modal open */
body.modal-open { overflow: hidden; }
@media (max-width:800px) { .modal .modal-content.role-modal-content { width: 94%; padding:12px; } .role-modal-grid { flex-direction:column; } }
</style>

<script>
// Server-side fallback for admin base (used when window.HQ_ADMIN_BASE is not available)
const ADMIN_BASE_SERVER = <?= json_encode(rtrim(admin_url(''), '/')) ?>;

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
  document.body.classList.add('modal-open');

  // Reset checkboxes
  menusContainer.querySelectorAll('input[type="checkbox"]').forEach(cb => cb.checked = false);

  if (mode === 'edit') {
    modalTitle.textContent = 'Edit Role';
    // Use runtime admin base exposed by header.php
  roleForm.action = 'index.php?pages=roles&action=edit&id=' + encodeURIComponent(role.id);
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
  document.body.classList.remove('modal-open');
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

// Delegated handler for destroy (AJAX remove) - posts to admin router
document.querySelector('table.roles-table').addEventListener('click', function(e){
  const btn = e.target.closest('button[data-action="destroy"]');
  if (!btn) return;
  const id = btn.getAttribute('data-id');
  if (!id) return;
  Swal.fire({
    title: 'Remove role?',
    text: 'Remove this role permanently? This action cannot be undone.',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#d33',
    confirmButtonText: 'Yes, remove'
  }).then(function(res){
  if (!res.isConfirmed) return;
  const fd = new FormData(); fd.append('action','delete'); fd.append('csrf_token','<?= $csrf ?>');
  // Post to this page (roles.php) so server-side handler in this file processes it and returns JSON when requested
    var xhr = new XMLHttpRequest();
  var target = 'index.php?pages=roles&action=delete&id=' + encodeURIComponent(id);
    xhr.open('POST', target, true);
    xhr.setRequestHeader('X-Requested-With','XMLHttpRequest');
    xhr.onload = function(){ try{ var j = JSON.parse(xhr.responseText||'{}'); } catch(e){ j=null; }
      if (xhr.status===200 && j && (j.status==='ok' || j.success===true)) { Swal.fire('Removed','Role removed','success').then(()=> location.reload()); }
      else { Swal.fire('Failed','Could not remove role','error'); }
    };
    xhr.onerror = function(){ Swal.fire('Error','Network error','error'); };
    xhr.send(fd);
  });
});
</script>
</body>
</html>
