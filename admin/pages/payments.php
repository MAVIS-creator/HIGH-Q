<?php
// admin/pages/payments.php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

// Ensure logs directory exists for AJAX handlers
try { if (!is_dir(__DIR__ . '/../../storage/logs')) @mkdir(__DIR__ . '/../../storage/logs', 0755, true); } catch (Throwable $e) {}

// handle ajax actions - keep this before any header/template output so AJAX gets JSON only
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
    // Always respond with JSON for AJAX
    header('Content-Type: application/json');
    // quick auth/session check - return JSON error if not logged in instead of redirecting
    if (empty($_SESSION['user'])) {
        echo json_encode(['status'=>'error','message'=>'Not authenticated']); exit;
    }
    // Simple rate limiter: max 6 actions per 30 seconds per session
    if (!isset($_SESSION['payments_rate'])) $_SESSION['payments_rate'] = ['count'=>0,'time'=>time()];
    $rate = &$_SESSION['payments_rate'];
    if (time() - $rate['time'] > 30) { $rate['count'] = 0; $rate['time'] = time(); }
    $rate['count']++;
    if ($rate['count'] > 6) { echo json_encode(['status'=>'error','message'=>'Rate limit exceeded, try again later']); exit; }
    $token = $_POST['_csrf'] ?? '';
    // verify CSRF; if invalid, log details for debugging (safe, local only)
    if (!verifyToken('payments_form', $token)) {
        $resp = ['status'=>'error','message'=>'Invalid CSRF'];
        // log
        try {
            $logDir = __DIR__ . '/../../storage/logs'; if (!is_dir($logDir)) @mkdir($logDir, 0755, true);
            $logFile = $logDir . '/payments_ajax.log';
            $entry = "[".date('Y-m-d H:i:s')."] Invalid CSRF on payments.ajax - IP=".($_SERVER['REMOTE_ADDR']??'')." Session=".session_id()." POST=".json_encode(array_keys($_POST))."\n";
            @file_put_contents($logFile, $entry, FILE_APPEND | LOCK_EX);
        } catch (Throwable $e) {}
        // include debug details when requested via GET
        if (!empty($_GET['debug']) && $_GET['debug']==='1') $resp['debug_post'] = $_POST;
        echo json_encode($resp); exit;
    }
    $action = $_POST['action'] ?? '';
    $id = intval($_POST['id'] ?? 0);
    if ($action === 'confirm') {
        // ensure idempotent: only confirm if not already confirmed
        $cur = $pdo->prepare('SELECT status FROM payments WHERE id = ? LIMIT 1'); $cur->execute([$id]); $curS = $cur->fetchColumn();
    if ($curS === 'confirmed') { echo json_encode(['status'=>'ok','message'=>'Already confirmed']); exit; }
        // mark payment confirmed
        $upd = $pdo->prepare('UPDATE payments SET status = "confirmed", confirmed_at = NOW(), updated_at = NOW() WHERE id = ?');
        $ok = $upd->execute([$id]);
    if ($ok) {
            // log action: admin confirmed payment
            try { logAction($pdo, (int)($_SESSION['user']['id'] ?? 0), 'confirm_payment', ['payment_id'=>$id]); } catch(Throwable $e){}
            // fetch payment details
                // include receipt download link in admin email log (if any)
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

                    $payerName = trim(($reg['first_name'] ?? '') . ' ' . ($reg['last_name'] ?? ''));
                    // Prefer registration email when available (for guest registrations)
                    $payerEmail = $p['email'] ?? '';
                    try {
                        if (empty($payerEmail) && !empty($p['registration_id'])) {
                            $r2 = $pdo->prepare('SELECT email FROM student_registrations WHERE id = ? LIMIT 1'); $r2->execute([$p['registration_id']]); $rr = $r2->fetch(PDO::FETCH_ASSOC);
                            if (!empty($rr['email'])) $payerEmail = $rr['email'];
                        }
                        // fallback to registration row fetched earlier
                        if (empty($payerEmail) && !empty($reg['email'])) $payerEmail = $reg['email'];
                    } catch (Throwable $e) { /* ignore */ }
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
                        // generate PDF using Dompdf if available
                        try {
                            $dompdfClass = '\\Dompdf\\Dompdf';
                            $dompdf = new $dompdfClass();
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
                            // Branded HTML email
                            $site = 'HIGH Q'; $logo = '';
                            try { $s = $pdo->query('SELECT site_name, logo_url FROM site_settings LIMIT 1')->fetch(PDO::FETCH_ASSOC); if (!empty($s['site_name'])) $site = $s['site_name']; if (!empty($s['logo_url'])) $logo = $s['logo_url']; } catch(Throwable $e){}
                            $subject = $site . ' — Payment Confirmed';
                            $htmlMail = '<!doctype html><html><head><meta charset="utf-8"><title>' . htmlspecialchars($subject) . '</title>';
                            $htmlMail .= '<style>body{font-family:Arial,Helvetica,sans-serif;color:#333}.container{max-width:640px;margin:0 auto;padding:18px}.btn{display:inline-block;padding:8px 12px;background:#d62828;color:#fff;border-radius:6px;text-decoration:none}</style>';
                            $htmlMail .= '</head><body><div class="container">';
                            if ($logo) $htmlMail .= '<div style="margin-bottom:12px"><img src="' . htmlspecialchars($logo) . '" alt="' . htmlspecialchars($site) . '" style="max-height:60px"></div>';
                            $htmlMail .= '<h2>Hi ' . htmlspecialchars($p['name'] ?? '') . '</h2>';
                            $htmlMail .= '<p>Your payment with reference <strong>' . htmlspecialchars($p['reference']) . '</strong> has been confirmed. Your account has been activated.</p>';
                                if (!empty($receiptPath)) {
                                // Use app_url() so APP_URL from .env (or computed fallback) is used consistently
                                $link = app_url($receiptPath);
                                $htmlMail .= '<p><a class="btn" href="' . htmlspecialchars($link) . '" target="_blank">Download Receipt</a></p>';
                            }
                            $htmlMail .= '<p>Thanks,<br>' . htmlspecialchars($site) . ' team</p>';
                            $htmlMail .= '</div></body></html>';
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
        $reason = trim($_POST['reason'] ?? '');
        $upd = $pdo->prepare('UPDATE payments SET status = "failed", updated_at = NOW() WHERE id = ?');
        $ok = $upd->execute([$id]);
    if ($ok) {
            // log action: admin rejected payment
            try { logAction($pdo, (int)($_SESSION['user']['id'] ?? 0), 'reject_payment', ['payment_id'=>$id,'reason'=>$reason]); } catch(Throwable $e){}
            // notify user of rejection
            $stmt = $pdo->prepare('SELECT student_id, reference FROM payments WHERE id = ?'); $stmt->execute([$id]); $p = $stmt->fetch();
                if ($p && !empty($p['student_id'])) { $u = $pdo->prepare('SELECT email, name FROM users WHERE id = ? LIMIT 1'); $u->execute([$p['student_id']]); $user = $u->fetch();
                if ($user && !empty($user['email'])) {
                    $site = 'HIGH Q'; $logo = '';
                    try { $s = $pdo->query('SELECT site_name, logo_url FROM site_settings LIMIT 1')->fetch(PDO::FETCH_ASSOC); if (!empty($s['site_name'])) $site = $s['site_name']; if (!empty($s['logo_url'])) $logo = $s['logo_url']; } catch(Throwable $e){}
                    $subject = $site . ' — Payment Not Accepted';
                    $html = '<!doctype html><html><head><meta charset="utf-8"><title>' . htmlspecialchars($subject) . '</title>';
                    $html .= '<style>body{font-family:Arial,Helvetica,sans-serif;color:#333}.container{max-width:640px;margin:0 auto;padding:18px}</style>';
                    $html .= '</head><body><div class="container">';
                    if ($logo) $html .= '<div style="margin-bottom:12px"><img src="' . htmlspecialchars($logo) . '" alt="' . htmlspecialchars($site) . '" style="max-height:60px"></div>';
                    $html .= '<h2>Hi ' . htmlspecialchars($user['name'] ?? '') . '</h2>';
                    $html .= '<p>Unfortunately, we could not accept your payment (reference: <strong>' . htmlspecialchars($p['reference']) . '</strong>).</p>';
                    if (!empty($reason)) $html .= '<p><strong>Reason:</strong> ' . htmlspecialchars($reason) . '</p>';
                    $html .= '<p>Please contact support for assistance.</p>';
                    $html .= '<p>Regards,<br>' . htmlspecialchars($site) . ' team</p>';
                    $html .= '</div></body></html>';
                    @sendEmail($user['email'], $subject, $html);
                }
            }
            echo json_encode(['status'=>'ok','message'=>'Payment rejected']);
        } else echo json_encode(['status'=>'error','message'=>'DB error']);
        exit;
    }
    // Unknown action - log and optionally return debug info
    $resp = ['status'=>'error','message'=>'Unknown action'];
    try {
        $logDir = __DIR__ . '/../../storage/logs'; if (!is_dir($logDir)) @mkdir($logDir, 0755, true);
        $logFile = $logDir . '/payments_ajax.log';
        $entry = "[".date('Y-m-d H:i:s')."] Unknown payments.ajax action - action=".($action?:'')." POST=".json_encode(array_keys($_POST))."\n";
        @file_put_contents($logFile, $entry, FILE_APPEND | LOCK_EX);
    } catch (Throwable $e) {}
    if (!empty($_GET['debug']) && $_GET['debug']==='1') $resp['debug_post'] = $_POST;
    echo json_encode($resp); exit;
}

// Now verify permission for the rest of the page (non-AJAX)
requirePermission('payments');

$pageTitle = 'Payments';
$pageCss = '<style>
.payments-filter-section { background:#fff; border-radius:12px; padding:1.5rem; margin-bottom:1.5rem; box-shadow:0 2px 10px rgba(0,0,0,0.04); }
.payments-filter-section h3 { margin:0 0 1.25rem 0; font-size:1.1rem; font-weight:600; color:#111; display:flex; align-items:center; gap:8px; }
.payments-filter-section h3 i { font-size:20px; color:#ffd600; }
.payment-filters { display:flex; flex-direction:column; gap:1rem; }
.filter-row { display:grid; grid-template-columns:repeat(auto-fit, minmax(200px, 1fr)); gap:1rem; align-items:end; }
.filter-group { display:flex; flex-direction:column; gap:0.5rem; }
.filter-group label { font-size:0.9rem; font-weight:600; color:#444; }
.filter-group input, .filter-group select { padding:0.75rem 1rem; border-radius:8px; border:1px solid #e0e0e0; background:#fafafa; font-size:0.95rem; transition:all 0.2s ease; }
.filter-group input:focus, .filter-group select:focus { outline:none; border-color:#ffd600; background:#fff; box-shadow:0 0 0 3px rgba(255,214,0,0.1); }
.filter-actions { display:flex; gap:0.75rem; align-items:center; }
.btn-filter { background:linear-gradient(180deg, #ffd24d, #f6c23a); border:none; color:#111; padding:0.75rem 1.5rem; border-radius:8px; cursor:pointer; font-weight:600; font-size:0.95rem; box-shadow:0 4px 12px rgba(246,194,58,0.25); display:flex; align-items:center; gap:8px; transition:all 0.2s ease; }
.btn-filter:hover { filter:brightness(0.98); transform:translateY(-1px); box-shadow:0 6px 16px rgba(246,194,58,0.35); }
.btn-clear { background:#fff; border:2px solid #e0e0e0; color:#666; padding:0.75rem 1.5rem; border-radius:8px; cursor:pointer; font-weight:600; font-size:0.95rem; text-decoration:none; display:flex; align-items:center; gap:8px; transition:all 0.2s ease; }
.btn-clear:hover { background:#f8f8f8; border-color:#ccc; }
.page-header-info { font-size:0.9rem; color:#666; margin-top:0.25rem; }
.page-header-info em { color:#ffd600; font-weight:600; font-style:normal; }
@media (max-width:768px) { .filter-row { grid-template-columns:1fr; } .filter-actions { flex-direction:column; width:100%; } .btn-filter, .btn-clear { width:100%; justify-content:center; } }
</style>';
require_once __DIR__ . '/../includes/header.php';

// List payments with pagination, search & filter
$perPage = 20;
$page = max(1, (int)($_GET['page'] ?? 1));
$statusFilter = $_GET['status'] ?? '';
$search = trim($_GET['q'] ?? '');
$fromDate = $_GET['from_date'] ?? '';
$toDate = $_GET['to_date'] ?? '';
$gateway = trim($_GET['gateway'] ?? '');
$refFilter = trim($_GET['ref'] ?? '');
$userEmail = trim($_GET['user_email'] ?? '');
$params = [];
$where = [];
if ($statusFilter !== '') { $where[] = 'LOWER(p.status) = ?'; $params[] = strtolower($statusFilter); }
if ($search !== '') { $where[] = '(p.reference LIKE ? OR u.email LIKE ? OR u.name LIKE ?)'; $params[] = "%{$search}%"; $params[] = "%{$search}%"; $params[] = "%{$search}%"; }
if ($fromDate !== '') { $where[] = 'p.created_at >= ?'; $params[] = $fromDate . ' 00:00:00'; }
if ($toDate !== '') { $where[] = 'p.created_at <= ?'; $params[] = $toDate . ' 23:59:59'; }
if ($gateway !== '') { $where[] = 'p.gateway LIKE ?'; $params[] = "%{$gateway}%"; }
if ($refFilter !== '') { $where[] = 'p.reference LIKE ?'; $params[] = "%{$refFilter}%"; }
if ($userEmail !== '') { $where[] = 'u.email LIKE ?'; $params[] = "%{$userEmail}%"; }
$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM payments p {$whereSql}");
$countStmt->execute($params);
$total = (int)$countStmt->fetchColumn();
$offset = ($page - 1) * $perPage;
$sql = "SELECT p.* FROM payments p {$whereSql} ORDER BY p.created_at DESC LIMIT {$perPage} OFFSET {$offset}";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
$totalPages = (int)ceil($total / $perPage);

?>
<div class="roles-page">
    <div class="page-header">
        <h1><i class="bx bxs-credit-card"></i> Payments</h1>
        <?php if($search): ?>
            <div class="page-header-info">Showing results for: <em><?= htmlspecialchars($search) ?></em></div>
        <?php endif; ?>
    </div>
    <script>window.__PAYMENTS_CSRF = '<?= generateToken('payments_form') ?>';</script>
    
    <div class="payments-filter-section">
        <h3><i class="bx bx-filter"></i> Filter Payments</h3>
        <form method="get" action="index.php?pages=payments" class="payment-filters">
            <input type="hidden" name="pages" value="payments">
            
            <div class="filter-row">
                <div class="filter-group">
                    <label for="statusFilter">Status</label>
                    <select id="statusFilter" name="status">
                        <option value=""<?= $statusFilter===''? ' selected':'' ?>>All Statuses</option>
                        <option value="pending"<?= $statusFilter==='pending'? ' selected':'' ?>>Pending</option>
                        <option value="sent"<?= $statusFilter==='sent'? ' selected':'' ?>>Sent</option>
                        <option value="confirmed"<?= $statusFilter==='confirmed'? ' selected':'' ?>>Confirmed</option>
                        <option value="failed"<?= $statusFilter==='failed'? ' selected':'' ?>>Failed</option>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label for="from_date">From Date</label>
                    <input type="date" id="from_date" name="from_date" value="<?= htmlspecialchars($_GET['from_date'] ?? '') ?>">
                </div>
                
                <div class="filter-group">
                    <label for="to_date">To Date</label>
                    <input type="date" id="to_date" name="to_date" value="<?= htmlspecialchars($_GET['to_date'] ?? '') ?>">
                </div>
                
                <div class="filter-group">
                    <label for="gateway">Gateway</label>
                    <input type="text" id="gateway" name="gateway" placeholder="e.g. paystack, stripe" value="<?= htmlspecialchars($_GET['gateway'] ?? '') ?>">
                </div>
            </div>
            
            <div class="filter-row">
                <div class="filter-group">
                    <label for="ref">Reference</label>
                    <input type="text" id="ref" name="ref" placeholder="Payment reference" value="<?= htmlspecialchars($_GET['ref'] ?? '') ?>">
                </div>
                
                <div class="filter-group">
                    <label for="user_email">Email/User</label>
                    <input type="text" id="user_email" name="user_email" placeholder="User email" value="<?= htmlspecialchars($_GET['user_email'] ?? '') ?>">
                </div>
                
                <div class="filter-actions">
                    <button type="submit" class="btn-filter">
                        <i class="bx bx-search"></i> Apply Filters
                    </button>
                    <a href="index.php?pages=payments" class="btn-clear">
                        <i class="bx bx-x"></i> Clear
                    </a>
                </div>
            </div>
        </form>
    </div>
    <table class="roles-table">
    <thead><tr><th>ID</th><th>Reference</th><th>Payer Name</th><th>Amount</th><th>Method</th><th>Status</th><th>Payer Account</th><th>Payer Bank</th><th>Created</th><th>Actions</th></tr></thead>
        <tbody>
        <?php foreach($payments as $p): ?>
            <?php
                // Compute a human-friendly display status when DB value is missing/null
                $displayStatus = $p['status'] ?? null;
                if (empty($displayStatus)) {
                    if (!empty($p['payer_account_number']) || !empty($p['payer_account_name']) || !empty($p['payer_bank_name'])) {
                        $displayStatus = 'sent';
                    } elseif (!empty($p['reference'])) {
                        $displayStatus = 'pending';
                    } else {
                        $displayStatus = 'unknown';
                    }
                }

                $dataAttrs = 'data-payment-id="' . htmlspecialchars($p['id']) . '"'
                    . ' data-payer-name="' . htmlspecialchars($p['payer_account_name'] ?? '') . '"'
                    . ' data-payer-account="' . htmlspecialchars($p['payer_account_number'] ?? '') . '"'
                    . ' data-payer-bank="' . htmlspecialchars($p['payer_bank_name'] ?? '') . '"'
                    . ' data-reference="' . htmlspecialchars($p['reference'] ?? '') . '"'
                    . ' data-email="' . htmlspecialchars($p['email'] ?? '') . '"'
                    . ' data-amount="' . htmlspecialchars(number_format($p['amount'],2)) . '"'
                    . ' data-display-status="' . htmlspecialchars($displayStatus) . '"';
            ?>
            <tr <?= $dataAttrs ?> >
                <td><?= htmlspecialchars($p['id']) ?></td>
                <td><?= htmlspecialchars($p['reference']) ?></td>
                <td><?= htmlspecialchars($p['payer_account_name'] ?? ($p['name'] ?? '')) ?></td>
                <td><?= htmlspecialchars(number_format($p['amount'],2)) ?></td>
                <td><?= htmlspecialchars($p['gateway'] ?? $p['payment_method']) ?></td>
                <td><?= htmlspecialchars(ucfirst($displayStatus)) ?></td>
                <td><?= htmlspecialchars($p['payer_account_number'] ?? '') ?></td>
                <td><?= htmlspecialchars($p['payer_bank_name'] ?? '') ?></td>
                <td><?= htmlspecialchars($p['created_at']) ?></td>
                <td>
                    <?php if (!empty($p['receipt_path'])): ?><a class="btn" href="<?= htmlspecialchars($p['receipt_path']) ?>" target="_blank">Download</a><?php endif; ?>
                    <?php if ($displayStatus === 'pending' || $displayStatus === 'sent'): ?>
                        <div style="margin-top:6px;">
                            <button class="btn" onclick="doAction('confirm',<?= $p['id'] ?>)">Confirm</button>
                            <button class="btn" onclick="doAction('reject',<?= $p['id'] ?>)">Reject</button>
                        </div>
                    <?php else: ?>
                        <!-- no actions -->
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <!-- Pagination (numbered) -->
    <div style="margin-top:12px;display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
        <?php
            // build base query preserving filters
            $qp = $_GET; // current query params
            $makeLink = function($pnum) use ($qp) {
                $qp['page'] = $pnum; return '?' . http_build_query($qp);
            };
            echo '<nav aria-label="Pages">';
            // if many pages, show a sliding window around current page
            $window = 3; // pages before/after
            $start = max(1, $page - $window);
            $end = min($totalPages, $page + $window);
            if ($start > 1) {
                echo '<a class="btn" href="' . $makeLink(1) . '">1</a>';
                if ($start > 2) echo '<span style="padding:6px 8px;color:#666">&hellip;</span>';
            }
            for ($i = $start; $i <= $end; $i++) {
                if ($i == $page) echo '<span style="padding:6px 10px;background:#111;color:#fff;border-radius:4px">' . $i . '</span>'; else echo '<a class="btn" href="' . $makeLink($i) . '">' . $i . '</a>';
            }
            if ($end < $totalPages) {
                if ($end < $totalPages - 1) echo '<span style="padding:6px 8px;color:#666">&hellip;</span>';
                echo '<a class="btn" href="' . $makeLink($totalPages) . '">' . $totalPages . '</a>';
            }
            echo '</nav>';
        ?>
    </div>
</div>

<!-- simple action handler removed in favor of SweetAlert2 modal-based handler below -->

<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
function showToast(type, title){
    Swal.fire({toast:true,position:'top-end',icon:type,title:title,showConfirmButton:false,timer:3000});
}

// Add escapeHtml helper
Swal.escapeHtml = function(str) {
    if (!str) return '';
    return String(str).replace(/[&<>"'`=\/]/g, function (s) {
        return ({
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#39;',
            '/': '&#x2F;',
            '`': '&#x60;',
            '=': '&#x3D;'
        })[s];
    });
};

function doAction(action, id) {
    const fd = new FormData();
    fd.append('action', action);
    fd.append('id', id);
    // include CSRF token expected by the server
    if (window.__PAYMENTS_CSRF) fd.append('_csrf', window.__PAYMENTS_CSRF);

    fetch('index.php?pages=payments', {
        method: 'POST',
        body: fd,
        credentials: 'same-origin',
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(r => {
        if (!r.ok) throw new Error('Network error ' + r.status);
        return r.text().then(text => {
            try { return JSON.parse(text); } catch(e) { return { __raw: text }; }
        });
    })
    .then(data => {
        if (data && data.__raw) {
            // server returned non-JSON (likely HTML), show raw body for debugging
            Swal.fire({ icon: 'error', title: 'Unexpected response', html: '<pre style="text-align:left;white-space:pre-wrap;max-height:300px;overflow:auto">' + Swal.escapeHtml(data.__raw) + '</pre>' });
            return;
        }
        const status = (data.status === 'ok') ? 'success' : data.status;
        const icon = (status === 'ok' || status === 'success') ? 'success' : status;
        Swal.fire({ icon: icon, title: data.message || '' }).then(() => {
            if (status === 'success') location.reload();
        });
    })
    .catch(err => {
        console.error('AJAX error:', err);
        Swal.fire({ icon: 'error', title: 'Unexpected Error', text: err.message });
    });
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php';
