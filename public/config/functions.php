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
    // Check for X-Forwarded-Proto header first (set by reverse proxies/load balancers)
    $scheme = 'http';
    if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO'])) {
        $scheme = strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']);
    } elseif (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
        $scheme = 'https';
    }
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
        $relativeProjectPath = substr($projectRoot, strlen($documentRoot));
        $baseUri = str_replace('\\', '/', $relativeProjectPath);

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

/**
 * Check if system email notifications are enabled in settings.
 */
function hqAdminEmailNotificationsEnabled(PDO $pdo): bool {
    try {
        $stmt = $pdo->prepare("SELECT value FROM settings WHERE `key` = ? LIMIT 1");
        $stmt->execute(['system_settings']);
        $raw = $stmt->fetchColumn();
        if (!$raw) {
            return true;
        }

        $settings = json_decode((string)$raw, true);
        if (!is_array($settings)) {
            return true;
        }

        if (isset($settings['notifications']) && is_array($settings['notifications']) && array_key_exists('email', $settings['notifications'])) {
            return (bool)$settings['notifications']['email'];
        }

        return true;
    } catch (Throwable $e) {
        return true;
    }
}

/**
 * Collect admin/team recipient emails for operational notifications.
 */
function hqAdminNotificationRecipients(PDO $pdo, ?int $actorUserId = null): array {
    $emails = [];

    try {
        $sql = "SELECT DISTINCT u.email
                FROM users u
                LEFT JOIN roles r ON r.id = u.role_id
                LEFT JOIN role_permissions rp ON rp.role_id = u.role_id
                WHERE u.email IS NOT NULL
                  AND u.email <> ''
                  AND (
                        LOWER(COALESCE(r.slug, '')) = 'admin'
                     OR LOWER(COALESCE(r.name, '')) = 'admin'
                     OR rp.menu_slug = 'settings'
                  )";
        $stmt = $pdo->query($sql);
        $rows = $stmt ? $stmt->fetchAll(PDO::FETCH_COLUMN) : [];
        foreach ($rows as $email) {
            $email = trim((string)$email);
            if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $emails[] = strtolower($email);
            }
        }
    } catch (Throwable $e) {
    }

    if ($actorUserId && $actorUserId > 0) {
        try {
            $stmt = $pdo->prepare("SELECT email FROM users WHERE id = ? LIMIT 1");
            $stmt->execute([$actorUserId]);
            $actorEmail = trim((string)$stmt->fetchColumn());
            if ($actorEmail !== '' && filter_var($actorEmail, FILTER_VALIDATE_EMAIL)) {
                $emails[] = strtolower($actorEmail);
            }
        } catch (Throwable $e) {
        }
    }

    if (!empty($_SESSION['user']['email'])) {
        $sessionEmail = trim((string)$_SESSION['user']['email']);
        if (filter_var($sessionEmail, FILTER_VALIDATE_EMAIL)) {
            $emails[] = strtolower($sessionEmail);
        }
    }

    return array_values(array_unique($emails));
}

/**
 * Send a styled notification email for important admin updates.
 */
function sendAdminChangeNotification(PDO $pdo, string $title, array $details = [], ?int $actorUserId = null): bool {
    if (!hqAdminEmailNotificationsEnabled($pdo)) {
        return false;
    }

    $recipients = hqAdminNotificationRecipients($pdo, $actorUserId);
    if (empty($recipients)) {
        return false;
    }

    $subject = 'HIGH-Q Admin Update: ' . $title;
    $actorName = trim((string)($_SESSION['user']['name'] ?? 'System'));
    $actorEmail = trim((string)($_SESSION['user']['email'] ?? 'N/A'));
    $occurredAt = date('Y-m-d H:i:s');

    $rowsHtml = '';
    foreach ($details as $k => $v) {
        $label = htmlspecialchars((string)$k, ENT_QUOTES, 'UTF-8');
        if (is_array($v) || is_object($v)) {
            $value = json_encode($v, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        } else {
            $value = (string)$v;
        }
        $value = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
        $rowsHtml .= "<tr><td style='padding:10px;border-bottom:1px solid #eef2f7;font-weight:600;color:#1f2937'>{$label}</td><td style='padding:10px;border-bottom:1px solid #eef2f7;color:#374151'>{$value}</td></tr>";
    }

    $panelUrl = app_url('admin/');
    $safeTitle = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
    $safeActorName = htmlspecialchars($actorName, ENT_QUOTES, 'UTF-8');
    $safeActorEmail = htmlspecialchars($actorEmail, ENT_QUOTES, 'UTF-8');
    $safeOccurredAt = htmlspecialchars($occurredAt, ENT_QUOTES, 'UTF-8');

    $html = "
    <div style='margin:0;padding:24px;background:#f4f6fb;font-family:Segoe UI,Arial,sans-serif;color:#111827'>
      <div style='max-width:680px;margin:0 auto;background:#ffffff;border:1px solid #e5e7eb;border-radius:14px;overflow:hidden'>
        <div style='background:linear-gradient(135deg,#111827,#1f2937);padding:20px 24px'>
          <h2 style='margin:0;font-size:20px;color:#ffffff'>Admin Update Notification</h2>
          <p style='margin:6px 0 0;color:#d1d5db;font-size:13px'>HIGH-Q System Alert</p>
        </div>
        <div style='padding:20px 24px'>
          <p style='margin:0 0 14px;font-size:15px;color:#111827'><strong>Event:</strong> {$safeTitle}</p>
          <div style='margin:0 0 16px;padding:12px 14px;background:#fff7ed;border:1px solid #fed7aa;border-radius:10px'>
            <p style='margin:0 0 4px;font-size:13px;color:#9a3412'><strong>Triggered By:</strong> {$safeActorName}</p>
            <p style='margin:0 0 4px;font-size:13px;color:#9a3412'><strong>Actor Email:</strong> {$safeActorEmail}</p>
            <p style='margin:0;font-size:13px;color:#9a3412'><strong>Time:</strong> {$safeOccurredAt}</p>
          </div>
          <table style='width:100%;border-collapse:collapse;border:1px solid #eef2f7;border-radius:10px;overflow:hidden'>
            <tbody>
              {$rowsHtml}
            </tbody>
          </table>
          <div style='margin-top:20px'>
            <a href='{$panelUrl}' style='display:inline-block;padding:10px 16px;background:#ffd600;color:#111827;text-decoration:none;border-radius:8px;font-weight:700'>Open Admin Panel</a>
          </div>
        </div>
      </div>
    </div>";

    $sentAny = false;
    foreach ($recipients as $email) {
        try {
            if (sendEmail($email, $subject, $html)) {
                $sentAny = true;
            }
        } catch (Throwable $e) {
        }
    }

    return $sentAny;
}

/**
 * Fire-and-forget wrapper for admin change notifications.
 */
function notifyAdminChange(PDO $pdo, string $title, array $details = [], ?int $actorUserId = null): void {
    try {
        sendAdminChangeNotification($pdo, $title, $details, $actorUserId);
    } catch (Throwable $e) {
    }
}
