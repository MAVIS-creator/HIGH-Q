<?php
// Test newsletter migration: check columns, insert test subscriber, and print result
$dsn = 'mysql:host=localhost;dbname=hiighq;charset=utf8mb4';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO($dsn, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    // Check columns
    $cols = $pdo->query("SHOW COLUMNS FROM newsletter_subscribers")->fetchAll(PDO::FETCH_COLUMN);
    echo "Columns in newsletter_subscribers:\n" . implode(", ", $cols) . "\n";
    if (!in_array('unsubscribe_token', $cols) || !in_array('token_created_at', $cols)) {
        echo "ERROR: Migration columns missing!\n";
        exit(1);
    }
    // Insert test subscriber
    $email = 'testuser_' . rand(1000,9999) . '@example.com';
    $token = bin2hex(random_bytes(24));
    $stmt = $pdo->prepare('INSERT INTO newsletter_subscribers (email, created_at, unsubscribe_token, token_created_at) VALUES (?, NOW(), ?, NOW())');
    $stmt->execute([$email, $token]);
    echo "Inserted test subscriber: $email with token $token\n";
    // Fetch and print
    $row = $pdo->query("SELECT * FROM newsletter_subscribers WHERE email='$email'")->fetch(PDO::FETCH_ASSOC);
    print_r($row);
    // Clean up
    $pdo->prepare('DELETE FROM newsletter_subscribers WHERE email=?')->execute([$email]);
    echo "Test subscriber deleted.\n";
} catch (Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
