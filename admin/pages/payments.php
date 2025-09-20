<?php
// admin/pages/payments.php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
requirePermission('payments');
require_once __DIR__ . '/../includes/db.php';

$pageTitle = 'Payments';
require_once __DIR__ . '/../includes/header.php';

// handle ajax actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
    $token = $_POST['_csrf'] ?? '';
    if (!verifyToken('payments_form', $token)) { echo json_encode(['status'=>'error','message'=>'Invalid CSRF']); exit; }
    $action = $_POST['action'] ?? '';
    $id = intval($_POST['id'] ?? 0);
    if ($action === 'confirm') {
        $upd = $pdo->prepare('UPDATE payments SET status = "confirmed", confirmed_at = NOW(), updated_at = NOW() WHERE id = ?');
        $ok = $upd->execute([$id]);
        if ($ok) {
            // activate user
            $stmt = $pdo->prepare('SELECT student_id, reference FROM payments WHERE id = ?'); $stmt->execute([$id]); $p = $stmt->fetch();
            if ($p && !empty($p['student_id'])) { $act = $pdo->prepare('UPDATE users SET is_active = 1 WHERE id = ?'); $act->execute([$p['student_id']]);
                // send confirmation email to user
                $u = $pdo->prepare('SELECT email, name FROM users WHERE id = ? LIMIT 1'); $u->execute([$p['student_id']]); $user = $u->fetch();
                if ($user && !empty($user['email'])) {
                    $subject = 'Payment Confirmed — HIGH Q SOLID ACADEMY';
                    $html = "<p>Hi " . htmlspecialchars($user['name']) . ",</p><p>Your payment (reference: " . htmlspecialchars($p['reference']) . ") has been confirmed. Your account is now active.</p>";
                    $attachments = [];
                    if (!empty($p['receipt_path'])) {
                        $fp = __DIR__ . '/../../public/' . ltrim($p['receipt_path'], '/');
                        if (is_readable($fp)) $attachments[] = $fp;
                    }
                    @sendEmail($user['email'], $subject, $html, $attachments);
                }
            }
            echo json_encode(['status'=>'ok','message'=>'Payment confirmed']);
        } else echo json_encode(['status'=>'error','message'=>'DB error']);
        exit;
    }
    if ($action === 'reject') {
        $upd = $pdo->prepare('UPDATE payments SET status = "failed", updated_at = NOW() WHERE id = ?');
        $ok = $upd->execute([$id]);
        if ($ok) {
            // notify user of rejection
            $stmt = $pdo->prepare('SELECT student_id, reference FROM payments WHERE id = ?'); $stmt->execute([$id]); $p = $stmt->fetch();
            if ($p && !empty($p['student_id'])) { $u = $pdo->prepare('SELECT email, name FROM users WHERE id = ? LIMIT 1'); $u->execute([$p['student_id']]); $user = $u->fetch();
                if ($user && !empty($user['email'])) {
                    $subject = 'Payment Not Accepted — HIGH Q SOLID ACADEMY';
                    $html = "<p>Hi " . htmlspecialchars($user['name']) . ",</p><p>We could not accept your payment (reference: " . htmlspecialchars($p['reference']) . "). Please review and contact support.</p>";
                    @sendEmail($user['email'], $subject, $html);
                }
            }
            echo json_encode(['status'=>'ok','message'=>'Payment rejected']);
        } else echo json_encode(['status'=>'error','message'=>'DB error']);
        exit;
    }
    echo json_encode(['status'=>'error','message'=>'Unknown action']); exit;
}

// List payments with pagination, search & filter
$perPage = 20;
$page = max(1, (int)($_GET['page'] ?? 1));
$statusFilter = $_GET['status'] ?? '';
$search = trim($_GET['q'] ?? '');
$params = [];
$where = [];
if ($statusFilter !== '') { $where[] = 'p.status = ?'; $params[] = $statusFilter; }
if ($search !== '') { $where[] = '(p.reference LIKE ? OR u.email LIKE ? OR u.name LIKE ?)'; $params[] = "%{$search}%"; $params[] = "%{$search}%"; $params[] = "%{$search}%"; }
$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM payments p LEFT JOIN users u ON p.student_id = u.id {$whereSql}");
$countStmt->execute($params);
$total = (int)$countStmt->fetchColumn();
$offset = ($page - 1) * $perPage;
$sql = "SELECT p.*, u.email, u.name FROM payments p LEFT JOIN users u ON p.student_id = u.id {$whereSql} ORDER BY p.created_at DESC LIMIT {$perPage} OFFSET {$offset}";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
$totalPages = (int)ceil($total / $perPage);

?>
<div class="roles-page">
    <div class="page-header"><h1><i class="bx bxs-credit-card"></i> Payments</h1></div>
    <table class="roles-table">
        <thead><tr><th>ID</th><th>Reference</th><th>User</th><th>Amount</th><th>Method</th><th>Status</th><th>Created</th><th>Actions</th></tr></thead>
        <tbody>
        <?php foreach($payments as $p): ?>
            <tr>
                <td><?= htmlspecialchars($p['id']) ?></td>
                <td><?= htmlspecialchars($p['reference']) ?></td>
                <td><?= htmlspecialchars($p['name'] . ' <' . $p['email'] . '>') ?></td>
                <td><?= htmlspecialchars(number_format($p['amount'],2)) ?></td>
                <td><?= htmlspecialchars($p['gateway'] ?? $p['payment_method']) ?></td>
                <td><?= htmlspecialchars($p['status']) ?></td>
                <td><?= htmlspecialchars($p['created_at']) ?></td>
                <td>
                    <?php if ($p['status'] === 'pending'): ?>
                        <button class="btn" onclick="doAction('confirm',<?= $p['id'] ?>)">Confirm</button>
                        <button class="btn" onclick="doAction('reject',<?= $p['id'] ?>)">Reject</button>
                    <?php else: ?>
                        &mdash;
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<script>
function doAction(action,id){
    if (!confirm('Are you sure?')) return;
    var fd = new FormData(); fd.append('action', action); fd.append('id', id); fd.append('_csrf', '<?= generateToken('payments_form') ?>');
    var xhr = new XMLHttpRequest(); xhr.open('POST', location.href, true); xhr.setRequestHeader('X-Requested-With','XMLHttpRequest');
    xhr.onload = function(){ try { var res = JSON.parse(xhr.responseText); } catch(e){ alert('Unexpected'); return; } if (res.status==='ok') location.reload(); else alert(res.message||'Error'); };
    xhr.send(fd);
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php';
