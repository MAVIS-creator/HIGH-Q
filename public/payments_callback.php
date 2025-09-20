<?php
// public/payments_callback.php
// Use public config includes
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/../vendor/autoload.php';
$cfg = require __DIR__ . '/config/payments.php';
$paymentsHelper = new \Src\Helpers\Payments($cfg);

$reference = $_GET['reference'] ?? ($_POST['reference'] ?? null);
if (!$reference) {
    echo 'Missing reference'; exit;
}

// Verify transaction server-side
try {
    $data = $paymentsHelper->verifyPaystackReference($reference);
} catch (Exception $e) {
    echo '<h3>Payment verification error</h3><p>' . htmlspecialchars($e->getMessage()) . '</p>'; exit;
}

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
