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
$csrf = generateToken('signup_form');
// Minimal waiting page: no header/footer for a clean payment flow
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

  <form method="post" action="/register.php" id="payer-form">
          <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrf) ?>">
          <input type="hidden" name="payment_id" value="<?= intval($payment['id'] ?? 0) ?>">
          <div class="form-row"><label>Name on Payer Account</label><input name="payer_name" required></div>
          <div class="form-row"><label>Payer Account Number</label><input name="payer_number" required></div>
          <div class="form-row"><label>Payer Bank Name</label><input name="payer_bank" required></div>
          <div style="margin-top:12px;"><button class="btn-primary" name="mark_sent" type="submit">I have sent the money</button></div>
        </form>

        <p style="margin-top:12px;color:#666;">Reference: <?= htmlspecialchars($payment['reference'] ?? '') ?></p>
      </div>

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
            var xhr = new XMLHttpRequest(); xhr.open('GET', '/api/payment_status.php?ref=' + encodeURIComponent(ref), true);
            xhr.onload = function(){ if (xhr.status===200){ try{ var r = JSON.parse(xhr.responseText); if (r.status==='ok' && r.payment && r.payment.status==='confirmed'){ if (r.payment.receipt_path){ window.location = '/public/' + r.payment.receipt_path; } else { location.reload(); } } }catch(e){} }};
            xhr.send();
          }
          var poll = setInterval(check, 10000);
        })();
      </script>

    <?php endif; ?>
  </div>
</section>

<?php include __DIR__ . '/includes/footer.php';
