<?php
// admin/pages/payment.php - small admin UI to create & send payment links
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

requirePermission('payments');
$pageTitle = 'Create Payment Link';
$pageCss = "<link rel=\"stylesheet\" href=\"/HIGH-Q/admin/assets/css/payment.css\">";

// fetch recent admin-created links for history row
try {
    $recentStmt = $pdo->prepare("SELECT id, reference, amount, metadata, created_at, status FROM payments WHERE reference LIKE ? ORDER BY created_at DESC LIMIT 8");
    $recentStmt->execute(['ADMIN-%']);
    $recentLinks = $recentStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $recentLinks = [];
}

require_once __DIR__ . '/../includes/header.php';

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['_csrf'] ?? '';
    if (!verifyToken('payments_form', $token)) {
        $message = 'Invalid CSRF token.';
    } else {
        $amount = floatval(str_replace(',', '', $_POST['amount'] ?? '0'));
        $email = trim($_POST['email'] ?? '');
        $msg = trim($_POST['message'] ?? '');
        if ($amount <= 0 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $message = 'Please provide a valid amount and email.';
        } else {
            // create payment record
            $ref = 'ADMIN-' . date('YmdHis') . '-' . bin2hex(random_bytes(4));
            $metadata = ['email_to' => $email, 'message' => $msg];
            $ins = $pdo->prepare('INSERT INTO payments (student_id, amount, payment_method, reference, status, created_at, metadata) VALUES (NULL, ?, ?, ?, ?, NOW(), ?)');
            $ok = $ins->execute([$amount, 'bank', $ref, 'pending', json_encode($metadata, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE)]);
            if ($ok) {
                $paymentId = $pdo->lastInsertId();
                // send email with link
                $link = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS']!=='off' ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . '/public/payments_wait.php?ref=' . urlencode($ref);
                $subject = 'Payment link — HIGH Q SOLID ACADEMY';
                $html = '<p>Hi,</p><p>Please use the following secure link to complete your payment of ₦' . number_format($amount,2) . ':</p>';
                $html .= '<p><a href="' . htmlspecialchars($link) . '">' . htmlspecialchars($link) . '</a></p>';
                if (!empty($msg)) $html .= '<p>Message: ' . nl2br(htmlspecialchars($msg)) . '</p>';
                $html .= '<p>Link expires in 2 days.</p><p>Thanks,<br>HIGH Q Solid Academy</p>';
                $sent = false;
                try { $sent = sendEmail($email, $subject, $html); } catch (Throwable $e) { $sent = false; }
                if ($sent) {
                    $message = 'Payment link created and emailed to ' . htmlspecialchars($email) . '.';
                    logAction($pdo, (int)($_SESSION['user']['id'] ?? 0), 'create_payment_link', ['payment_id'=>$paymentId,'email'=>$email]);
                } else {
                    $message = 'Payment created but email sending failed. You can copy the link below to send manually.';
                }
            } else {
                $message = 'Failed to create payment record.';
            }
        }
    }
}

