<?php
// Quick menu sync endpoint - run this to ensure all menus are synced with correct icons
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

// Only allow admins
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['user']['role_name'] !== 'Admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$configMenus = require __DIR__ . '/../includes/menu.php';
require_once __DIR__ . '/../includes/menu_sync.php';

try {
    // Force sync
    sync_menus_from_config($pdo, $configMenus);
    
    // Also update any missing icons
    $stmt = $pdo->prepare("UPDATE menus SET icon = ? WHERE slug = ?");
    foreach ($configMenus as $slug => $item) {
        if (!empty($item['icon'])) {
            $stmt->execute([$item['icon'], $slug]);
        }
    }
    
    echo json_encode(['success' => true, 'message' => 'Menus synced successfully']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
