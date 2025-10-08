<?php
require_once __DIR__ . '/config/db.php';

$email = trim($_GET['email'] ?? $_POST['email'] ?? '');
$token = trim($_GET['token'] ?? $_POST['token'] ?? '');

// Accept either token alone or email+token for safety
if (!$token) {
    // show a simple form to paste token/email
    ?>
    <!doctype html>
    <html><head><meta charset="utf-8"><title>Unsubscribe</title>
    <link rel="stylesheet" href="./assets/css/responsive-utils.css">
    </head><body>
    <h2>Unsubscribe from newsletter</h2>
    <form method="post">
    <label>Email (optional): <input type="email" name="email" value="<?= htmlspecialchars($email) ?>"></label><br>
    <label>Token: <input type="text" name="token"></label><br>
    <button type="submit">Unsubscribe</button>
    </form>
    </body></html>
    <?php
    exit;
}

if ($email) {
    $stmt = $pdo->prepare('SELECT id FROM newsletter_subscribers WHERE email=? AND unsubscribe_token=? LIMIT 1');
    $stmt->execute([$email, $token]);
    $id = $stmt->fetchColumn();
} else {
    $stmt = $pdo->prepare('SELECT id FROM newsletter_subscribers WHERE unsubscribe_token=? LIMIT 1');
    $stmt->execute([$token]);
    $id = $stmt->fetchColumn();
}

if ($id) {
    $del = $pdo->prepare('DELETE FROM newsletter_subscribers WHERE id=?');
    $del->execute([$id]);
    echo "You've been unsubscribed.";
} else {
    echo "Invalid unsubscribe link or token.";
}
