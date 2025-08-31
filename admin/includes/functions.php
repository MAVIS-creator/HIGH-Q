<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Autoload PHPMailer classes (via Composer)
require_once __DIR__ . '/../../vendor/autoload.php';

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
        // Server settings
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';     // change if using another provider
        $mail->SMTPAuth   = true;
        $mail->Username   = 'your-email@gmail.com'; // your Gmail
        $mail->Password   = 'your-app-password';   // use App Password, not Gmail login
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        // Recipients
        $mail->setFrom('no-reply@yourdomain.com', 'HIGH Q SOLID ACADEMY');
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
