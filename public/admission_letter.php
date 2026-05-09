<?php

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/functions.php';
require_once __DIR__ . '/includes/admission-package.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$ref = trim((string)($_GET['ref'] ?? ($_SESSION['last_payment_reference'] ?? '')));
$rid = (int)($_GET['rid'] ?? 0);
$format = strtolower(trim((string)($_GET['format'] ?? 'html')));

$data = null;
if ($ref !== '') {
    $data = hqAdmissionDataByPaymentReference($pdo, $ref);
}

if ($data === null && $rid > 0) {
    try {
        $stmt = $pdo->prepare('SELECT payment_reference FROM universal_registrations WHERE id = ? LIMIT 1');
        $stmt->execute([$rid]);
        $paymentReference = (string)$stmt->fetchColumn();
        if ($paymentReference !== '') {
            $data = hqAdmissionDataByPaymentReference($pdo, $paymentReference);
            $ref = $paymentReference;
        }
    } catch (Throwable $e) {
    }
}

if ($data === null) {
    http_response_code(404);
    echo 'Admission letter data was not found for this registration/payment.';
    exit;
}

if ($format === 'pdf') {
    $file = hqGenerateAdmissionLetterFile($pdo, $data);
    if (!empty($file['path']) && is_readable($file['path'])) {
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . basename((string)$file['path']) . '"');
        readfile($file['path']);
        exit;
    }

    http_response_code(500);
    echo 'Unable to generate admission letter PDF.';
    exit;
}

echo hqAdmissionHtml($data, false);

