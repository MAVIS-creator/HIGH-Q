<?php
// admin/modules/trap.php - Canary Token System
if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['user'])) {
    http_response_code(401);
    die('Unauthorized');
}

require_once __DIR__ . '/../includes/db.php';

// Handle canary token operations
$action = $_POST['action'] ?? $_GET['action'] ?? '';
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'create_trap') {
        $trapType = $_POST['trap_type'] ?? 'fake_user';
        $trapName = $_POST['trap_name'] ?? 'admin_backup';
        $trapEmail = $_POST['trap_email'] ?? 'backup@system.local';
        
        try {
            // Create fake user trap
            if ($trapType === 'fake_user') {
                $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role_id, is_canary) 
                                      VALUES (?, ?, ?, 1, 1) ON DUPLICATE KEY UPDATE is_canary=1");
                $stmt->execute([$trapName, $trapEmail, password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT)]);
                $message = "Canary trap user created: $trapName";
            }
        } catch (Exception $e) {
            $error = "Failed to create trap: " . $e->getMessage();
        }
    } elseif ($action === 'check_traps') {
        // Check for trap access in audit logs
        try {
            $stmt = $pdo->query("SELECT * FROM audit_logs WHERE action LIKE '%canary%' ORDER BY created_at DESC LIMIT 10");
            $alerts = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $message = count($alerts) . " trap alert(s) found";
        } catch (Exception $e) {
            $error = "Failed to check traps: " . $e->getMessage();
        }
    }
}

// Get existing canary traps
$traps = [];
try {
    $stmt = $pdo->query("SELECT id, name, email, created_at FROM users WHERE is_canary = 1");
    $traps = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // Column might not exist yet
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css">
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; padding: 20px; background: #fafbff; }
        .trap-card { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); margin-bottom: 20px; }
        .trap-form { display: flex; flex-direction: column; gap: 12px; }
        .trap-form input, .trap-form select { padding: 10px; border: 1px solid #ddd; border-radius: 6px; }
        .trap-form button { padding: 12px; background: #ff4757; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; }
        .trap-form button:hover { background: #ff3838; }
        .trap-list { margin-top: 20px; }
        .trap-item { padding: 12px; background: #f8f9fa; border-left: 4px solid #ff4757; margin-bottom: 10px; border-radius: 4px; }
        .message { padding: 12px; background: #d4edda; border: 1px solid #c3e6cb; color: #155724; border-radius: 6px; margin-bottom: 15px; }
        .error { padding: 12px; background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; border-radius: 6px; margin-bottom: 15px; }
        .info-box { background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; border-radius: 6px; margin-bottom: 20px; }
    </style>
</head>
<body>
    <div class="info-box">
        <strong><i class='bx bx-target-lock'></i> Canary Tokens:</strong> Fake honeypot users/credentials that trigger alerts when accessed by attackers.
    </div>

    <?php if ($message): ?>
        <div class="message"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="trap-card">
        <h3>Create Canary Trap</h3>
        <form method="POST" class="trap-form">
            <input type="hidden" name="action" value="create_trap">
            <select name="trap_type">
                <option value="fake_user">Fake Admin User</option>
            </select>
            <input type="text" name="trap_name" placeholder="Trap Name (e.g., admin_backup)" required>
            <input type="email" name="trap_email" placeholder="Trap Email (e.g., backup@system.local)" required>
            <button type="submit"><i class='bx bx-error-circle'></i> Deploy Canary</button>
        </form>
    </div>

    <div class="trap-card">
        <h3>Active Canary Traps (<?= count($traps) ?>)</h3>
        <div class="trap-list">
            <?php if (empty($traps)): ?>
                <p style="color:#666;">No canary traps deployed yet.</p>
            <?php else: ?>
                <?php foreach ($traps as $trap): ?>
                    <div class="trap-item">
                        <strong><?= htmlspecialchars($trap['name']) ?></strong> - <?= htmlspecialchars($trap['email']) ?>
                        <br><small>Created: <?= htmlspecialchars($trap['created_at']) ?></small>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        <form method="POST" style="margin-top:15px;">
            <input type="hidden" name="action" value="check_traps">
            <button type="submit" style="background:#3742fa;"><i class='bx bx-search-alt'></i> Check for Alerts</button>
        </form>
    </div>
</body>
</html>
