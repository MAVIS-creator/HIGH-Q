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
            $stmt = $pdo->prepare('SELECT student_id FROM payments WHERE id = ?'); $stmt->execute([$id]); $p = $stmt->fetch();
            if ($p && !empty($p['student_id'])) { $act = $pdo->prepare('UPDATE users SET is_active = 1 WHERE id = ?'); $act->execute([$p['student_id']]); }
            echo json_encode(['status'=>'ok','message'=>'Payment confirmed']);
        } else echo json_encode(['status'=>'error','message'=>'DB error']);
        exit;
    }
    if ($action === 'reject') {
        $upd = $pdo->prepare('UPDATE payments SET status = "failed", updated_at = NOW() WHERE id = ?');
        $ok = $upd->execute([$id]);
        if ($ok) echo json_encode(['status'=>'ok','message'=>'Payment rejected']); else echo json_encode(['status'=>'error','message'=>'DB error']);
        exit;
    }
    echo json_encode(['status'=>'error','message'=>'Unknown action']); exit;
}

// List pending payments
$stmt = $pdo->query("SELECT p.*, u.email, u.name FROM payments p LEFT JOIN users u ON p.student_id = u.id ORDER BY p.created_at DESC");
$payments = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
