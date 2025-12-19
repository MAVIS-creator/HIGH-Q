<?php
http_response_code(404);
require_once __DIR__ . '/../includes/functions.php';
$home = admin_url('index.php');
$logo = app_url('assets/images/hq-logo.jpeg');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - Page Not Found | HIGH Q Solid Academy</title>
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #ffd600 0%, #f6c23a 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .error-container {
            background: #fff;
            border-radius: 20px;
            padding: 3rem;
            max-width: 600px;
            width: 100%;
            text-align: center;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
        }

        .error-icon {
            font-size: 120px;
            color: #ffd600;
            margin-bottom: 1.5rem;
            animation: bounce 2s infinite;
        }

        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-20px); }
        }

        h1 {
            font-size: 3rem;
            color: #111;
            margin-bottom: 0.5rem;
            font-weight: 800;
        }

        .error-code {
            font-size: 6rem;
            font-weight: 900;
            color: #ffd600;
            line-height: 1;
            margin-bottom: 1rem;
        }

        p {
            color: #666;
            font-size: 1.1rem;
            margin-bottom: 2rem;
            line-height: 1.6;
        }

        .error-actions {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
        }

        .btn {
            padding: 12px 28px;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 600;
            font-size: 1rem;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background: linear-gradient(180deg, #ffd24d, #f6c23a);
            color: #111;
            box-shadow: 0 4px 15px rgba(246, 194, 58, 0.3);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(246, 194, 58, 0.4);
        }

        .btn-secondary {
            background: #fff;
            color: #666;
            border: 2px solid #e0e0e0;
        }

        .btn-secondary:hover {
            background: #f8f8f8;
            border-color: #ccc;
        }

        .logo {
            margin-bottom: 2rem;
        }

        .logo img {
            height: 60px;
            width: auto;
        }

        @media (max-width: 768px) {
            .error-container {
                padding: 2rem;
            }

            .error-code {
                font-size: 4rem;
            }

            h1 {
                font-size: 2rem;
            }

            p {
                font-size: 1rem;
            }

            .error-actions {
                flex-direction: column;
            }

            .btn {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="logo">
            <img src="<?= htmlspecialchars($logo) ?>" alt="HIGH Q Solid Academy">
        </div>
        
        <i class='bx bx-search-alt error-icon'></i>
        
        <div class="error-code">404</div>
        <h1>Page Not Found</h1>
        <p>
            Oops! The admin page you're looking for doesn't exist or has been moved.
            This might be because the page was removed, renamed, or you don't have permission to access it.
        </p>
        
        <div class="error-actions">
            <a href="<?= htmlspecialchars($home) ?>" class="btn btn-primary">
                <i class='bx bx-home'></i>
                Back to Dashboard
            </a>
            <a href="javascript:history.back()" class="btn btn-secondary">
                <i class='bx bx-arrow-back'></i>
                Go Back
            </a>
        </div>
    </div>
</body>
</html>