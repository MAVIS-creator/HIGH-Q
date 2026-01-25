<?php
// admin/pending.php - Account Pending Approval Page
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Pending - HIGH Q SOLID ACADEMY</title>
    <link rel="shortcut icon" href="./assets/img/favicon.ico" type="image/x-icon">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="./assets/css/auth.css">
    <link rel="stylesheet" href="./assets/css/admin-minimal.css">
    <script src="./assets/js/device-capability.js"></script>
</head>
<body class="auth-page">
    <div class="auth-container">
        <div class="auth-card">
            <!-- Pending Icon -->
            <div class="status-icon pending">
                <i class='bx bx-time'></i>
            </div>

            <!-- Title -->
            <h1 class="auth-title">Pending Approval</h1>
            <p class="auth-subtitle">Thank you for registering with HIGH Q SOLID ACADEMY!</p>

            <!-- Info Card -->
            <div class="alert alert-warning" style="animation: none; margin-top: 24px;">
                <span class="alert-icon"><i class='bx bx-info-circle'></i></span>
                <div>
                    <strong>What's happening?</strong><br>
                    Your registration is currently under review by our administrators. This usually takes 24-48 hours.
                </div>
            </div>

            <!-- Steps -->
            <div style="margin-top: 24px;">
                <div style="display: flex; align-items: flex-start; gap: 16px; padding: 16px 0; border-bottom: 1px dashed #e5e7eb;">
                    <div style="width: 32px; height: 32px; background: linear-gradient(135deg, #22c55e, #4ade80); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: 700; flex-shrink: 0;">✓</div>
                    <div>
                        <div style="font-weight: 600; color: var(--hq-black);">Registration Submitted</div>
                        <div style="font-size: 0.85rem; color: var(--hq-gray-dark);">Your account has been created</div>
                    </div>
                </div>
                <div style="display: flex; align-items: flex-start; gap: 16px; padding: 16px 0; border-bottom: 1px dashed #e5e7eb;">
                    <div style="width: 32px; height: 32px; background: linear-gradient(135deg, var(--hq-yellow), var(--hq-yellow-light)); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: var(--hq-black); font-weight: 700; flex-shrink: 0; animation: pulse 2s ease-in-out infinite;">2</div>
                    <div>
                        <div style="font-weight: 600; color: var(--hq-black);">Under Review</div>
                        <div style="font-size: 0.85rem; color: var(--hq-gray-dark);">Admin is reviewing your request</div>
                    </div>
                </div>
                <div style="display: flex; align-items: flex-start; gap: 16px; padding: 16px 0;">
                    <div style="width: 32px; height: 32px; background: #e5e7eb; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: var(--hq-gray-dark); font-weight: 700; flex-shrink: 0;">3</div>
                    <div>
                        <div style="font-weight: 600; color: var(--hq-gray-dark);">Account Activated</div>
                        <div style="font-size: 0.85rem; color: var(--hq-gray-dark);">You'll receive an email notification</div>
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div style="margin-top: 24px; display: flex; gap: 12px;">
                <a href="login.php" class="btn-secondary" style="flex: 1; display: flex; align-items: center; justify-content: center; text-decoration: none;">
                    <i class='bx bx-log-in'></i>&nbsp; Try Login
                </a>
                <a href="index.php" class="btn-primary" style="flex: 1; display: flex; align-items: center; justify-content: center; text-decoration: none;">
                    <i class='bx bx-home'></i>&nbsp; Home
                </a>
            </div>

            <!-- Contact Info -->
            <div class="auth-divider">Need help?</div>
            
            <div class="auth-features" style="grid-template-columns: 1fr 1fr;">
                <div class="feature-item">
                    <div class="feature-icon"><i class='bx bx-envelope'></i></div>
                    <div>
                        <div class="feature-title">Email Us</div>
                        <div class="feature-desc">Check inbox/spam</div>
                    </div>
                </div>
                <div class="feature-item">
                    <div class="feature-icon"><i class='bx bx-support'></i></div>
                    <div>
                        <div class="feature-title">Support</div>
                        <div class="feature-desc">24-48 hrs response</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="auth-footer">
        © <?= date('Y') ?> HIGH Q SOLID ACADEMY LIMITED. All rights reserved.
    </footer>
</body>
</html>
