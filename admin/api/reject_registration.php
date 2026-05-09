<?php
// admin/api/reject_registration.php - Reject registration (universal, postutme, or regular)
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/csrf.php';

header('Content-Type: application/json');
requirePermission('academic');

$id = intval($_POST['id'] ?? 0);
$action = trim($_POST['action'] ?? 'reject_registration');
$reason = trim($_POST['reason'] ?? '');
$currentUserId = $_SESSION['user']['id'] ?? null;

if ($id <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid registration ID']);
    exit;
}

try {
    $email = null;
    $studentName = 'Student';
    
    // Determine which table to use based on action
    if ($action === 'reject_universal') {
        // Universal registrations
        $stmt = $pdo->prepare('SELECT * FROM universal_registrations WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        $reg = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$reg) {
            echo json_encode(['success' => false, 'error' => 'Registration not found']);
            exit;
        }
        
        $upd = $pdo->prepare('UPDATE universal_registrations SET status = ?, updated_at = NOW() WHERE id = ?');
        $upd->execute(['rejected', $id]);
        
        $email = $reg['email'] ?? null;
        if (empty($email) && !empty($reg['payload'])) {
            $payload = json_decode($reg['payload'], true);
            $email = $payload['email'] ?? null;
        }
        $studentName = trim(($reg['first_name'] ?? '') . ' ' . ($reg['last_name'] ?? '')) ?: 'Student';
        
    } elseif ($action === 'reject_postutme') {
        // Post-UTME registrations
        $stmt = $pdo->prepare('SELECT * FROM post_utme_registrations WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        $reg = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$reg) {
            echo json_encode(['success' => false, 'error' => 'Registration not found']);
            exit;
        }
        
        $upd = $pdo->prepare('UPDATE post_utme_registrations SET status = ? WHERE id = ?');
        $upd->execute(['rejected', $id]);
        
        $email = $reg['email'] ?? null;
        $studentName = trim(($reg['first_name'] ?? $reg['surname'] ?? '') . ' ' . ($reg['other_name'] ?? '')) ?: 'Student';
        
    } else {
        // Regular student registrations
        $stmt = $pdo->prepare('SELECT sr.*, COALESCE(sr.email, u.email) AS email FROM student_registrations sr LEFT JOIN users u ON u.id = sr.user_id WHERE sr.id = ? LIMIT 1');
        $stmt->execute([$id]);
        $reg = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$reg) {
            echo json_encode(['success' => false, 'error' => 'Registration not found']);
            exit;
        }
        
        $upd = $pdo->prepare('UPDATE student_registrations SET status = ? WHERE id = ?');
        $upd->execute(['rejected', $id]);
        
        $email = $reg['email'] ?? null;
        $studentName = trim(($reg['first_name'] ?? '') . ' ' . ($reg['last_name'] ?? '')) ?: 'Student';
    }
    
    // Log action
    if (function_exists('logAction') && $currentUserId) {
        logAction($pdo, $currentUserId, $action, ['registration_id' => $id, 'reason' => $reason]);
    }
    
    // Send rejection email
    $emailSent = false;
    if (!empty($email) && filter_var($email, FILTER_VALIDATE_EMAIL) && function_exists('sendEmail')) {
        $subject = 'Registration Update — HIGH Q SOLID ACADEMY';
        $body = '<p>Hi ' . htmlspecialchars($studentName) . ',</p>';
        $body .= '<p>We regret to inform you that your registration has been <strong style="color:#dc2626;">rejected</strong>.</p>';
        if ($reason) {
            $body .= '<p><strong>Reason:</strong> ' . htmlspecialchars($reason) . '</p>';
        }
        $body .= '<p>If you have questions, please contact our support team.</p>';
        $body .= '<p>Best regards,<br>HIGH Q SOLID ACADEMY</p>';
        
        try {
            $emailSent = (bool) sendEmail($email, $subject, $body);
        } catch (Throwable $e) {
            $emailSent = false;
        }
    }

    if (function_exists('sendAdminChangeNotification')) {
        try {
            sendAdminChangeNotification(
                $pdo,
                'Registration Rejected',
                [
                    'Registration ID' => $id,
                    'Action' => $action,
                    'Student Email' => $email ?: 'N/A',
                    'Reason' => $reason ?: 'Not provided',
                    'Applicant Email Sent' => $emailSent ? 'Yes' : 'No'
                ],
                (int)($currentUserId ?? 0)
            );
        } catch (Throwable $_) {}
    }
    
    echo json_encode(['success' => true, 'message' => 'Registration rejected', 'status' => 'ok', 'email_sent' => $emailSent]);
    
} catch (Throwable $e) {
    @file_put_contents(__DIR__ . '/../../storage/logs/reject_reg_errors.log', date('Y-m-d H:i:s') . " | ID: $id | " . $e->getMessage() . "\n", FILE_APPEND | LOCK_EX);
    echo json_encode(['success' => false, 'error' => 'Server error: ' . $e->getMessage()]);
}
