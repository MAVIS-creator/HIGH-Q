<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../includes/csrf.php';

requirePermission('courses');

$csrf = generateToken();
$errors = [];
$success = [];

// handle add/edit/delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action'])) {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid CSRF token';
    } else {
        $act = $_GET['action'];
        if ($act === 'create') {
            $name = trim($_POST['name'] ?? '');
            $filename = trim($_POST['filename'] ?? '');
            $class = trim($_POST['class'] ?? '');
            if (!$name) $errors[] = 'Name required';
            if (!$filename && !$class) $errors[] = 'Either filename or class is required';
            if (!$errors) {
                $stmt = $pdo->prepare('INSERT INTO icons (name, filename, `class`) VALUES (?,?,?)');
                $stmt->execute([$name, $filename ?: null, $class ?: null]);
                $success[] = 'Icon added';
            }
        }
        if ($act === 'delete' && isset($_GET['id'])) {
            $id = (int)$_GET['id'];
            $pdo->prepare('DELETE FROM icons WHERE id = ?')->execute([$id]);
            $success[] = 'Icon deleted';
        }
    }
    header('Location: index.php?pages=icons'); exit;
}

$icons = $pdo->query('SELECT * FROM icons ORDER BY name')->fetchAll();

?>
<!DOCTYPE html>
<html><head>
  <title>Icons - Admin</title>
  <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
</head><body>
<?php include __DIR__ . '/../includes/header.php'; ?>
<div class="admin-main">
  <h2>Icons</h2>
  <?php if ($errors): ?><div class="alert error"><?php foreach($errors as $e) echo '<p>'.htmlspecialchars($e).'</p>'; ?></div><?php endif; ?>
  <?php if ($success): ?><div class="alert success"><?php foreach($success as $s) echo '<p>'.htmlspecialchars($s).'</p>'; ?></div><?php endif; ?>

  <table class="table">
    <thead><tr><th>ID</th><th>Name</th><th>Filename</th><th>Class</th><th>Preview</th><th>Actions</th></tr></thead>
    <tbody>
      <?php foreach($icons as $ic): ?>
      <tr>
        <td><?= $ic['id'] ?></td>
        <td><?= htmlspecialchars($ic['name']) ?></td>
        <td><?= htmlspecialchars($ic['filename']) ?></td>
        <td><?= htmlspecialchars($ic['class']) ?></td>
        <td><?php if ($ic['class']): ?><i class="<?= htmlspecialchars($ic['class']) ?>"></i><?php elseif ($ic['filename']): ?><img src="../public/assets/images/icons/<?= htmlspecialchars($ic['filename']) ?>" style="width:24px;height:24px"><?php endif; ?></td>
        <td>
          <form method="post" action="?pages=icons&action=delete&id=<?= $ic['id'] ?>" style="display:inline">
            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
            <button class="btn-delete">Delete</button>
          </form>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>

  <h3>Add Icon</h3>
  <form method="post" action="?pages=icons&action=create">
    <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
    <div><label>Name</label><input name="name"></div>
    <div><label>Filename (optional)</label><input name="filename"></div>
    <div><label>Boxicons class (optional)</label><input name="class"></div>
    <div><button type="submit">Add</button></div>
  </form>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
</body></html>
