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

// --- HQ URL / file helpers (small, safe helpers used across admin/public code)
if (!function_exists('hq_app_base')) {
    function hq_app_base(): string {
        static $b = null;
        if ($b !== null) return $b;
        $env = $_ENV['APP_URL'] ?? getenv('APP_URL') ?: '';
        if (!empty($env)) { $b = rtrim($env, '/'); return $b; }
        // Fallback to request-derived base when running under webserver
        if (!empty($_SERVER['HTTP_HOST'])) {
            $proto = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $b = $proto . '://' . $_SERVER['HTTP_HOST'];
            return $b;
        }
        // CLI / unknown: default to localhost
        $b = 'http://localhost';
        return $b;
    }
}

if (!function_exists('hq_project_root')) {
    function hq_project_root(): string {
        // admin/includes is two levels deep from project root
        return realpath(__DIR__ . '/../../') ?: __DIR__ . '/../../';
    }
}

if (!function_exists('hq_public_url')) {
    function hq_public_url(?string $stored): string {
        if (empty($stored)) return '';
        $s = (string)$stored;
        if (preg_match('#^https?://#i', $s)) return $s;
        // keep relative paths (./ ../ no leading slash) unchanged
        if ($s[0] !== '/') return $s;
        $appPath = parse_url(hq_app_base(), PHP_URL_PATH) ?? '';
        if ($appPath !== '' && strpos($s, $appPath) === 0) {
            $rel = substr($s, strlen($appPath));
        } else {
            $rel = $s;
        }
        $rel = '/' . ltrim($rel, '/');
        return rtrim(hq_app_base(), '/') . $rel;
    }
}

if (!function_exists('hq_fs_path_from_stored')) {
    /**
     * Map a stored path value to either a remote URL or a filesystem path inside the project.
     * Returns: ['type'=>'remote','url'=>...] | ['type'=>'file','path'=>...] | ['type'=>'notfound']
     */
    function hq_fs_path_from_stored(?string $stored): array {
        if (empty($stored)) return ['type'=>'notfound'];
        $s = (string)$stored;
        if (preg_match('#^https?://#i', $s)) return ['type'=>'remote','url'=>$s];
        // Absolute filesystem (Windows drive) or absolute path that exists
        if (preg_match('#^[A-Za-z]:\\#', $s) && is_file($s)) return ['type'=>'file','path'=>$s];
        if (strpos($s, '/') === 0 && is_file($s)) return ['type'=>'file','path'=>$s];

        $projectRoot = hq_project_root();
        $appPath = parse_url(hq_app_base(), PHP_URL_PATH) ?? '';
        $candidateRel = $s;
        if ($appPath !== '' && strpos($candidateRel, $appPath) === 0) {
            $candidateRel = substr($candidateRel, strlen($appPath));
        }
        $candidateRel = ltrim($candidateRel, '/');
        $candidateFs = $projectRoot . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $candidateRel);
        if (is_file($candidateFs)) return ['type'=>'file','path'=>$candidateFs];
        $candidateFs2 = $projectRoot . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $candidateRel);
        if (is_file($candidateFs2)) return ['type'=>'file','path'=>$candidateFs2];
        return ['type'=>'notfound'];
    }
}

// surcharge helper and createPayment helper (admin-side)
if (!function_exists('hq_compute_surcharge')) {
    function hq_compute_surcharge(float $amount): float {
        // Random pct between 0.5% and 3.0% of the amount
        $minPct = 0.005; $maxPct = 0.03;
        $rand = mt_rand() / mt_getrandmax();
        $pct = $minPct + ($rand * ($maxPct - $minPct));
        $s = round($amount * $pct, 2);
        // enforce bounds
        if ($s < 0.01) $s = 0.01;
        if ($s > 167.54) $s = 167.54;
        return $s;
    }
}

if (!function_exists('hq_create_payment')) {
    function hq_create_payment(PDO $pdo, $studentId, float $amount, string $method, string $reference, $extraMeta = null) {
        // compute surcharge and final amount
        $surcharge = hq_compute_surcharge($amount);
        $final = round($amount + $surcharge, 2);
        $meta = is_string($extraMeta) ? @json_decode($extraMeta, true) : (is_array($extraMeta) ? $extraMeta : []);
        if (!is_array($meta)) $meta = [];
        $meta['original_amount'] = round($amount,2);
        $meta['surcharge'] = $surcharge;
        $metaJson = json_encode($meta, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        try {
            $ins = $pdo->prepare('INSERT INTO payments (student_id, amount, payment_method, reference, status, created_at, metadata) VALUES (?, ?, ?, ?, "pending", NOW(), ?)');
            $ins->execute([$studentId, $final, $method, $reference, $metaJson]);
            return (int)$pdo->lastInsertId();
        } catch (Throwable $e) {
            // fallback: replicate insertPaymentWithFallback logic (lock and explicit id)
            try {
                $pdo->beginTransaction();
                $pdo->exec('LOCK TABLES payments WRITE');
                $row = $pdo->query('SELECT MAX(id) AS m FROM payments')->fetch(PDO::FETCH_ASSOC);
                $next = (int)($row['m'] ?? 0) + 1;
                $ins2 = $pdo->prepare('INSERT INTO payments (id, student_id, amount, payment_method, reference, status, created_at, metadata) VALUES (?, ?, ?, ?, ?, "pending", NOW(), ?)');
                $ins2->execute([$next, $studentId, $final, $method, $reference, $metaJson]);
                $pdo->exec('UNLOCK TABLES');
                $pdo->commit();
                return $next;
            } catch (Throwable $_) {
                try { $pdo->exec('UNLOCK TABLES'); } catch (Throwable $__){ }
                if ($pdo->inTransaction()) $pdo->rollBack();
                throw $e;
            }
        }
    }
}
