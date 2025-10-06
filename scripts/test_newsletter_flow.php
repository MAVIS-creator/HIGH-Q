<?php
// Test newsletter flow: subscribe, publish, check DB, send test email, and test unsubscribe
// Usage: php scripts/test_newsletter_flow.php

require_once __DIR__ . '/../public/config/db.php';
require_once __DIR__ . '/../admin/includes/functions.php';

// 1. Subscribe a test email
$testEmail = 'testuser+' . rand(1000,9999) . '@mailtrap.io';
$token = bin2hex(random_bytes(24));
$stmt = $pdo->prepare('INSERT INTO newsletter_subscribers (email, created_at, unsubscribe_token, token_created_at) VALUES (?, NOW(), ?, NOW())');
$stmt->execute([$testEmail, $token]);
echo "Subscribed: $testEmail\n";

// 2. Simulate publishing a post
$postTitle = 'Test Newsletter Post ' . date('Y-m-d H:i');
$postContent = 'This is a test post for newsletter delivery.';
$postExcerpt = 'Test excerpt for newsletter.';
// Find first admin user
$adminStmt = $pdo->query("SELECT id FROM users WHERE role='admin' OR role='superadmin' OR role='administrator' ORDER BY id ASC LIMIT 1");
$adminId = $adminStmt->fetchColumn();
if (!$adminId) {
    die("No admin user found. Please create an admin user in the users table.\n");
}
$stmt = $pdo->prepare('INSERT INTO posts (title, slug, excerpt, content, status, author_id, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())');
$slug = strtolower(str_replace(' ', '-', $postTitle));
$stmt->execute([$postTitle, $slug, $postExcerpt, $postContent, 'published', $adminId]);
$postId = $pdo->lastInsertId();
echo "Published post: $postTitle (ID: $postId, Author: $adminId)\n";

// 3. Send newsletter email to all subscribers
$stmt = $pdo->prepare('SELECT email, unsubscribe_token FROM newsletter_subscribers');
$stmt->execute();
$subs = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($subs as $sub) {
    $unsubscribeUrl = 'http://localhost/HIGH-Q/public/unsubscribe_newsletter.php?token=' . urlencode($sub['unsubscribe_token']);
    $postUrl = 'http://localhost/HIGH-Q/post.php?id=' . $postId;
    $html = "<p>Hi,</p><p>A new article was published: <strong>$postTitle</strong></p>";
    $html .= "<p>$postExcerpt</p>";
    $html .= "<p><a href='$postUrl'>Read the full article</a></p>";
    $html .= "<hr><p style='font-size:0.9rem;color:#666'>If you no longer wish to receive these emails, <a href='$unsubscribeUrl'>unsubscribe</a>.</p>";
    $ok = sendEmail($sub['email'], 'New article: ' . $postTitle, $html);
    echo "Sent to {$sub['email']}: " . ($ok ? 'OK' : 'FAIL') . "\n";
}

// 4. Test unsubscribe
$stmt = $pdo->prepare('DELETE FROM newsletter_subscribers WHERE email=?');
$stmt->execute([$testEmail]);
echo "Unsubscribed: $testEmail\n";

echo "Test complete.\n";
