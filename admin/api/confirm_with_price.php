<?php
// admin/api/confirm_with_price.php - Confirm registration and send payment link email
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../../public/config/payment_references.php';

header('Content-Type: application/json');
requirePermission('academic');

$id = intval($_POST['id'] ?? 0);
$amount = floatval($_POST['amount'] ?? 0);
$customMessage = trim($_POST['custom_message'] ?? '');
$source = trim($_POST['source'] ?? 'regular');

if ($id <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid registration ID']);
    exit;
}

if ($amount <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid amount']);
    exit;
}

try {
    $pdo->beginTransaction();
    
    // Determine which table to use based on source
    $registration = null;
    $tableName = 'student_registrations';
    
    if ($source === 'universal') {
        $tableName = 'universal_registrations';
        $stmt = $pdo->prepare('SELECT * FROM universal_registrations WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        $registration = $stmt->fetch(PDO::FETCH_ASSOC);
    } elseif ($source === 'postutme') {
        $tableName = 'post_utme_registrations';
        $stmt = $pdo->prepare('SELECT * FROM post_utme_registrations WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        $registration = $stmt->fetch(PDO::FETCH_ASSOC);
    } else {
        // Regular student_registrations
        $stmt = $pdo->prepare('SELECT sr.*, COALESCE(sr.email, u.email) AS email FROM student_registrations sr LEFT JOIN users u ON u.id = sr.user_id WHERE sr.id = ? LIMIT 1');
        $stmt->execute([$id]);
        $registration = $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    if (!$registration) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'error' => 'Registration not found']);
        exit;
    }
    
    // Get email from registration
    $email = $registration['email'] ?? null;
    
    // For universal registrations, also check payload
    if ($source === 'universal' && empty($email) && !empty($registration['payload'])) {
        $payload = json_decode($registration['payload'], true);
        $email = $payload['email'] ?? null;
    }
    
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'error' => 'No valid email found for this registration']);
        exit;
    }
    
    // Get student name
    $firstName = $registration['first_name'] ?? '';
    $lastName = $registration['last_name'] ?? $registration['surname'] ?? '';
    $studentName = trim("$firstName $lastName") ?: 'Student';
    
    // Generate payment reference based on program type
    $programType = $registration['program_type'] ?? $registration['registration_type'] ?? 'registration';
    $prefixMap = [
        'jamb' => 'JAMB',
        'waec' => 'WAEC',
        'postutme' => 'PUTM',
        'digital' => 'DIGI',
        'international' => 'INTL',
    ];
    $prefix = $prefixMap[strtolower($programType)] ?? 'REG';
    $reference = generatePaymentReference($prefix);
    
    // Create payment record
    $insPayment = $pdo->prepare('INSERT INTO payments (student_id, amount, payment_method, reference, status, created_at, metadata, registration_type) VALUES (NULL, ?, ?, ?, "pending", NOW(), ?, ?)');
    $metadata = json_encode([
        'registration_id' => $id,
        'source' => $source,
        'program_type' => $programType,
        'email' => $email,
        'name' => $studentName,
        'custom_message' => $customMessage,
    ]);
    $insPayment->execute([$amount, 'bank', $reference, $metadata, $programType]);
    $paymentId = $pdo->lastInsertId();
    
    // Update registration status
    if ($source === 'universal') {
        $upd = $pdo->prepare('UPDATE universal_registrations SET status = ?, payment_status = ?, amount = ?, payment_reference = ? WHERE id = ?');
        $upd->execute(['awaiting_payment', 'pending', $amount, $reference, $id]);
    } elseif ($source === 'postutme') {
        $upd = $pdo->prepare('UPDATE post_utme_registrations SET status = ?, payment_status = ? WHERE id = ?');
        $upd->execute(['awaiting_payment', 'pending', $id]);
    } else {
        $upd = $pdo->prepare('UPDATE student_registrations SET status = ? WHERE id = ?');
        $upd->execute(['awaiting_payment', $id]);
    }
    
    // Load site settings for email
    $siteName = 'HIGH Q SOLID ACADEMY';
    $contactEmail = '';
    $contactPhone = '';
    try {
        $settings = $pdo->query('SELECT site_name, contact_email, contact_phone FROM site_settings ORDER BY id ASC LIMIT 1')->fetch(PDO::FETCH_ASSOC);
        if ($settings) {
            $siteName = $settings['site_name'] ?: $siteName;
            $contactEmail = $settings['contact_email'] ?: '';
            $contactPhone = $settings['contact_phone'] ?: '';
        }
    } catch (Throwable $e) {}
    
    // Build payment link
    $paymentLink = function_exists('app_url') ? app_url('pay/' . urlencode($reference)) : 'payments_wait.php?ref=' . urlencode($reference);
    
    // Build email
    $subject = "$siteName - Payment Required for Your Registration";
    
    $body = '<!DOCTYPE html><html><head><meta charset="utf-8"></head><body style="font-family:Arial,Helvetica,sans-serif;color:#333;line-height:1.6;">';
    $body .= '<div style="max-width:600px;margin:0 auto;padding:20px;">';
    $body .= '<div style="background:linear-gradient(135deg,#0b1a2c 0%,#1e3a5f 100%);color:#ffd600;padding:24px;border-radius:12px 12px 0 0;text-align:center;">';
    $body .= '<h1 style="margin:0;font-size:1.5rem;">' . htmlspecialchars($siteName) . '</h1>';
    $body .= '<p style="margin:8px 0 0;opacity:0.9;">Registration Confirmation</p>';
    $body .= '</div>';
    
    $body .= '<div style="background:#fff;border:1px solid #e2e8f0;border-top:0;padding:24px;">';
    $body .= '<h2 style="color:#0b1a2c;margin-top:0;">Hello ' . htmlspecialchars($studentName) . ',</h2>';
    $body .= '<p>Great news! Your registration has been <strong style="color:#22c55e;">approved</strong>. To complete your enrollment, please make the following payment:</p>';
    
    $body .= '<div style="background:linear-gradient(135deg,#fefce8 0%,#fef9c3 100%);border:2px solid #ffd600;border-radius:12px;padding:20px;margin:20px 0;text-align:center;">';
    $body .= '<p style="margin:0;font-size:0.9rem;color:#64748b;">Amount to Pay</p>';
    $body .= '<p style="margin:8px 0 0;font-size:2rem;font-weight:800;color:#0b1a2c;">₦' . number_format($amount, 2) . '</p>';
    $body .= '</div>';
    
    if (!empty($customMessage)) {
        $body .= '<div style="background:#f8fafc;border-left:4px solid #ffd600;padding:16px;margin:20px 0;">';
        $body .= '<p style="margin:0;font-style:italic;color:#475569;">' . nl2br(htmlspecialchars($customMessage)) . '</p>';
        $body .= '</div>';
    }
    
    $body .= '<p style="text-align:center;margin:24px 0;">';
    $body .= '<a href="' . htmlspecialchars($paymentLink) . '" style="display:inline-block;background:linear-gradient(135deg,#ffd600 0%,#e6c200 100%);color:#0b1a2c;padding:14px 32px;border-radius:8px;text-decoration:none;font-weight:700;font-size:1.1rem;">Complete Payment Now</a>';
    $body .= '</p>';
    
    $body .= '<p style="font-size:0.9rem;color:#64748b;">If the button above doesn\'t work, copy and paste this link into your browser:</p>';
    $body .= '<p style="background:#f1f5f9;padding:10px;border-radius:6px;word-break:break-all;font-size:0.85rem;"><a href="' . htmlspecialchars($paymentLink) . '">' . htmlspecialchars($paymentLink) . '</a></p>';
    
    $body .= '<p style="margin-top:24px;padding-top:16px;border-top:1px solid #e2e8f0;font-size:0.85rem;color:#64748b;">';
    $body .= '<strong>Payment Reference:</strong> ' . htmlspecialchars($reference) . '<br>';
    $body .= 'This link expires in 48 hours.';
    $body .= '</p>';
    $body .= '</div>';
    
    // Footer
    $body .= '<div style="text-align:center;padding:16px;color:#64748b;font-size:0.8rem;">';
    $body .= htmlspecialchars($siteName);
    if ($contactPhone) $body .= ' | ' . htmlspecialchars($contactPhone);
    if ($contactEmail) $body .= ' | ' . htmlspecialchars($contactEmail);
    $body .= '</div>';
    
    $body .= '</div></body></html>';
    
    // Send email
    $emailSent = false;
    if (function_exists('sendEmail')) {
        try {
            $emailSent = (bool) sendEmail($email, $subject, $body);
        } catch (Throwable $e) {
            // Log error but don't fail
            @file_put_contents(__DIR__ . '/../../storage/logs/email_errors.log', date('Y-m-d H:i:s') . " | confirm_with_price | $email | " . $e->getMessage() . "\n", FILE_APPEND | LOCK_EX);
        }
    }
    
    // Log action
    $currentUserId = $_SESSION['user']['id'] ?? null;
    if (function_exists('logAction') && $currentUserId) {
        logAction($pdo, $currentUserId, 'confirm_with_price', [
            'registration_id' => $id,
            'source' => $source,
            'amount' => $amount,
            'reference' => $reference,
            'email' => $email,
            'email_sent' => $emailSent,
        ]);
    }

    if (function_exists('sendAdminChangeNotification')) {
        try {
            sendAdminChangeNotification(
                $pdo,
                'Registration Confirmed With Price',
                [
                    'Registration ID' => $id,
                    'Source' => $source,
                    'Student Email' => $email,
                    'Amount' => number_format($amount, 2),
                    'Reference' => $reference,
                    'Applicant Email Sent' => $emailSent ? 'Yes' : 'No'
                ],
                (int)($currentUserId ?? 0)
            );
        } catch (Throwable $_) {}
    }
    
    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Registration confirmed and payment link sent',
        'reference' => $reference,
        'email' => $email,
        'amount' => $amount,
        'email_sent' => $emailSent,
        'payment_link' => $paymentLink,
    ]);
    
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    
    // Log error
    @file_put_contents(__DIR__ . '/../../storage/logs/confirm_with_price_errors.log', date('Y-m-d H:i:s') . " | ID: $id | " . $e->getMessage() . "\n" . $e->getTraceAsString() . "\n", FILE_APPEND | LOCK_EX);
    
    echo json_encode([
        'success' => false,
        'error' => 'Server error: ' . $e->getMessage(),
    ]);
}
