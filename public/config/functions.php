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

    $envBase = $_ENV['APP_URL'] ?? null;
    if (!empty($envBase)) {
        $base = rtrim((string)$envBase, '/');
        return $path === '' ? $base : ($base . '/' . ltrim($path, '/'));
    }
    
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
 * Return the admin application base URL.
 * Honors ADMIN_URL when configured, otherwise derives it from APP_URL/app_url().
 */
function admin_url(string $path = ''): string {
    $adminEnv = $_ENV['ADMIN_URL'] ?? null;
    if (!empty($adminEnv)) {
        $base = rtrim((string)$adminEnv, '/');
        return $path === '' ? $base : ($base . '/' . ltrim($path, '/'));
    }

    $base = rtrim(app_url(''), '/');
    if (preg_match('#/public$#i', $base)) {
        $base = preg_replace('#/public$#i', '/admin', $base);
    } elseif (!preg_match('#/admin$#i', $base)) {
        $base .= '/admin';
    }

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
 * Normalize admin menu/page slugs used for permission-scoped notifications.
 */
function hqNormalizeNotificationMenuSlug(?string $slug): ?string {
    $slug = trim((string)$slug);
    if ($slug === '') {
        return null;
    }

    $slug = strtolower($slug);
    $map = [
        'payment' => 'payments',
        'payments' => 'payments',
        'registration' => 'academic',
        'registrations' => 'academic',
        'student' => 'academic',
        'students' => 'academic',
        'academic' => 'academic',
        'appointment' => 'appointments',
        'appointments' => 'appointments',
        'chat' => 'chat',
        'chat_view' => 'chat',
        'comment' => 'comments',
        'comments' => 'comments',
        'course' => 'courses',
        'courses' => 'courses',
        'post' => 'post',
        'posts' => 'post',
        'testimonial' => 'testimonials',
        'testimonials' => 'testimonials',
        'user' => 'users',
        'users' => 'users',
        'role' => 'roles',
        'roles' => 'roles',
        'setting' => 'settings',
        'settings' => 'settings',
        'dashboard' => 'dashboard',
        'support' => 'chat',
    ];

    return $map[$slug] ?? $slug;
}

/**
 * Infer the admin menu slug a notification targets from a link or current request context.
 */
function hqNotificationTargetMenuSlug(?string $linkUrl = null): ?string {
    if (!empty($linkUrl)) {
        $query = parse_url($linkUrl, PHP_URL_QUERY);
        if (is_string($query)) {
            parse_str($query, $queryParts);
            if (!empty($queryParts['pages'])) {
                return hqNormalizeNotificationMenuSlug((string)$queryParts['pages']);
            }
        }

        $path = parse_url($linkUrl, PHP_URL_PATH);
        if (is_string($path) && $path !== '') {
            $basename = pathinfo($path, PATHINFO_FILENAME);
            $normalized = hqNormalizeNotificationMenuSlug($basename);
            if ($normalized !== null) {
                return $normalized;
            }
        }
    }

    if (!empty($_GET['pages'])) {
        return hqNormalizeNotificationMenuSlug((string)$_GET['pages']);
    }

    $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
    if (is_string($scriptName) && $scriptName !== '') {
        $basename = pathinfo($scriptName, PATHINFO_FILENAME);
        return hqNormalizeNotificationMenuSlug($basename);
    }

    return null;
}

/**
 * Collect recipient emails for operational notifications based on the admin page they can access.
 */
function hqAdminNotificationRecipients(PDO $pdo, ?int $actorUserId = null, ?string $targetMenuSlug = null): array {
    $emails = [];
    $targetMenuSlug = hqNormalizeNotificationMenuSlug($targetMenuSlug);
    $allowedSlugs = [];

    if ($targetMenuSlug !== null) {
        $allowedSlugs[] = $targetMenuSlug;
        if ($targetMenuSlug === 'payments') {
            $allowedSlugs[] = 'create_payment_link';
        } elseif ($targetMenuSlug === 'create_payment_link') {
            $allowedSlugs[] = 'payments';
        }
    }

    try {
        $sql = "SELECT DISTINCT u.email
                FROM users u
                LEFT JOIN roles r ON r.id = u.role_id
                LEFT JOIN role_permissions rp ON rp.role_id = u.role_id
                WHERE u.email IS NOT NULL
                  AND u.email <> ''
                  AND COALESCE(u.is_active, 1) = 1";
        $params = [];

        if (!empty($allowedSlugs)) {
            $placeholders = implode(',', array_fill(0, count($allowedSlugs), '?'));
            $sql .= " AND (
                        LOWER(COALESCE(r.slug, '')) = 'admin'
                     OR LOWER(COALESCE(r.name, '')) = 'admin'
                     OR rp.menu_slug IN ($placeholders)
                  )";
            $params = $allowedSlugs;
        } else {
            $sql .= " AND (
                        LOWER(COALESCE(r.slug, '')) = 'admin'
                     OR LOWER(COALESCE(r.name, '')) = 'admin'
                     OR rp.menu_slug = 'settings'
                  )";
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_COLUMN);
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
function sendAdminChangeNotification(PDO $pdo, string $title, array $details = [], ?int $actorUserId = null, ?string $linkUrl = null): bool {
    if (!hqAdminEmailNotificationsEnabled($pdo)) {
        return false;
    }

    $targetMenuSlug = hqNotificationTargetMenuSlug($linkUrl);
    $recipients = hqAdminNotificationRecipients($pdo, $actorUserId, $targetMenuSlug);
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
        $rowsHtml .= "<tr><td style='padding:14px 16px;border-bottom:1px solid #e2e8f0;font-weight:600;color:#0b1a2c;background:#f9fafb;width:35%;font-size:13px'>{$label}</td><td style='padding:14px 16px;border-bottom:1px solid #e2e8f0;color:#334155;font-size:13px'>{$value}</td></tr>";
    }

    $panelUrl = htmlspecialchars($linkUrl ?? admin_url('index.php?pages=dashboard'), ENT_QUOTES, 'UTF-8');
    $safeTitle = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
    $safeActorName = htmlspecialchars($actorName, ENT_QUOTES, 'UTF-8');
    $safeActorEmail = htmlspecialchars($actorEmail, ENT_QUOTES, 'UTF-8');
    $safeOccurredAt = htmlspecialchars($occurredAt, ENT_QUOTES, 'UTF-8');

    $html = "
    <div style='margin:0;padding:0;font-family:&quot;Segoe UI&quot;, -apple-system, BlinkMacSystemFont, &quot;Helvetica Neue&quot;, sans-serif;background:#f8f9fb'>
      <div style='max-width:720px;margin:0 auto;background:#ffffff;box-shadow:0 4px 32px rgba(0,0,0,0.08)'>
        <!-- Header -->
        <div style='background:linear-gradient(135deg, #0b1a2c 0%, #1e3a5f 100%);padding:40px 32px;text-align:center'>
          <div style='font-size:24px;font-weight:700;color:#ffd600;margin-bottom:8px;letter-spacing:-0.5px'>HIGH-Q</div>
          <h1 style='margin:0;font-size:24px;font-weight:700;color:#ffffff;line-height:1.3'>Admin Notification</h1>
          <p style='margin:12px 0 0;font-size:13px;color:#a8c5dd;letter-spacing:0.3px;text-transform:uppercase'>System Event Alert</p>
        </div>
        
        <!-- Content -->
        <div style='padding:40px 32px'>
          <!-- Event Title -->
          <div style='margin-bottom:28px'>
            <div style='font-size:11px;font-weight:600;color:#64748b;text-transform:uppercase;letter-spacing:1px;margin-bottom:8px'>Event Details</div>
            <h2 style='margin:0;font-size:20px;font-weight:600;color:#0b1a2c;line-height:1.4'>{$safeTitle}</h2>
          </div>
          
          <!-- Actor Info Box -->
          <div style='background:linear-gradient(135deg, #f0f7ff 0%, #f8fbff 100%);border:1px solid #d4e6f7;border-radius:12px;padding:20px;margin-bottom:28px'>
            <div style='display:grid;grid-template-columns:1fr 1fr;gap:16px;font-size:13px'>
              <div>
                <div style='font-weight:600;color:#334155;margin-bottom:4px'>Triggered By</div>
                <div style='color:#475569'>{$safeActorName}</div>
              </div>
              <div>
                <div style='font-weight:600;color:#334155;margin-bottom:4px'>Email</div>
                <div style='color:#475569;word-break:break-all'>{$safeActorEmail}</div>
              </div>
              <div style='grid-column:1/-1'>
                <div style='font-weight:600;color:#334155;margin-bottom:4px'>Timestamp</div>
                <div style='color:#475569'>{$safeOccurredAt}</div>
              </div>
            </div>
          </div>
          
          <!-- Details Table -->
          <div style='margin-bottom:28px'>
            <div style='font-size:11px;font-weight:600;color:#64748b;text-transform:uppercase;letter-spacing:1px;margin-bottom:12px'>Details</div>
            <table style='width:100%;border-collapse:collapse;border:1px solid #e2e8f0;border-radius:8px;overflow:hidden'>
              <tbody>
                {$rowsHtml}
              </tbody>
            </table>
          </div>
          
          <!-- CTA Button -->
          <div style='text-align:center;margin-bottom:20px'>
            <a href='{$panelUrl}' style='display:inline-block;padding:14px 32px;background:linear-gradient(135deg, #ffd600 0%, #e6c200 100%);color:#0b1a2c;text-decoration:none;border-radius:8px;font-weight:700;font-size:14px;letter-spacing:0.3px;transition:all 0.2s ease;box-shadow:0 4px 12px rgba(255,214,0,0.3)'>Access Admin Panel</a>
          </div>
        </div>
        
        <!-- Footer -->
        <div style='background:#f8f9fb;border-top:1px solid #e2e8f0;padding:24px 32px;text-align:center;font-size:12px;color:#64748b'>
          <p style='margin:0 0 8px'>This is an automated notification from HIGH-Q Admin System</p>
          <p style='margin:0;opacity:0.8'>Do not reply to this email. Contact support if you have questions.</p>
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
function notifyAdminChange(PDO $pdo, string $title, array $details = [], ?int $actorUserId = null, ?string $linkUrl = null): void {
    try {
        sendAdminChangeNotification($pdo, $title, $details, $actorUserId, $linkUrl);
    } catch (Throwable $e) {
        error_log('Notification send error: ' . $e->getMessage());
    }
}
