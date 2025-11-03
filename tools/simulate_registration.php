<?php
// tools/simulate_registration.php
// CLI helper to simulate registration payment creation for debugging.
// Usage: php simulate_registration.php regular
//        php simulate_registration.php postutme

if (php_sapi_name() !== 'cli') {
    echo "This script is CLI-only.\n";
    exit(1);
}
$mode = $argv[1] ?? 'regular';
$allowed = ['regular','postutme'];
if (!in_array($mode, $allowed)) {
    echo "Usage: php simulate_registration.php [regular|postutme]\n";
    exit(1);
}

// Load DB and helper functions (same as the app uses)
require_once __DIR__ . '/../public/config/db.php';
require_once __DIR__ . '/../public/config/functions.php';

// Use $pdo from db.php
if (!isset($pdo) || !$pdo) {
    echo "PDO not available. Check public/config/db.php.\n";
    exit(1);
}

echo "Simulating registration mode: $mode\n";

try {
    $pdo->beginTransaction();

    if ($mode === 'postutme') {
        // Create a PTU-style payment (student_id NULL like the real flow)
        $post_form_fee = 1000.00;
        $post_tutor_fee = 8000.00;
        $service_charge = round(mt_rand(0, 16754) / 100.0, 2);
        $total_amount = $post_form_fee + $post_tutor_fee + $service_charge;

        $reference = generatePaymentReference('PTU');
        $paymentMetadata = json_encode([
            'components' => [
                'post_form_fee' => number_format((float)$post_form_fee, 2, '.', ''),
                'tutor_fee' => number_format((float)$post_tutor_fee, 2, '.', ''),
                'service_charge' => number_format((float)$service_charge, 2, '.', ''),
            ],
            'total' => number_format((float)$total_amount, 2, '.', ''),
            'registration_type' => 'postutme'
        ], JSON_UNESCAPED_SLASHES);

        $ins = $pdo->prepare('INSERT INTO payments (student_id, amount, payment_method, reference, status, created_at, metadata, form_fee_paid, tutor_fee_paid, registration_type) VALUES (NULL, ?, ?, ?, "pending", NOW(), ?, ?, ?, "postutme")');
        $ins->execute([$total_amount, 'bank', $reference, $paymentMetadata, 0, 1]);
        $paymentId = $pdo->lastInsertId();

        echo "Created (simulated) PTU payment id={$paymentId}, reference={$reference}, amount={$total_amount}\n";
        echo "Session would be set: \\$_SESSION['last_payment_id'] = $paymentId; \\$_SESSION['last_payment_reference'] = $reference\n";

    } else {
        // Regular flow: assume some fixed-priced programs selected
        $amount = 5000.00; // base fixed programs total
        $form_fee = 1000.00; $card_fee = 1500.00;
        $amount += $form_fee + $card_fee; // same server-side calculation

        $reference = generatePaymentReference('REG');
        $metadata = json_encode(['fixed_programs' => [1,2], 'varies_programs' => []]);
        $ins = $pdo->prepare('INSERT INTO payments (student_id, amount, payment_method, reference, status, created_at, metadata) VALUES (NULL, ?, ?, ?, "pending", NOW(), ?)');
        $ins->execute([$amount, 'bank', $reference, $metadata]);
        $paymentId = $pdo->lastInsertId();

        echo "Created (simulated) REG payment id={$paymentId}, reference={$reference}, amount={$amount}\n";
        echo "Session would be set: \\$_SESSION['last_payment_id'] = $paymentId; \\$_SESSION['last_payment_reference'] = $reference\n";
    }

    // Don't commit — roll back so we don't alter DB during simulation
    $pdo->rollBack();
    echo "Transaction rolled back (no data persisted).\n";

} catch (Throwable $e) {
    echo "Error during simulation: " . $e->getMessage() . "\n";
    try { if ($pdo->inTransaction()) $pdo->rollBack(); } catch(Throwable $_){}
}

echo "Done.\n";
