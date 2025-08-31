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
function logAction(PDO $pdo, int $actor_id, string $action, array $meta = []): void {
    $stmt = $pdo->prepare("INSERT INTO audit_logs (actor_id, action, meta, created_at) VALUES (?, ?, ?, NOW())");
    $stmt->execute([$actor_id, $action, json_encode($meta, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)]);
}

/**
 * Send email using PHPMailer (SMTP)
 */
function sendEmail(string $to, string $subject, string $html): bool {
    $mail = new PHPMailer(true);

    try {
        // Server settings (from .env)
        $mail->isSMTP();
        $mail->Host       = $_ENV['MAIL_HOST'];
        $mail->SMTPAuth   = true;
        $mail->Username   = $_ENV['MAIL_USERNAME'];
        $mail->Password   = $_ENV['MAIL_PASSWORD'];
        $mail->SMTPSecure = $_ENV['MAIL_ENCRYPTION'] ?? PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = $_ENV['MAIL_PORT'] ?? 587;

        // Recipients
        $mail->setFrom($_ENV['MAIL_FROM_ADDRESS'], $_ENV['MAIL_FROM_NAME']);
        $mail->addAddress($to);

        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $html;

        return $mail->send();
    } catch (Exception $e) {
        error_log("Mailer Error: " . $mail->ErrorInfo);
        return false;
    }
}
