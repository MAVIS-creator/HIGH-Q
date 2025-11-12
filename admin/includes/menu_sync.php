<?php
// admin/includes/menu_sync.php
// Synchronize menu items from config into the database and grant Admin role permissions

function menus_table_exists(PDO $pdo): bool {
    try {
        $stmt = $pdo->query("SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'menus' LIMIT 1");
        return (bool)$stmt->fetchColumn();
    } catch (Throwable $e) {
        return false;
    }
}

function ensure_menus_table(PDO $pdo): void {
    if (menus_table_exists($pdo)) return;
    $sql = "CREATE TABLE IF NOT EXISTS `menus` (
      `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
      `slug` VARCHAR(80) NOT NULL,
      `title` VARCHAR(150) NOT NULL,
      `icon` VARCHAR(80) DEFAULT NULL,
      `url` VARCHAR(255) NOT NULL,
      `sort_order` INT NOT NULL DEFAULT 100,
      `enabled` TINYINT(1) NOT NULL DEFAULT 1,
      `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
      `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`),
      UNIQUE KEY `uniq_menus_slug` (`slug`),
      KEY `idx_menus_enabled_sort` (`enabled`, `sort_order`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    try { $pdo->exec($sql); } catch (Throwable $e) { /* ignore, page can fallback to config */ }
}

function sync_menus_from_config(PDO $pdo, array $configMenus): void {
    ensure_menus_table($pdo);
    if (!menus_table_exists($pdo)) return; // fallback will use config

    // Upsert all config menus
    $up = $pdo->prepare("INSERT INTO menus (slug, title, icon, url, sort_order, enabled)
        VALUES (?, ?, ?, ?, ?, 1)
        ON DUPLICATE KEY UPDATE title=VALUES(title), icon=VALUES(icon), url=VALUES(url), sort_order=VALUES(sort_order), enabled=1");

    $sort = 10;
    foreach ($configMenus as $slug => $item) {
        $title = $item['title'] ?? ucfirst($slug);
        $icon  = $item['icon'] ?? null;
        $url   = $item['url'] ?? 'index.php?pages=' . $slug;
        $up->execute([$slug, $title, $icon, $url, $sort]);
        $sort += 10;
    }

    // Soft-disable menus that are not present in config anymore
    try {
        $slugs = array_keys($configMenus);
        if (!empty($slugs)) {
            $placeholders = implode(',', array_fill(0, count($slugs), '?'));
            $stmt = $pdo->prepare("UPDATE menus SET enabled=0 WHERE slug NOT IN ($placeholders)");
            $stmt->execute($slugs);
        }
    } catch (Throwable $e) { /* best-effort */ }

    // Auto-grant permissions for Admin role (slug 'admin' or name 'admin')
    try {
        $roleStmt = $pdo->query("SELECT id FROM roles WHERE slug='admin' OR LOWER(name)='admin' LIMIT 1");
        $adminRoleId = $roleStmt ? $roleStmt->fetchColumn() : null;
        if ($adminRoleId) {
            $check = $pdo->prepare("SELECT 1 FROM role_permissions WHERE role_id=? AND menu_slug=? LIMIT 1");
            $ins = $pdo->prepare("INSERT INTO role_permissions (role_id, menu_slug) VALUES (?, ?)");
            foreach (array_keys($configMenus) as $slug) {
                $check->execute([$adminRoleId, $slug]);
                if (!$check->fetch()) {
                    $ins->execute([$adminRoleId, $slug]);
                }
            }
        }
    } catch (Throwable $e) {
        error_log('menu sync admin grant failed: ' . $e->getMessage());
    }
}
