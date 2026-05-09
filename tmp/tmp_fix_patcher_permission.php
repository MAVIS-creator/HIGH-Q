<?php
// Fix script to add patcher permission to admin roles
require_once __DIR__ . '/admin/includes/db.php';
require_once __DIR__ . '/admin/includes/auth.php';

try {
    $pdo->beginTransaction();
    
    // Get all admin roles (typically role_id 1 is admin)
    $stmt = $pdo->query("SELECT id FROM roles WHERE name LIKE '%admin%' OR id = 1");
    $adminRoles = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (empty($adminRoles)) {
        echo "No admin roles found. Checking if patcher permission exists first...<br>";
    }
    
    // Add patcher permission to each admin role if not already present
    $addedCount = 0;
    foreach ($adminRoles as $roleId) {
        // Check if patcher permission already exists for this role
        $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM role_permissions WHERE role_id = ? AND menu_slug = 'patcher'");
        $checkStmt->execute([$roleId]);
        $exists = $checkStmt->fetchColumn() > 0;
        
        if (!$exists) {
            $insertStmt = $pdo->prepare("INSERT INTO role_permissions (role_id, menu_slug) VALUES (?, 'patcher')");
            $insertStmt->execute([$roleId]);
            $addedCount++;
            echo "Added 'patcher' permission to role ID: $roleId<br>";
        } else {
            echo "Role ID $roleId already has 'patcher' permission<br>";
        }
    }
    
    $pdo->commit();
    echo "<br><strong>✓ Patcher permission fix complete!</strong><br>";
    echo "Added permissions: $addedCount<br><br>";
    echo "<a href='http://localhost/HIGH-Q/admin/pages/?pages=patcher'>Try accessing Patcher page now</a>";
    
} catch (Exception $e) {
    $pdo->rollBack();
    echo "Error: " . $e->getMessage();
}
?>
