<?php
// public/receipt.php - simple colored receipt page
require_once __DIR__ . '/config/db.php';
$ref = $_GET['ref'] ?? '';
if (!$ref) {
  $err = __DIR__ . '/errors/400.php';
  if (file_exists($err)) { include $err; } else { http_response_code(400); echo "Missing reference"; }
  exit;
}
$stmt = $pdo->prepare('SELECT p.*, sr.first_name, sr.last_name, sr.email AS reg_email, sr.passport_path FROM payments p LEFT JOIN student_registrations sr ON sr.id = p.student_id WHERE p.reference = ? LIMIT 1');
$stmt->execute([$ref]); $p = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$p) {
  $err = __DIR__ . '/errors/404.php';
  if (file_exists($err)) { include $err; } else { http_response_code(404); echo "Receipt not found"; }
  exit;
}
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Receipt - <?= htmlspecialchars($p['reference']) ?></title>
<link rel="stylesheet" href="./assets/css/public.css">
<style>
.receipt { max-width:900px;margin:24px auto;background:linear-gradient(180deg,#fff,#fff);padding:24px;border-radius:8px;border:1px solid rgba(0,0,0,0.06);} 
.header { display:flex;gap:12px;align-items:center;}
.logo img{height:56px}
.amount { font-size:28px;color:var(--hq-black);font-weight:700}
</style>
</head>
<body>
<div class="container">
  <div class="receipt">
    <div class="header">
      <div class="logo"><img src="./assets/images/hq-logo.jpeg" alt="HQ"></div>
      <div>
        <h2>Payment Receipt</h2>
        <div>Reference: <strong><?= htmlspecialchars($p['reference']) ?></strong></div>
        <div>Date: <?= htmlspecialchars($p['created_at']) ?></div>
      </div>
      <div style="margin-left:auto;text-align:right">
        <div class="amount">₦<?= number_format($p['amount'],2) ?></div>
        <div style="font-size:12px;color:#666">Status: <?= htmlspecialchars($p['status']) ?></div>
      </div>
    </div>
    <hr>
    <h3>Payer</h3>
    <div><strong>Name:</strong> <?= htmlspecialchars($p['payer_account_name'] ?? $p['name'] ?? '') ?></div>
    <div><strong>Email:</strong> <?= htmlspecialchars($p['reg_email'] ?? $p['email'] ?? '') ?></div>
    <div><strong>Account:</strong> <?= htmlspecialchars($p['payer_account_number'] ?? '') ?> (<?= htmlspecialchars($p['payer_bank_name'] ?? '') ?>)</div>
    <?php if (!empty($p['receipt_path'])): ?>
      <p><a href="<?= htmlspecialchars($p['receipt_path']) ?>" target="_blank">Download receipt file</a></p>
    <?php endif; ?>
    <?php if (!empty($p['passport_path'])): ?>
      <hr>
      <h4>Passport</h4>
      <img src="<?= htmlspecialchars($p['passport_path']) ?>" alt="passport" style="width:140px;border-radius:4px;border:1px solid #eee">
    <?php endif; ?>
    <hr>
  <p style="margin-top:12px"><a class="btn" href="javascript:window.print()">Print / Save as PDF</a> <a class="btn" href="<?= htmlspecialchars(function_exists('app_url') ? rtrim(app_url(''), '/') . '/index.php' : 'index.php') ?>">Return to site</a></p>
  </div>
</div>
</body>
</html>
<?php
// public/receipt.php - styled receipt view after payment confirmed
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/functions.php';

$ref = $_GET['ref'] ?? '';
if (!$ref) { header('Location: /'); exit; }

$stmt = $pdo->prepare('SELECT p.*, u.name as payer_name, s.site_name FROM payments p LEFT JOIN users u ON u.id = p.student_id LEFT JOIN site_settings s ON s.id = 1 WHERE p.reference = ? LIMIT 1');
$stmt->execute([$ref]);
$p = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$p) { echo '<p>Receipt not found for reference ' . htmlspecialchars($ref) . '</p>'; exit; }

$siteName = $p['site_name'] ?? 'HIGH Q SOLID ACADEMY';
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Receipt - <?= htmlspecialchars($siteName) ?></title>
  <link rel="stylesheet" href="assets/css/public.css">
  <style>
    .receipt-card{max-width:800px;margin:24px auto;padding:24px;border:1px solid #eee;border-radius:8px;background:#fff}
    .receipt-header{display:flex;align-items:center;gap:12px}
    .receipt-meta{margin-top:12px;color:#555}
    .actions{margin-top:18px}
  </style>
</head>
<body>
  <div class="receipt-card">
    <div class="receipt-header">
  <img src="<?= htmlspecialchars(function_exists('app_url') ? rtrim(app_url(''), '/') . '/assets/images/hq-logo.jpeg' : '/assets/images/hq-logo.jpeg') ?>" alt="logo" style="height:60px;">
      <div>
        <h2>Payment Receipt</h2>
        <div style="color:#888"><?= htmlspecialchars($siteName) ?></div>
      </div>
    </div>

    <hr>
    <div>
      <strong>Reference:</strong> <?= htmlspecialchars($p['reference']) ?> <br>
  <strong>Amount:</strong> ₦<?= number_format($p['amount'],2) ?> <br>
  <strong>Status:</strong> <?= htmlspecialchars($p['status']) ?> <br>
  <strong>Name:</strong> <?= htmlspecialchars($p['payer_account_name'] ?? $p['payer_name'] ?? '') ?> <br>
  <strong>Email:</strong> <?= htmlspecialchars($p['payer_email'] ?? $p['email'] ?? '') ?> <br>
  <strong>Address:</strong> <?= nl2br(htmlspecialchars($p['payer_address'] ?? '')) ?> <br>
  <div class="receipt-meta">Payment recorded at: <?= htmlspecialchars($p['created_at'] ?? '') ?></div>
    </div>

    <div class="actions">
  <button onclick="window.print();" class="btn-primary">Print / Download</button>
  <a href="<?= htmlspecialchars(function_exists('app_url') ? rtrim(app_url(''), '/') . '/contact.php' : 'contact.php') ?>" class="btn" style="margin-left:8px;">Contact Support</a>
  <a href="<?= htmlspecialchars(function_exists('app_url') ? rtrim(app_url(''), '/') . '/contact.php#livechat' : 'contact.php#livechat') ?>" class="btn" style="margin-left:8px;">Open Live Chat</a>
    </div>

    <div style="margin-top:18px;color:#333">
      <h4>Next steps</h4>
      <p>Thank you for registering. You can contact us via the contact form for faster responses or start a live chat with an agent.</p>
    </div>
  </div>
</body>
</html>
