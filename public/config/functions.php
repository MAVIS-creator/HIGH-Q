<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use Dotenv\Dotenv;

// Autoload (Composer)
require_once __DIR__ . '/../../vendor/autoload.php';

// Load .env (project root is 2 levels up from /admin/includes/)
$dotenv = Dotenv::createImmutable(__DIR__ . '/../../');
$dotenv->load();

/**
 * Log actions into audit_logs
 */
function logAction(PDO $pdo, int $user_id, string $action, array $meta = []): void {
    $stmt = $pdo->prepare("INSERT INTO audit_logs (user_id, action, meta, created_at) VALUES (?, ?, ?, NOW())");
    $stmt->execute([$user_id, $action, json_encode($meta, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)]);
}

/**
 * Get the current full URL of the request
 * @return string Current URL
 */
function current_url(): string {
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $path = $_SERVER['REQUEST_URI'] ?? '';
    return $scheme . '://' . $host . $path;
}

/**
 * Generate a meta tag with proper escaping
 * @param string $name Meta tag name (description, keywords, etc)
 * @param string $content Meta tag content
 * @return string HTML meta tag
 */
function meta_tag(string $name, string $content): string {
    if (empty($name) || empty($content)) return '';
    return '<meta name="' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . '" content="' . htmlspecialchars($content, ENT_QUOTES, 'UTF-8') . '">' . "\n";
}

/**
 * Generate OpenGraph meta tag
 * @param string $property Property name (og:title, og:description, etc)
 * @param string $content Property content
 * @return string HTML meta tag
 */
function og_tag(string $property, string $content): string {
    if (empty($property) || empty($content)) return '';
    return '<meta property="' . htmlspecialchars($property, ENT_QUOTES, 'UTF-8') . '" content="' . htmlspecialchars($content, ENT_QUOTES, 'UTF-8') . '">' . "\n";
}

/**
 * Return the application base URL
 * Works both in production (at root /) and development (in subfolders like /HIGH-Q)
 * @param string $path Optional path to append
 * @return string Full URL
 */
function app_url(string $path = ''): string {
    static $cachedBase = null;
    
    // Return cached base URL if already computed
    if ($cachedBase !== null) {
        if ($path === '') return $cachedBase;
        return $cachedBase . '/' . ltrim($path, '/');
    }

    // Determine scheme and host
    // Check for X-Forwarded-Proto header first (set by reverse proxies/load balancers)
    $scheme = 'http';
    if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO'])) {
        $scheme = strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']);
    } elseif (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
        $scheme = 'https';
    }
    $host = $_SERVER['HTTP_HOST'] ?? ($_ENV['APP_FALLBACK_HOST'] ?? 'localhost');
    
    // Get the project base path from filesystem
    // __DIR__ = /path/to/project/public/config
    // realpath(__DIR__ . '/../../') = /path/to/project
    $projectRoot = realpath(__DIR__ . '/../../');
    $documentRoot = realpath($_SERVER['DOCUMENT_ROOT'] ?? '');
    
    $baseUri = '';
    
    // If both paths are available, compute relative path from document root
    if (!empty($documentRoot) && !empty($projectRoot) && strpos($projectRoot, $documentRoot) === 0) {
        $relPath = substr($projectRoot, strlen($documentRoot));
        $baseUri = str_replace('\\', '/', $relPath);  // Normalize Windows backslashes
        
        // If project IS at document root, baseUri will be empty - that's correct for production
        // If project is in a subfolder (like /HIGH-Q for dev), baseUri will be /HIGH-Q - also correct
    } else {
        // Fallback: try to extract from SCRIPT_NAME when filesystem detection fails
        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
        $scriptPath = str_replace('\\', '/', $scriptName);
        
        // Extract the path portion (everything before /public or the project folder)
        // For /HIGH-Q/public/index.php → /HIGH-Q
        // For /public/index.php → / (which means document root)
        if (preg_match('|^(/.+?)?/public/|', $scriptPath, $matches)) {
            $baseUri = $matches[1] ?? '';  // Will be /HIGH-Q, /projects/myapp, or empty string
        }
    }

    // Build the complete base URL
    $cachedBase = $scheme . '://' . $host . ($baseUri !== '' ? $baseUri : '');
    
    if ($path === '') return $cachedBase;
    return $cachedBase . '/' . ltrim($path, '/');
}

/**
 * Send email using PHPMailer (SMTP). Optionally attach files.
 * $attachments = ['/path/to/file1', '/path/to/file2']
 */
function sendEmail(string $to, string $subject, string $html, array $attachments = []): bool {
    $mail = new PHPMailer(true);

    try {
        // Prepare debug logging if requested
        $debugEnabled = !empty($_ENV['MAIL_DEBUG']) && ($_ENV['MAIL_DEBUG'] === '1' || strtolower($_ENV['MAIL_DEBUG']) === 'true');
        $logDir = __DIR__ . '/../../storage/logs'; if (!is_dir($logDir)) @mkdir($logDir, 0755, true);
        $debugLog = $logDir . '/mailer_debug.log';

        // Server settings (from .env)
        $mail->isSMTP();
        $mail->Host       = $_ENV['MAIL_HOST'];
        $mail->SMTPAuth   = true;
        $mail->Username   = $_ENV['MAIL_USERNAME'];
        $mail->Password   = $_ENV['MAIL_PASSWORD'];
        $mail->SMTPSecure = $_ENV['MAIL_ENCRYPTION'] ?? PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = $_ENV['MAIL_PORT'] ?? 587;

        if (!empty($debugEnabled)) {
            $mail->SMTPDebug = 2; // show client/server messages
            $mail->Debugoutput = function($str, $level) use ($debugLog) {
                @file_put_contents($debugLog, "[" . date('c') . "] [level=" . $level . "] " . $str . "\n", FILE_APPEND | LOCK_EX);
            };
        }

        // Ensure PHPMailer uses PHP's configured CA file when present to avoid cert verify failures
        $caFile = ini_get('openssl.cafile') ?: ($_ENV['MAIL_CAFILE'] ?? null);
        if (!empty($caFile) && is_readable($caFile)) {
            $mail->SMTPOptions = [
                'ssl' => [
                    'verify_peer' => true,
                    'verify_peer_name' => true,
                    'allow_self_signed' => false,
                    'cafile' => $caFile,
                ],
            ];
        }

        // Recipients
        $mail->setFrom($_ENV['MAIL_FROM_ADDRESS'], $_ENV['MAIL_FROM_NAME']);
        $mail->addAddress($to);

        // Attach files if present
        foreach ($attachments as $file) {
            if (is_readable($file)) {
                $mail->addAttachment($file);
            }
        }

        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $html;

        return $mail->send();
    } catch (Exception $e) {
        // Log PHPMailer error (includes Debug output if enabled)
        try { @file_put_contents(__DIR__ . '/../../storage/logs/students_confirm_errors.log', "[" . date('c') . "] Mailer Exception: " . ($mail->ErrorInfo ?? $e->getMessage()) . "\n", FILE_APPEND | LOCK_EX); } catch (Throwable $_) {}
        error_log("Mailer Error: " . ($mail->ErrorInfo ?? $e->getMessage()));
        return false;
    }
}
