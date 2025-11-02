<?php
// admin/pages/payment.php - small admin UI to create & send payment links
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../includes/functions.php';

requirePermission('payments');
$pageTitle = 'Create Payment Link';
$pageCss = '../assets/css/payment.css';
require_once __DIR__ . '/../includes/header.php';

$message = '';
// fetch recent admin-created payment links
$recentLinks = [];
try {
    $rs = $pdo->prepare("SELECT id, reference, amount, metadata, created_at FROM payments WHERE reference LIKE 'ADMIN-%' ORDER BY created_at DESC LIMIT 8");
    $rs->execute();
    $recentLinks = $rs->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $_) { $recentLinks = []; }
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
                // send email with link (build app base dynamically)
                // Build the link using app_url() so APP_URL and deployment base path are honored
                $link = app_url('public/payments_wait.php?ref=' . urlencode($ref));
                $subject = 'Payment link — HIGH Q SOLID ACADEMY';
                $html = '<p>Hi,</p><p>Please use the following secure link to complete your payment of ₦' . number_format($amount,2) . ':</p>';
                $html .= '<p><a href="' . htmlspecialchars($link) . '">' . htmlspecialchars($link) . '</a></p>';
                if (!empty($msg)) $html .= '<p>Message: ' . nl2br(htmlspecialchars($msg)) . '</p>';
                $html .= '<p>Link expires in 2 days.</p><p>Thanks,<br>HIGH Q Solid Academy</p>';
                $sent = false;
                try { $sent = sendEmail($email, $subject, $html); } catch (Throwable $e) { $sent = false; }
                if ($sent) {
                    $message = 'Payment link created and emailed to ' . htmlspecialchars($email) . '.';
                }
            }
        }
    }
}

?>
<head>
    <link rel="stylesheet" href="../assets/css/payment.css">
</head>
<div class="admin-payment-card">
    <h3>Create Payment Link</h3>
    <div id="adminMsg" style="display:none;" class="alert"></div>
    <form id="adminPaymentForm" class="admin-payment-form">
        <input type="hidden" name="_csrf" value="<?= generateToken('payments_form') ?>">
        <div class="form-row"><label>Amount (NGN)</label><input type="text" name="amount" placeholder="e.g. 1080" required></div>
        <div class="form-row"><label>Recipient email</label><input type="email" name="email" placeholder="payer@example.com" required></div>
        <div class="form-row"><label>Message (optional)</label><textarea name="message" rows="4" placeholder="Message to include with the payment link"></textarea></div>
        <div class="admin-payment-actions">
            <button class="btn" id="createSendBtn" type="button">Create & Send Link</button>
            <div id="createdLinkWrap" style="display:none;flex:1;">
                <div class="admin-payment-link" id="createdLink"></div>
                <button class="admin-payment-copy" id="copyLinkBtn" style="margin-left:8px;" type="button">Copy link</button>
            </div>
        </div>
    </form>
</div>

<?php if (!empty($recentLinks)): ?>
        <div class="admin-payment-card" style="margin-top:18px;">
            <h3>Recent created links</h3>
            <table class="table" style="width:100%"><thead><tr><th>ID</th><th>Amount</th><th>Email</th><th>Message</th><th>Link</th><th>Emailed</th><th>Actions</th><th>Created</th></tr></thead><tbody>
        <?php
            // compute app base for recent links using helper
            $appBase = rtrim(app_url(''), '/');
        foreach($recentLinks as $r):
                $meta = [];
                if (!empty($r['metadata'])) { $meta = json_decode($r['metadata'], true) ?: []; }
                $emailTo = $meta['email_to'] ?? '';
                $msgText = $meta['message'] ?? '';
    $link = app_url('public/payments_wait.php?ref=' . urlencode($r['reference']));
        ?>
            <tr>
                <td><?= htmlspecialchars($r['id']) ?></td>
                <td>₦<?= number_format($r['amount'],2) ?></td>
                <td><?= htmlspecialchars($emailTo) ?></td>
                <td><?= htmlspecialchars(strlen($msgText) > 60 ? substr($msgText,0,57).'...' : $msgText) ?></td>
                    <td><div style="display:flex;gap:8px;align-items:center;"><div style="max-width:420px;overflow:hidden;text-overflow:ellipsis;"><?= htmlspecialchars($link) ?></div></div></td>
                    <td class="small-muted"><?= (!empty($meta['emailed']) ? '<strong style="color:var(--hq-dark)">Yes</strong>' : '<span class="small-muted">No</span>') ?></td>
                    <td>
                            <div style="display:flex;gap:8px;align-items:center;">
                                    <button class="admin-payment-copy action-btn" data-link="<?= htmlspecialchars($link) ?>">Copy</button>
                                    <button class="admin-payment-resend action-btn" data-id="<?= htmlspecialchars($r['id']) ?>">Resend</button>
                            </div>
                    </td>
                    <td><?= htmlspecialchars($r['created_at']) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody></table>
    </div>
<?php endif; ?>

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
    fetch((window.HQ_ADMIN_BASE || '') + '/api/create_payment_link.php', { method: 'POST', body: fd, credentials: 'same-origin', headers: {'X-Requested-With':'XMLHttpRequest'} })
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
