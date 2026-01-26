<?php
require_once __DIR__ . '/public/config/db.php';

echo "Chat Threads:\n";
$stmt = $pdo->query("SELECT * FROM chat_threads ORDER BY id DESC LIMIT 5");
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    print_r($row);
}

echo "\n\nChat Messages:\n";
$stmt = $pdo->query("SELECT id, thread_id, sender_name, LEFT(message, 50) as msg_preview, is_from_staff, created_at FROM chat_messages ORDER BY id DESC LIMIT 10");
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    print_r($row);
}
