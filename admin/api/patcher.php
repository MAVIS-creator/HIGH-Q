<?php
// Patcher API v2.0 - Enhanced Security
// Start session first
session_start();

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json');

// Check admin session - use session check since patcher is standalone
if (empty($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized - Please login']);
    exit;
}

// Also verify permission if available
try {
    if (function_exists('requirePermission')) {
        requirePermission('patcher');
    }
} catch (Exception $e) {
    // If requirePermission fails, allow if session is valid
    if (empty($_SESSION['admin_logged_in'])) {
        http_response_code(401);
        echo json_encode(['error' => 'Unauthorized']);
        exit;
    }
}

// Security: Define safe directories and blocked items
const ALLOWED_DIRS = ['public', 'admin', 'config', 'src', 'migrations'];
const BLOCKED_FILES = ['.env', '.htaccess', 'config/db.php', 'admin/auth_check.php'];
const ALLOWED_EXTENSIONS = ['php', 'html', 'css', 'js', 'json', 'sql', 'txt', 'md'];
const BLOCKED_EXTENSIONS = ['exe', 'sh', 'bat', 'cmd', 'com', 'bin'];

$action = $_GET['action'] ?? '';
$response = ['error' => 'Unknown action'];

try {
    switch ($action) {
        case 'listFiles':
            $response = listFiles();
            break;
        case 'readFile':
            $path = $_GET['path'] ?? '';
            $response = getFileContent($path);
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
        case 'runCommand':
            $data = json_decode(file_get_contents('php://input'), true);
            $response = runCommand($data);
            break;
    }
} catch (Exception $e) {
    http_response_code(400);
    $response = ['error' => $e->getMessage()];
}

header('Content-Type: application/json');
echo json_encode($response, JSON_UNESCAPED_SLASHES);

/**
 * Validate file path for security (prevent path traversal, etc.)
 */
function validatePath($relPath) {
    $relPath = trim($relPath, '/\\');
    
    if (strpos($relPath, '..') !== false || strpos($relPath, '//') !== false) {
        throw new Exception('Invalid path: traversal not allowed');
    }
    
    $parts = explode('/', str_replace('\\', '/', $relPath));
    if (empty($parts[0]) || !in_array($parts[0], ALLOWED_DIRS, true)) {
        throw new Exception('Path not in allowed directories');
    }
    
    foreach (BLOCKED_FILES as $blocked) {
        if ($relPath === $blocked || str_ends_with($relPath, '/' . $blocked)) {
            throw new Exception('Access to this file is blocked');
        }
    }
    
    $baseDir = dirname(__DIR__, 2);
    $fullPath = realpath($baseDir . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relPath));
    
    if (!$fullPath || strpos($fullPath, realpath($baseDir)) !== 0) {
        throw new Exception('Invalid file path');
    }
    
    return $fullPath;
}

function listFiles() {
    $baseDir = dirname(__DIR__, 2);
    $files = [];
    
    foreach (ALLOWED_DIRS as $dirName) {
        $dir = realpath($baseDir . DIRECTORY_SEPARATOR . $dirName);
        if (!is_dir($dir)) continue;
        
        try {
            $iterator = new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS);
            $filter = new RecursiveCallbackFilterIterator($iterator, function($file, $key, $iterator) {
                if ($iterator->hasChildren()) return true;
                $name = $file->getFilename();
                $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
                return in_array($ext, ALLOWED_EXTENSIONS, true) && $name[0] !== '.';
            });

            foreach (new RecursiveIteratorIterator($filter) as $file) {
                if ($file->isFile()) {
                    $fullPath = $file->getRealPath();
                    $relPath = str_replace($baseDir . DIRECTORY_SEPARATOR, '', $fullPath);
                    $relPath = str_replace('\\', '/', $relPath);
                    
                    $isBlocked = false;
                    foreach (BLOCKED_FILES as $blocked) {
                        if ($relPath === $blocked) {
                            $isBlocked = true;
                            break;
                        }
                    }
                    if ($isBlocked) continue;
                    
                    $files[] = [
                        'name' => $file->getFilename(),
                        'path' => $relPath,
                        'extension' => $file->getExtension(),
                        'dir' => dirname($relPath),
                    ];
                }
            }
        } catch (Exception $e) {
            continue;
        }
    }

    usort($files, function($a, $b) {
        if ($a['dir'] !== $b['dir']) return strcmp($a['dir'], $b['dir']);
        return strcmp($a['name'], $b['name']);
    });

    return ['files' => $files];
}

