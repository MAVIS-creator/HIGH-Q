<?php
// public/api/like_post.php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../config/db.php';

// Simple like toggling / increment. For public site we will increment a counter in posts.likes
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
	http_response_code(405);
	echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
	exit;
}

$postId = intval($_POST['post_id'] ?? 0);
if (!$postId) {
	http_response_code(400);
	echo json_encode(['status' => 'error', 'message' => 'Missing post_id']);
	exit;
}

try {
	// Attempt to increment the posts.likes column; if column missing, fallback to a likes table could be implemented later
	$stmt = $pdo->prepare('UPDATE posts SET likes = COALESCE(likes, 0) + 1 WHERE id = ?');
	$stmt->execute([$postId]);

	// return the new likes count
	$q = $pdo->prepare('SELECT COALESCE(likes,0) AS likes FROM posts WHERE id = ?');
	$q->execute([$postId]);
	$likes = $q->fetchColumn();

	echo json_encode(['status' => 'ok', 'likes' => (int)$likes]);
} catch (Throwable $e) {
	http_response_code(500);
	echo json_encode(['status' => 'error', 'message' => 'DB error']);
}

