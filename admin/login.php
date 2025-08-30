<?php
session_start();
require '../includes/db.php';
require '../includes/functions.php'; // sendEmail(), etc.

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email']);
    $password = trim($_POST['password']);

    $stmt = $pdo->prepare("SELECT u.*, r.slug AS role_slug, r.name AS role_name 
                           FROM users u 
                           LEFT JOIN roles r ON u.role_id = r.id 
                           WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        if ($user['is_active'] == 0) {
            $error = "Your account is pending approval.";
        } elseif ($user['is_active'] == 2) {
            $error = "Your account has been banned.";
        } else {
            $_SESSION['user'] = [
                'id'        => $user['id'],
                'name'      => $user['name'],
                'email'     => $user['email'],
                'role_id'   => $user['role_id'],
                'role_slug' => $user['role_slug'],
                'role_name' => $user['role_name']
            ];
            header("Location: pages/index.php");
            exit;
        }
    } else {
        $error = "Invalid email or password.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Admin Login - HIGH Q SOLID ACADEMY</title>
<link rel="stylesheet" href="../public/assets/css/theme.css">
</head>
<body>
<div class="login-card">
    <h2>Sign In</h2>
    <?php if ($error): ?>
        <div class="error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <form method="POST">
        <label>Email</label>
        <input type="email" name="email" required>
        <label>Password</label>
        <input type="password" name="password" required>
        <button type="submit">Sign In</button>
    </form>
</div>
</body>
</html>
