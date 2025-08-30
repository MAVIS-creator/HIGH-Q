<?php
require_once __DIR__ . "/../config/db.php";
require_once __DIR__ . "/../src/Models/User.php";

session_start();
$userModel = new User($pdo);
$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    $user = $userModel->findByEmail($email);

    if ($user && password_verify($password, $user['password'])) {
        if ($user['is_active'] == 0) {
            $error = "Account is inactive. Contact Admin.";
        } else {
            // Fetch role name/slug
            $stmt = $pdo->prepare("SELECT * FROM roles WHERE id = ?");
            $stmt->execute([$user['role_id']]);
            $role = $stmt->fetch();

            $_SESSION['user'] = [
                "id" => $user['id'],
                "name" => $user['name'],
                "email" => $user['email'],
                "role_slug" => $role['slug'],
                "role_name" => $role['name']
            ];

            $userModel->updateLastLogin($user['id']);
            header("Location: /admin/index.php");
            exit;
        }
    } else {
        $error = "Invalid email or password.";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin Login</title>
    <link rel="stylesheet" href="../public/assets/css/style.css">
</head>
<body>
    <div class="login-box">
        <h2>Admin Login</h2>
        <?php if ($error): ?>
            <p style="color:red;"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>
        <form method="POST">
            <input type="email" name="email" placeholder="Email" required><br><br>
            <input type="password" name="password" placeholder="Password" required><br><br>
            <button type="submit">Login</button>
        </form>
    </div>
</body>
</html>
