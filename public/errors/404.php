<?php
http_response_code(404);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 Not Found - HIGH Q SOLID ACADEMY</title>
    <link rel="stylesheet" href="/HIGH-Q/public/assets/css/public.css">
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
        <div class="error-code">404</div>
        <h1>Page Not Found</h1>
        <p class="error-message">The page you're looking for does not exist or has been moved.</p>
        <a href="/HIGH-Q/public/index.php" class="back-link">← Return to Homepage</a>
    </div>

    <?php include '../includes/footer.php'; ?>
</body>
</html>