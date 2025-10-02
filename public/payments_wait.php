<?php
// public/payments_wait.php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/csrf.php';
require_once __DIR__ . '/config/functions.php';
$ref = $_GET['ref'] ?? ($_SESSION['last_payment_reference'] ?? '');
$payment = null;
if ($ref) {
    $stmt = $pdo->prepare('SELECT p.*, u.email, u.name FROM payments p LEFT JOIN users u ON u.id = p.student_id WHERE p.reference = ? LIMIT 1');
    $stmt->execute([$ref]);
    $payment = $stmt->fetch(PDO::FETCH_ASSOC);
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
  <p>Please transfer <strong>₦<?= number_format($payment['amount'],2) ?></strong> to the account below and use reference <strong><?= htmlspecialchars($payment['reference']) ?></strong>.</p>
  <p><strong>Account Name:</strong> <?= htmlspecialchars($siteSettings['bank_account_name'] ?? 'High Q Solid Academy Limited') ?><br>
  <strong>Bank:</strong> <?= htmlspecialchars($siteSettings['bank_name'] ?? '') ?><br>
  <strong>Account Number:</strong> <?= htmlspecialchars($siteSettings['bank_account_number'] ?? '') ?></p>

        <p>After making the transfer, click "I have sent the money" and provide your transfer details. An admin will verify within 10 minutes. If confirmation takes longer, an agent will contact you.</p>

  <div id="countdown" style="font-size:18px;font-weight:600;color:#b33;margin-bottom:12px;">10:00</div>

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
          (function(){
            var form = document.getElementById('payer-form');
            var btn = document.getElementById('markSentBtn');
            form.addEventListener('submit', function(e){
              e.preventDefault();
              btn.disabled = true; btn.textContent = 'Recording...';
              var fd = new FormData(form);
              fetch('api/mark_sent.php', { method: 'POST', body: fd, credentials: 'same-origin' })
                .then(function(r){ return r.json(); })
                .then(function(j){
                      if (j.status === 'ok') {
                        // show success and let polling detect confirmation
                        btn.textContent = 'Recorded — awaiting admin verification';
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
                      } else {
                        alert(j.message || 'Failed to record transfer.');
                        btn.disabled = false; btn.textContent = 'I have sent the money';
                      }
                }).catch(function(){ alert('Network error'); btn.disabled = false; btn.textContent = 'I have sent the money'; });
            });
            
          })();
        </script>

        <p style="margin-top:12px;color:#666;">Reference: <?= htmlspecialchars($payment['reference'] ?? '') ?></p>
      </div>

      <div id="payerRecordedInfo" style="display:none;"></div>

      <script>
        // simple 10 minute countdown + polling for status
        (function(){
          var total = 10*60; // seconds
          var el = document.getElementById('countdown');
          function fmt(s){ var m=Math.floor(s/60); var ss=s%60; return (m<10? '0'+m: m)+":"+(ss<10? '0'+ss:ss); }
          var iv = setInterval(function(){ total--; if (total<0){ clearInterval(iv); el.textContent = 'Time elapsed — confirmation may take longer. A tutor will contact you.'; return; } el.textContent = fmt(total); }, 1000);

          // polling
          var ref = <?= json_encode($payment['reference'] ?? '') ?>;
          function check(){
            var xhr = new XMLHttpRequest(); xhr.open('GET', 'api/payment_status.php?ref=' + encodeURIComponent(ref), true);
            xhr.onload = function(){ if (xhr.status===200){ try{ var r = JSON.parse(xhr.responseText); if (r.status==='ok' && r.payment && r.payment.status==='confirmed'){ if (r.payment.receipt_path){ window.location = r.payment.receipt_path; } else { window.location = 'receipt.php?ref=' + encodeURIComponent(ref); } } }catch(e){} }};
            xhr.send();
          }
          var poll = setInterval(check, 10000);
        })();
      </script>

    <?php endif; ?>
  </div>
</section>

  </main>
  <footer style="padding:18px 0;text-align:center;color:#777;font-size:13px;">&copy; <?= date('Y') ?> HIGH Q SOLID ACADEMY</footer>
</body>
</html>
