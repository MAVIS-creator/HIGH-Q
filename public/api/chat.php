<?php
// public/api/chat.php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/db.php';

// Simple actions: send_message, get_threads, get_messages
$action = $_GET['action'] ?? $_POST['action'] ?? 'send_message';

if ($action === 'send_message') {
    // public visitor sends a message into a thread (creates thread if not exists)
    $thread_id = !empty($_POST['thread_id']) ? intval($_POST['thread_id']) : null;
    $visitor_name = trim($_POST['name'] ?? 'Guest');
    $visitor_email = trim($_POST['email'] ?? '');
    $message = trim($_POST['message'] ?? '');
    if ($message === '') { echo json_encode(['status'=>'error','message'=>'Empty message']); exit; }

    try {
        if (!$thread_id) {
            $ins = $pdo->prepare('INSERT INTO chat_threads (visitor_name, visitor_email, created_at) VALUES (?, ?, NOW())');
            $ins->execute([$visitor_name, $visitor_email]);
            $thread_id = (int)$pdo->lastInsertId();
        }
    $ins2 = $pdo->prepare('INSERT INTO chat_messages (thread_id, sender_id, sender_name, message, is_from_staff, created_at) VALUES (?, NULL, ?, ?, 0, NOW())');
    $ins2->execute([$thread_id, $visitor_name, $message]);
    // Update thread last_activity so admin list shows latest activity
    $upd = $pdo->prepare('UPDATE chat_threads SET last_activity = NOW() WHERE id = ?');
    $upd->execute([$thread_id]);
    echo json_encode(['status'=>'ok','thread_id'=>$thread_id]);
    } catch (Exception $e) { http_response_code(500); echo json_encode(['status'=>'error','message'=>'DB error']); }
    exit;
}

if ($action === 'get_threads') {
    // return recent threads
    $stmt = $pdo->query('SELECT * FROM chat_threads ORDER BY last_activity DESC LIMIT 50');
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['status'=>'ok','threads'=>$rows]);
    exit;
}

if ($action === 'get_messages') {
    $thread_id = intval($_GET['thread_id'] ?? 0);
    if (!$thread_id) { echo json_encode(['status'=>'error','message'=>'Missing thread']); exit; }
    $stmt = $pdo->prepare('SELECT * FROM chat_messages WHERE thread_id = ? ORDER BY created_at ASC');
    $stmt->execute([$thread_id]);
    $msgs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['status'=>'ok','messages'=>$msgs]);
    exit;
}

http_response_code(400);
echo json_encode(['status'=>'error','message'=>'Unknown action']);
