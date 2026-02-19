<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use Dotenv\Dotenv;

// Autoload (Composer)
require_once __DIR__ . '/../../vendor/autoload.php';

// Load project root .env first (shared settings as defaults)
$rootDotenv = Dotenv::createImmutable(__DIR__ . '/../../');
try {
    $rootDotenv->safeLoad();
} catch (Throwable $e) {
    // Non-fatal
}

// Then load admin folder's .env (admin-specific settings override root)
$adminDotenv = Dotenv::createImmutable(__DIR__ . '/../');
try {
    // Use safeLoad to override root values with admin-specific ones
    $adminDotenv->load(); // Using load() instead of safeLoad() to override
} catch (Throwable $e) {
    // Non-fatal: if admin .env doesn't exist, just use root values
}

/**
 * Log actions into audit_logs
 */
function logAction(PDO $pdo, ?int $user_id, string $action, array $meta = []): void {
    $resolvedUserId = null;

    if (!empty($user_id) && $user_id > 0) {
        try {
            $check = $pdo->prepare('SELECT 1 FROM users WHERE id = ? LIMIT 1');
            $check->execute([$user_id]);
            if ($check->fetchColumn()) {
                $resolvedUserId = $user_id;
            }
        } catch (Throwable $e) {
            $resolvedUserId = null;
        }
    }

    $stmt = $pdo->prepare("INSERT INTO audit_logs (user_id, action, meta, created_at) VALUES (?, ?, ?, NOW())");
    $stmt->execute([$resolvedUserId, $action, json_encode($meta, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)]);
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

    $panelUrl = htmlspecialchars(admin_url('index.php?pages=dashboard'), ENT_QUOTES, 'UTF-8');
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


