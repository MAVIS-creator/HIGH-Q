<?php
// admin/pages/payment.php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/csrf.php';

// permission may be 'students' or 'payments' depending on roles; use students for now
requirePermission('payments');
$csrf = generateToken('admin_payment_form');
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Create Payment Link — Admin</title>
  <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../assets/css/style.css">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
<?php include __DIR__ . '/../includes/header.php'; ?>
<?php include __DIR__ . '/../includes/sidebar.php'; ?>

<main style="padding:18px;">
  <h2>Create & Send Payment Link</h2>
  <div style="max-width:720px;background:#fff;padding:18px;border-radius:8px;border:1px solid #eee;">
    <form id="createPaymentForm">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
      <div style="margin-bottom:10px;"><label>Amount (NGN)</label><input type="number" step="0.01" name="amount" required class="form-control" placeholder="e.g. 1080"></div>
      <div style="margin-bottom:10px;"><label>Recipient email</label><input type="email" name="email" required class="form-control" placeholder="user@example.com"></div>
      <div style="margin-bottom:10px;"><label>Message to include in email (optional)</label><textarea name="message" rows="4" class="form-control" placeholder="Custom message to include with the payment link"></textarea></div>
      <div style="display:flex;gap:8px;justify-content:flex-end;"><button type="submit" class="btn btn-primary">Send payment link</button></div>
    </form>
  </div>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>

<script>
document.getElementById('createPaymentForm').addEventListener('submit', function(e){
  e.preventDefault();
  var f = new FormData(this);
  fetch('../api/send_payment_link.php', { method: 'POST', body: f, credentials: 'same-origin' })
    .then(r=>r.json()).then(function(j){
      if (!j) return Swal.fire('Error','No response from server','error');
      if (j.success) {
        Swal.fire({ title: 'Payment link sent', html: '<p>Reference: <code>' + (j.reference||'') + '</code></p><p><a href="' + (j.link||'#') + '" target="_blank">Open link</a></p>', icon: 'success' });
      } else {
        Swal.fire('Error', j.error || 'Failed to send', 'error');
      }
    }).catch(function(err){ Swal.fire('Error', err.message || 'Request failed', 'error'); });
});
</script>
</body>
</html>
