<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
requirePermission('chat');

$id = intval($_POST['id'] ?? 0);
$token = $_POST['_csrf'] ?? '';
if (!verifyToken('chat_form', $token)) { echo json_encode(['status'=>'error','message'=>'Invalid CSRF']); exit; }
if (!$id) { echo json_encode(['status'=>'error','message'=>'Missing id']); exit; }

try {
    $stmt = $pdo->prepare('SELECT file_url FROM chat_attachments WHERE id = ? LIMIT 1');
    $stmt->execute([$id]);
    $file = $stmt->fetchColumn();
    if (!$file) { echo json_encode(['status'=>'error','message'=>'Not found']); exit; }
    $path = __DIR__ . '/../../public/' . $file;
    // Remove DB row first
    $del = $pdo->prepare('DELETE FROM chat_attachments WHERE id = ?');
    $del->execute([$id]);
    // Try to unlink file
    $fs = realpath($path);
    $base = realpath(__DIR__ . '/../../public/uploads/chat/');
    if ($fs && strpos($fs, $base) === 0 && is_file($fs)) @unlink($fs);
    notifyAdminChange($pdo, 'Chat Attachment Deleted', ['Attachment ID' => $id, 'File' => $file], (int)($_SESSION['user']['id'] ?? 0));
    echo json_encode(['status'=>'ok']);
} catch (Throwable $e) {
    echo json_encode(['status'=>'error','message'=>$e->getMessage()]);
}
