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
  <link rel="stylesheet" href="/assets/css/public.css">
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
      <img src="/assets/images/hq-logo.jpeg" alt="logo" style="height:60px;">
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
      <strong>Payer Name:</strong> <?= htmlspecialchars($p['payer_account_name'] ?? $p['payer_name'] ?? '') ?> <br>
      <strong>Payer Number:</strong> <?= htmlspecialchars($p['payer_account_number'] ?? '') ?> <br>
      <div class="receipt-meta">Payment recorded at: <?= htmlspecialchars($p['created_at'] ?? '') ?></div>
    </div>

    <div class="actions">
      <button onclick="window.print();" class="btn-primary">Print / Download</button>
      <a href="/contact.php" class="btn" style="margin-left:8px;">Contact Support</a>
      <a href="/contact.php#livechat" class="btn" style="margin-left:8px;">Open Live Chat</a>
    </div>

    <div style="margin-top:18px;color:#333">
      <h4>Next steps</h4>
      <p>Thank you for registering. You can contact us via the contact form for faster responses or start a live chat with an agent.</p>
    </div>
  </div>
</body>
</html>
