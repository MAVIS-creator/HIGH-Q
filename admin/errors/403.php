<?php
http_response_code(403);
// Minimal standalone admin 403 page. This file is intentionally self-contained
// so it can be included from early bootstrap code (before header/footer are safe).
?><!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>403 Forbidden - Admin</title>
    <link rel="stylesheet" href="/admin/assets/css/admin.css">
    <style>body{font-family:Arial,Helvetica,sans-serif;background:#fafafa;color:#111;margin:0;padding:40px} .center{max-width:820px;margin:40px auto;text-align:center} .code{font-size:5rem;color:#d33;font-weight:700} .msg{font-size:1.1rem;color:#333}</style>
</head>
<body>
  <div class="center">
    <div class="code">403</div>
    <h1>Access Forbidden</h1>
    <p class="msg">You do not have permission to view this admin page.</p>
    <p><a href="/admin/login.php">Login to Admin Panel</a></p>
  </div>
</body>
</html>
<?php
http_response_code(403);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>403 Forbidden - Admin Panel</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
        .error-container {
            min-height: 60vh;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            text-align: center;
            padding: 2rem;
            margin-left: var(--sidebar-width);
        }
        .error-code {
            font-size: 4rem;
            color: var(--hq-red);
            font-weight: bold;
        }
        .error-message {
            margin: 1rem 0;
            font-size: 1.2rem;
        }
        .back-link {
            margin-top: 1rem;
            color: var(--hq-yellow);
            text-decoration: none;
            padding: 0.5rem 1rem;
            border-radius: 4px;
            background: var(--hq-black);
        }
        .back-link:hover {
            background: var(--hq-yellow);
            color: var(--hq-black);
        }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="error-code">403</div>
        <h1>Access Forbidden</h1>
        <p class="error-message">You don't have permission to access this resource.</p>
        <a href="./index.php" class="back-link">← Return to Dashboard</a>
    </div>
</body>
</html>