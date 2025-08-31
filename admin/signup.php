<?php
require '/includes/db.php';
require '/includes/functions.php'; // sendEmail()

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name     = trim($_POST['name']);
    $phone    = trim($_POST['phone']);
    $email    = trim($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    // Insert as pending
    $stmt = $pdo->prepare("
        INSERT INTO users (role_id, name, email, password, is_active)
        VALUES (NULL, ?, ?, ?, 0)
    ");
    $stmt->execute([$name, $email, $password]);

    // Send pending approval email
    sendEmail($email, "Account Pending Approval", "
        Hi $name,<br><br>
        Thanks for registering with HIGH Q SOLID ACADEMY.<br>
        Your account is pending admin approval.<br>
        You’ll receive another email when approved.
    ");

    // Redirect to pending page
    header("Location: pending.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Sign Up - HIGH Q SOLID ACADEMY</title>
<link rel="stylesheet" href="../public/assets/css/theme.css">
<style>
    body {
        font-family: Arial, sans-serif;
        background: linear-gradient(135deg, #fcbf49, #d62828);
        display: flex;
        align-items: center;
        justify-content: center;
        height: 100vh;
    }
    .signup-card {
        background: #fff;
        padding: 2rem;
        border-radius: 8px;
        width: 350px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.2);
    }
    h2 {
        color: #d62828;
        margin-bottom: 1rem;
        text-align: center;
    }
    label {
        font-weight: bold;
        color: #000;
    }
    input {
        width: 100%;
        padding: 0.6rem;
        margin: 0.4rem 0 1rem;
        border: 1px solid #ccc;
        border-radius: 4px;
    }
    button {
        background: #d62828;
        color: #fff;
        border: none;
        padding: 0.8rem;
        width: 100%;
        font-size: 1rem;
        border-radius: 4px;
        cursor: pointer;
    }
    button:hover {
        background: #fcbf49;
        color: #000;
    }
    .error {
        background: #ffdddd;
        padding: 0.5rem;
        margin-bottom: 1rem;
        border-left: 4px solid #d62828;
    }
</style>
</head>
<body>

<div class="signup-card">
    <h2>Create Account</h2>
    <?php if (!empty($errors)): ?>
        <div class="error">
            <?php foreach ($errors as $err) echo "<p>$err</p>"; ?>
        </div>
    <?php endif; ?>
    <form method="POST">
        <label>Name</label>
        <input type="text" name="name" placeholder="John Doe" required>

        <label>Phone Number</label>
        <input type="text" name="phone" placeholder="+234 801 234 5678">

        <label>Email</label>
        <input type="email" name="email" placeholder="you@example.com" required>

        <label>Password</label>
        <input type="password" name="password" required>

        <button type="submit">Create Account</button>
    </form>
</div>

</body>
</html>
