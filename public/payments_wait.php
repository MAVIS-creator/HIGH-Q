<?php
// public/payments_wait.php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/csrf.php';
$siteSettings = [];
require_once __DIR__ . '/config/functions.php';
$ref = $_GET['ref'] ?? ($_SESSION['last_payment_reference'] ?? '');
$HQ_SUBPATH = '/HIGH-Q'; // adjust if you move the project
$HQ_BASE = (isset($_SERVER['HTTP_HOST']) ? ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS']!=='off') ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . $HQ_SUBPATH : '');

// load site settings (bank details, logo) for display
try {
  $stmtS = $pdo->query("SELECT * FROM site_settings ORDER BY id ASC LIMIT 1");
  $siteSettings = $stmtS->fetch(PDO::FETCH_ASSOC) ?: [];
} catch (Throwable $e) { $siteSettings = []; }
$payment = null;
if ($ref) {
  $stmt = $pdo->prepare('SELECT p.*, u.email, u.name FROM payments p LEFT JOIN users u ON u.id = p.student_id WHERE p.reference = ? LIMIT 1');
  $stmt->execute([$ref]);
  $payment = $stmt->fetch(PDO::FETCH_ASSOC);

  // enforce 2-day expiry for unpaid pending links
  try {
    if ($payment && !empty($payment['created_at'])) {
      $createdTs = strtotime($payment['created_at']);
      $expirySeconds = 2 * 24 * 60 * 60; // 2 days
      if (time() - $createdTs > $expirySeconds && in_array($payment['status'], ['pending'])) {
        $upd = $pdo->prepare('UPDATE payments SET status = "expired", updated_at = NOW() WHERE id = ?');
        $upd->execute([$payment['id']]);
        $payment['status'] = 'expired';
      }
    }
  } catch (Throwable $e) { /* ignore expiry update errors */ }
}
// Minimal branded waiting page: include site CSS but omit full header/footer
$csrf = generateToken('signup_form');
// Include basic head assets (brand styles) without full header include
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Payment in Progress - HIGH Q SOLID ACADEMY</title>
  <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
  <link rel="stylesheet" href="<?= htmlspecialchars($HQ_SUBPATH) ?>/public/assets/css/payment.css">
</head>
<body>
  <div class="minimal-header" style="background:#fff;padding:12px;border-bottom:1px solid #eee;">
    <div class="container" style="display:flex;align-items:center;gap:12px;">
  <img src="<?= htmlspecialchars($HQ_SUBPATH) ?>/public/assets/images/hq-logo.jpeg" alt="HQ" style="height:44px;">
      <div>
        <strong>HIGH Q SOLID ACADEMY</strong>
        <div style="font-size:12px;color:#666;">Secure payment</div>
      </div>
    </div>
  </div>
  <main class="public-main" style="padding:28px 0;">
<?php
?>
<section class="about-hero">
  <div class="container">
    <h2>Payment in Progress</h2>
    <?php if (!$payment): ?>
  <p>We couldn't find your payment reference. If you just registered, return to the registration page.</p>
  <?php else: ?>
      <div class="card">
        <div class="spinner" id="pageSpinner" style="display:none"></div>
  <p>Please transfer <strong>₦<?= number_format($payment['amount'],2) ?></strong> to the account below and use reference <strong><?= htmlspecialchars($payment['reference']) ?></strong>.</p>
  <p><strong>Account Name:</strong> <?= htmlspecialchars($siteSettings['bank_account_name'] ?? 'High Q Solid Academy Limited') ?><br>
  <strong>Bank:</strong> <?= htmlspecialchars($siteSettings['bank_name'] ?? '[Bank Name]') ?><br>
  <strong>Account Number:</strong> <?= htmlspecialchars($siteSettings['bank_account_number'] ?? '[Account Number]') ?></p>

    <p>This payment link expires after 2 days. After making the transfer, click "I have sent the money" and provide your transfer details. An admin will verify and confirm your payment.</p>

  <div id="countdown" style="font-size:18px;font-weight:600;color:#b33;margin-bottom:12px;">--:--</div>

  <form method="post" action="#" id="payer-form">
          <!-- mark_sent API expects _csrf and payment_id fields -->
          <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf) ?>">
          <input type="hidden" name="payment_id" value="<?= intval($payment['id'] ?? 0) ?>">
          <div class="form-row"><label>Name on Payer Account</label><input name="payer_name" required></div>
          <div class="form-row"><label>Account Number</label><input name="payer_number" required></div>
          <div class="form-row"><label>Bank Name</label><input name="payer_bank" required></div>
          <div style="margin-top:12px;"><button class="btn-primary" id="markSentBtn" type="submit">I have sent the money</button></div>
        </form>

        <script>
          // submit mark-sent via fetch to API endpoint and handle response
          // Expose HQ_BASE globally so other scripts (polling) can use it
          window.HQ_BASE = window.location.origin + '<?= $HQ_SUBPATH ?>';
          (function(){
            var form = document.getElementById('payer-form');
            var btn = document.getElementById('markSentBtn');
            form.addEventListener('submit', function(e){
              e.preventDefault();
              btn.disabled = true; btn.textContent = 'Recording...';
              document.getElementById('pageSpinner').style.display = 'block';
              var fd = new FormData(form);
              fetch(window.HQ_BASE + '/public/api/mark_sent.php', {
                method: 'POST',
                body: fd,
                credentials: 'same-origin',
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
              })
                .then(function(r){
                    // Try to parse JSON safely; if not JSON, read as text for debugging
                    return r.text().then(function(text){
                        try { return JSON.parse(text); } catch(e) { return { status: 'error', message: 'Invalid server response', raw: text }; }
                    });
                })
                .then(function(j){
                      if (j.status === 'ok') {
                        // show success and let polling detect confirmation
                        btn.textContent = 'Recorded — awaiting admin verification';
                        btn.disabled = true;
                        // display recorded payer details
                        var info = document.getElementById('payerRecordedInfo');
                        if (info) {
                          var pay = j.payment || {};
                          info.innerHTML = '<h4>Recorded transfer details</h4>'+
                            '<div><strong>Name:</strong> ' + (pay.payer_name||'') + '</div>'+
                            '<div><strong>Account:</strong> ' + (pay.payer_number||'') + '</div>'+
                            '<div><strong>Bank:</strong> ' + (pay.payer_bank||'') + '</div>';
                          info.style.border = '1px dashed #ccc';
                          info.style.padding = '10px';
                          info.style.marginTop = '12px';
                          info.style.display = 'block';
                        }
                        // trigger an immediate check so the page can react to a quick admin confirmation
                        try { if (typeof check === 'function') check(); } catch(e){}
                        document.getElementById('pageSpinner').style.display = 'none';
                      } else {
                        // show raw server message when available to make debugging easier
                        var msg = j.message || 'Failed to record transfer.';
                        if (j.raw) msg += '\nServer response:\n' + j.raw;
                        alert(msg);
                        btn.disabled = false; btn.textContent = 'I have sent the money'; document.getElementById('pageSpinner').style.display = 'none';
                      }
                }).catch(function(err){ alert('Network error: ' + (err && err.message ? err.message : 'unknown')); btn.disabled = false; btn.textContent = 'I have sent the money'; });
            });
            
          })();
        </script>

        <p style="margin-top:12px;color:#666;">Reference: <?= htmlspecialchars($payment['reference'] ?? '') ?></p>
      </div>

      <div id="payerRecordedInfo" style="display:none;"></div>

      <script>
        // Page-level timer: 10 minutes per page load (persisted in localStorage per reference)
        // Link-level expiry: server enforces 2 days from payment.created_at (already handled server-side)
        (function(){
          var created = <?= json_encode($payment['created_at'] ?? null) ?>;
          var ref = <?= json_encode($payment['reference'] ?? '') ?>;
          var pageTimeout = 10 * 60; // 10 minutes
          var el = document.getElementById('countdown');
          var form = document.getElementById('payer-form');
          var storageKey = 'hq_pay_timer_' + ref;

          function fmt(s){ var m=Math.floor(s/60); var ss=s%60; return (m<10? '0'+m: m)+":"+(ss<10? '0'+ss:ss); }

          // create or reuse a start timestamp for the page timer so refresh doesn't reset it
          var startTs = null;
          try {
            var stored = localStorage.getItem(storageKey);
            if (stored) startTs = parseInt(stored,10);
            if (!startTs || isNaN(startTs)) { startTs = Math.floor(Date.now()/1000); localStorage.setItem(storageKey, startTs); }
          } catch (e) { startTs = Math.floor(Date.now()/1000); }

          function updatePageTimer(){
            var now = Math.floor(Date.now()/1000);
            var remain = pageTimeout - (now - startTs);
            if (remain <= 0) {
              el.textContent = 'Payment window closed — please request a new link or contact support.';
              if (form) form.style.display = 'none';
              return false;
            }
            el.textContent = fmt(remain);
            return true;
          }

          updatePageTimer();
          setInterval(updatePageTimer, 1000);

          // polling for admin confirmation and server-side expiry
          function check(){
            if (!ref) return;
            var xhr = new XMLHttpRequest(); xhr.open('GET', (window.HQ_BASE||'') + '/public/api/payment_status.php?ref=' + encodeURIComponent(ref), true);
            xhr.onload = function(){ if (xhr.status===200){ try{ var r = JSON.parse(xhr.responseText);
                  if (r.status==='ok' && r.payment) {
                    var st = r.payment.status || '';
                    if (st === 'confirmed') {
                      // redirect to receipt when confirmed
                      if (r.payment.receipt_path) { window.location = r.payment.receipt_path; }
                      else { window.location = (window.HQ_BASE||'') + '/public/receipt.php?ref=' + encodeURIComponent(ref); }
                    } else if (st === 'expired') {
                      // backend marked expired
                      if (form) form.style.display = 'none';
                      el.textContent = 'Link expired';
                    }
                  }
                } catch(e){} }};
            xhr.send();
          }
          // run an initial check and then poll
          check();
          setInterval(check, 10000);

          // when the user successfully records a payment we can clear the page timer so UX resets on next visit
          document.addEventListener('hq.payment.recorded', function(){ try { localStorage.removeItem(storageKey); } catch(e){} });
        })();
      </script>

    <?php endif; ?>
  </div>
</section>

  </main>
  <footer style="padding:18px 0;text-align:center;color:#777;font-size:13px;">&copy; <?= date('Y') ?> HIGH Q SOLID ACADEMY</footer>
</body>
</html>
