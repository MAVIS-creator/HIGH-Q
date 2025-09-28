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
    // Always respond with JSON for AJAX
    header('Content-Type: application/json');
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

                // generate a receipt (prefer PDF if Dompdf is available), save to uploads/receipts
                $receiptPath = '';
                try {
                    $uploads = __DIR__ . '/../../public/uploads/receipts/';
                    if (!is_dir($uploads)) mkdir($uploads, 0755, true);

                    // fetch latest registration row for this user (if any) to get address/email and programs
                    $reg = null;
                    try {
                        $r = $pdo->prepare('SELECT * FROM student_registrations WHERE user_id = ? ORDER BY created_at DESC LIMIT 1');
                        $r->execute([$p['user_id']]);
                        $reg = $r->fetch(PDO::FETCH_ASSOC);
                    } catch (Throwable $ex) { $reg = null; }

                    // gather programs for the registration if available
                    $progHtml = '';
                    try {
                        if (!empty($reg['id'])) {
                            $ps = $pdo->prepare('SELECT c.title, c.price FROM student_programs sp JOIN courses c ON c.id = sp.course_id WHERE sp.registration_id = ?');
                            $ps->execute([$reg['id']]);
                            $rows = $ps->fetchAll(PDO::FETCH_ASSOC);
                            foreach ($rows as $row) { $progHtml .= '<li>' . htmlspecialchars($row['title']) . ' - ₦' . number_format($row['price'],2) . '</li>'; }
                        }
                    } catch (Throwable $ex) { /* ignore */ }

                    $payerName = $reg['first_name'] . ' ' . $reg['last_name'];
                    $payerEmail = $reg['email'] ?? $p['email'] ?? '';
                    $payerAddress = $reg['home_address'] ?? '';

                    $html = '<!doctype html><html><head><meta charset="utf-8"><title>Receipt ' . htmlspecialchars($p['reference']) . '</title>';
                    $html .= '<style>body{font-family:Arial,Helvetica,sans-serif;color:#222} .container{max-width:700px;margin:0 auto;padding:24px} h2{color:#111} .meta{margin:12px 0;color:#444}</style>';
                    $html .= '</head><body><div class="container">';
                    $html .= '<h2>Payment Receipt</h2>';
                    $html .= '<div class="meta"><strong>Reference:</strong> ' . htmlspecialchars($p['reference']) . '</div>';
                    $html .= '<div class="meta"><strong>Name:</strong> ' . htmlspecialchars(trim($payerName)) . '</div>';
                    $html .= '<div class="meta"><strong>Email:</strong> ' . htmlspecialchars($payerEmail) . '</div>';
                    if ($payerAddress) $html .= '<div class="meta"><strong>Address:</strong> ' . nl2br(htmlspecialchars($payerAddress)) . '</div>';
                    $html .= '<div class="meta"><strong>Amount:</strong> ₦' . number_format($p['amount'],2) . '</div>';
                    if ($progHtml) $html .= '<div class="meta"><strong>Programs:</strong><ul>' . $progHtml . '</ul></div>';
                    $html .= '<p>Thank you for registering with HIGH Q Solid Academy.</p>';
                    $html .= '</div></body></html>';

                    // prefer PDF if library available
                    $fn = 'receipt-' . preg_replace('/[^A-Za-z0-9\-]/','', $p['reference']);
                    if (class_exists('\Dompdf\Dompdf')) {
                        // generate PDF
                        try {
                            $dompdf = new \Dompdf\Dompdf();
                            $dompdf->loadHtml($html);
                            $dompdf->setPaper('A4', 'portrait');
                            $dompdf->render();
                            $output = $dompdf->output();
                            $fn = $fn . '.pdf';
                            $fp = $uploads . $fn;
                            file_put_contents($fp, $output);
                            $receiptPath = 'uploads/receipts/' . $fn;
                        } catch (Throwable $e) {
                            // fallback to html
                            $fn = $fn . '.html';
                            $fp = $uploads . $fn;
                            file_put_contents($fp, $html);
                            $receiptPath = 'uploads/receipts/' . $fn;
                        }
                    } else {
                        // no dompdf installed - save HTML receipt as fallback
                        $fn = $fn . '.html';
                        $fp = $uploads . $fn;
                        file_put_contents($fp, $html);
                        $receiptPath = 'uploads/receipts/' . $fn;
                    }

                    // update payments with receipt_path and confirmed_at
                    $upd2 = $pdo->prepare('UPDATE payments SET receipt_path = ?, confirmed_at = NOW() WHERE id = ?');
                    $upd2->execute([$receiptPath, $id]);
                } catch (Throwable $e) { /* ignore save errors */ }

                // send confirmation email to user (attach receipt if available) and to admin
                try {
                    // user
                    if (!empty($p['email'])) {
                        $subject = 'Payment Confirmed — HIGH Q SOLID ACADEMY';
                        $htmlMail = "<p>Hi " . htmlspecialchars($p['name']) . ",</p><p>Your payment (reference: " . htmlspecialchars($p['reference']) . ") has been confirmed. Your account is now active.</p>";
                        $attachments = [];
                        if (!empty($receiptPath)) {
                            $full = __DIR__ . '/../../public/' . ltrim($receiptPath, '/');
                            if (is_readable($full)) $attachments[] = $full;
                        }
                        @sendEmail($p['email'], $subject, $htmlMail, $attachments);
                    }

                    // admin
                    $adminEmail = null;
                    try {
                        $r = $pdo->query("SELECT contact_email FROM site_settings LIMIT 1")->fetch(PDO::FETCH_ASSOC);
                        if (!empty($r['contact_email'])) $adminEmail = $r['contact_email'];
                    } catch (Throwable $ex) { $adminEmail = null; }
                    if (!empty($adminEmail)) {
                        $subject = 'Payment Confirmed: ' . htmlspecialchars($p['reference']);
                        $htmlMail = "<p>A payment has been confirmed.</p><p><strong>Reference:</strong> " . htmlspecialchars($p['reference']) . "</p>";
                        if (!empty($receiptPath)) {
                            $full = __DIR__ . '/../../public/' . ltrim($receiptPath, '/');
                            $attachments = is_readable($full) ? [$full] : [];
                        } else $attachments = [];
                        @sendEmail($adminEmail, $subject, $htmlMail, $attachments);
                    }
                } catch (Throwable $e) { /* ignore email errors */ }

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
    <div style="margin:12px 0;display:flex;gap:12px;align-items:center;">
        <form method="get" style="margin:0;display:flex;gap:8px;align-items:center;">
            <label for="statusFilter">Status:</label>
            <select id="statusFilter" name="status">
                <option value=""<?= $statusFilter===''? ' selected':'' ?>>All</option>
                <option value="pending"<?= $statusFilter==='pending'? ' selected':'' ?>>Pending</option>
                <option value="confirmed"<?= $statusFilter==='confirmed'? ' selected':'' ?>>Confirmed</option>
                <option value="failed"<?= $statusFilter==='failed'? ' selected':'' ?>>Failed</option>
            </select>
            <button class="btn" type="submit">Filter</button>
        </form>
        <div style="margin-left:auto;color:#666;font-size:13px;">Search: <em><?= htmlspecialchars($search) ?></em></div>
    </div>
    <table class="roles-table">
        <thead><tr><th>ID</th><th>Reference</th><th>User</th><th>Amount</th><th>Method</th><th>Status</th><th>Payer</th><th>Payer Account</th><th>Payer Bank</th><th>Created</th><th>Actions</th></tr></thead>
        <tbody>
        <?php foreach($payments as $p): ?>
            <tr>
                <td><?= htmlspecialchars($p['id']) ?></td>
                <td><?= htmlspecialchars($p['reference']) ?></td>
                <td><?= htmlspecialchars($p['name'] . ' <' . $p['email'] . '>') ?></td>
                <td><?= htmlspecialchars(number_format($p['amount'],2)) ?></td>
                <td><?= htmlspecialchars($p['gateway'] ?? $p['payment_method']) ?></td>
                <td><?= htmlspecialchars($p['status']) ?></td>
                <td><?= htmlspecialchars($p['payer_account_name'] ?? '') ?></td>
                <td><?= htmlspecialchars($p['payer_account_number'] ?? '') ?></td>
                <td><?= htmlspecialchars($p['payer_bank_name'] ?? '') ?></td>
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
    xhr.onload = function(){
        var text = xhr.responseText || '';
        try {
            var res = JSON.parse(text);
            if (res.status === 'ok') location.reload(); else alert(res.message || 'Error');
        } catch (e) {
            // Not JSON — show raw response for debugging
            alert('Unexpected response from server:\n' + text);
        }
    };
    xhr.send(fd);
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php';
