<?php
require __DIR__ . '/../vendor/autoload.php';
use Dotenv\Dotenv;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$root = dirname(__DIR__);
$dotenv = Dotenv::createImmutable($root);
$dotenv->safeLoad();

$mail = new PHPMailer(true);
try {
    $mail->isSMTP();
    $mail->Host = $_ENV['MAIL_HOST'] ?? getenv('MAIL_HOST');
    $mail->SMTPAuth = true;
    $mail->Username = $_ENV['MAIL_USERNAME'] ?? getenv('MAIL_USERNAME');
    $mail->Password = $_ENV['MAIL_PASSWORD'] ?? getenv('MAIL_PASSWORD');
    $mail->SMTPSecure = $_ENV['MAIL_ENCRYPTION'] ?? PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = $_ENV['MAIL_PORT'] ?? 587;

    $mail->SMTPOptions = [
        'ssl' => [
            'verify_peer' => false,
            'verify_peer_name' => false,
            'allow_self_signed' => true,
        ],
    ];

    $debug = true;
    if ($debug) {
        $mail->SMTPDebug = 2;
        $mail->Debugoutput = function($str, $level) {
            echo "[" . date('c') . "] [level={$level}] {$str}\n";
        };
    }

    $mail->setFrom($_ENV['MAIL_FROM_ADDRESS'] ?? 'no-reply@example.com', $_ENV['MAIL_FROM_NAME'] ?? 'HIGH-Q');
    $mail->addAddress($_ENV['MAIL_USERNAME'] ?? 'you@example.com');

    $mail->isHTML(true);
    $mail->Subject = 'HIGH-Q SMTP test noverify';
    $mail->Body    = 'This is a test email from HIGH-Q test script (no verify).';

    $ok = $mail->send();
    echo $ok ? "Mail sent successfully\n" : "Mail send returned false\n";
} catch (Exception $e) {
    echo "Mail exception: " . ($mail->ErrorInfo ?? $e->getMessage()) . "\n";
}