?>
<div class="container" style="max-width:760px;margin:18px auto;padding:18px;background:#fff;border-radius:8px;">
        <h1>Create Payment Link</h1>
        <div id="adminMsg" style="display:none;margin-bottom:12px;padding:10px;border-radius:6px;background:#f7f7f7;border:1px solid #eee"></div>
        <div class="admin-payment-card">
            <form id="adminPaymentForm" class="admin-payment-form">
                <input type="hidden" name="_csrf" value="<?= generateToken('payments_form') ?>">
                <div class="form-row"><label>Amount (NGN)</label><input type="text" name="amount" placeholder="e.g. 1080" required></div>
                <div class="form-row"><label>Recipient email</label><input type="email" name="email" placeholder="payer@example.com" required></div>
                <div class="form-row"><label>Message (optional)</label><textarea name="message" rows="4" placeholder="Message to include with the payment link"></textarea></div>
                <div class="admin-payment-actions">
                        <button class="btn" id="createSendBtn" type="button">Create & Send Link</button>
                        <div id="createdLinkWrap" style="display:none;flex:1;">
                                <div class="admin-payment-link" id="createdLink"></div>
                                <button class="admin-payment-copy" id="copyLinkBtn" style="margin-left:8px;">Copy link</button>
                        </div>
                </div>
            </form>
        </div>
    
            <!-- Recent created links (history) -->
            <div class="card" style="margin-top:16px;">
                <h4 style="margin-top:0;margin-bottom:8px">Recent created links</h4>
                <?php if (empty($recentLinks)): ?>
                    <div style="color:#666">No recent admin-created links.</div>
                <?php else: ?>
                    <div style="display:flex;flex-direction:column;gap:8px;">
                        <?php foreach($recentLinks as $rl):
                                $meta = @json_decode($rl['metadata'] ?? '{}', true) ?: [];
                                $emailTo = $meta['email_to'] ?? ($meta['email'] ?? '');
                                $msgShort = isset($meta['message']) ? (strlen($meta['message'])>80 ? substr($meta['message'],0,77).'...' : $meta['message']) : '';
                                $link = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS']!=='off' ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . '/public/payments_wait.php?ref=' . urlencode($rl['reference']);
                        ?>
                            <div style="display:flex;justify-content:space-between;align-items:center;padding:8px;border-radius:6px;border:1px solid #eee;background:#fafafa">
                                <div style="flex:1;min-width:0">
                                    <div style="font-weight:700;color:#111;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?= htmlspecialchars($rl['reference']) ?></div>
                                    <div style="font-size:13px;color:#666">To: <?= htmlspecialchars($emailTo) ?> &middot; ₦<?= number_format($rl['amount'],2) ?> &middot; <?= htmlspecialchars($rl['status']) ?> &middot; <?= htmlspecialchars($rl['created_at']) ?></div>
                                    <?php if ($msgShort): ?><div style="font-size:13px;color:#444;margin-top:6px"><?= htmlspecialchars($msgShort) ?></div><?php endif; ?>
                                </div>
                                <div style="display:flex;gap:8px;margin-left:12px">
                                    <button class="btn" type="button" onclick="navigator.clipboard.writeText('<?= addslashes($link) ?>').then(()=>Swal.fire({toast:true,position:'top-end',icon:'success',title:'Link copied',showConfirmButton:false,timer:1400}))">Copy</button>
                                    <a class="btn" href="<?= htmlspecialchars($link) ?>" target="_blank">Open</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php';
?>
<script>
document.addEventListener('DOMContentLoaded', function(){
    var btn = document.getElementById('createSendBtn');
    var form = document.getElementById('adminPaymentForm');
    var msg = document.getElementById('adminMsg');
    var linkWrap = document.getElementById('createdLinkWrap');
    var createdLink = document.getElementById('createdLink');
    var copyBtn = document.getElementById('copyLinkBtn');
    btn.addEventListener('click', function(){
        var fd = new FormData(form);
        btn.disabled = true; btn.textContent = 'Sending...';
        fetch('/HIGH-Q/admin/api/create_payment_link.php', { method: 'POST', body: fd, credentials: 'same-origin', headers: {'X-Requested-With':'XMLHttpRequest'} })
            .then(r => r.text())
            .then(function(t){ try { return JSON.parse(t); } catch(e) { return { status:'error', raw:t }; } })
            .then(function(j){
                btn.disabled = false; btn.textContent = 'Create & Send Link';
                if (j.status === 'ok') {
                    msg.style.display = 'block'; msg.textContent = 'Link created' + (j.emailed ? ' and emailed.' : ' (email failed - copy below)');
                    linkWrap.style.display = 'flex'; createdLink.textContent = j.link;
                    // show toast
                    Swal.fire({ toast:true, position:'top-end', icon:'success', title:'Payment link created', showConfirmButton:false, timer:2000 });
                } else {
                    msg.style.display = 'block'; msg.textContent = j.message || 'Error creating link';
                    Swal.fire({ icon:'error', title:'Error', text: j.message || 'Server error' });
                }
            }).catch(function(err){ btn.disabled = false; btn.textContent = 'Create & Send Link'; Swal.fire({ icon:'error', title:'Network error' }); });
    });
    copyBtn.addEventListener('click', function(){ var txt = createdLink.textContent || ''; navigator.clipboard.writeText(txt).then(function(){ Swal.fire({toast:true,position:'top-end',icon:'success',title:'Link copied',showConfirmButton:false,timer:1500}); }); });
});
</script>
