<?php
// admin/pages/payment.php - small admin UI to create & send payment links
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

requirePermission('payments');
$pageTitle = 'Payment';
// Load page-specific styles (kept minimal, uses admin-modern.css primitives)
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
            require_once __DIR__ . '/../../public/config/payment_references.php';
            $ref = generatePaymentReference('admin');
            $metadata = ['email_to' => $email, 'message' => $msg];
            $ins = $pdo->prepare('INSERT INTO payments (student_id, amount, payment_method, reference, status, created_at, metadata) VALUES (NULL, ?, ?, ?, ?, NOW(), ?)');
            $ok = $ins->execute([$amount, 'bank', $ref, 'pending', json_encode($metadata, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE)]);
            if ($ok) {
                $paymentId = $pdo->lastInsertId();
                // send email with link (build app base dynamically)
                // Build the link using app_url() so APP_URL and deployment base path are honored
                // Use friendly pay route so APP_URL and subfolder installs are respected
                $link = app_url('pay/' . urlencode($ref));
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
<div class="admin-page-content">
    <section class="page-hero">
        <div class="page-hero-content">
            <div>
                <div class="page-hero-badge"><i class='bx bx-wallet'></i> Payments</div>
                <h2 class="page-hero-title">Create Payment Link</h2>
                <p class="page-hero-subtitle">Generate and share secure payment links</p>
            </div>
        </div>
    </section>

    <div class="payment-grid">
        <!-- Primary Card -->
        <div class="admin-card">
            <div class="admin-card-header">
                <h3 class="admin-card-title"><i class='bx bx-link'></i> New Link</h3>
            </div>
            <div class="admin-card-body">
                <div id="adminMsg" class="alert" style="display:none;"></div>
                <form id="adminPaymentForm" class="admin-payment-form">
                    <input type="hidden" name="_csrf" value="<?= generateToken('payments_form') ?>">
                    <div class="form-row form-group">
                        <label class="form-label">Amount (NGN)</label>
                        <input class="form-input" type="text" name="amount" placeholder="e.g. 1080" required>
                    </div>
                    <div class="form-row form-group">
                        <label class="form-label">Recipient email</label>
                        <input class="form-input" type="email" name="email" placeholder="payer@example.com" required>
                    </div>
                    <div class="form-row form-group">
                        <label class="form-label">Message (optional)</label>
                        <textarea class="form-textarea" name="message" rows="4" placeholder="Message to include with the payment link"></textarea>
                    </div>
                    <div class="admin-payment-actions">
                        <button class="btn btn-primary" id="createSendBtn" type="button">Create & Send Link</button>
                        <div id="createdLinkWrap" class="created-link-wrap" style="display:none;">
                            <div class="admin-payment-link" id="createdLink"></div>
                            <button class="admin-payment-copy btn btn-secondary" id="copyLinkBtn" type="button">Copy link</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Aside: Preview & Tips -->
        <aside class="admin-card payment-aside">
            <div class="admin-card-header">
                <h3 class="admin-card-title"><i class='bx bx-info-circle'></i> Preview & Tips</h3>
            </div>
            <div class="admin-card-body">
                <p class="small-muted" style="margin-bottom:8px">Your secure link appears after creating.</p>
                <ul class="payment-tips">
                    <li>Use recipient’s primary email address</li>
                    <li>Amounts are in NGN (₦)</li>
                    <li>Links expire in 48 hours by default</li>
                    <li>Copy and resend if email delivery fails</li>
                </ul>
            </div>
        </aside>
    </div>

<div class="admin-payment-card admin-payment-side">
    <h3>Preview & Tips</h3>
    <div class="small-muted" style="margin-bottom:8px">Your secure link appears after creating.</div>
    <ul style="margin:0;padding-left:18px;color:#444;line-height:1.6">
      <li>Use recipient’s primary email address</li>
      <li>Amounts are in NGN (₦)</li>
      <li>Links expire in 48 hours by default</li>
      <li>Copy and resend if email delivery fails</li>
    </ul>
</div>

<?php if (!empty($recentLinks)): ?>
        <div class="admin-card" style="margin-top:18px;">
            <div class="admin-card-header">
                <h3 class="admin-card-title"><i class='bx bx-history'></i> Recent created links</h3>
            </div>
            <div class="admin-card-body">
                <table class="admin-table"><thead><tr><th>ID</th><th>Amount</th><th>Email</th><th>Message</th><th>Link</th><th>Emailed</th><th>Actions</th><th>Created</th></tr></thead><tbody>
        <?php
            // compute app base for recent links using helper
            $appBase = rtrim(app_url(''), '/');
        foreach($recentLinks as $r):
                $meta = [];
                if (!empty($r['metadata'])) { $meta = json_decode($r['metadata'], true) ?: []; }
                $emailTo = $meta['email_to'] ?? '';
                $msgText = $meta['message'] ?? '';
    // Use friendly pay route for consistency
    $link = app_url('pay/' . urlencode($r['reference']));
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
                                    <button class="admin-payment-copy action-btn btn btn-secondary btn-sm" data-link="<?= htmlspecialchars($link) ?>">Copy</button>
                                    <button class="admin-payment-resend action-btn btn btn-secondary btn-sm" data-id="<?= htmlspecialchars($r['id']) ?>">Resend</button>
                                </div>
                            </td>
                            <td><?= htmlspecialchars($r['created_at']) ?></td>
                        </tr>
        <?php endforeach; ?>
        </tbody></table>
      </tbody></table>
      </div>
    </div>
<?php endif; ?>
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
    var csrfInput = form ? form.querySelector('input[name="_csrf"]') : null;
    var csrfToken = csrfInput ? csrfInput.value : '';
    btn.addEventListener('click', function(){
        var fd = new FormData(form);
        btn.disabled = true; btn.textContent = 'Sending...'; btn.classList.add('is-loading');
    fetch((window.HQ_ADMIN_BASE || '') + '/api/create_payment_link.php', { method: 'POST', body: fd, credentials: 'same-origin', headers: {'X-Requested-With':'XMLHttpRequest'} })
            .then(r => r.text())
            .then(function(t){ try { return JSON.parse(t); } catch(e) { return { status:'error', raw:t }; } })
            .then(function(j){
                btn.disabled = false; btn.textContent = 'Create & Send Link'; btn.classList.remove('is-loading');
                if (j.status === 'ok') {
                    msg.style.display = 'block'; msg.textContent = 'Link created' + (j.emailed ? ' and emailed.' : ' (email failed - copy below)');
                    linkWrap.style.display = 'flex'; createdLink.textContent = j.link;
                    // show toast
                    Swal.fire({ toast:true, position:'top-end', icon:'success', title:'Payment link created', showConfirmButton:false, timer:2000 });
                } else {
                    msg.style.display = 'block'; msg.textContent = j.message || 'Error creating link';
                    Swal.fire({ icon:'error', title:'Error', text: j.message || 'Server error' });
                }
            }).catch(function(err){ btn.disabled = false; btn.textContent = 'Create & Send Link'; btn.classList.remove('is-loading'); Swal.fire({ icon:'error', title:'Network error' }); });
    });
    copyBtn.addEventListener('click', function(){ var txt = createdLink.textContent || ''; navigator.clipboard.writeText(txt).then(function(){ Swal.fire({toast:true,position:'top-end',icon:'success',title:'Link copied',showConfirmButton:false,timer:1500}); }); });

    document.querySelectorAll('.admin-payment-resend').forEach(function(btn){
        btn.addEventListener('click', function(){
            var paymentId = this.getAttribute('data-id');
            if (!paymentId) return;
            var fd = new FormData();
            fd.append('_csrf', csrfToken);
            fd.append('payment_id', paymentId);
            this.disabled = true;
            var original = this.textContent;
            this.textContent = 'Sending...';

            fetch((window.HQ_ADMIN_BASE || '') + '/api/resend_payment_link.php', {
                method: 'POST',
                body: fd,
                credentials: 'same-origin',
                headers: {'X-Requested-With':'XMLHttpRequest'}
            })
                .then(function(r){ return r.text(); })
                .then(function(t){ try { return JSON.parse(t); } catch(e){ return { status:'error', message:'Invalid server response' }; } })
                .then(function(j){
                    if (j.status === 'ok') {
                        Swal.fire({ toast:true, position:'top-end', icon:'success', title:'Payment link resent', showConfirmButton:false, timer:2000 });
                    } else {
                        Swal.fire({ icon:'error', title:'Resend failed', text: j.message || 'Server error' });
                    }
                })
                .catch(function(){
                    Swal.fire({ icon:'error', title:'Network error', text:'Unable to resend payment link.' });
                })
                .finally(function(){
                    btn.disabled = false;
                    btn.textContent = original;
                });
        });
    });
});
</script>
