<?php
// admin/api/notifications.php - Aggregates recent admin notifications
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../../public/config/functions.php';

header('Content-Type: application/json');

if (empty($_SESSION['user'])) {
    echo json_encode(['error'=>'unauthenticated']); exit;
}

$notifications = [];

// 1) Pending comments awaiting moderation
$cstmt = $pdo->prepare("SELECT id, post_id, name, email, content, created_at FROM comments WHERE is_approved = 0 ORDER BY created_at DESC LIMIT 5");
$cstmt->execute();
while ($row = $cstmt->fetch(PDO::FETCH_ASSOC)) {
    $notifications[] = [
        'type' => 'comment',
        'id' => (int)$row['id'],
        'title' => 'Pending comment',
        'message' => substr(strip_tags($row['content']),0,120),
        'meta' => ['post_id'=>(int)$row['post_id'],'author'=>$row['name']],
        'created_at' => $row['created_at']
    ];
}

// 2) Student applications (pending users)
$sstmt = $pdo->prepare("SELECT id, name, email, created_at FROM users WHERE (role_id IS NULL OR role_id=(SELECT id FROM roles WHERE slug='student' LIMIT 1)) AND is_active = 0 ORDER BY created_at DESC LIMIT 5");
$sstmt->execute();
while ($row = $sstmt->fetch(PDO::FETCH_ASSOC)) {
    $notifications[] = [
        'type' => 'student_application',
        'id' => (int)$row['id'],
        'title' => 'New student application',
        'message' => $row['email'],
        'meta' => ['name'=>$row['name']],
        'created_at' => $row['created_at']
    ];
}

// 3) Recent payments not reconciled (status pending or receipt uploaded)
$pstmt = $pdo->prepare("SELECT id, user_id, method, amount, status, created_at FROM payments WHERE status IN ('pending','uploaded') ORDER BY created_at DESC LIMIT 5");
$pstmt->execute();
while ($row = $pstmt->fetch(PDO::FETCH_ASSOC)) {
    $notifications[] = [
        'type' => 'payment',
        'id' => (int)$row['id'],
        'title' => 'Payment update',
        'message' => strtoupper($row['method']) . ' - ' . number_format((float)$row['amount'],2),
        'meta' => ['status'=>$row['status'],'user_id'=>(int)$row['user_id']],
        'created_at' => $row['created_at']
    ];
}

// 4) Chat unread threads (reuse naive unread calc from threads.php)
$tstmt = $pdo->prepare('SELECT id, visitor_name, last_activity FROM chat_threads ORDER BY last_activity DESC LIMIT 10');
$tstmt->execute();
while ($r = $tstmt->fetch(PDO::FETCH_ASSOC)) {
    $c = $pdo->prepare('SELECT COUNT(*) FROM chat_messages WHERE thread_id = ? AND is_from_staff = 0'); $c->execute([$r['id']]); $visitorCount = (int)$c->fetchColumn();
    $s = $pdo->prepare('SELECT COUNT(*) FROM chat_messages WHERE thread_id = ? AND is_from_staff = 1'); $s->execute([$r['id']]); $staffCount = (int)$s->fetchColumn();
    $unread = max(0, $visitorCount - $staffCount);
    if ($unread > 0) {
        $notifications[] = [
            'type' => 'chat',
            'id' => (int)$r['id'],
            'title' => 'Chat: ' . ($r['visitor_name'] ?: 'Guest'),
            'message' => "{$unread} new message(s)",
            'meta' => ['thread_id'=>(int)$r['id']],
            'created_at' => $r['last_activity']
        ];
    }
}

// sort notifications by created_at desc (string compare OK for timestamp)
usort($notifications, function($a,$b){ return strcmp($b['created_at'],$a['created_at']); });

// Trim to top 10
$notifications = array_slice($notifications,0,10);

echo json_encode(['notifications'=>$notifications, 'count'=>count($notifications)]);
exit;
