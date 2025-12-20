<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use Dotenv\Dotenv;

// Autoload (Composer)
require_once __DIR__ . '/../../vendor/autoload.php';

// Load .env (project root is 2 levels up from /admin/includes/)
$dotenv = Dotenv::createImmutable(__DIR__ . '/../../');
try {
    // Use safeLoad to avoid fatal errors when .env is missing or has parse issues
    $dotenv->safeLoad();
} catch (Throwable $e) {
    // Non-fatal: rely on getenv()/defaults below
}

/**
 * Log actions into audit_logs
 */
function logAction(PDO $pdo, int $user_id, string $action, array $meta = []): void {
    $stmt = $pdo->prepare("INSERT INTO audit_logs (user_id, action, meta, created_at) VALUES (?, ?, ?, NOW())");
    $stmt->execute([$user_id, $action, json_encode($meta, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)]);
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

/**
 * Compute canonical URL parts (scheme, host, project prefix like /HIGH-Q) once per request.
 */
function hq_url_parts(): array {
    static $parts = null;
    if ($parts !== null) return $parts;

    $scheme = 'http';
    if (
        (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
        (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https') ||
        (!empty($_SERVER['HTTP_X_FORWARDED_SSL']) && strtolower($_SERVER['HTTP_X_FORWARDED_SSL']) === 'on')
    ) {
        $scheme = 'https';
    }

    $host = $_SERVER['HTTP_HOST'] ?? ($_ENV['APP_FALLBACK_HOST'] ?? 'localhost');

    // Derive project prefix from filesystem vs document root to support subfolder installs (e.g., /HIGH-Q)
    $docrootRaw = $_SERVER['DOCUMENT_ROOT'] ?? '';
    $docrootNorm = $docrootRaw ? str_replace('\\', '/', rtrim($docrootRaw, '/\\')) : '';
    $publicDirRaw = realpath(__DIR__ . '/../../public') ?: '';
    $publicDirNorm = $publicDirRaw ? str_replace('\\', '/', $publicDirRaw) : '';

    $projPrefix = '';
    if ($docrootNorm !== '' && $publicDirNorm !== '') {
        $docrootLower = strtolower($docrootNorm);
        $publicLower = strtolower($publicDirNorm);
        if (strpos($publicLower, $docrootLower) === 0) {
            $relativePublic = ltrim(substr($publicDirNorm, strlen($docrootNorm)), '/');
            $segments = $relativePublic !== '' ? explode('/', $relativePublic) : [];
            if (!empty($segments) && strtolower(end($segments)) === 'public') {
                array_pop($segments);
            }
            if (!empty($segments)) {
                $projPrefix = '/' . implode('/', $segments);
            }
        }
    }

    // Fallback: infer from REQUEST_URI when SCRIPT_NAME/docroot are misleading
    if ($projPrefix === '') {
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        $uri = is_string($uri) ? $uri : '';
        $uriParts = $uri !== '' ? explode('/', trim($uri, '/')) : [];
        if (!empty($uriParts)) {
            $idx = array_search('admin', $uriParts, true);
            if ($idx !== false && $idx > 0) {
                $projPrefix = '/' . implode('/', array_slice($uriParts, 0, $idx));
            } else {
                $projectRootName = $publicDirNorm !== '' ? basename(dirname($publicDirNorm)) : '';
                if ($projectRootName !== '' && strpos($uri, '/' . $projectRootName . '/') === 0) {
                    $projPrefix = '/' . $projectRootName;
                }
            }
        }
    }

    // Last-resort fallback: derive from filesystem folder name (e.g., /HIGH-Q) if still empty
    if ($projPrefix === '') {
        $projectRootName = basename(dirname(__DIR__, 2));
        if (!empty($projectRootName) && $projectRootName !== '.' && $projectRootName !== '/') {
            $projPrefix = '/' . $projectRootName;
        }
    }

    $parts = [$scheme, $host, $projPrefix];
    return $parts;
}

/**
 * Return the configured application base URL (public site).
 * Prefer APP_URL from environment; otherwise derive from request so ngrok/local/prod and subfolders work.
 */
function app_url(string $path = ''): string {
    $env = $_ENV['APP_URL'] ?? null;
    if (!empty($env)) {
        $base = rtrim($env, '/');
        return $path === '' ? $base : ($base . '/' . ltrim($path, '/'));
    }

    [$scheme, $host, $projPrefix] = hq_url_parts();
    $base = $scheme . '://' . $host . $projPrefix . '/public';
    return $path === '' ? $base : ($base . '/' . ltrim($path, '/'));
}

/**
 * Return the admin base URL (admin area lives alongside /public as /admin).
 */
function admin_url(string $path = ''): string {
    $env = $_ENV['ADMIN_URL'] ?? null;
    if (!empty($env)) {
        $base = rtrim($env, '/');
        return $path === '' ? $base : ($base . '/' . ltrim($path, '/'));
    }

    // If APP_URL is set, derive admin path from it by swapping /public for /admin (or appending /admin)
    $envApp = $_ENV['APP_URL'] ?? null;
    if (!empty($envApp)) {
        $base = rtrim($envApp, '/');
        if (preg_match('#/public$#', $base)) {
            $base = preg_replace('#/public$#', '/admin', $base);
        } else {
            $base .= '/admin';
        }
        return $path === '' ? $base : ($base . '/' . ltrim($path, '/'));
    }

    [$scheme, $host, $projPrefix] = hq_url_parts();
    $base = $scheme . '://' . $host . $projPrefix . '/admin';
    return $path === '' ? $base : ($base . '/' . ltrim($path, '/'));
}

/**
 * Check if user has permission for a specific resource.
 * Throws exception if permission denied. Returns true if allowed.
 */


