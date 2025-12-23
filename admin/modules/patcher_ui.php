<?php
// admin/modules/patcher_ui.php - Smart Code Patcher with Git Integration
if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['user'])) {
    http_response_code(401);
    die('Unauthorized');
}

require_once __DIR__ . '/../includes/db.php';

$message = '';
$error = '';
$action = $_POST['action'] ?? $_GET['action'] ?? '';
$selectedFile = $_GET['file'] ?? '';
$fileContent = '';
$files = [];

// Scan for editable files (excluding vendor, node_modules, etc.)
function scanEditableFiles($dir, $baseDir = null, &$files = []) {
    if ($baseDir === null) $baseDir = $dir;
    $excludeDirs = ['vendor', 'node_modules', '.git', 'storage', 'tmp'];
    $items = @scandir($dir);
    if (!$items) return;
    
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') continue;
        $path = $dir . '/' . $item;
        $relativePath = str_replace($baseDir . '/', '', $path);
        
        if (is_dir($path)) {
            $skip = false;
            foreach ($excludeDirs as $excl) {
                if (strpos($relativePath, $excl) === 0) {
                    $skip = true;
                    break;
                }
            }
            if (!$skip) scanEditableFiles($path, $baseDir, $files);
        } elseif (is_file($path) && preg_match('/\.(php|js|css|html)$/i', $item)) {
            $files[] = $relativePath;
        }
    }
}

$rootPath = dirname(__DIR__, 2);
scanEditableFiles($rootPath, $rootPath, $files);
sort($files);

