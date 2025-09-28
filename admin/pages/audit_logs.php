<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/csrf.php';
requirePermission('settings');
$pageTitle = 'Audit Logs';
require_once __DIR__ . '/../includes/header.php';

$perPage = 30;
$page = max(1, (int)($_GET['page'] ?? 1));
$offset = ($page - 1) * $perPage;
$actionFilter = trim($_GET['action'] ?? '');
$where = '';
$params = [];
if ($actionFilter !== '') { $where = 'WHERE action = ?'; $params[] = $actionFilter; }
$count = $pdo->prepare("SELECT COUNT(*) FROM audit_logs {$where}"); $count->execute($params); $total = (int)$count->fetchColumn();
$stmt = $pdo->prepare("SELECT a.*, u.name as admin_name FROM audit_logs a LEFT JOIN users u ON u.id = a.user_id {$where} ORDER BY a.created_at DESC LIMIT ? OFFSET ?");
$stmt->bindValue(1, $perPage, PDO::PARAM_INT);
$stmt->bindValue(2, $offset, PDO::PARAM_INT);
if (!empty($params)) { $stmt->bindValue(3, $params[0]); }
$stmt->execute();
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
$totalPages = (int)ceil($total / $perPage);
?>
<div class="page-header"><h1>Audit Logs</h1></div>
<div class="card">
  <form method="get" style="display:flex;gap:8px;align-items:center;margin-bottom:12px;">
    <input name="action" placeholder="action filter" value="<?= htmlspecialchars($actionFilter) ?>">
    <button class="btn" type="submit">Filter</button>
  </form>
  <table class="roles-table">
    <thead><tr><th>ID</th><th>Admin</th><th>Action</th><th>Meta</th><th>IP</th><th>When</th></tr></thead>
    <tbody>
    <?php foreach ($rows as $r): ?>
      <tr>
        <td><?= htmlspecialchars($r['id']) ?></td>
        <td><?= htmlspecialchars($r['admin_name'] ?? 'System') ?></td>
        <td><?= htmlspecialchars($r['action']) ?></td>
        <td><?= htmlspecialchars($r['meta']) ?></td>
        <td><?= htmlspecialchars($r['ip'] ?? '') ?></td>
        <td><?= htmlspecialchars($r['created_at']) ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  <div style="margin-top:12px;display:flex;gap:8px;align-items:center;">
    <?php if ($page>1): ?><a class="btn" href="?page=<?= $page-1 ?>">&laquo; Prev</a><?php endif; ?>
    <div style="margin:0 8px;">Page <?= $page ?> of <?= $totalPages ?></div>
    <?php if ($page < $totalPages): ?><a class="btn" href="?page=<?= $page+1 ?>">Next &raquo;</a><?php endif; ?>
  </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php';
