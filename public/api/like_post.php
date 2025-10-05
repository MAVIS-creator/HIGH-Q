<?php
// public/api/like_post.php
header('Content-Type: application/json; charset=utf-8');
// public API files live in public/api; the public DB config is at public/config/db.php
require_once __DIR__ . '/../config/db.php';

// Lightweight like handling with a post_likes table to avoid duplicate likes per session/IP.
// GET ?post_id=... returns current likes and whether this session/ip has liked.
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$postId = intval($_REQUEST['post_id'] ?? 0);
if (!$postId) {
	http_response_code(400);
	echo json_encode(['status' => 'error', 'message' => 'Missing post_id']);
	exit;
}

// determine session id and ip for lightweight guarding
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
$sessionId = session_id() ?: null;
$ip = $_SERVER['REMOTE_ADDR'] ?? null;

// Helper: get likes count
function get_likes($pdo, $postId) {
	// Prefer posts.likes if present, otherwise count post_likes table
	try {
		$q = $pdo->prepare('SELECT COUNT(1) FROM post_likes WHERE post_id = ?');
		$q->execute([$postId]);
		return (int)$q->fetchColumn();
	} catch (Throwable $e) {
		// As a final fallback, try to read posts.likes if it exists
		try { $q = $pdo->prepare('SELECT COALESCE(likes,0) FROM posts WHERE id = ?'); $q->execute([$postId]); return (int)$q->fetchColumn(); } catch (Throwable $e2) { return 0; }
	}
}

try {
	if ($method === 'GET') {
		// return likes and whether this visitor already liked
		$likes = get_likes($pdo, $postId);
		$chk = $pdo->prepare('SELECT 1 FROM post_likes WHERE post_id = ? AND (session_id = ? OR ip = ?) LIMIT 1');
		$chk->execute([$postId, $sessionId, $ip]);
		$liked = (bool)$chk->fetchColumn();
		echo json_encode(['status' => 'ok', 'likes' => $likes, 'liked' => $liked]);
		exit;
	}

	if ($method === 'POST') {
		// attempt to insert into post_likes; rely on unique index to prevent duplicates
		$pdo->beginTransaction();
		try {
			$ins = $pdo->prepare('INSERT IGNORE INTO post_likes (post_id, session_id, ip) VALUES (?, ?, ?)');
			$ins->execute([$postId, $sessionId, $ip]);
			$affected = $ins->rowCount();
			if ($affected) {
				// If posts.likes column exists, increment it; otherwise rely on post_likes count
				try {
					$up = $pdo->prepare('UPDATE posts SET likes = COALESCE(likes,0) + 1 WHERE id = ?');
					$up->execute([$postId]);
				} catch (Throwable $e) {
					// ignore - some installs don't have posts.likes
				}
			}
			$pdo->commit();
		} catch (Throwable $e) {
			$pdo->rollBack();
			throw $e;
		}

		$likes = get_likes($pdo, $postId);
		echo json_encode(['status' => 'ok', 'likes' => $likes, 'liked' => (bool)$affected]);
		exit;
	}

	http_response_code(405);
	echo json_encode(['status'=>'error','message'=>'Method not allowed']);
} catch (Throwable $e) {
	// Log the exception to a file for local debugging (temporary)
	try {
		$logPath = __DIR__ . '/../../storage/like_post_error.log';
		$msg = date('c') . " | " . ($e->getMessage() ?? 'unknown') . "\n" . ($e->getTraceAsString() ?? '') . "\n---\n";
		@file_put_contents($logPath, $msg, FILE_APPEND | LOCK_EX);
	} catch (Throwable $_) { /* ignore logging failures */ }
	http_response_code(500);
	echo json_encode(['status' => 'error', 'message' => 'DB error']);
}


