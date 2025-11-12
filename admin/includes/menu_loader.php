<?php
// admin/includes/menu_loader.php
// Returns array of menus [slug => ['title'=>..., 'icon'=>..., 'url'=>...]]

require_once __DIR__ . '/db.php';
$configMenus = require __DIR__ . '/menu.php';
require_once __DIR__ . '/menu_sync.php';

// Best effort sync from config into DB so menus and role permissions stay up-to-date
try { sync_menus_from_config($pdo, $configMenus); } catch (Throwable $e) { /* ignore */ }

// Try loading from DB
$menus = [];
try {
    $stmt = $pdo->query("SELECT slug, title, icon, url FROM menus WHERE enabled = 1 ORDER BY sort_order ASC, title ASC");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $slug = $row['slug'];
        $menus[$slug] = [
            'title' => $row['title'],
            'icon'  => $row['icon'],
            'url'   => $row['url'],
        ];
    }
} catch (Throwable $e) {
    // Fallback to config
    $menus = $configMenus;
}

return $menus;
