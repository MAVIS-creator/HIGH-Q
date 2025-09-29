<?php
// admin/api/mac_blocklist.php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/csrf.php';
require_once __DIR__ . '/../../includes/db.php';

requirePermission('settings');

header('Content-Type: application/json');

// GET -> list rows
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $stmt = $pdo->query('SELECT id, mac, reason, enabled, created_at FROM mac_blocklist ORDER BY id DESC LIMIT 200');
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['status'=>'ok','rows'=>$rows]);
    exit;
}

// POST actions (add, toggle, delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['_csrf'] ?? '';
    if (!verifyToken('settings_form', $token)) { echo json_encode(['status'=>'error','message'=>'Invalid CSRF']); exit; }
    $action = $_POST['action'] ?? '';
    try {
        if ($action === 'add') {
            $mac = trim($_POST['mac'] ?? '');
            $reason = trim($_POST['reason'] ?? '');
            if (!$mac) throw new Exception('MAC required');
            $ins = $pdo->prepare('INSERT INTO mac_blocklist (mac, reason, enabled) VALUES (?, ?, 1)');
            $ins->execute([$mac, $reason]);
            echo json_encode(['status'=>'ok','message'=>'Added']); exit;
        }
        if ($action === 'toggle') {
            $id = intval($_POST['id'] ?? 0);
            $stmt = $pdo->prepare('SELECT enabled FROM mac_blocklist WHERE id = ?'); $stmt->execute([$id]); $cur = $stmt->fetchColumn();
            $new = $cur ? 0 : 1;
            $upd = $pdo->prepare('UPDATE mac_blocklist SET enabled = ? WHERE id = ?'); $upd->execute([$new, $id]);
            echo json_encode(['status'=>'ok','message'=>'Toggled']); exit;
        }
        if ($action === 'delete') {
            $id = intval($_POST['id'] ?? 0);
            $del = $pdo->prepare('DELETE FROM mac_blocklist WHERE id = ?'); $del->execute([$id]);
            echo json_encode(['status'=>'ok','message'=>'Deleted']); exit;
        }
    } catch (Exception $e) {
        echo json_encode(['status'=>'error','message'=>$e->getMessage()]); exit;
    }
}

echo json_encode(['status'=>'error','message'=>'Unsupported method']);
exit;
