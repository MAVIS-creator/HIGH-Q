<?php
http_response_code(401);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>401 Unauthorized - HIGH Q SOLID ACADEMY</title>
    <link rel="stylesheet" href="/assets/css/public.css">
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
    <?php include __DIR__ . '/../includes/header.php'; ?>
    
    <div class="error-container">
        <div class="error-code">401</div>
        <h1>Unauthorized Access</h1>
        <p class="error-message">You need to be authenticated to access this resource.</p>
        <a href="../public/index.php" class="back-link">← Return to Homepage</a>
    </div>

    <?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>