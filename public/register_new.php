<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/csrf.php';
require_once __DIR__ . '/config/functions.php';
$csrf = generateToken('registration_form');

// Get registration type from query param, default to regular
$registrationType = $_GET['type'] ?? 'regular';

// Fixed fees for POST UTME
$post_utme_form_fee = 1000;  // ₦1,000 compulsory form fee
$post_utme_tutor_fee = 8000; // ₦8,000 optional tutorial fee

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Registration - HIGH Q SOLID ACADEMY</title>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="./assets/css/public.css">
    <style>
        /* Registration type toggle */
        .registration-toggle {
            display: flex;
            justify-content: center;
            gap: 16px;
            margin: 32px auto;
            max-width: 500px;
        }

        .toggle-btn {
            flex: 1;
            padding: 12px 24px;
            border: 2px solid var(--hq-primary);
            background: none;
            color: var(--hq-primary);
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.2s;
            font-size: 15px;
        }

        .toggle-btn.active {
            background: var(--hq-primary);
            color: white;
        }

        .toggle-btn:hover:not(.active) {
            background: rgba(0, 102, 255, 0.1);
        }

        /* Form sections */
        .form-section {
            display: none;
        }

        .form-section.active {
            display: block;
        }

        /* POST UTME specific styles */
        .form-steps {
            display: flex;
            margin: 24px auto;
            gap: 4px;
            justify-content: center;
            max-width: 300px;
        }

        .step-indicator {
            width: 40px;
            height: 4px;
            background: #e5e7eb;
            border-radius: 2px;
            transition: background-color 0.3s;
        }

        .step-indicator.active {
            background: var(--hq-primary);
        }

        .step-content {
            display: none;
            animation: fadeIn 0.3s ease-in-out;
        }

        .step-content.active {
            display: block;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .passport-upload {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 12px;
            padding: 24px;
            border: 2px dashed #e5e7eb;
            border-radius: 8px;
            margin-bottom: 20px;
            background: #f9fafb;
        }

        .passport-preview {
            width: 150px;
            height: 150px;
            border-radius: 4px;
            object-fit: cover;
            display: none;
            border: 1px solid #e5e7eb;
        }

        .upload-btn {
            background: #f3f4f6;
            color: #374151;
            padding: 8px 16px;
            border-radius: 4px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            border: 1px solid #e5e7eb;
        }

        .upload-btn:hover {
            background: #e5e7eb;
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
            margin-bottom: 24px;
        }

        @media (max-width: 768px) {
            .form-grid {
                grid-template-columns: 1fr;
            }
        }

        .step-navigation {
            display: flex;
            justify-content: space-between;
            margin-top: 24px;
            padding-top: 24px;
            border-top: 1px solid #e5e7eb;
        }

        .fee-options {
            margin: 24px 0;
            padding: 16px;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            background: #f9fafb;
        }

        .fee-option {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid #f3f4f6;
        }

        .fee-option:last-child {
            border-bottom: none;
        }

        .fee-option .small-meta {
            font-size: 13px;
            color: #6b7280;
            margin-top: 4px;
        }

        .checkbox-label {
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
        }

        .checkbox-label input[type="checkbox"] {
            width: 16px;
            height: 16px;
        }
    </style>
</head>
<body class="public-body">
    <?php include __DIR__ . '/includes/header.php'; ?>

    <!-- Registration Type Toggle -->
    <div class="registration-toggle">
        <button class="toggle-btn <?= $registrationType === 'regular' ? 'active' : '' ?>" data-target="#regularForm">Regular Registration</button>
        <button class="toggle-btn <?= $registrationType === 'post-utme' ? 'active' : '' ?>" data-target="#postUtmeForm">POST UTME Registration</button>
    </div>

    <main class="public-main">
        <div class="container">
            <?php if ($registrationType === 'regular'): ?>
                <!-- Regular Registration Form -->
                <div class="form-section active" id="regularForm">
                    <!-- Your existing regular registration form content -->
                </div>
            <?php else: ?>
                <!-- POST UTME Registration Form -->
                <div class="form-section active" id="postUtmeForm">
                    <div class="card">
                        <h3>POST UTME Registration Form</h3>
                        <p class="card-desc">Complete this form to register for POST UTME preparation. Required fields are marked with *</p>

                        <form action="api/register_post_utme.php" method="POST" enctype="multipart/form-data" id="postUtmeRegistration">
                            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf) ?>">
                            
                            <!-- Step indicators -->
                            <div class="form-steps">
                                <div class="step-indicator active" data-step="1"></div>
                                <div class="step-indicator" data-step="2"></div>
                                <div class="step-indicator" data-step="3"></div>
                                <div class="step-indicator" data-step="4"></div>
                                <div class="step-indicator" data-step="5"></div>
                            </div>

                            <!-- Step 1: Personal Information -->
                            <div class="step-content active" data-step="1">
                                <h4>Personal Information</h4>
                                
                                <div class="passport-upload">
                                    <img id="passportPreview" class="passport-preview" alt="Passport preview">
                                    <label class="upload-btn">
                                        <i class="bx bx-upload"></i>
                                        Upload Passport Photo
                                        <input type="file" name="passport" accept="image/*" style="display: none" required>
                                    </label>
                                    <div class="small-meta">Upload a passport-size photo with visible face</div>
                                </div>

                                <div class="form-grid">
                                    <div class="form-group">
                                        <label>Institution *</label>
                                        <input type="text" name="institution" required>
                                    </div>
                                    <div class="form-group">
                                        <label>First Name *</label>
                                        <input type="text" name="first_name" required>
                                    </div>
                                    <div class="form-group">
                                        <label>Surname *</label>
                                        <input type="text" name="surname" required>
                                    </div>
                                    <div class="form-group">
                                        <label>Other Names</label>
                                        <input type="text" name="other_name">
                                    </div>
                                    <div class="form-group">
                                        <label>Gender *</label>
                                        <select name="gender" required>
                                            <option value="">Select Gender</option>
                                            <option value="male">Male</option>
                                            <option value="female">Female</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label>Contact Email *</label>
                                        <input type="email" name="email" required>
                                    </div>
                                    <div class="form-group">
                                        <label>Parent Phone Number *</label>
                                        <input type="tel" name="parent_phone" required>
                                    </div>
                                    <div class="form-group">
                                        <label>Address *</label>
                                        <textarea name="address" required></textarea>
                                    <?php if ($registrationType === 'regular'): ?>
                                        <!-- Regular Registration Form placeholder -->
                                        <div class="form-section active" id="regularForm">
                                            <div class="card">
                                                <h3>Regular Registration</h3>
                                                <p class="card-desc">Use the regular registration form.</p>
                                                <!-- existing regular form can be added here if needed -->
                                            </div>
                                        </div>
                                    <?php endif; ?>

                                    <?php
                                    // Always include the POST-UTME include so it's available for in-page toggling
                                    $post_registration_action = './api/register_post_utme.php';
                                    $post_utme_form_fee = $post_utme_form_fee ?? 1000;
                                    $post_utme_tutor_fee = $post_utme_tutor_fee ?? 8000;
                                    $post_csrf = $csrf;
                                    include __DIR__ . '/includes/post_utme_form.php';
                                    ?>

            passportInput?.addEventListener('change', function() {
                const file = this.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        passportPreview.src = e.target.result;
                        passportPreview.style.display = 'block';
                    }
                    reader.readAsDataURL(file);
                }
            });

            // Tutorial fee calculator
            const tutorialCheckbox = document.querySelector('input[name="include_tutorial"]');
            const totalAmountEl = document.getElementById('totalAmount');
            const formFee = <?= $post_utme_form_fee ?>;
            const tutorialFee = <?= $post_utme_tutor_fee ?>;

            tutorialCheckbox?.addEventListener('change', function() {
                const total = formFee + (this.checked ? tutorialFee : 0);
                totalAmountEl.textContent = '₦' + total.toLocaleString('en-NG', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                });
            });

            // Form submission
            form.addEventListener('submit', async function(e) {
                e.preventDefault();
                
                if (!validateStep(currentStep)) {
                    alert('Please fill in all required fields before submitting.');
                    return;
                }

                const formData = new FormData(this);
                
                try {
                    const response = await fetch(this.action, {
                        method: 'POST',
                        body: formData,
                        credentials: 'same-origin'
                    });
                    
                    const result = await response.json();
                    
                    if (result.status === 'ok') {
                        // Redirect to payment page
                        window.location.href = result.payment_url;
                    } else {
                        alert(result.message || 'Registration failed. Please try again.');
                    }
                } catch (error) {
                    alert('An error occurred. Please try again.');
                }
            });
        }
    </script>