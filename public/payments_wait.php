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
    /* Paystack-like checkout styling (local to this page) */
    .hq-checkout { display:flex; gap:18px; align-items:stretch; }
    .hq-checkout .methods { width:180px; background:#f7f8fb; border-radius:8px; padding:12px; box-shadow:0 4px 18px rgba(0,0,0,0.04); }
    .hq-checkout .methods .mitem { display:flex; align-items:center; gap:10px; padding:10px; border-radius:6px; cursor:pointer; color:#444; font-weight:600; }
    .hq-checkout .methods .mitem.active { background:#fff; box-shadow:0 6px 18px rgba(0,0,0,0.06); color:#0b5ed7; }
    .hq-checkout .methods .mitem .icon { width:34px;height:34px;border-radius:6px;background:#fff;display:inline-flex;align-items:center;justify-content:center;border:1px solid #ececec }
    .hq-checkout .panel { flex:1; background:#fff;border-radius:8px;padding:22px; box-shadow:0 10px 30px rgba(0,0,0,0.06); position:relative }
    .hq-paycard { max-width:620px; margin:0 auto; }
    .paybox { background:#fafafa;border-radius:8px;padding:24px;text-align:center;border:1px solid #f1f1f3 }
    .paybox .bank { font-size:13px;color:#666;margin-bottom:8px }
    .paybox .acct { font-size:22px;font-weight:700;letter-spacing:1px;margin:8px 0 }
    .paybox .expires { font-size:12px;color:#888;margin-top:8px }
    .btn-primary { background:#0b5ed7;color:#fff;padding:10px 16px;border-radius:8px;border:none;font-weight:700;cursor:pointer }
    .btn-primary[disabled]{ opacity:0.6; cursor:not-allowed }
    /* SweetAlert custom sizing for the checking modal */
    .swal2-container .swal2-popup.hq-checking { width:480px; max-width:92%; }
    .swal2-container .swal2-popup.hq-success { width:420px; max-width:92%; }
    .hq-success .hq-success-icon{ width:90px;height:90px;border-radius:50%;background:#ebf9f0;margin:18px auto;display:flex;align-items:center;justify-content:center }
    .hq-success .hq-success-icon svg{ width:56px;height:56px; color:#2aa24b }
  </style>
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
        <div class="hq-checkout">
          <div class="methods">
            <div class="mitem"><span class="icon">💳</span><span>Card</span></div>
            <div class="mitem"><span class="icon">🏦</span><span>Bank</span></div>
            <div class="mitem active"><span class="icon">🔁</span><span>Transfer</span></div>
            <div class="mitem"><span class="icon">💱</span><span>USSD</span></div>
            <div class="mitem"><span class="icon">🔎</span><span>Visa QR</span></div>
          </div>
          <div class="panel">
            <div class="hq-paycard">
              <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px">
                <div style="font-size:13px;color:#666">Pay with</div>
                <div style="font-size:13px;color:#333;font-weight:700"><?= htmlspecialchars($payment['email'] ?? '') ?> &nbsp; <span style="color:#0b5ed7">Pay <strong>NGN <?= number_format($payment['amount'],2) ?></strong></span></div>
              </div>

              <div class="paybox">
                <div class="bank">Paystack Checkout</div>
                <div style="font-size:16px;font-weight:700;margin-bottom:6px"><?= htmlspecialchars($siteSettings['bank_name'] ?? 'Zenith Bank') ?></div>
                <div class="acct"><?= htmlspecialchars($siteSettings['bank_account_number'] ?? '1190020180') ?> <button onclick="copyAcct(event)" style="margin-left:8px;border:none;background:none;cursor:pointer">📋</button></div>
                <div class="expires">Reference: <strong><?= htmlspecialchars($payment['reference']) ?></strong> &nbsp; • Expires in 29:23</div>
              </div>

              <div style="text-align:center;margin-top:14px">
                <button class="btn-primary" id="markSentBtn" type="button">I have sent the money</button>
              </div>

              <div id="payerRecordedInfo" style="display:none;margin-top:14px"></div>

              <div style="margin-top:14px;color:#666;text-align:center">This payment link expires after 2 days. After making the transfer, click "I have sent the money" and provide your transfer details.</div>

              <div id="countdown" style="font-size:18px;font-weight:600;color:#b33;margin-top:12px;">--:--</div>

              <form method="post" action="#" id="payer-form" style="margin-top:12px">
                <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf) ?>">
                <input type="hidden" name="payment_id" value="<?= intval($payment['id'] ?? 0) ?>">
                <div class="form-row"><label>Name on Payer Account</label><input name="payer_name" required></div>
                <div class="form-row"><label>Account Number</label><input name="payer_number" required></div>
                <div class="form-row"><label>Bank Name</label><input name="payer_bank" required></div>
              </form>
            </div>
          </div>
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
                      if (typeof Swal !== 'undefined') {
                        Swal.fire({ icon: 'success', title: 'Payment Successful', html: 'Your payment has been confirmed. Redirecting to your receipt...', showConfirmButton: false, timer: 2200, customClass: { popup: 'hq-swal' } }).then(()=>{
                          if (r.payment.receipt_path) { window.location = r.payment.receipt_path; }
                          else { window.location = (window.HQ_BASE||'') + '/public/receipt.php?ref=' + encodeURIComponent(ref); }
                        });
                      } else {
                        if (r.payment.receipt_path) { window.location = r.payment.receipt_path; }
                        else { window.location = (window.HQ_BASE||'') + '/public/receipt.php?ref=' + encodeURIComponent(ref); }
                      }
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

  </main>
  <footer style="padding:18px 0;text-align:center;color:#777;font-size:13px;">&copy; <?= date('Y') ?> HIGH Q SOLID ACADEMY</footer>
</body>
</html>
