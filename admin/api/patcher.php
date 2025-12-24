<?php
require_once __DIR__ . '/../../config/db.php';

// Check admin session
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// === SECURITY VALIDATION ===

// Blocked files that cannot be edited under any circumstances
$BLOCKED_FILES = [
    'db.php',
    '.env',
    '.htaccess',
    'config.php',
    '.git',
    '.gitignore',
    'vendor',
    'node_modules',
    'composer.lock',
    'package-lock.json'
];

// Allowed file extensions
$ALLOWED_EXTENSIONS = [
    'php', 'html', 'css', 'js', 'json', 'sql', 'txt',
    'md', 'yaml', 'yml', 'xml', 'ini', 'env'
];

// Project root for file operations
$PROJECT_ROOT = realpath(__DIR__ . '/../../');

function validatePath($path) {
    global $PROJECT_ROOT, $BLOCKED_FILES, $ALLOWED_EXTENSIONS;
    
    // Check for path traversal
    if (strpos($path, '..') !== false || strpos($path, '\\') !== false) {
        throw new Exception('Invalid path: path traversal detected');
    }
    
    // Get real path and check it's within project
    $realPath = realpath($PROJECT_ROOT . '/' . $path);
    if ($realPath === false || strpos($realPath, $PROJECT_ROOT) !== 0) {
        throw new Exception('Invalid path: outside project root');
    }
    
    // Check blocked files
    $basename = basename($path);
    if (in_array(strtolower($basename), array_map('strtolower', $BLOCKED_FILES))) {
        throw new Exception("File '{$basename}' is protected and cannot be edited");
    }
    
    // Check extension if file (not directory)
    if (is_file($realPath)) {
        $ext = strtolower(pathinfo($realPath, PATHINFO_EXTENSION));
        if (!in_array($ext, $ALLOWED_EXTENSIONS)) {
            throw new Exception("File type '.{$ext}' is not allowed");
        }
    }
    
    return $realPath;
}

function generateDiff($original, $modified) {
    $originalLines = explode("\n", $original);
    $modifiedLines = explode("\n", $modified);
    
    $diff = [];
    $maxLines = max(count($originalLines), count($modifiedLines));
    
    for ($i = 0; $i < $maxLines; $i++) {
        $origLine = $originalLines[$i] ?? '';
        $modLine = $modifiedLines[$i] ?? '';
        
        if ($origLine === $modLine) {
            $diff[] = [
                'type' => 'unchanged',
                'line' => $i + 1,
                'content' => htmlspecialchars($origLine)
            ];
        } else {
            if (isset($originalLines[$i])) {
                $diff[] = [
                    'type' => 'removed',
                    'line' => $i + 1,
                    'content' => htmlspecialchars($origLine)
                ];
            }
            if (isset($modifiedLines[$i])) {
                $diff[] = [
                    'type' => 'added',
                    'line' => $i + 1,
                    'content' => htmlspecialchars($modLine)
                ];
            }
        }
    }
    
    return $diff;
}

function logPatcherAction($action, $path, $status, $details = '') {
    $logDir = __DIR__ . '/../../logs';
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    
    $logFile = $logDir . '/patcher_audit.log';
    $timestamp = date('Y-m-d H:i:s');
    $user = $_SESSION['admin_name'] ?? 'Unknown User';
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'Unknown IP';
    
    $logEntry = sprintf(
        "[%s] Action: %s | Path: %s | Status: %s | User: %s | IP: %s | Details: %s\n",
        $timestamp,
        $action,
        $path,
        $status,
        $user,
        $ip,
        $details
    );
    
    file_put_contents($logFile, $logEntry, FILE_APPEND);
}


// === REQUEST HANDLING ===

$action = $_GET['action'] ?? '';
$response = ['error' => 'Unknown action'];

try {
    switch ($action) {
        case 'listFiles':
            $response = handleListFiles();
            break;
        case 'readFile':
            $path = $_GET['path'] ?? '';
            $response = handleReadFile($path);
            break;
        case 'previewDiff':
            $data = json_decode(file_get_contents('php://input'), true);
            $response = handlePreviewDiff($data);
            break;
        case 'applyFix':
            $data = json_decode(file_get_contents('php://input'), true);
            $response = handleApplyFix($data);
            break;
        case 'listBackups':
            $path = $_GET['path'] ?? '';
            $response = handleListBackups($path);
            break;
        case 'createFile':
            $data = json_decode(file_get_contents('php://input'), true);
            $response = handleCreateFile($data);
            break;
        case 'createFolder':
            $data = json_decode(file_get_contents('php://input'), true);
            $response = handleCreateFolder($data);
            break;
    }
} catch (Exception $e) {
    $response = ['error' => $e->getMessage()];
    logPatcherAction('error', $_GET['path'] ?? 'unknown', 'failed', $e->getMessage());
}

header('Content-Type: application/json');
echo json_encode($response);

// === HANDLER FUNCTIONS ===

