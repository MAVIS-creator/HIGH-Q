<?php
=
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Account Pending Approval - HIGH Q SOLID ACADEMY</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<style>
    :root {
        --primary-red: #d62828;
        --accent-yellow: #fcbf49;
        --dark-black: #000000;
        --pure-white: #ffffff;
    }
    body {
        margin: 0;
        font-family: Arial, sans-serif;
        background: linear-gradient(135deg, var(--accent-yellow), var(--primary-red));
        display: flex;
        align-items: center;
        justify-content: center;
        height: 100vh;
        color: var(--pure-white);
    }
    .pending-card {
        background: var(--pure-white);
        color: var(--dark-black);
        padding: 2rem;
        border-radius: 10px;
        max-width: 420px;
        text-align: center;
        box-shadow: 0 4px 20px rgba(0,0,0,0.2);
        animation: fadeIn 0.6s ease-in-out;
    }
    .pending-card h1 {
        color: var(--primary-red);
        margin-bottom: 0.5rem;
    }
    .pending-card p {
        margin: 0.5rem 0 1.5rem;
        line-height: 1.5;
    }
    .logo {
        width: 60px;
        height: 60px;
        margin-bottom: 1rem;
    }
    .btn-home {
        display: inline-block;
        padding: 0.7rem 1.5rem;
        background: var(--primary-red);
        color: var(--pure-white);
        border-radius: 5px;
        text-decoration: none;
        font-weight: bold;
        transition: background 0.3s ease;
    }
    .btn-home:hover {
        background: var(--accent-yellow);
        color: var(--dark-black);
    }
    @keyframes fadeIn {
        from {opacity: 0; transform: translateY(20px);}
        to {opacity: 1; transform: translateY(0);}
    }
</style>
</head>
<body>

<div class="pending-card">
    <img src="../public/assets/images/logo.png" alt="Academy Logo" class="logo">
    <h1>Account Pending Approval</h1>
    <p>
        Thank you for creating an account with <strong>HIGH Q SOLID ACADEMY</strong>.<br>
        Your registration has been received and is currently under review by our administrators.
    </p>
    <p>
        You will receive an email once your account has been approved.<br>
        Please check your inbox (and spam folder) for updates.
    </p>
    <a href="../public/landing.php" class="btn-home">Return to Home</a>
</div>

</body>
</html>
