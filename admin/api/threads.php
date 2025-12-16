<?php
// admin/api/threads.php - lightweight JSON API for admin thread list and unread counts
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

if (empty($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode(['error'=>'unauthenticated','threads'=>[]]); 
    exit;
}

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../../public/config/functions.php';

// Simple query: return recent open threads with minimal fields and unread counts
$limit = 50;
$threads = $pdo->prepare(
  'SELECT id, visitor_name, visitor_email, assigned_admin_id, status, last_activity
   FROM chat_threads
   WHERE status != "closed"
   ORDER BY last_activity DESC
   LIMIT ?'
);
$threads->bindValue(1, $limit, PDO::PARAM_INT); $threads->execute();
$rows = $threads->fetchAll(PDO::FETCH_ASSOC);

// Unread messages per thread: consider messages where is_from_staff=0 and created_at > last read (not implemented) -> approximate by counting visitor messages
$unread_total = 0;
$thread_list = [];
foreach ($rows as $r) {
    // count visitor messages in thread
    $c = $pdo->prepare('SELECT COUNT(*) FROM chat_messages WHERE thread_id = ? AND is_from_staff = 0');
    $c->execute([$r['id']]);
    $visitorCount = (int)$c->fetchColumn();
    // count staff messages
    $s = $pdo->prepare('SELECT COUNT(*) FROM chat_messages WHERE thread_id = ? AND is_from_staff = 1');
    $s->execute([$r['id']]);
    $staffCount = (int)$s->fetchColumn();
    // naive unread = visitorCount - staffCount (if positive)
    $unread = max(0, $visitorCount - $staffCount);
    $unread_total += $unread;
    $thread_list[] = [
        'id' => (int)$r['id'],
        'visitor_name' => $r['visitor_name'] ?: 'Guest',
        'assigned_admin_id' => $r['assigned_admin_id'] ? (int)$r['assigned_admin_id'] : null,
        'status' => $r['status'],
        'last_activity' => $r['last_activity'],
        'unread' => $unread
    ];
}

echo json_encode(['threads' => $thread_list, 'unread_total' => $unread_total]);
exit;
