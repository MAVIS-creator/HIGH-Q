<?php
http_response_code(400);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>400 Bad Request - HIGH Q SOLID ACADEMY</title>
    <?php $appBase = rtrim($_ENV['APP_URL'] ?? '', '/'); if ($appBase === '') $appBase = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost'); ?>
    <link rel="stylesheet" href="<?= $appBase ?>/public/assets/css/public.css">
    <style>
        .error-container {
            min-height: 60vh;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            text-align: center;
            padding: 2rem;
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
        }
        .back-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="error-container">
        <div class="error-code">400</div>
        <h1>Bad Request</h1>
        <p class="error-message">Sorry, your browser sent a request that this server could not understand.</p>
        <a href="../public/index.php" class="back-link">← Return to Homepage</a>
    </div>

    <?php include '../includes/footer.php'; ?>
</body>
</html>