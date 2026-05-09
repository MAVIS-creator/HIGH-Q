<?php
header('Content-Type: application/json; charset=utf-8');
// public API files live in public/api; the public DB config is at public/config/db.php
require_once __DIR__ . '/../config/db.php';

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$commentId = intval($_REQUEST['comment_id'] ?? 0);
if (!$commentId) { http_response_code(400); echo json_encode(['status'=>'error','message'=>'Missing comment_id']); exit; }

if (session_status() !== PHP_SESSION_ACTIVE) session_start();
$sessionId = session_id() ?: null;

try {
    if ($method === 'GET') {
        $q = $pdo->prepare('SELECT COUNT(1) FROM comment_likes WHERE comment_id = ?'); $q->execute([$commentId]); $likes = (int)$q->fetchColumn();
        $chk = $pdo->prepare('SELECT 1 FROM comment_likes WHERE comment_id = ? AND session_id = ? LIMIT 1'); $chk->execute([$commentId, $sessionId]);
        $liked = (bool)$chk->fetchColumn();
        echo json_encode(['status'=>'ok','likes'=>$likes,'liked'=>$liked]); exit;
    }
    if ($method === 'POST') {
        // Check if already liked
        $chk = $pdo->prepare('SELECT 1 FROM comment_likes WHERE comment_id = ? AND session_id = ? LIMIT 1');
        $chk->execute([$commentId, $sessionId]);
        $alreadyLiked = (bool)$chk->fetchColumn();
        
        if ($alreadyLiked) {
            // Unlike: delete the like
            $del = $pdo->prepare('DELETE FROM comment_likes WHERE comment_id = ? AND session_id = ?');
            $del->execute([$commentId, $sessionId]);
        } else {
            // Like: insert the like
            $ins = $pdo->prepare('INSERT IGNORE INTO comment_likes (comment_id, session_id, ip) VALUES (?, ?, ?)');
            $ins->execute([$commentId, $sessionId, null]);
        }
        
        // Get updated count
        $q = $pdo->prepare('SELECT COUNT(1) FROM comment_likes WHERE comment_id = ?');
        $q->execute([$commentId]);
        $likes = (int)$q->fetchColumn();
        
        // Check new like status
        $chk = $pdo->prepare('SELECT 1 FROM comment_likes WHERE comment_id = ? AND session_id = ? LIMIT 1');
        $chk->execute([$commentId, $sessionId]);
        $isLiked = (bool)$chk->fetchColumn();
        
        echo json_encode(['status'=>'ok','likes'=>$likes,'liked'=>$isLiked]); exit;
    }
    http_response_code(405); echo json_encode(['status'=>'error','message'=>'Method not allowed']);
} catch (Throwable $e) {
    http_response_code(500); echo json_encode(['status'=>'error','message'=>'DB error']);
}
