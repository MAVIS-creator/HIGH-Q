<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
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
  <style>
    .icons-page{min-height:calc(100vh - 140px);padding:32px 18px 72px;background:#f6f7fb;display:flex;flex-direction:column;align-items:center;gap:18px;box-sizing:border-box}
    .icons-card{width:min(1120px,96%);background:#fff;border:1px solid #e9e9ef;border-radius:14px;box-shadow:0 10px 28px rgba(0,0,0,0.06);padding:22px 24px}
    .icons-card h2{margin:0 0 12px 0;font-size:1.6rem;color:#111}
    .icons-card h3{margin:0 0 12px 0;color:#222}
    .icons-table{width:100%;border-collapse:collapse}
    .icons-table th,.icons-table td{padding:10px 12px;border-bottom:1px solid #f0f0f0;text-align:left}
    .icons-table th{background:#fafafa;font-weight:700;color:#444}
    .icons-actions form{display:inline}
    .icons-form label{display:block;font-weight:600;margin:10px 0 6px}
    .icons-form input{width:100%;max-width:420px;padding:10px;border:1px solid #ddd;border-radius:10px;background:#fafafa}
    .icons-form button{margin-top:12px;padding:10px 16px;border:none;border-radius:10px;background:#ffd600;color:#111;font-weight:700;cursor:pointer}
    .icons-form button:hover{background:#f5c400}
  </style>
</head><body>
<?php include __DIR__ . '/../includes/header.php'; ?>

<div class="icons-page">
  <div class="icons-card">
    <h2>Icons</h2>
    <?php if ($errors): ?><div class="alert error"><?php foreach($errors as $e) echo '<p>'.htmlspecialchars($e).'</p>'; ?></div><?php endif; ?>
    <?php if ($success): ?><div class="alert success"><?php foreach($success as $s) echo '<p>'.htmlspecialchars($s).'</p>'; ?></div><?php endif; ?>

    <table class="icons-table">
      <thead><tr><th>ID</th><th>Name</th><th>Filename</th><th>Class</th><th>Preview</th><th>Actions</th></tr></thead>
      <tbody>
        <?php foreach($icons as $ic): ?>
        <tr>
          <td><?= $ic['id'] ?></td>
          <td><?= htmlspecialchars($ic['name']) ?></td>
          <td><?= htmlspecialchars($ic['filename']) ?></td>
          <td><?= htmlspecialchars($ic['class']) ?></td>
          <td><?php if ($ic['class']): ?><i class="<?= htmlspecialchars($ic['class']) ?>"></i><?php elseif ($ic['filename']): ?><img src="../public/assets/images/icons/<?= htmlspecialchars($ic['filename']) ?>" style="width:24px;height:24px"><?php endif; ?></td>
          <td class="icons-actions">
            <form method="post" action="index.php?pages=icons&action=delete&id=<?= $ic['id'] ?>">
              <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
              <button class="btn-delete">Delete</button>
            </form>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <div class="icons-card">
    <h3>Add Icon</h3>
    <form class="icons-form" method="post" action="index.php?pages=icons&action=create">
      <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
      <div><label>Name</label><input name="name" required></div>
      <div><label>Filename (optional)</label><input name="filename" placeholder="book-open.svg"></div>
      <div><label>Boxicons class (optional)</label><input name="class" placeholder="bx bxs-book-open"></div>
      <div><button type="submit">Add</button></div>
    </form>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
</body></html>
