<?php
// public/payments_wait.php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/csrf.php';
$siteSettings = [];
require_once __DIR__ . '/config/functions.php';
$ref = $_GET['ref'] ?? ($_SESSION['last_payment_reference'] ?? '');
$HQ_SUBPATH = '';
$HQ_BASE = (isset($_SERVER['HTTP_HOST']) ? ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS']!=='off') ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] : '');

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
  <link rel="stylesheet" href="./assets/css/payment.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <style>
    @keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
    .swal-spinner { display:inline-block; }
  </style>
</head>
<body>
  <div class="minimal-header" style="background:#fff;padding:12px;border-bottom:1px solid #eee;">
    <div class="container" style="display:flex;align-items:center;gap:12px;">
  <img src="./assets/images/hq-logo.jpeg" alt="HQ" style="height:44px;">
      <div>
        <strong>HIGH Q SOLID ACADEMY</strong>
        <div style="font-size:12px;color:#666;">Secure payment</div>
      </div>
    </div>
  </div>
  <script>window.HQ_BASE = <?= json_encode(rtrim($HQ_BASE, '/')) ?>;</script>
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

        <div class="payment-wait-layout" style="display:flex;gap:18px;align-items:flex-start;">
          <!-- Sidebar removed — only Transfer is allowed -->

          <!-- Main transfer panel -->
          <div style="flex:1;">
            <div class="transfer-card" style="background:#fff;border-radius:8px;padding:18px;border:1px solid #eee;text-align:center;position:relative;">
              <!-- Close (X) button -->
              <button id="closeBtn" aria-label="Close" style="position:absolute;right:12px;top:12px;background:transparent;border:none;cursor:pointer;font-size:18px;color:#666"><i class="bx bx-x"></i></button>
              <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;">
                <div style="text-align:left">
                  <div style="font-size:13px;color:#666">Paying as</div>
                  <div style="font-weight:600"><?= htmlspecialchars($payment['email'] ?? $payment['name'] ?? 'Guest') ?></div>
                </div>
                <div style="text-align:right">
                  <div style="font-size:13px;color:#666">Amount</div>
                  <div style="font-weight:700;color:#0a8a3a;">₦<?= number_format($payment['amount'],2) ?></div>
                </div>
              </div>

              <h3 style="margin:10px 0 6px;color:#444;font-weight:700;">Transfer ₦<?= number_format($payment['amount'],2) ?></h3>

              <div style="margin:14px auto;padding:16px;border-radius:8px;background:#fbfbfb;border:1px solid #f0f0f0;max-width:560px;">
                <div style="font-size:13px;color:#888;margin-bottom:6px">Paystack Checkout</div>
                <div style="font-size:18px;font-weight:700;color:#222;margin-bottom:6px"><?= htmlspecialchars($siteSettings['bank_name'] ?? '[Bank Name]') ?></div>
                <div style="font-size:30px;letter-spacing:2px;font-weight:800;"><?= htmlspecialchars($siteSettings['bank_account_number'] ?? '[Account Number]') ?> <button id="copyAcct" aria-label="Copy account number" style="margin-left:8px;border:none;background:transparent;cursor:pointer;font-size:18px;color:#444"><i class="bx bx-copy"></i></button></div>
                <div style="color:#999;margin-top:8px">Account name: <?= htmlspecialchars($siteSettings['bank_account_name'] ?? 'High Q Solid Academy Limited') ?></div>
                <div style="margin-top:10px;color:#b33;font-weight:600">Expires in <span id="transferExpire">29:59</span></div>
              </div>

              <div style="margin-top:10px">
                <p style="color:#666">Use reference <strong><?= htmlspecialchars($payment['reference']) ?></strong> when making the transfer.</p>
                <p style="color:#666">This payment link expires after 2 days. After making the transfer, click "I've sent the money" and provide your transfer details for verification.</p>
              </div>

              <div style="margin-top:12px;display:flex;gap:12px;justify-content:center;align-items:center;">
                <button class="btn-primary" id="markSentBtn" type="button">I've sent the money</button>
              </div>
            </div>

            <!-- inline payer details form (visible — user must provide before clicking 'I've sent the money') -->
            <div id="payerFormWrap" style="margin-top:14px;display:block;max-width:560px;margin-left:auto;margin-right:auto;text-align:left;">
              <div style="border:1px solid #f0f0f0;padding:12px;border-radius:6px;background:#fafafa;">
                <strong>Payment details (required)</strong>
                <p style="margin:6px 0 10px;color:#666;font-size:13px">Provide the account name, number and bank you used for the transfer. Add a short transaction description (programme name) to speed up verification.</p>
                <form method="post" action="#" id="payer-form">
                  <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf) ?>">
                  <input type="hidden" name="payment_id" value="<?= intval($payment['id'] ?? 0) ?>">
                  <div class="form-row"><label>Name on Payer Account</label><input name="payer_name" required style="width:100%"></div>
                  <div class="form-row"><label>Account Number</label><input name="payer_number" required style="width:100%"></div>
                  <div class="form-row"><label>Bank Name</label><input name="payer_bank" required style="width:100%"></div>
                  <div class="form-row"><label>Transaction description (programme)</label><input name="transaction_description" placeholder="E.g. PTU — Computer Science" style="width:100%"></div>
                </form>
              </div>
            </div>

            <div id="payerRecordedInfo" style="display:none;margin-top:12px;max-width:560px;margin-left:auto;margin-right:auto;"></div>
          </div>
        </div>
      </div>

      <script>
        // Page-level timer: 10 minutes per page load (persisted in localStorage per reference)
        // Link-level expiry: server enforces 2 days from payment.created_at (already handled server-side)
        (function(){
          var created = <?= json_encode($payment['created_at'] ?? null) ?>;
          var ref = <?= json_encode($payment['reference'] ?? '') ?>;
          var pageTimeout = 10 * 60; // 10 minutes
          var elTransfer = document.getElementById('transferExpire');
          var form = document.getElementById('payer-form');
          var payerFormWrap = document.getElementById('payerFormWrap');
          var markSentBtn = document.getElementById('markSentBtn');
          var storageKey = 'hq_pay_timer_' + ref;
          var transferStartKey = 'hq_transfer_start_' + ref;
          var transferDuration = 30 * 60; // 30 minutes visual countdown for transfer

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
              // hide payer form and indicate closed
              if (form) form.style.display = 'none';
              if (elTransfer) elTransfer.textContent = '00:00';
              return false;
            }
            return true;
          }

          // transfer countdown (visual 30-minute timer shown in the card)
          function updateTransferTimer(){
            if (!elTransfer) return;
            var now = Math.floor(Date.now()/1000);
            var tStart = null;
            try { tStart = parseInt(localStorage.getItem(transferStartKey), 10); } catch(e) { tStart = null; }
            if (!tStart || isNaN(tStart)) { tStart = Math.floor(Date.now()/1000); try { localStorage.setItem(transferStartKey, tStart); } catch(e){} }
            var remain = transferDuration - (now - tStart);
            if (remain < 0) remain = 0;
            elTransfer.textContent = fmt(remain);
          }

          // initialize timers
          updatePageTimer();
          updateTransferTimer();
          setInterval(function(){ updatePageTimer(); updateTransferTimer(); }, 1000);

          // polling for admin confirmation and server-side expiry
          function check(){
            if (!ref) return;
            var xhr = new XMLHttpRequest();
            // add a timestamp to prevent aggressive caching by proxies/browsers
            var url = (window.HQ_BASE||'') + '/public/api/payment_status.php?ref=' + encodeURIComponent(ref) + '&t=' + Date.now();
            xhr.open('GET', url, true);
            xhr.onload = function(){ if (xhr.status===200){ try{ var r = JSON.parse(xhr.responseText);
                  if (r.status==='ok' && r.payment) {
                    var st = r.payment.status || '';
                    if (st === 'confirmed') {
                      // close any checking modal then show success then redirect
                      try { if (typeof Swal !== 'undefined') Swal.close(); } catch(e){}
                      // If this payment is for a Post-UTME, show friendly message that an agent will contact them after review
                      var isPost = (r.payment.registration_type && r.payment.registration_type === 'postutme');
                      if (isPost) {
                        var msg = 'Payment confirmed. An agent will get in touch with you after your details have been reviewed.';
                        if (typeof Swal !== 'undefined') {
                          Swal.fire({ icon: 'success', title: 'Payment Received', html: msg, showConfirmButton: true, customClass: { popup: 'hq-swal' } }).then(function(){
                            if (r.payment.receipt_path) { window.location = r.payment.receipt_path; }
                            else { window.location = (window.HQ_BASE||'') + '/public/receipt.php?ref=' + encodeURIComponent(ref); }
                          });
                        } else {
                          alert(msg);
                          if (r.payment.receipt_path) { window.location = r.payment.receipt_path; }
                          else { window.location = (window.HQ_BASE||'') + '/public/receipt.php?ref=' + encodeURIComponent(ref); }
                        }
                      } else {
                        if (typeof Swal !== 'undefined') {
                          Swal.fire({ icon: 'success', title: 'Payment Successful', html: 'Your payment has been confirmed. Redirecting to your receipt...', showConfirmButton: false, timer: 2200, customClass: { popup: 'hq-swal' } }).then(()=>{
                            if (r.payment.receipt_path) { window.location = r.payment.receipt_path; }
                            else { window.location = (window.HQ_BASE||'') + '/public/receipt.php?ref=' + encodeURIComponent(ref); }
                          });
                        } else {
                          if (r.payment.receipt_path) { window.location = r.payment.receipt_path; }
                          else { window.location = (window.HQ_BASE||'') + '/public/receipt.php?ref=' + encodeURIComponent(ref); }
                        }
                      }
                    } else if (st === 'expired') {
                      // backend marked expired
                      if (form) form.style.display = 'none';
                      if (elTransfer) elTransfer.textContent = 'expired';
                    }
                  }
                } catch(e){} }};
            xhr.send();
          }
          // run an initial check and then poll every 5 seconds
          check();
          setInterval(check, 5000);

          // when the user successfully records a payment we can clear the page timer so UX resets on next visit
          document.addEventListener('hq.payment.recorded', function(){ try { localStorage.removeItem(storageKey); localStorage.removeItem(transferStartKey); } catch(e){} });

          // copy account number helper
          try {
            var copyBtn = document.getElementById('copyAcct');
            if (copyBtn) {
              copyBtn.addEventListener('click', function(){
                var acct = <?= json_encode($siteSettings['bank_account_number'] ?? '') ?>;
                try { navigator.clipboard.writeText(acct); copyBtn.innerHTML = '<i class="bx bx-check"></i>'; setTimeout(function(){ copyBtn.innerHTML = '<i class="bx bx-copy"></i>'; },1500); } catch(e){ alert('Copy: ' + acct); }
              });
            }
          } catch(e){}

          // When primary button is clicked, submit the payer form (user should have filled details)
          try {
            if (markSentBtn && form) {
              markSentBtn.addEventListener('click', function(){
                // trigger a normal submit so our submit handler processes it
                if (typeof form.requestSubmit === 'function') form.requestSubmit(); else form.submit();
              });
            }
          } catch(e){}

          // handle payer-form submission (record transfer details)
          try {
            if (form) {
              var submitBtn = document.getElementById('markSentBtn');
              form.addEventListener('submit', function(e){
                e.preventDefault();
                if (submitBtn) { submitBtn.disabled = true; submitBtn.textContent = 'Recording...'; }
                if (document.getElementById('pageSpinner')) document.getElementById('pageSpinner').style.display = 'block';
                var fd = new FormData(form);
                fetch((window.HQ_BASE||'') + '/public/api/mark_sent.php', {
                  method: 'POST', body: fd, credentials: 'same-origin', headers: { 'X-Requested-With':'XMLHttpRequest' }
                }).then(function(r){ return r.text().then(function(t){ try { return JSON.parse(t); } catch(e){ return { status: 'error', raw: t }; } }); })
                .then(function(j){
                  if (j && j.status === 'ok') {
                    if (submitBtn) { submitBtn.textContent = 'Recorded — awaiting admin verification'; submitBtn.disabled = true; }
                    if (document.getElementById('pageSpinner')) document.getElementById('pageSpinner').style.display = 'none';
                    var info = document.getElementById('payerRecordedInfo');
                    if (info) {
                      var pay = j.payment || {};
                      info.innerHTML = '<h4>Recorded transfer details</h4>'+
                        '<div><strong>Name:</strong> ' + (pay.payer_name||'') + '</div>'+
                        '<div><strong>Account:</strong> ' + (pay.payer_number||'') + '</div>'+
                        '<div><strong>Bank:</strong> ' + (pay.payer_bank||'') + '</div>';
                      info.style.border = '1px dashed #ccc'; info.style.padding = '10px'; info.style.marginTop = '12px'; info.style.display = 'block';
                    }
                    // show checking modal
                    if (typeof Swal !== 'undefined') {
                      Swal.fire({ title: 'Checking transaction status', html: '<div style="text-align:center"><div class="swal-spinner" style="margin:12px auto 0;width:36px;height:36px;border:4px solid rgba(0,0,0,0.08);border-top-color:var(--hq-black);border-radius:50%;animation:spin 1s linear infinite"></div></div>', showConfirmButton:false, allowOutsideClick:false, customClass:{ popup: 'hq-swal' } });
                    }
                    // trigger check immediately
                    try { check(); } catch(e){}
                    // fire event so timers clear
                    try { document.dispatchEvent(new Event('hq.payment.recorded')); } catch(e){}
                  } else {
                    var msg = (j && j.message) ? j.message : 'Failed to record transfer.';
                    if (j && j.raw) msg += '\n' + j.raw;
                    if (typeof Swal !== 'undefined') Swal.fire('Error', msg, 'error'); else alert(msg);
                    if (submitBtn) { submitBtn.disabled = false; submitBtn.textContent = 'Record transfer details'; }
                    if (document.getElementById('pageSpinner')) document.getElementById('pageSpinner').style.display = 'none';
                  }
                }).catch(function(err){ var m = 'Network error: ' + (err && err.message ? err.message : 'unknown'); if (typeof Swal !== 'undefined') Swal.fire('Error', m, 'error'); else alert(m); if (submitBtn) { submitBtn.disabled = false; submitBtn.textContent = 'Record transfer details'; } if (document.getElementById('pageSpinner')) document.getElementById('pageSpinner').style.display = 'none'; });
              });
            }
          } catch(e){}
        })();
      </script>

    <?php endif; ?>
  </div>
</section>

  </main>
  <footer style="padding:18px 0;text-align:center;color:#777;font-size:13px;">&copy; <?= date('Y') ?> HIGH Q SOLID ACADEMY</footer>
</body>
</html>
