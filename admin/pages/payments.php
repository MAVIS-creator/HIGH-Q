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
        // mark payment confirmed
        $upd = $pdo->prepare('UPDATE payments SET status = "confirmed", confirmed_at = NOW(), updated_at = NOW() WHERE id = ?');
        $ok = $upd->execute([$id]);
        if ($ok) {
            // fetch payment details
            $stmt = $pdo->prepare('SELECT p.*, u.email, u.name, u.id as user_id FROM payments p LEFT JOIN users u ON u.id = p.student_id WHERE p.id = ?'); $stmt->execute([$id]); $p = $stmt->fetch();
            // update latest registration for this user to confirmed
            if ($p && !empty($p['user_id'])) {
                try {
                    // find latest non-confirmed registration
                    $r = $pdo->prepare('SELECT id FROM student_registrations WHERE user_id = ? ORDER BY created_at DESC LIMIT 1');
                    $r->execute([$p['user_id']]); $reg = $r->fetch();
                    if ($reg && !empty($reg['id'])) {
                        $uup = $pdo->prepare('UPDATE student_registrations SET status = ? WHERE id = ?');
                        $uup->execute(['confirmed', $reg['id']]);
                    }
                } catch (Throwable $e) { /* ignore if table missing */ }

                // generate a simple HTML receipt and save to uploads/receipts
                $receiptPath = '';
                try {
                    $uploads = __DIR__ . '/../../public/uploads/receipts/';
                    if (!is_dir($uploads)) mkdir($uploads, 0755, true);
                    // gather programs for the registration if available
                    $progHtml = '';
                    try {
                        $ps = $pdo->prepare('SELECT c.title, c.price FROM student_programs sp JOIN courses c ON c.id = sp.course_id WHERE sp.registration_id = ?');
                        if (!empty($reg['id'])) { $ps->execute([$reg['id']]); $rows = $ps->fetchAll(PDO::FETCH_ASSOC); foreach ($rows as $row) { $progHtml .= '<li>' . htmlspecialchars($row['title']) . ' - ₦' . number_format($row['price'],2) . '</li>'; } }
                    } catch (Throwable $ex) { /* ignore */ }

                    $html = '<!doctype html><html><head><meta charset="utf-8"><title>Receipt ' . htmlspecialchars($p['reference']) . '</title></head><body>';
                    $html .= '<h2>Payment Receipt</h2>';
                    $html .= '<p><strong>Reference:</strong> ' . htmlspecialchars($p['reference']) . '</p>';
                    $html .= '<p><strong>Name:</strong> ' . htmlspecialchars($p['name'] ?? '') . '</p>';
                    $html .= '<p><strong>Email:</strong> ' . htmlspecialchars($p['email'] ?? '') . '</p>';
                    $html .= '<p><strong>Amount:</strong> ₦' . number_format($p['amount'],2) . '</p>';
                    if ($progHtml) $html .= '<p><strong>Programs:</strong><ul>' . $progHtml . '</ul></p>';
                    $html .= '<p>Thank you for registering with HIGH Q Solid Academy.</p>';
                    $html .= '</body></html>';
                    $fn = 'receipt-' . preg_replace('/[^A-Za-z0-9\-]/','', $p['reference']) . '.html';
                    $fp = $uploads . $fn;
                    file_put_contents($fp, $html);
                    $receiptPath = 'uploads/receipts/' . $fn;
                    // update payments with receipt_path and confirmed_at
                    $upd2 = $pdo->prepare('UPDATE payments SET receipt_path = ?, confirmed_at = NOW() WHERE id = ?');
                    $upd2->execute([$receiptPath, $id]);
                } catch (Throwable $e) { /* ignore save errors */ }

                // activate user
                $act = $pdo->prepare('UPDATE users SET is_active = 1 WHERE id = ?'); $act->execute([$p['user_id']]);

                // send confirmation email to user (attach receipt if available)
                if (!empty($p['email'])) {
                    $subject = 'Payment Confirmed — HIGH Q SOLID ACADEMY';
                    $html = "<p>Hi " . htmlspecialchars($p['name']) . ",</p><p>Your payment (reference: " . htmlspecialchars($p['reference']) . ") has been confirmed. Your account is now active.</p>";
                    $attachments = [];
                    if (!empty($receiptPath)) {
                        $full = __DIR__ . '/../../public/' . ltrim($receiptPath, '/');
                        if (is_readable($full)) $attachments[] = $full;
                    }
                    @sendEmail($p['email'], $subject, $html, $attachments);
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