function getFileContent($relPath) {
    $fullPath = validatePath($relPath);
    
    if (!is_readable($fullPath)) {
        throw new Exception('File is not readable');
    }

    $content = file_get_contents($fullPath);
    $ext = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));

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

    $fullPath = validatePath($path);

    if (!is_file($fullPath)) {
        throw new Exception('File not found');
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

    $fullPath = validatePath($path);

    if (!is_writable($fullPath)) {
        throw new Exception('File is not writable');
    }

    $backupDir = dirname($fullPath) . '/.backups';
    if (!is_dir($backupDir)) {
        if (!mkdir($backupDir, 0755, true)) {
            throw new Exception('Cannot create backup directory');
        }
    }

    $backupFile = $backupDir . '/' . basename($fullPath) . '.bak.' . date('Ymd_His');
    if (!copy($fullPath, $backupFile)) {
        throw new Exception('Failed to create backup');
    }

    if (file_put_contents($fullPath, $content) === false) {
        throw new Exception('Failed to write file');
    }

    $baseDir = dirname(__DIR__, 2);
    $logDir = $baseDir . '/storage/logs';
    if (!is_dir($logDir)) mkdir($logDir, 0755, true);
    
    $logFile = $logDir . '/patcher_audit.log';
    $logEntry = sprintf(
        "[%s] Admin %s modified %s (backup: %s) | Lines: %d\n",
        date('Y-m-d H:i:s'),
        $_SESSION['admin_username'] ?? 'Unknown',
        $path,
        basename($backupFile),
        count(explode("\n", $content))
    );
    @file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);

    return [
        'success' => true,
        'backup' => basename($backupFile),
        'path' => $path,
        'message' => 'File updated successfully'
    ];
}

function listBackups($relPath) {
    $fullPath = validatePath($relPath);
    
    if (!is_file($fullPath)) {
        throw new Exception('File not found');
    }

    $backupDir = dirname($fullPath) . '/.backups';
    $backups = [];

    if (is_dir($backupDir)) {
        $pattern = $backupDir . '/' . basename($fullPath) . '.bak.*';
        $files = glob($pattern);
        rsort($files);

        foreach (array_slice($files, 0, 20) as $file) {
            if (is_file($file)) {
                $backups[] = [
                    'name' => basename($file),
                    'created' => date('Y-m-d H:i:s', filemtime($file)),
                    'size' => filesize($file),
                    'path' => $relPath,
                ];
            }
        }
    }

    return [
        'backups' => $backups,
        'count' => count($backups),
        'total_available' => count(glob($backupDir . '/' . basename($fullPath) . '.bak.*') ?? [])
    ];
}

function runCommand($data) {
    $command = $data['command'] ?? '';
    
    if (empty($command)) {
        return ['output' => ''];
    }
    
    $baseDir = dirname(__DIR__, 2);
    chdir($baseDir);
    
    // Security: Whitelist allowed commands
    $allowedPrefixes = ['git', 'ls', 'dir', 'echo', 'composer', 'php', 'whoami', 'ver', 'python', 'python3', 'pip', 'pip3'];
    
    $isAllowed = false;
    foreach ($allowedPrefixes as $prefix) {
        if (str_starts_with(strtolower($command), $prefix)) {
            $isAllowed = true;
            break;
        }
    }
    
    if (!$isAllowed) {
        return ['output' => "Command not allowed. Allowed: " . implode(', ', $allowedPrefixes)];
    }
    
    $output = [];
    $returnVar = 0;
    exec($command . ' 2>&1', $output, $returnVar);
    
    return [
        'command' => $command,
        'output' => implode("\n", $output),
        'code' => $returnVar
    ];
}

function createFile($data) {
    $path = $data['path'] ?? '';
    $content = $data['content'] ?? '';
    
    if (empty($path)) {
        throw new Exception('Path is required');
    }

    $path = trim($path, '/\\');
    $parts = explode('/', str_replace('\\', '/', $path));
    if (empty($parts[0]) || !in_array($parts[0], ALLOWED_DIRS, true)) {
        throw new Exception('File must be in an allowed directory');
    }
    
    $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
    if (!in_array($ext, ALLOWED_EXTENSIONS, true)) {
        throw new Exception('File extension not allowed');
    }
    
    if (in_array($ext, BLOCKED_EXTENSIONS, true)) {
        throw new Exception('File extension is blocked');
    }

    $baseDir = dirname(__DIR__, 2);
    $fullPath = realpath($baseDir . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $path));
    
    if (!$fullPath || strpos($fullPath, realpath($baseDir)) !== 0) {
        throw new Exception('Invalid file path');
    }

    if (file_exists($fullPath)) {
        throw new Exception('File already exists');
    }

    $dir = dirname($fullPath);
    if (!is_dir($dir)) {
        if (!mkdir($dir, 0755, true)) {
            throw new Exception('Cannot create directory');
        }
    }

    if (file_put_contents($fullPath, $content) === false) {
        throw new Exception('Failed to create file');
    }

    return ['success' => true, 'path' => $path, 'message' => 'File created successfully'];
}

function createFolder($data) {
    $path = $data['path'] ?? '';
    
    if (empty($path)) {
        throw new Exception('Path is required');
    }

    $path = trim($path, '/\\');
    $parts = explode('/', str_replace('\\', '/', $path));
    if (empty($parts[0]) || !in_array($parts[0], ALLOWED_DIRS, true)) {
        throw new Exception('Folder must be in an allowed directory');
    }

    $baseDir = dirname(__DIR__, 2);
    $fullPath = realpath($baseDir . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $path));
    
    if (!$fullPath || strpos($fullPath, realpath($baseDir)) !== 0) {
        throw new Exception('Invalid folder path');
    }

    if (is_dir($fullPath)) {
        throw new Exception('Folder already exists');
    }

    if (!mkdir($fullPath, 0755, true)) {
        throw new Exception('Failed to create folder');
    }

    return ['success' => true, 'path' => $path, 'message' => 'Folder created successfully'];
}
