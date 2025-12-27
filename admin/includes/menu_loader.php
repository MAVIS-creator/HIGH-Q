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

// Ensure new modules are always present
// Also update students to academic
$required = [
    'academic' => ['title' => 'Academic Management', 'icon' => 'bx bxs-graduation', 'url' => 'index.php?pages=academic'],
    'sentinel' => ['title' => 'Security Scan', 'icon' => 'bx bxs-shield-alt', 'url' => 'index.php?pages=sentinel'],
    'patcher' => ['title' => 'Smart Patcher', 'icon' => 'bx bx-wrench', 'url' => 'index.php?pages=patcher', 'target' => '_blank'],
    'automator' => ['title' => 'Automator', 'icon' => 'bx bx-cog', 'url' => 'index.php?pages=automator'],
    'trap' => ['title' => 'Canary Trap', 'icon' => 'bx bx-bug', 'url' => 'index.php?pages=trap'],
];
foreach ($required as $slug => $item) {
    if (!isset($menus[$slug])) $menus[$slug] = $item;
}

// Ensure Smart Patcher always opens in a new tab with correct URL via router
if (isset($menus['patcher'])) {
    $menus['patcher']['url'] = 'index.php?pages=patcher';
    $menus['patcher']['target'] = '_blank';
}

return $menus;
