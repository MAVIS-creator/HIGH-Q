<?php
http_response_code(400);
require_once __DIR__ . '/../includes/functions.php';
$dashboardUrl = admin_url('index.php?pages=dashboard');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>400 Bad Request - Admin Panel</title>
    <link rel="stylesheet" href="<?= htmlspecialchars(admin_url('assets/css/admin.css'), ENT_QUOTES, 'UTF-8') ?>">
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
        <div class="error-code">400</div>
        <h1>Bad Request</h1>
        <p class="error-message">The request could not be processed.</p>
        <a href="<?= htmlspecialchars($dashboardUrl, ENT_QUOTES, 'UTF-8') ?>" class="back-link">← Return to Dashboard</a>
    </div>
</body>
</html>
