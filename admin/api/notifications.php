<?php
// admin/api/notifications.php - Aggregates recent admin notifications
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

if (empty($_SESSION['user'])) {
    echo json_encode(['error'=>'unauthenticated']); 
    exit;
}

$notifications = [];
$debug = []; // collect debug info

// 1) Pending comments
$cstmt = $pdo->prepare("SELECT id, post_id, content, status, created_at 
                        FROM comments 
                        WHERE status = 'pending' 
                        ORDER BY created_at DESC 
                        LIMIT 5");
$cstmt->execute();
while ($row = $cstmt->fetch(PDO::FETCH_ASSOC)) {
    $notifications[] = [
        'type' => 'comment',
        'id' => (int)$row['id'],
        'title' => 'New Comment',
        'message' => substr($row['content'], 0, 50) . (strlen($row['content']) > 50 ? '...' : ''),
        'meta' => [
            'post_id' => (int)$row['post_id'],
            'status' => $row['status']
        ],
        'created_at' => $row['created_at']
    ];
}

// 2) Student applications
$sstmt = $pdo->prepare("SELECT id, name, email, created_at FROM users WHERE (role_id IS NULL OR role_id=(SELECT id FROM roles WHERE slug='student' LIMIT 1)) AND is_active = 0 ORDER BY created_at DESC LIMIT 5");
$sstmt->execute();
$rows = $sstmt->fetchAll(PDO::FETCH_ASSOC);
$debug['students_found'] = count($rows);
foreach ($rows as $row) {
    $notifications[] = [
        'type' => 'student_application',
        'id' => (int)$row['id'],
        'title' => 'New student application',
        'message' => $row['email'],
        'meta' => ['name'=>$row['name']],
        'created_at' => $row['created_at']
    ];
}

// 3) Payments
$pstmt = $pdo->prepare("SELECT id, student_id, payment_method, amount, status, created_at 
                        FROM payments 
                        WHERE status IN ('pending','confirmed') 
                        ORDER BY created_at DESC 
                        LIMIT 5");
$pstmt->execute();
while ($row = $pstmt->fetch(PDO::FETCH_ASSOC)) {
    $notifications[] = [
        'type' => 'payment',
        'id' => (int)$row['id'],
        'title' => 'Payment update',
        'message' => strtoupper($row['payment_method']) . ' - ' . number_format((float)$row['amount'], 2),
        'meta' => [
            'status' => $row['status'],
            'student_id' => (int)$row['student_id']
        ],
        'created_at' => $row['created_at']
    ];
}

// 4) Chat unread
$tstmt = $pdo->prepare('SELECT id, visitor_name, last_activity FROM chat_threads ORDER BY last_activity DESC LIMIT 10');
$tstmt->execute();
$chatThreads = $tstmt->fetchAll(PDO::FETCH_ASSOC);
$debug['chat_threads_checked'] = count($chatThreads);
$chatUnread = 0;
foreach ($chatThreads as $r) {
    $c = $pdo->prepare('SELECT COUNT(*) FROM chat_messages WHERE thread_id = ? AND is_from_staff = 0'); 
    $c->execute([$r['id']]); 
    $visitorCount = (int)$c->fetchColumn();
    $s = $pdo->prepare('SELECT COUNT(*) FROM chat_messages WHERE thread_id = ? AND is_from_staff = 1'); 
    $s->execute([$r['id']]); 
    $staffCount = (int)$s->fetchColumn();
    $unread = max(0, $visitorCount - $staffCount);
    if ($unread > 0) {
        $chatUnread++;
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
$debug['chat_unread_found'] = $chatUnread;

// 5) New User Registrations
$ustmt = $pdo->prepare("SELECT id, name, email, created_at 
                        FROM users 
                        ORDER BY created_at DESC 
                        LIMIT 5");
$ustmt->execute();
while ($row = $ustmt->fetch(PDO::FETCH_ASSOC)) {
    $notifications[] = [
        'type' => 'user',
        'id' => (int)$row['id'],
        'title' => 'New User Registered',
        'message' => $row['name'] . ' (' . $row['email'] . ')',
        'meta' => [
            'email' => $row['email']
        ],
        'created_at' => $row['created_at']
    ];
}

// sort notifications by created_at desc
usort($notifications, function($a,$b){ return strcmp($b['created_at'],$a['created_at']); });

// Trim to top 10
$notifications = array_slice($notifications,0,10);

// Check read status for all notifications
foreach ($notifications as &$notification) {
    $readStmt = $pdo->prepare(
        "SELECT is_read FROM notifications WHERE user_id = ? AND type = ? AND reference_id = ?"
    );
    $readStmt->execute([$_SESSION['user']['id'], $notification['type'], $notification['id']]);
    $notification['is_read'] = (bool)$readStmt->fetchColumn();
}
unset($notification); // Break reference
// Wrap in try/catch so missing notifications table doesn't cause a fatal error on installs without that table.
try {
    foreach ($notifications as &$notification) {
        $readStmt = $pdo->prepare(
            "SELECT is_read FROM notifications WHERE user_id = ? AND type = ? AND reference_id = ?"
        );
        $readStmt->execute([$_SESSION['user']['id'], $notification['type'], $notification['id']]);
        $notification['is_read'] = (bool)$readStmt->fetchColumn();
    }
    unset($notification); // Break reference
} catch (Throwable $e) {
    // If the notifications table doesn't exist (or any other DB error), mark all as unread
    foreach ($notifications as &$notification) {
        $notification['is_read'] = false;
    }
    unset($notification);
    // Preserve debug info when ?debug=1 is used
    $debug['notifications_read_error'] = $e->getMessage();
}

// ✅ Normal mode
$response = [
    'notifications' => $notifications,
    'count' => count($notifications),
    // Use index.php routing so the admin chrome (header/sidebar) is rendered
    'urls' => [
        'comment' => 'pages/index.php?pages=comments&id=',
        'payment' => 'pages/index.php?pages=payments&id=',
        'student_application' => 'pages/index.php?pages=students&id=',
        'chat' => 'pages/index.php?pages=chat&thread=',
        'user' => 'pages/index.php?pages=users&id='
    ]
];

// ✅ Debug mode toggle via ?debug=1
if (!empty($_GET['debug'])) {
    $response['debug'] = $debug;
    $response['session'] = $_SESSION;
}

echo json_encode($response);
exit;
