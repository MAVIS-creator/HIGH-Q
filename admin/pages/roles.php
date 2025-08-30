<?php
// admin/pages/roles.php
require '../includes/auth.php';
require '../includes/db.php';
require '../includes/csrf.php';
require '../includes/functions.php';

// Only Admins
requireRole(['admin']);

$csrf = generateToken();
$errors = [];
$flash  = [];

// Handle create/edit/delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action'])) {
  if (!verifyToken($_POST['csrf_token'])) {
    $errors[] = "Invalid CSRF token.";
  } else {
    switch ($_GET['action']) {
      case 'create':
        $name      = trim($_POST['name']);
        $slug      = trim($_POST['slug']);
        $max_count = (int)$_POST['max_count'];
        if (!$name || !$slug) {
          $errors[] = "Name and slug are required.";
        } else {
          $stmt = $pdo->prepare("INSERT INTO roles (name, slug, max_count) VALUES (?,?,?)");
          $stmt->execute([$name, $slug, $max_count ?: null]);
          logAction($pdo, $_SESSION['user']['id'], "role_created", ['slug'=>$slug]);
          $flash[] = "Created role '{$name}'.";
        }
        break;
      case 'edit':
        $id        = (int)$_GET['id'];
        $name      = trim($_POST['name']);
        $slug      = trim($_POST['slug']);
        $max_count = (int)$_POST['max_count'];
        if (!$name || !$slug) {
          $errors[] = "Name and slug are required.";
        } else {
          $stmt = $pdo->prepare("UPDATE roles SET name=?, slug=?, max_count=? WHERE id=?");
          $stmt->execute([$name, $slug, $max_count ?: null, $id]);
          logAction($pdo, $_SESSION['user']['id'], "role_updated", ['role_id'=>$id]);
          $flash[] = "Updated role '{$name}'.";
        }
        break;
      case 'delete':
        $id = (int)$_GET['id'];
        // Optional: check no users are assigned this role
        $stmt = $pdo->prepare("DELETE FROM roles WHERE id=?");
        $stmt->execute([$id]);
        logAction($pdo, $_SESSION['user']['id'], "role_deleted", ['role_id'=>$id]);
        $flash[] = "Deleted role.";
        break;
    }
  }
  header("Location: index.php?page=roles");
  exit;
}

// Fetch all roles
$roles = $pdo->query("SELECT * FROM roles ORDER BY id ASC")->fetchAll();
?>
<div class="container">
  <h1>Roles Management</h1>
  <?php if ($flash): ?>
    <div class="alert success"><?= implode('<br>', $flash) ?></div>
  <?php endif; ?>
  <?php if ($errors): ?>
    <div class="alert error"><?= implode('<br>', $errors) ?></div>
  <?php endif; ?>

  <button id="newRoleBtn" class="btn-approve">+ New Role</button>

  <table>
    <tr><th>ID</th><th>Name</th><th>Slug</th><th>Max Users</th><th>Actions</th></tr>
    <?php foreach ($roles as $r): ?>
    <tr>
      <td><?= $r['id'] ?></td>
      <td><?= htmlspecialchars($r['name']) ?></td>
      <td><?= htmlspecialchars($r['slug']) ?></td>
      <td><?= $r['max_count'] ?? '∞' ?></td>
      <td>
        <button class="btn-editRole" data-id="<?= $r['id'] ?>"
                data-name="<?= htmlspecialchars($r['name']) ?>"
                data-slug="<?= htmlspecialchars($r['slug']) ?>"
                data-max="<?= (int)$r['max_count'] ?>">Edit</button>
        <form method="post" action="index.php?page=roles&action=delete&id=<?= $r['id'] ?>"
              style="display:inline">
          <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
          <button type="submit" class="btn-banish">Delete</button>
        </form>
      </td>
    </tr>
    <?php endforeach; ?>
  </table>
</div>

<!-- Modal for Create/Edit -->
<div class="modal" id="roleModal">…</div>
<div id="modalOverlay"></div>

<script>
  // JS to open #roleModal in “create” or “edit” mode, populate fields,
  // switch the form action to ?action=create or ?action=edit&id=…
</script>