function handleListFiles() {
    global $PROJECT_ROOT;
    
    $allowedDirs = [
        $PROJECT_ROOT . '/public',
        $PROJECT_ROOT . '/admin',
        $PROJECT_ROOT . '/config',
        $PROJECT_ROOT . '/migrations',
    ];

    $files = [];
    
    foreach ($allowedDirs as $dir) {
        if (!is_dir($dir)) continue;
        
        $iterator = new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS);
        $filter = new RecursiveCallbackFilterIterator($iterator, function($file, $key, $iterator) {
            if ($iterator->hasChildren()) return true;
            $name = $file->getFilename();
            $ext = pathinfo($name, PATHINFO_EXTENSION);
            $allowed = ['php', 'html', 'css', 'js', 'json', 'sql'];
            return in_array($ext, $allowed) && $name[0] !== '.';
        });

        foreach (new RecursiveIteratorIterator($filter) as $file) {
            if ($file->isFile()) {
                $fullPath = $file->getRealPath();
                $relPath = str_replace($PROJECT_ROOT . DIRECTORY_SEPARATOR, '', $fullPath);
                $relPath = str_replace('\\', '/', $relPath);
                
                $files[] = [
                    'name' => $file->getFilename(),
                    'path' => $relPath,
                    'extension' => $file->getExtension(),
                ];
            }
        }
    }

    usort($files, function($a, $b) {
        return strcmp($a['path'], $b['path']);
    });

    return ['files' => $files];
}

function handleReadFile($path) {
    global $PROJECT_ROOT;
    
    $fullPath = validatePath($path);
    
    if (!is_readable($fullPath)) {
        throw new Exception('File is not readable');
    }

    $content = file_get_contents($fullPath);
    $ext = pathinfo($fullPath, PATHINFO_EXTENSION);

    logPatcherAction('read', $path, 'success');

    return [
        'path' => $path,
        'filename' => basename($fullPath),
        'content' => $content,
        'extension' => $ext,
        'size' => filesize($fullPath),
        'modified' => date('Y-m-d H:i:s', filemtime($fullPath)),
    ];
}

function handlePreviewDiff($data) {
    global $PROJECT_ROOT;
    
    $path = $data['path'] ?? '';
    $newContent = $data['content'] ?? '';

    $fullPath = validatePath($path);
    
    $oldContent = file_get_contents($fullPath);
    $diff = generateDiff($oldContent, $newContent);
    
    $added = count(array_filter($diff, fn($l) => $l['type'] === 'added'));
    $removed = count(array_filter($diff, fn($l) => $l['type'] === 'removed'));
    
    return [
        'diff' => $diff,
        'stats' => [
            'added' => $added,
            'removed' => $removed,
        ],
    ];
}

function handleApplyFix($data) {
    global $PROJECT_ROOT;
    
    $path = $data['path'] ?? '';
    $content = $data['content'] ?? '';

    $fullPath = validatePath($path);
    
    if (!is_writable($fullPath)) {
        throw new Exception('File is not writable');
    }

    // Create backup
    $backupDir = dirname($fullPath) . '/.backups';
    if (!is_dir($backupDir)) {
        mkdir($backupDir, 0755, true);
    }

    $timestamp = date('Y-m-d-H-i-s');
    $backupFile = $backupDir . '/' . basename($fullPath) . '.' . $timestamp . '.bak';
    copy($fullPath, $backupFile);

    // Write new content
    file_put_contents($fullPath, $content);

    logPatcherAction('apply_fix', $path, 'success', 'Backup: ' . basename($backupFile));

    return [
        'success' => true,
        'backup' => basename($backupFile),
        'path' => $path,
    ];
}

function handleListBackups($path) {
    global $PROJECT_ROOT;
    
    $fullPath = validatePath($path);
    
    if (!is_file($fullPath)) {
        throw new Exception('File not found');
    }

    $backupDir = dirname($fullPath) . '/.backups';
    $backups = [];

    if (is_dir($backupDir)) {
        $pattern = $backupDir . '/' . basename($fullPath) . '.*.bak';
        $files = glob($pattern);
        rsort($files);

        foreach (array_slice($files, 0, 10) as $file) {
            $backups[] = [
                'name' => basename($file),
                'created' => date('Y-m-d H:i:s', filemtime($file)),
                'size' => filesize($file),
            ];
        }
    }

    return ['backups' => $backups, 'count' => count($backups)];
}

function handleCreateFile($data) {
    global $PROJECT_ROOT;
    
    $path = $data['path'] ?? '';
    $content = $data['content'] ?? '';

    $fullPath = validatePath($path);
    
    if (file_exists($fullPath)) {
        throw new Exception('File already exists');
    }

    $dir = dirname($fullPath);
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }

    file_put_contents($fullPath, $content);
    
    logPatcherAction('create_file', $path, 'success');

    return ['success' => true, 'path' => $path];
}

function handleCreateFolder($data) {
    global $PROJECT_ROOT;
    
    $path = $data['path'] ?? '';

    // Validate path doesn't traverse
    if (strpos($path, '..') !== false) {
        throw new Exception('Invalid path');
    }

    $fullPath = $PROJECT_ROOT . '/' . $path;
    $realPath = realpath(dirname($fullPath));
    
    if (!$realPath || strpos($realPath, $PROJECT_ROOT) !== 0) {
        throw new Exception('Invalid path: outside project root');
    }

    if (is_dir($fullPath)) {
        throw new Exception('Folder already exists');
    }

    mkdir($fullPath, 0755, true);
    
    logPatcherAction('create_folder', $path, 'success');

    return ['success' => true, 'path' => $path];
}
