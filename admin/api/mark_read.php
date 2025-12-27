<?php
// admin/api/mark_read.php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

if (empty($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$userId = $_SESSION['user']['id'];
$action = $_POST['action'] ?? 'mark_single';

try {
    // Ensure notifications table exists
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS notifications (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            type VARCHAR(50) NOT NULL,
            reference_id INT NOT NULL,
            is_read TINYINT(1) DEFAULT 0,
            read_at DATETIME NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_notification (user_id, type, reference_id)
        )
    ");
    
    if ($action === 'mark_all') {
        // Mark all notifications as read for this user
        // Get all current notification types and ids from the notifications API
        $types = ['comment', 'student_application', 'payment', 'chat', 'user'];
        
        // Insert/update read status for common notification types
        foreach ($types as $type) {
            // Get IDs based on type
            switch ($type) {
                case 'comment':
                    $ids = $pdo->query("SELECT id FROM comments WHERE status = 'pending' LIMIT 20")->fetchAll(PDO::FETCH_COLUMN);
                    break;
                case 'student_application':
                    $ids = $pdo->query("SELECT id FROM users WHERE (role_id IS NULL OR role_id=(SELECT id FROM roles WHERE slug='student' LIMIT 1)) AND is_active = 0 LIMIT 20")->fetchAll(PDO::FETCH_COLUMN);
                    break;
                case 'payment':
                    $ids = $pdo->query("SELECT id FROM payments WHERE status IN ('pending','confirmed') ORDER BY created_at DESC LIMIT 20")->fetchAll(PDO::FETCH_COLUMN);
                    break;
                case 'chat':
                    $ids = $pdo->query("SELECT id FROM chat_threads ORDER BY last_activity DESC LIMIT 20")->fetchAll(PDO::FETCH_COLUMN);
                    break;
                case 'user':
                    $ids = $pdo->query("SELECT id FROM users ORDER BY created_at DESC LIMIT 20")->fetchAll(PDO::FETCH_COLUMN);
                    break;
                default:
                    $ids = [];
            }
            
            foreach ($ids as $refId) {
                $stmt = $pdo->prepare("
                    INSERT INTO notifications (user_id, type, reference_id, is_read, read_at)
                    VALUES (?, ?, ?, 1, NOW())
                    ON DUPLICATE KEY UPDATE is_read = 1, read_at = NOW()
                ");
                $stmt->execute([$userId, $type, $refId]);
            }
        }
        
        echo json_encode(['success' => true, 'message' => 'All notifications marked as read']);
        exit;
    }
    
    // Single notification mark as read
    $type = $_POST['type'] ?? '';
    $id = (int)($_POST['id'] ?? 0);
    
    if (!$type || !$id) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing required parameters']);
        exit;
    }
    
    // Insert or update notification record
    $stmt = $pdo->prepare("
        INSERT INTO notifications (user_id, type, reference_id, is_read, read_at)
        VALUES (?, ?, ?, 1, NOW())
        ON DUPLICATE KEY UPDATE is_read = 1, read_at = NOW()
    ");
    $stmt->execute([$userId, $type, $id]);
    
    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error', 'details' => $e->getMessage()]);
}