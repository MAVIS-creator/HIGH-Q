<?php
http_response_code(503);
?>
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>503 Service Unavailable - Admin Panel</title>
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
        .error-container{min-height:60vh;display:flex;align-items:center;justify-content:center;padding:24px;text-align:center;margin-left:var(--sidebar-width)}
        .error-card{max-width:720px;background:#fff;padding:28px;border-radius:10px;box-shadow:0 8px 30px rgba(0,0,0,0.08)}
        .error-code{font-size:3.5rem;color:#d32f2f;font-weight:700;margin-bottom:8px}
    </style>
</head>
<body>
<main class="error-container">
    <div class="error-card">
        <div class="error-code">503</div>
        <h1>Service temporarily unavailable</h1>
        <p>The admin area is currently offline for maintenance. Try again later or contact support.</p>
        <p><a href="./login.php">Admin login</a></p>
    </div>
</main>
</body>
</html>
