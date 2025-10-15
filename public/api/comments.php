<?php
// public/api/comments.php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/csrf.php';

// Support GET for fetching comments (used by the public post page) and POST for submitting
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $postId = intval($_GET['post_id'] ?? 0);
    if (!$postId) { echo json_encode([]); exit; }
    try {
    // fetch top-level approved comments and any pending comments created in this session
    if (session_status() !== PHP_SESSION_ACTIVE) session_start();
    $stmt = $pdo->prepare('SELECT id, name, email, content, created_at, status, session_id, user_id FROM comments WHERE post_id = ? AND parent_id IS NULL AND (status = "approved" OR (status = "pending" AND session_id = ?)) ORDER BY created_at DESC');
    $stmt->execute([$postId, session_id()]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $out = [];
        foreach ($rows as $r) {
            $r['replies'] = [];
            // fetch replies (approved or pending if created in this session)
            $rstmt = $pdo->prepare('SELECT id, name, email, content, created_at, status, session_id, user_id FROM comments WHERE parent_id = ? AND (status = "approved" OR (status = "pending" AND session_id = ?)) ORDER BY created_at ASC');
            $rstmt->execute([$r['id'], session_id()]);
            $replies = $rstmt->fetchAll(PDO::FETCH_ASSOC);
            // add can_delete flag for replies as well
            foreach ($replies as &$rep) {
                $rep['can_delete'] = false;
                try { if (!empty($rep['session_id']) && $rep['session_id'] === session_id()) $rep['can_delete'] = true; if (!$rep['can_delete'] && isset($_SESSION['user']) && !empty($rep['user_id']) && $_SESSION['user']['id'] == $rep['user_id']) $rep['can_delete'] = true; } catch (Throwable $e) { $rep['can_delete'] = false; }
            }
            $r['replies'] = $replies ?: [];
            // comment likes count
            try {
                $lc = $pdo->prepare('SELECT COUNT(1) FROM comment_likes WHERE comment_id = ?'); $lc->execute([$r['id']]); $r['likes'] = (int)$lc->fetchColumn();
                $chk = $pdo->prepare('SELECT 1 FROM comment_likes WHERE comment_id = ? AND (session_id = ? OR ip = ?) LIMIT 1'); $chk->execute([$r['id'], session_id(), $_SERVER['REMOTE_ADDR'] ?? null]); $r['liked'] = (bool)$chk->fetchColumn();
            } catch (Throwable $e) { $r['likes'] = 0; $r['liked'] = false; }
            // whether this visitor may delete this comment: either it was created in this session
            // (guest) or it belongs to the logged-in user (user_id matches session user)
            $r['can_delete'] = false;
            try {
                // if created in this session, allow deletion
                if (!empty($r['session_id']) && $r['session_id'] === session_id()) $r['can_delete'] = true;
                // if user is logged in and owns the comment
                if (!$r['can_delete'] && isset($_SESSION['user']) && !empty($r['user_id']) && $_SESSION['user']['id'] == $r['user_id']) $r['can_delete'] = true;
            } catch (Throwable $e) { $r['can_delete'] = false; }
            $out[] = $r;
        }
        echo json_encode($out);
        exit;
    } catch (Throwable $e) {
        echo json_encode([]);
        exit;
    }
}

$postId = intval($_POST['post_id'] ?? 0);
$parentId = !empty($_POST['parent_id']) ? intval($_POST['parent_id']) : null;
$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$content = trim($_POST['content'] ?? '');

if (!$postId || $content === '') {
    echo json_encode(['status'=>'error','message'=>'Missing required fields']);
    exit;
}

try {
    // Determine comment moderation setting (default to true if unknown)
    $commentModeration = true;
    try {
        $stmt = $pdo->query("SELECT * FROM site_settings ORDER BY id ASC LIMIT 1");
        $ss = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($ss && isset($ss['comment_moderation'])) {
            $commentModeration = (bool)$ss['comment_moderation'];
        } else {
            // fallback to legacy settings
            $stmt2 = $pdo->prepare("SELECT value FROM settings WHERE `key` = ? LIMIT 1");
            $stmt2->execute(['system_settings']);
            $val = $stmt2->fetchColumn();
            $j = $val ? json_decode($val, true) : [];
            if (isset($j['content']['comment_moderation'])) $commentModeration = (bool)$j['content']['comment_moderation'];
        }
    } catch (Throwable $e) {
        // ignore and default to moderation on
    }

    $status = $commentModeration ? 'pending' : 'approved';

    if (session_status() !== PHP_SESSION_ACTIVE) session_start();
    $stmt = $pdo->prepare("INSERT INTO comments (post_id, parent_id, user_id, name, email, content, status, session_id, created_at) VALUES (?, ?, NULL, ?, ?, ?, ?, ?, NOW())");
    $stmt->execute([$postId, $parentId, $name ?: null, $email ?: null, $content, $status, session_id()]);
    // debug log insertion
    try { @file_put_contents(__DIR__ . '/../storage/comments-debug.log', date('c') . " INSERT post={$postId} parent={$parentId} status={$status}\n", FILE_APPEND | LOCK_EX); } catch (Throwable $e) {}
    if ($status === 'pending') {
        // Provide a friendly info message instead of sounding like an error
        echo json_encode(['status'=>'ok','message'=>'Thanks — your comment was received and is awaiting moderation']);
    } else {
        echo json_encode(['status'=>'ok','message'=>'Comment submitted']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status'=>'error','message'=>'DB error']);
}

// Support DELETE via POST when a guest/user wants to delete their own comment
// Expect: method POST with _method=delete and comment_id
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['_method']) && strtolower($_POST['_method']) === 'delete') {
    $cid = intval($_POST['comment_id'] ?? 0);
    if ($cid <= 0) { echo json_encode(['status'=>'error','message'=>'Missing comment id']); exit; }
    try {
        if (session_status() !== PHP_SESSION_ACTIVE) session_start();
        // fetch comment
        $cstmt = $pdo->prepare('SELECT id, session_id, user_id FROM comments WHERE id = ? LIMIT 1');
        $cstmt->execute([$cid]);
        $c = $cstmt->fetch(PDO::FETCH_ASSOC);
        if (!$c) { echo json_encode(['status'=>'error','message'=>'Not found']); exit; }
        $allowed = false;
        // owner by session
        if (!empty($c['session_id']) && $c['session_id'] === session_id()) $allowed = true;
        // owner by logged-in user
        if (!$allowed && isset($_SESSION['user']) && !empty($c['user_id']) && $_SESSION['user']['id'] == $c['user_id']) $allowed = true;
        if (!$allowed) { echo json_encode(['status'=>'error','message'=>'Permission denied']); exit; }
        // soft-delete: mark as deleted
        $upd = $pdo->prepare('UPDATE comments SET status = "deleted" WHERE id = ?');
        $ok = $upd->execute([$cid]);
        if ($ok) {
            echo json_encode(['status'=>'ok']);
        } else {
            echo json_encode(['status'=>'error','message'=>'DB failed']);
        }
    } catch (Throwable $e) { echo json_encode(['status'=>'error','message'=>'Exception']); }
    exit;
}
