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
  <link rel="stylesheet" href="./assets/css/public.css">
  <link rel="stylesheet" href="./assets/css/payment.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <!-- page-specific styles moved to assets/css/payment.css -->
  <style>
    @keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
    .swal-spinner { display:inline-block; }
  </style>
</head>
<body class="is-loaded">
  <div class="payment-page">
    <div class="payment-backdrop">
      <div class="payment-header">
        <div class="header-content">
          <div class="payment-logo">
            <img src="./assets/images/hq-logo.jpeg" alt="HQ" style="width:100%;height:100%;object-fit:cover;border-radius:inherit;">
          </div>
          <div class="header-text">
            <div class="logo-text">HIGH Q SOLID ACADEMY</div>
            <div class="secure-text">Secure payment</div>
          </div>
        </div>
      </div>

    <?php if (!$payment): ?>
      <div class="payment-container">
        <div class="payment-content">
          <div class="payment-title">Payment Not Found</div>
          <p>We couldn't find your payment reference. If you just registered, return to the registration page.</p>
        </div>
      </div>
    <?php else: ?>
      <div class="payment-container">
        <div class="payment-content">
          <div class="payment-title">Complete Your Payment</div>
          <div class="spinner" id="pageSpinner" style="display:none"></div>
          
          <div class="payment-details">
            <div class="amount-row">
              <span class="amount-label">Amount</span>
              <span class="amount-value">NGN <?= number_format($payment['amount'],2) ?></span>
            </div>
          </div>

          <div class="hq-paybox">
            <div class="transfer-title">Paystack Checkout</div>
            <div class="checkout-caption"><?= htmlspecialchars($siteSettings['bank_name'] ?? 'Moniepoint PBS') ?></div>

            <div class="acct-row" style="margin-top:8px;">
              <div class="acct" id="acctNum"><?= htmlspecialchars($siteSettings['bank_account_number'] ?? '5017167271') ?></div>
              <button class="copy-inline" id="copyBtn" title="Copy account number" aria-label="Copy account number"><i class="bx bx-clipboard"></i></button>
            </div>

            <div class="ref">Reference: <strong><?= htmlspecialchars($payment['reference']) ?></strong></div>
            <div class="expires"><span class="paybox-countdown" id="payboxCountdown">--:--</span></div>

            <form method="post" action="#" id="payer-form">
              <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf) ?>">
              <input type="hidden" name="payment_id" value="<?= intval($payment['id'] ?? 0) ?>">
              <div class="form-row">
                <label>Name on Payer Account</label>
                <input name="payer_name" class="form-control" required>
              </div>
              <div class="form-row">
                <label>Account Number</label>
                <input name="payer_number" class="form-control" required>
              </div>
              <div class="form-row">
                <label>Bank Name</label>
                <input name="payer_bank" class="form-control" required>
              </div>
            </form>
          </div>

          <div class="payment-footer">
            <div id="payerRecordedInfo" style="display:none"></div>
            <div class="payment-status">Payment expires in <span id="payboxCountdown">--:--</span></div>
            <div class="payment-actions">
              <button class="btn btn-secondary" onclick="history.back()">Cancel</button>
              <button class="btn btn-primary" id="markSentBtn" type="button">I have sent the money</button>
            </div>
          </div>
        </div>

        <div class="small-meta" style="text-align:center;margin-top:12px;color:var(--hq-gray)">
          This payment link expires after 2 days. After making the transfer, click "I have sent the money" and provide your transfer details.
        </div>

        <script>
          // make a bookmark for base
          window.HQ_BASE = window.location.origin;

          (function(){
            var form = document.getElementById('payer-form');
            var btn = document.getElementById('markSentBtn');
            var info = document.getElementById('payerRecordedInfo');

            function showCheckingModal(){
              if (typeof Swal === 'undefined') return;
              Swal.fire({
                title: 'Checking transaction status',
                html: '<div style="text-align:center"><div class="swal-spinner" style="margin:12px auto 0;width:36px;height:36px;border:4px solid rgba(0,0,0,0.08);border-top-color:#222;border-radius:50%;animation:spin 1s linear infinite"></div></div>',
                showConfirmButton: false,
                allowOutsideClick: false,
                customClass: { popup: 'hq-checking' }
              });
            }

            function showSuccessModal(){
              if (typeof Swal === 'undefined') return;
              Swal.fire({
                title: 'Payment Successful',
                html: '<div class="hq-success"><div class="hq-success-icon">' +
                      '<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M20 6L9 17l-5-5" stroke="#2aa24b" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg>' +
                      '</div><div style="font-size:16px;color:#333;margin-top:8px">You paid NGN <?= number_format($payment['amount'],2) ?> to Paystack</div></div>',
                showConfirmButton: false,
                timer: 1800,
                customClass: { popup: 'hq-success' }
              });
            }

            async function postMarkSent(fd){
              var endpoint = (typeof window.hqFetchCompat === 'function') ? window.hqFetchCompat : (typeof hqFetch === 'function' ? hqFetch : null);
              var url = (window.HQ_BASE||'') + '/public/api/mark_sent.php';
              try{
                var resp = null;
                if (endpoint) resp = await endpoint(url, { method: 'POST', body: fd, credentials: 'same-origin' });
                else resp = await fetch(url, { method: 'POST', body: fd, credentials: 'same-origin' });

                // normalize
                var j = null;
                if (resp && resp._parsed) j = resp._parsed;
                else if (resp && typeof resp.text === 'function') {
                  var txt = await resp.text();
                  try{ j = JSON.parse(txt); } catch(e){ j = { status:'error', raw: txt, message: 'Invalid server response' }; }
                } else j = resp;

                return j;
              } catch(e){ return { status: 'error', message: e && e.message ? e.message : 'Network error' }; }
            }

            btn.addEventListener('click', async function(e){
              e.preventDefault();
              // read values from inline form fields
              var fd = new FormData(form);
              btn.disabled = true; btn.textContent = 'Recording...';
              document.getElementById('pageSpinner').style.display = 'block';

              var j = await postMarkSent(fd);
              if (j && j.status === 'ok'){
                // show recorded details and checking modal
                var pay = j.payment || {};
                if (info) {
                  info.innerHTML = '<h4 style="margin:0 0 8px">Recorded transfer details</h4>' +
                    '<div><strong>Name:</strong> ' + (pay.payer_name||'') + '</div>' +
                    '<div><strong>Account:</strong> ' + (pay.payer_number||'') + '</div>' +
                    '<div><strong>Bank:</strong> ' + (pay.payer_bank||'') + '</div>';
                  info.style.border = '1px dashed #ccc'; info.style.padding = '10px'; info.style.marginTop = '12px'; info.style.display = 'block';
                }
                // dispatch event for other listeners and clear page timer
                document.dispatchEvent(new CustomEvent('hq.payment.recorded', { detail: j }));
                showCheckingModal();
                document.getElementById('pageSpinner').style.display = 'none';
                btn.textContent = 'Recorded — awaiting admin verification';
                btn.disabled = true;
                // run immediate check (the existing polling will pick up confirmation)
                try{ if (typeof check === 'function') check(); } catch(e){}
              } else {
                var msg = (j && (j.message || j.raw)) ? (j.message || j.raw) : 'Failed to record transfer.';
                if (typeof Swal !== 'undefined') Swal.fire({ icon: 'error', title: 'Failed', text: msg }); else alert(msg);
                btn.disabled = false; btn.textContent = 'I have sent the money'; document.getElementById('pageSpinner').style.display = 'none';
              }
            });

            // copy button behavior (now supports .copy-inline)
            var copyBtn = document.getElementById('copyBtn') || document.querySelector('.copy-inline');
            if (copyBtn) {
              copyBtn.addEventListener('click', function(e){
                e.preventDefault();
                var acct = document.getElementById('acctNum');
                if (!acct) return;
                var text = acct.textContent.trim();
                var oldHtml = copyBtn.innerHTML;
                try {
                  navigator.clipboard.writeText(text).then(function(){
                    // temporary feedback
                    copyBtn.innerHTML = '<i class="bx bx-check"></i>';
                    setTimeout(function(){ copyBtn.innerHTML = oldHtml; }, 1200);
                    if (typeof Swal !== 'undefined') Swal.fire({ title: 'Copied', text: 'Account number copied', icon: 'success', timer: 900, showConfirmButton: false });
                  }).catch(function(){
                    // fallback
                    var ta = document.createElement('textarea'); ta.value = text; document.body.appendChild(ta); ta.select(); try { document.execCommand('copy'); } catch(e){} ta.remove();
                    copyBtn.innerHTML = '<i class="bx bx-check"></i>'; setTimeout(function(){ copyBtn.innerHTML = oldHtml; }, 1200);
                    if (typeof Swal !== 'undefined') Swal.fire({ title: 'Copied', text: 'Account number copied', icon: 'success', timer: 900, showConfirmButton: false });
                  });
                } catch(e){ /* ignore */ }
              });
            }
          })();
        </script>

  <p class="card-desc" style="margin-top:12px;">Reference: <?= htmlspecialchars($payment['reference'] ?? '') ?></p>
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
          var payEl = document.getElementById('payboxCountdown');
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
              var msg = 'Payment window closed — please request a new link or contact support.';
              if (el) el.textContent = msg;
              if (payEl) payEl.textContent = msg;
              if (form) form.style.display = 'none';
              return false;
            }
            var f = fmt(remain);
            if (el) el.textContent = f;
            if (payEl) payEl.textContent = f;
            return true;
          }

          updatePageTimer();
          setInterval(updatePageTimer, 1000);

          // polling for admin confirmation and server-side expiry
          // Expose a global success UI so polling can reuse it
          window._hqShowPaymentSuccess = function(payment){
            try { if (typeof Swal !== 'undefined') {
                Swal.fire({
                  title: 'Payment Successful',
                  html: '<div class="hq-success"><div class="hq-success-icon">' +
                        '<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M20 6L9 17l-5-5" stroke="#2aa24b" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg>' +
                        '</div><div style="font-size:16px;color:#333;margin-top:8px">You paid NGN ' + (payment?payment.amount.toFixed(2):'') + ' to Paystack</div></div>',
                  showConfirmButton: false,
                  timer: 1800,
                  customClass: { popup: 'hq-success' }
                });
              } } catch(e){}
          };

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
                      try { window._hqShowPaymentSuccess(r.payment); } catch(e){}
                      // after short delay, redirect to receipt
                      setTimeout(function(){
                        if (r.payment.receipt_path) { window.location = r.payment.receipt_path; }
                        else { window.location = (window.HQ_BASE||'') + '/public/receipt.php?ref=' + encodeURIComponent(ref); }
                      }, 1500);
                    } else if (st === 'expired') {
                      // backend marked expired
                      if (form) form.style.display = 'none';
                      el.textContent = 'Link expired';
                    }
                  }
                } catch(e){} }};
            xhr.send();
          }
          // run an initial check and then poll every 5 seconds
          check();
          setInterval(check, 5000);

          // when the user successfully records a payment we can clear the page timer so UX resets on next visit
          document.addEventListener('hq.payment.recorded', function(){ try { localStorage.removeItem(storageKey); } catch(e){} });
        })();
      </script>

    <?php endif; ?>
  </div>
</section>

    </div>
    <footer class="text-center" style="margin-top:auto;padding:18px;color:var(--hq-panel)">
      &copy; <?= date('Y') ?> HIGH Q SOLID ACADEMY
    </footer>
  </div>
</body>
</html>
<?php endif; ?>
