<?php
function logAction(PDO $pdo, int $actor_id, string $action, array $meta = []): void {
    $stmt = $pdo->prepare("INSERT INTO audit_logs (actor_id, action, meta, created_at) VALUES (?, ?, ?, NOW())");
    $stmt->execute([$actor_id, $action, json_encode($meta, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)]);
}

// Minimal mail helper (swap for your mailer later)
function sendEmail(string $to, string $subject, string $html): void {
    $headers  = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8\r\n";
    $headers .= "From: HIGH Q SOLID ACADEMY <no-reply@yourdomain.com>\r\n";
    @mail($to, $subject, $html, $headers);
}
