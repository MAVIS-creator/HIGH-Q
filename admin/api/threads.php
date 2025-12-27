<?php
// admin/api/threads.php - lightweight JSON API for admin thread list and unread counts
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

if (empty($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode(['error'=>'unauthenticated','threads'=>[]]); 
    exit;
}

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../../public/config/functions.php';

// Pagination
$perPage = (int)($_GET['per_page'] ?? 12);
if ($perPage < 1) $perPage = 12;
if ($perPage > 100) $perPage = 100;
$page = max(1, (int)($_GET['page'] ?? 1));
$offset = ($page - 1) * $perPage;

$total = 0;
try {
    $total = (int)$pdo->query('SELECT COUNT(*) FROM chat_threads WHERE status != "closed"')->fetchColumn();
} catch (Throwable $e) {
    $total = 0;
}
$totalPages = max(1, (int)ceil($total / $perPage));
if ($page > $totalPages) {
    $page = $totalPages;
    $offset = ($page - 1) * $perPage;
}

// Query: threads + assigned admin name + aggregated message counts (avoid N+1)
$sql =
  'SELECT t.id, t.visitor_name, t.visitor_email, t.assigned_admin_id, t.status, t.last_activity,
          u.name AS assigned_admin_name,
          COALESCE(SUM(CASE WHEN m.is_from_staff = 0 THEN 1 ELSE 0 END), 0) AS visitor_count,
          COALESCE(SUM(CASE WHEN m.is_from_staff = 1 THEN 1 ELSE 0 END), 0) AS staff_count
   FROM chat_threads t
   LEFT JOIN users u ON u.id = t.assigned_admin_id
   LEFT JOIN chat_messages m ON m.thread_id = t.id
   WHERE t.status != "closed"
   GROUP BY t.id
   ORDER BY t.last_activity DESC
   LIMIT :limit OFFSET :offset';

$threads = $pdo->prepare($sql);
$threads->bindValue(':limit', $perPage, PDO::PARAM_INT);
$threads->bindValue(':offset', $offset, PDO::PARAM_INT);
$threads->execute();
$rows = $threads->fetchAll(PDO::FETCH_ASSOC);

$unread_total = 0;
$thread_list = [];
foreach ($rows as $r) {
    $visitorCount = (int)($r['visitor_count'] ?? 0);
    $staffCount = (int)($r['staff_count'] ?? 0);
    $unread = max(0, $visitorCount - $staffCount);
    $unread_total += $unread;
    $thread_list[] = [
        'id' => (int)$r['id'],
        'visitor_name' => ($r['visitor_name'] ?: 'Guest'),
        'assigned_admin_id' => !empty($r['assigned_admin_id']) ? (int)$r['assigned_admin_id'] : null,
        'assigned_admin_name' => $r['assigned_admin_name'] ?: null,
        'status' => $r['status'],
        'last_activity' => $r['last_activity'],
        'unread' => $unread
    ];
}

echo json_encode([
    'threads' => $thread_list,
    'unread_total' => $unread_total,
    'page' => $page,
    'per_page' => $perPage,
    'total' => $total,
    'total_pages' => $totalPages
]);
exit;
