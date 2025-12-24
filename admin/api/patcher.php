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


$action = $_GET['action'] ?? '';
$response = ['error' => 'Unknown action'];

try {
    switch ($action) {
        case 'listFiles':
            $response = listFiles();
            break;
        case 'readFile':
            $path = $_GET['path'] ?? '';
            $response = readFile($path);
            break;
        case 'previewDiff':
            $data = json_decode(file_get_contents('php://input'), true);
            $response = previewDiff($data);
            break;
        case 'applyFix':
            $data = json_decode(file_get_contents('php://input'), true);
            $response = applyFix($data);
            break;
        case 'listBackups':
            $path = $_GET['path'] ?? '';
            $response = listBackups($path);
            break;
        case 'createFile':
            $data = json_decode(file_get_contents('php://input'), true);
            $response = createFile($data);
            break;
        case 'createFolder':
            $data = json_decode(file_get_contents('php://input'), true);
            $response = createFolder($data);
            break;
    }
} catch (Exception $e) {
    $response = ['error' => $e->getMessage()];
}

header('Content-Type: application/json');
echo json_encode($response);

function listFiles() {
    $allowedDirs = [
        __DIR__ . '/../../public' => 'public',
        __DIR__ . '/../../admin' => 'admin',
        __DIR__ . '/../../config' => 'config',
        __DIR__ . '/../../migrations' => 'migrations',
    ];

    $files = [];
    
    foreach ($allowedDirs as $dir => $prefix) {
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
                $relPath = str_replace(dirname($dir) . DIRECTORY_SEPARATOR, '', $fullPath);
                $relPath = str_replace('\\', '/', $relPath);
                
                $files[] = [
                    'name' => $file->getFilename(),
                    'path' => $relPath,
                    'extension' => $file->getExtension(),
                    'dir' => $prefix . '/' . str_replace('\\', '/', dirname(str_replace($dir, '', $fullPath))),
                ];
            }
        }
    }

    usort($files, function($a, $b) {
        if ($a['dir'] !== $b['dir']) return strcmp($a['dir'], $b['dir']);
        return strcmp($a['name'], $b['name']);
    });

    return ['files' => $files];
}

function readFile($relPath) {
    $baseDir = dirname(__DIR__, 2);
    $fullPath = realpath($baseDir . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relPath));
    
    if (!$fullPath || !is_file($fullPath) || strpos($fullPath, realpath($baseDir)) !== 0) {
        throw new Exception('Invalid file path');
    }

    if (!is_readable($fullPath)) {
        throw new Exception('File is not readable');
    }

    $content = file_get_contents($fullPath);
    $ext = pathinfo($fullPath, PATHINFO_EXTENSION);

    return [
        'path' => $relPath,
        'filename' => basename($fullPath),
        'content' => $content,
        'extension' => $ext,
        'size' => filesize($fullPath),
        'modified' => date('Y-m-d H:i:s', filemtime($fullPath)),
    ];
}

function previewDiff($data) {
    $path = $data['path'] ?? '';
    $newContent = $data['content'] ?? '';

    $baseDir = dirname(__DIR__, 2);
    $fullPath = realpath($baseDir . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $path));
    
    if (!$fullPath || !is_file($fullPath) || strpos($fullPath, realpath($baseDir)) !== 0) {
        throw new Exception('Invalid file path');
    }

    $oldContent = file_get_contents($fullPath);
    $oldLines = explode("\n", $oldContent);
    $newLines = explode("\n", $newContent);

    $diff = computeDiff($oldLines, $newLines);
    
    return [
        'diff' => $diff,
        'stats' => [
            'added' => count(array_filter($diff['lines'], fn($l) => $l['type'] === 'added')),
            'removed' => count(array_filter($diff['lines'], fn($l) => $l['type'] === 'removed')),
            'unchanged' => count(array_filter($diff['lines'], fn($l) => $l['type'] === 'unchanged')),
        ],
    ];
}

function computeDiff($oldLines, $newLines) {
    $lines = [];
    $lineNum = 1;
    $maxLines = max(count($oldLines), count($newLines));

    for ($i = 0; $i < $maxLines; $i++) {
        $oldLine = $oldLines[$i] ?? '';
        $newLine = $newLines[$i] ?? '';

        if ($oldLine === $newLine) {
            $lines[] = ['lineNum' => $lineNum, 'content' => $newLine, 'type' => 'unchanged'];
            $lineNum++;
        } else {
            if (isset($oldLines[$i])) {
                $lines[] = ['lineNum' => $lineNum, 'content' => $oldLines[$i], 'type' => 'removed'];
                $lineNum++;
            }
            if (isset($newLines[$i])) {
                $lines[] = ['lineNum' => $lineNum, 'content' => $newLines[$i], 'type' => 'added'];
                $lineNum++;
            }
        }
    }

    return ['lines' => $lines];
}

function applyFix($data) {
    $path = $data['path'] ?? '';
    $content = $data['content'] ?? '';

    $baseDir = dirname(__DIR__, 2);
    $fullPath = realpath($baseDir . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $path));
    
    if (!$fullPath || !is_file($fullPath) || strpos($fullPath, realpath($baseDir)) !== 0) {
        throw new Exception('Invalid file path');
    }

    if (!is_writable($fullPath)) {
        throw new Exception('File is not writable');
    }

    // Create backup
    $backupDir = dirname($fullPath) . '/.backups';
    if (!is_dir($backupDir)) {
        mkdir($backupDir, 0755, true);
    }

    $backupFile = $backupDir . '/' . basename($fullPath) . '.' . date('Y-m-d-H-i-s') . '.bak';
    copy($fullPath, $backupFile);

    // Write new content
    file_put_contents($fullPath, $content);

    // Log action
    $logFile = $baseDir . '/.backups/patcher.log';
    $logDir = dirname($logFile);
    if (!is_dir($logDir)) mkdir($logDir, 0755, true);
    
    $logEntry = sprintf(
        "[%s] Admin %s modified %s (backup: %s)\n",
        date('Y-m-d H:i:s'),
        $_SESSION['admin_username'] ?? 'Unknown',
        $path,
        basename($backupFile)
    );
    file_put_contents($logFile, $logEntry, FILE_APPEND);

    return [
        'success' => true,
        'backup' => basename($backupFile),
        'path' => $path,
    ];
}

function listBackups($relPath) {
    $baseDir = dirname(__DIR__, 2);
    $fullPath = realpath($baseDir . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relPath));
    
    if (!$fullPath || !is_file($fullPath)) {
        throw new Exception('Invalid file path');
    }

    $backupDir = dirname($fullPath) . '/.backups';
    $backups = [];

    if (is_dir($backupDir)) {
        $files = glob($backupDir . '/' . basename($fullPath) . '*.bak');
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

function createFile($data) {
    $path = $data['path'] ?? '';
    if (strpos($path, '..') !== false) {
        throw new Exception('Invalid path');
    }

    $baseDir = dirname(__DIR__, 2);
    $fullPath = $baseDir . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $path);
    $dir = dirname($fullPath);

    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }

    if (file_exists($fullPath)) {
        throw new Exception('File already exists');
    }

    file_put_contents($fullPath, '');
    return ['success' => true, 'path' => $path];
}

function createFolder($data) {
    $path = $data['path'] ?? '';
    if (strpos($path, '..') !== false) {
        throw new Exception('Invalid path');
    }

    $baseDir = dirname(__DIR__, 2);
    $fullPath = $baseDir . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $path);

    if (is_dir($fullPath)) {
        throw new Exception('Folder already exists');
    }

    mkdir($fullPath, 0755, true);
    return ['success' => true, 'path' => $path];
}
