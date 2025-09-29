<?php
// admin/api/ip_logs.php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/db.php';

requirePermission('settings');
header('Content-Type: application/json');

// Basic filtering by ip or user_id, limit to 500 rows
$where = [];
$params = [];
if (!empty($_GET['ip'])) { $where[] = 'ip LIKE ?'; $params[] = '%' . $_GET['ip'] . '%'; }
if (!empty($_GET['user_id'])) { $where[] = 'user_id = ?'; $params[] = intval($_GET['user_id']); }

$sql = 'SELECT id, ip, user_agent, path, referer, user_id, created_at FROM ip_logs';
if ($where) $sql .= ' WHERE ' . implode(' AND ', $where);
$sql .= ' ORDER BY id DESC LIMIT 500';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode(['status'=>'ok','rows'=>$rows]);
exit;
