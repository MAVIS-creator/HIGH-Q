<?php
// public/payments_callback.php
// Use public config includes
require_once __DIR__ . '/config/db.php';
$cfg = require __DIR__ . '/config/payments.php';
$secret = $cfg['paystack']['secret'] ?? '';

$reference = $_GET['reference'] ?? ($_POST['reference'] ?? null);
if (!$reference) {
    echo 'Missing reference'; exit;
}

// Verify transaction server-side
$ch = curl_init('https://api.paystack.co/transaction/verify/' . urlencode($reference));
curl_setopt($ch, CURLOPT_HTTPHEADER, [ 'Authorization: Bearer ' . $secret ]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$res = curl_exec($ch);
curl_close($ch);
$data = json_decode($res, true);

if (!empty($data['status']) && $data['status'] && !empty($data['data'])) {
    $d = $data['data'];
    // Optionally show success page; actual DB update should happen in webhook
    echo '<h3>Payment Completed</h3>';
    echo '<p>Reference: ' . htmlspecialchars($reference) . '</p>';
    echo '<p>Amount: ' . number_format(($d['amount'] ?? 0)/100,2) . '</p>';
} else {
    echo '<h3>Payment not verified</h3>';
    echo '<p>Please contact support with reference ' . htmlspecialchars($reference) . '</p>';
}
