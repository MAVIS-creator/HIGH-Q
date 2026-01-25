<?php
// admin/api/confirm_registration.php - Confirm registration (universal, postutme, or regular)
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/csrf.php';

header('Content-Type: application/json');
requirePermission('academic');

$id = intval($_POST['id'] ?? 0);
$action = trim($_POST['action'] ?? 'confirm_registration');
$currentUserId = $_SESSION['user']['id'] ?? null;

if ($id <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid registration ID']);
    exit;
}

try {
    // Determine which table to use based on action
    if ($action === 'confirm_universal') {
        // Universal registrations
        $stmt = $pdo->prepare('SELECT * FROM universal_registrations WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        $reg = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$reg) {
            echo json_encode(['success' => false, 'error' => 'Registration not found']);
            exit;
        }
        
        if (strtolower($reg['status'] ?? '') === 'confirmed') {
            echo json_encode(['success' => false, 'error' => 'Already confirmed']);
            exit;
        }
        
        $upd = $pdo->prepare('UPDATE universal_registrations SET status = ?, updated_at = NOW() WHERE id = ?');
        $upd->execute(['confirmed', $id]);
        
    } elseif ($action === 'confirm_postutme') {
        // Post-UTME registrations
        $stmt = $pdo->prepare('SELECT * FROM post_utme_registrations WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        $reg = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$reg) {
            echo json_encode(['success' => false, 'error' => 'Registration not found']);
            exit;
        }
        
        if (strtolower($reg['status'] ?? '') === 'confirmed') {
            echo json_encode(['success' => false, 'error' => 'Already confirmed']);
            exit;
        }
        
        $upd = $pdo->prepare('UPDATE post_utme_registrations SET status = ? WHERE id = ?');
        $upd->execute(['confirmed', $id]);
        
    } else {
        // Regular student registrations
        $stmt = $pdo->prepare('SELECT sr.*, COALESCE(sr.email, u.email) AS email FROM student_registrations sr LEFT JOIN users u ON u.id = sr.user_id WHERE sr.id = ? LIMIT 1');
        $stmt->execute([$id]);
        $reg = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$reg) {
            echo json_encode(['success' => false, 'error' => 'Registration not found']);
            exit;
        }
        
        if (strtolower($reg['status'] ?? '') === 'confirmed') {
            echo json_encode(['success' => false, 'error' => 'Already confirmed']);
            exit;
        }
        
        $upd = $pdo->prepare('UPDATE student_registrations SET status = ?, updated_at = NOW() WHERE id = ?');
        $upd->execute(['confirmed', $id]);
    }
    
    // Log action
    if (function_exists('logAction') && $currentUserId) {
        logAction($pdo, $currentUserId, $action, ['registration_id' => $id]);
    }
    
    echo json_encode(['success' => true, 'message' => 'Registration confirmed', 'status' => 'ok']);
    
} catch (Throwable $e) {
    @file_put_contents(__DIR__ . '/../../storage/logs/confirm_reg_errors.log', date('Y-m-d H:i:s') . " | ID: $id | " . $e->getMessage() . "\n", FILE_APPEND | LOCK_EX);
    echo json_encode(['success' => false, 'error' => 'Server error: ' . $e->getMessage()]);
}
