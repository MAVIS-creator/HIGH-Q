<?php
// admin/api/mark_read.php
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../bootstrap.php';
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

try {
    $stmt->execute([$_SESSION['user']['id'], $type, $id]);
    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error']);
}