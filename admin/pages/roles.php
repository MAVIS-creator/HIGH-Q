<?php
// admin/pages/roles.php
require './includes/auth.php';
require './includes/db.php';
require './includes/csrf.php';
require './includes/functions.php';

// Only Admins
requireRole(['admin']);

$csrf   = generateToken();
$errors = [];
$flash  = [];

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action'])) {
    if (!verifyToken($_POST['csrf_token'] ?? '')) {
        $errors[] = "Invalid CSRF token.";
    } else {
        $action = $_GET['action'];
        $id     = isset($_GET['id']) ? (int)$_GET['id'] : null;

        // Sanitize inputs
        $name      = trim($_POST['name']      ?? '');
        $slug      = trim($_POST['slug']      ?? '');
        $max_count = (int)($_POST['max_count'] ?? 0) ?: null;

        if ($action === 'create') {
            if (!$name || !$slug) {
                $errors[] = "Name and slug are required.";
            } else {
                $stmt = $pdo->prepare(
                    "INSERT INTO roles (name, slug, max_count) VALUES (?,?,?)"
                );
                $stmt->execute([$name, $slug, $max_count]);
                logAction($pdo, $_SESSION['user']['id'], 'role_created', ['slug'=>$slug]);
                $flash[] = "Role '{$name}' created.";
            }
        }

        if ($action === 'edit' && $id) {
            if (!$name || !$slug) {
                $errors[] = "Name and slug are required.";
            } else {
                $stmt = $pdo->prepare(
                    "UPDATE roles SET name=?, slug=?, max_count=? WHERE id=?"
                );
                $stmt->execute([$name, $slug, $max_count, $id]);
                logAction($pdo, $_SESSION['user']['id'], 'role_updated', ['role_id'=>$id]);
                $flash[] = "Role '{$name}' updated.";
            }
        }

        if ($action === 'delete' && $id) {
            // Optional: prevent deletion if users assigned
            $stmt = $pdo->prepare("DELETE FROM roles WHERE id=?");
            $stmt->execute([$id]);
            logAction($pdo, $_SESSION['user']['id'], 'role_deleted', ['role_id'=>$id]);
            $flash[] = "Role deleted.";
        }
    }

    // Redirect to avoid form resubmission
    header("Location: index.php?page=roles");
    exit;
}

// Fetch roles list
$roles = $pdo->query("SELECT * FROM roles ORDER BY id ASC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Roles Management - HIGH Q SOLID ACADEMY</title>
  <link rel="stylesheet" href="../public/assets/css/admin.css">
  <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
</head>
<body>
  <?php include '../includes/header.php'; ?>
  <?php include '../includes/sidebar.php'; ?>

  <div class="container" style="margin-left:240px;">
    <h1>Roles Management</h1>

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

    <button id="newRoleBtn" class="btn-approve"><i class='bx bx-plus'></i> New Role</button>

    <table class="roles-table">
      <thead>
        <tr>
          <th>ID</th>
          <th>Name</th>
          <th>Slug</th>
          <th>Max Users</th>
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
          <td>
            <button
              class="btn-editRole"
              data-id="<?= $r['id'] ?>"
              data-name="<?= htmlspecialchars($r['name']) ?>"
              data-slug="<?= htmlspecialchars($r['slug']) ?>"
              data-max="<?= $r['max_count'] ?>"
            >
              <i class='bx bx-edit'></i> Edit
            </button>
            <form
              method="post"
              action="index.php?page=roles&action=delete&id=<?= $r['id'] ?>"
              style="display:inline"
            >
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

      <form id="roleForm" method="post">
        <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
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
        <div class="form-actions">
          <button type="submit" class="btn-approve">Save Role</button>
        </div>
      </form>
    </div>
  </div>
  <div id="modalOverlay"></div>

  <?php include '../includes/footer.php'; ?>

  <script>
  // Modal control
  const roleModal    = document.getElementById('roleModal');
  const overlay      = document.getElementById('modalOverlay');
  const closeRoleBtn = document.getElementById('roleModalClose');
  const newRoleBtn   = document.getElementById('newRoleBtn');
  const roleForm     = document.getElementById('roleForm');
  const modalTitle   = document.getElementById('roleModalTitle');
  const nameInput    = document.getElementById('roleName');
  const slugInput    = document.getElementById('roleSlug');
  const maxInput     = document.getElementById('roleMax');

  function openModal(mode, role = {}) {
    overlay.classList.add('open');
    roleModal.classList.add('open');
    if (mode === 'edit') {
      modalTitle.textContent = 'Edit Role';
      roleForm.action      = `index.php?page=roles&action=edit&id=${role.id}`;
      nameInput.value      = role.name;
      slugInput.value      = role.slug;
      maxInput.value       = role.max_count || '';
    } else {
      modalTitle.textContent = 'New Role';
      roleForm.action       = 'index.php?page=roles&action=create';
      nameInput.value       = '';
      slugInput.value       = '';
      maxInput.value        = '';
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
        max_count: btn.dataset.max
      };
      openModal('edit', role);
    });
  });
  </script>
</body>
</html>
