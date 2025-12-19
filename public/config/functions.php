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
 * Return the application base URL.
 * - Prefer APP_URL from .env (works for production/ngrok/custom domains).
 * - Otherwise derive from the current request and filesystem so subfolder installs work (e.g., /HIGH-Q/public).
 */
function app_url(string $path = ''): string {
    // 1) Explicit APP_URL wins
    $envBase = $_ENV['APP_URL'] ?? null;
    if (!empty($envBase)) {
        $base = rtrim($envBase, '/');
        return $path === '' ? $base : ($base . '/' . ltrim($path, '/'));
    }

    // 2) Derive scheme/host honoring reverse proxies
    $scheme = 'http';
    if (
        (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
        (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https') ||
        (!empty($_SERVER['HTTP_X_FORWARDED_SSL']) && strtolower($_SERVER['HTTP_X_FORWARDED_SSL']) === 'on')
    ) {
        $scheme = 'https';
    }
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';

    // 3) Compute project prefix using filesystem vs document root (captures /HIGH-Q when hosted in a subfolder)
    $docrootRaw = $_SERVER['DOCUMENT_ROOT'] ?? '';
    $docrootNorm = $docrootRaw ? str_replace('\\', '/', rtrim($docrootRaw, '/\\')) : '';
    $publicDirRaw = realpath(__DIR__ . '/../') ?: '';
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

    // 4) Fallback to REQUEST_URI inspection when DOCUMENT_ROOT comparison fails
    if ($projPrefix === '') {
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        $uri = is_string($uri) ? $uri : '';
        $uriParts = $uri !== '' ? explode('/', trim($uri, '/')) : [];
        if (!empty($uriParts)) {
            $idx = array_search('public', $uriParts, true);
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

    $base = $scheme . '://' . $host . $projPrefix . '/public';
    return $path === '' ? $base : ($base . '/' . ltrim($path, '/'));
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