if ($selectedFile && file_exists($rootPath . '/' . $selectedFile)) {
    $fileContent = file_get_contents($rootPath . '/' . $selectedFile);
}

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'save_file') {
        $file = $_POST['file'] ?? '';
        $content = $_POST['content'] ?? '';
        
        if ($file && file_exists($rootPath . '/' . $file)) {
            // Create backup
            $backupDir = $rootPath . '/storage/backups';
            if (!is_dir($backupDir)) @mkdir($backupDir, 0755, true);
            $backupFile = $backupDir . '/' . basename($file) . '.' . time() . '.bak';
            copy($rootPath . '/' . $file, $backupFile);
            
            // Save file
            if (file_put_contents($rootPath . '/' . $file, $content)) {
                $message = "File saved successfully! Backup: " . basename($backupFile);
            } else {
                $error = "Failed to save file";
            }
        }
    } elseif ($action === 'git_checkout') {
        $file = $_POST['git_file'] ?? '';
        $branch = $_POST['git_branch'] ?? 'main';
        
        if ($file) {
            // Execute git checkout
            $output = [];
            $returnCode = 0;
            exec("cd " . escapeshellarg($rootPath) . " && git checkout origin/$branch -- " . escapeshellarg($file) . " 2>&1", $output, $returnCode);
            
            if ($returnCode === 0) {
                $message = "Successfully pulled $file from origin/$branch";
            } else {
                $error = "Git checkout failed: " . implode("\n", $output);
            }
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; padding: 20px; background: #fafbff; margin: 0; }
        .patcher-container { display: flex; gap: 20px; height: calc(100vh - 40px); }
        .file-list { width: 300px; background: white; border-radius: 8px; padding: 15px; overflow-y: auto; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .editor-panel { flex: 1; display: flex; flex-direction: column; gap: 15px; }
        .editor-card { background: white; border-radius: 8px; padding: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .file-item { padding: 8px; cursor: pointer; border-radius: 4px; font-size: 13px; margin-bottom: 5px; }
        .file-item:hover { background: #f0f0f0; }
        .file-item.active { background: #5f27cd; color: white; }
        textarea { width: 100%; height: 400px; font-family: 'Courier New', monospace; font-size: 13px; padding: 12px; border: 1px solid #ddd; border-radius: 6px; }
        .btn { padding: 10px 20px; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; margin-right: 10px; }
        .btn-primary { background: #5f27cd; color: white; }
        .btn-secondary { background: #2ecc71; color: white; }
        .btn:hover { opacity: 0.9; }
        .message { padding: 12px; background: #d4edda; border: 1px solid #c3e6cb; color: #155724; border-radius: 6px; margin-bottom: 15px; }
        .error { padding: 12px; background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; border-radius: 6px; margin-bottom: 15px; }
        .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); }
        .modal-content { background: white; margin: 10% auto; padding: 30px; width: 500px; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.2); }
        .modal-content h3 { margin-top: 0; color: #2ecc71; }
        .modal-content input, .modal-content select { width: 100%; padding: 10px; margin: 10px 0; border: 1px solid #ddd; border-radius: 6px; }
        .close { float: right; font-size: 28px; font-weight: bold; cursor: pointer; color: #999; }
        .close:hover { color: #333; }
    </style>
</head>
<body>
    <div class="patcher-container">
        <div class="file-list">
            <h4 style="margin-top:0;">📁 Editable Files (<?= count($files) ?>)</h4>
            <input type="text" id="fileSearch" placeholder="Search files..." style="width:100%;padding:8px;margin-bottom:10px;border:1px solid #ddd;border-radius:4px;">
            <div id="fileListContainer">
                <?php foreach ($files as $file): ?>
                    <div class="file-item <?= $file === $selectedFile ? 'active' : '' ?>" onclick="window.location.href='?file=<?= urlencode($file) ?>'">
                        <?= htmlspecialchars($file) ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="editor-panel">
            <?php if ($message): ?>
                <div class="message"><?= htmlspecialchars($message) ?></div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <?php if ($selectedFile): ?>
                <div class="editor-card">
                    <h3 style="margin-top:0;">✏️ Editing: <?= htmlspecialchars($selectedFile) ?></h3>
                    <form method="POST">
                        <input type="hidden" name="action" value="save_file">
                        <input type="hidden" name="file" value="<?= htmlspecialchars($selectedFile) ?>">
                        <textarea name="content"><?= htmlspecialchars($fileContent) ?></textarea>
                        <div style="margin-top:15px;">
                            <button type="submit" class="btn btn-primary">💾 Save Changes</button>
                            <button type="button" class="btn btn-secondary" onclick="openGitModal()">🔄 Pull from GitHub</button>
                        </div>
                    </form>
                </div>
            <?php else: ?>
                <div class="editor-card">
                    <p style="color:#666;text-align:center;padding:50px;">← Select a file from the list to edit</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Git Checkout Modal -->
    <div id="gitModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeGitModal()">&times;</span>
            <h3>🔄 Pull File from GitHub</h3>
            <p>This will replace your local copy with the version from GitHub.</p>
            <form method="POST">
                <input type="hidden" name="action" value="git_checkout">
                <input type="hidden" name="git_file" id="gitFileName" value="<?= htmlspecialchars($selectedFile) ?>">
                <label>Branch:</label>
                <select name="git_branch">
                    <option value="main">main</option>
                    <option value="master">master</option>
                    <option value="develop">develop</option>
                </select>
                <label>File:</label>
                <input type="text" value="<?= htmlspecialchars($selectedFile) ?>" readonly style="background:#f5f5f5;">
                <button type="submit" class="btn btn-secondary" style="width:100%;margin-top:15px;">Pull File</button>
            </form>
        </div>
    </div>

    <script>
        // File search
        document.getElementById('fileSearch').addEventListener('input', function(e) {
            const search = e.target.value.toLowerCase();
            const items = document.querySelectorAll('.file-item');
            items.forEach(item => {
                const text = item.textContent.toLowerCase();
                item.style.display = text.includes(search) ? 'block' : 'none';
            });
        });

        function openGitModal() {
            document.getElementById('gitModal').style.display = 'block';
        }

        function closeGitModal() {
            document.getElementById('gitModal').style.display = 'none';
        }

        window.onclick = function(event) {
            const modal = document.getElementById('gitModal');
            if (event.target == modal) {
                closeGitModal();
            }
        }
    </script>
</body>
</html>
