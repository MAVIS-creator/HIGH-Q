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
        <button class="toggle-btn <?= $registrationType === 'regular' ? 'active' : '' ?>" 
                onclick="window.location.href='?type=regular'">
            Regular Registration
        </button>
        <button class="toggle-btn <?= $registrationType === 'post-utme' ? 'active' : '' ?>"
                onclick="window.location.href='?type=post-utme'">
            POST UTME Registration
        </button>
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
                                    </div>
                                </div>
                            </div>

                            <!-- Step 2: Additional Information -->
                            <div class="step-content" data-step="2">
                                <h4>Additional Information</h4>
                                
                                <div class="form-grid">
                                    <div class="form-group">
                                        <label>NIN Number</label>
                                        <input type="text" name="nin_number">
                                    </div>
                                    <div class="form-group">
                                        <label>State of Origin *</label>
                                        <input type="text" name="state_of_origin" required>
                                    </div>
                                    <div class="form-group">
                                        <label>Local Government *</label>
                                        <input type="text" name="local_government" required>
                                    </div>
                                    <div class="form-group">
                                        <label>Place of Birth</label>
                                        <input type="text" name="place_of_birth">
                                    </div>
                                    <div class="form-group">
                                        <label>Nationality *</label>
                                        <input type="text" name="nationality" required>
                                    </div>
                                    <div class="form-group">
                                        <label>Religion</label>
                                        <input type="text" name="religion">
                                    </div>
                                    <div class="form-group">
                                        <label>Marital Status</label>
                                        <select name="marital_status">
                                            <option value="single">Single</option>
                                            <option value="married">Married</option>
                                            <option value="other">Other</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label>Any Disability</label>
                                        <input type="text" name="disability" placeholder="Leave blank if none">
                                    </div>
                                </div>
                            </div>

                            <!-- Step 3: JAMB and Course Details -->
                            <div class="step-content" data-step="3">
                                <h4>JAMB and Course Details</h4>
                                
                                <div class="form-grid">
                                    <div class="form-group">
                                        <label>JAMB Registration Number *</label>
                                        <input type="text" name="jamb_registration_number" required>
                                    </div>
                                    <div class="form-group">
                                        <label>JAMB Score *</label>
                                        <input type="number" name="jamb_score" required>
                                    </div>
                                    <div class="form-group">
                                        <label>Mode of Entry</label>
                                        <input type="text" name="mode_of_entry">
                                    </div>
                                    <div class="form-group">
                                        <label>First Choice Course *</label>
                                        <input type="text" name="course_first_choice" required>
                                    </div>
                                    <div class="form-group">
                                        <label>Second Choice Course</label>
                                        <input type="text" name="course_second_choice">
                                    </div>
                                    <div class="form-group">
                                        <label>First Choice Institution *</label>
                                        <input type="text" name="institution_first_choice" required>
                                    </div>
                                </div>

                                <!-- JAMB Subjects -->
                                <h4>JAMB Subjects and Scores</h4>
                                <div id="jambSubjects" class="form-grid">
                                    <div class="form-group">
                                        <label>English Language *</label>
                                        <input type="number" name="jamb_subjects[english]" required min="0" max="100">
                                    </div>
                                    <div class="form-group">
                                        <label>Mathematics</label>
                                        <input type="number" name="jamb_subjects[mathematics]" min="0" max="100">
                                    </div>
                                    <div class="form-group">
                                        <label>Third Subject</label>
                                        <input type="number" name="jamb_subjects[subject3]" min="0" max="100">
                                    </div>
                                    <div class="form-group">
                                        <label>Fourth Subject</label>
                                        <input type="number" name="jamb_subjects[subject4]" min="0" max="100">
                                    </div>
                                </div>
                            </div>

                            <!-- Step 4: Educational Background -->
                            <div class="step-content" data-step="4">
                                <h4>Educational Background</h4>
                                
                                <div class="form-grid">
                                    <div class="form-group">
                                        <label>Primary School Name</label>
                                        <input type="text" name="primary_school">
                                    </div>
                                    <div class="form-group">
                                        <label>Year Ended</label>
                                        <input type="number" name="primary_year_ended" min="2000" max="2025">
                                    </div>
                                    <div class="form-group">
                                        <label>Secondary School Name *</label>
                                        <input type="text" name="secondary_school" required>
                                    </div>
                                    <div class="form-group">
                                        <label>Year Ended *</label>
                                        <input type="number" name="secondary_year_ended" required min="2000" max="2025">
                                    </div>
                                </div>

                                <h4>O'Level Details</h4>
                                <div class="form-grid">
                                    <div class="form-group">
                                        <label>Exam Type *</label>
                                        <select name="exam_type" required>
                                            <option value="WAEC">WAEC</option>
                                            <option value="NECO">NECO</option>
                                            <option value="GCE">GCE</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label>Candidate Name *</label>
                                        <input type="text" name="candidate_name" required>
                                    </div>
                                    <div class="form-group">
                                        <label>Exam Number *</label>
                                        <input type="text" name="exam_number" required>
                                    </div>
                                    <div class="form-group">
                                        <label>Exam Year and Month *</label>
                                        <input type="text" name="exam_year_month" required>
                                    </div>
                                </div>

                                <!-- O'Level Results -->
                                <h4>O'Level Results</h4>
                                <div id="olevelSubjects" class="form-grid">
                                    <div class="form-group">
                                        <label>English Language *</label>
                                        <input type="text" name="olevel_results[english]" required placeholder="Enter grade">
                                    </div>
                                    <div class="form-group">
                                        <label>Mathematics *</label>
                                        <input type="text" name="olevel_results[mathematics]" required placeholder="Enter grade">
                                    </div>
                                    <div class="form-group">
                                        <label>Biology</label>
                                        <input type="text" name="olevel_results[biology]" placeholder="Enter grade">
                                    </div>
                                    <div class="form-group">
                                        <label>Chemistry</label>
                                        <input type="text" name="olevel_results[chemistry]" placeholder="Enter grade">
                                    </div>
                                    <div class="form-group">
                                        <label>Physics</label>
                                        <input type="text" name="olevel_results[physics]" placeholder="Enter grade">
                                    </div>
                                    <div class="form-group">
                                        <label>Government</label>
                                        <input type="text" name="olevel_results[government]" placeholder="Enter grade">
                                    </div>
                                </div>
                            </div>

                            <!-- Step 5: Parent & Payment Details -->
                            <div class="step-content" data-step="5">
                                <h4>Parent Information</h4>
                                
                                <div class="form-grid">
                                    <div class="form-group">
                                        <label>Father's Name</label>
                                        <input type="text" name="father_name">
                                    </div>
                                    <div class="form-group">
                                        <label>Father's Phone</label>
                                        <input type="tel" name="father_phone">
                                    </div>
                                    <div class="form-group">
                                        <label>Father's Email</label>
                                        <input type="email" name="father_email">
                                    </div>
                                    <div class="form-group">
                                        <label>Father's Occupation</label>
                                        <input type="text" name="father_occupation">
                                    </div>
                                    <div class="form-group">
                                        <label>Mother's Name</label>
                                        <input type="text" name="mother_name">
                                    </div>
                                    <div class="form-group">
                                        <label>Mother's Phone</label>
                                        <input type="tel" name="mother_phone">
                                    </div>
                                    <div class="form-group">
                                        <label>Mother's Occupation</label>
                                        <input type="text" name="mother_occupation">
                                    </div>
                                </div>

                                <h4>Next of Kin Details</h4>
                                <div class="form-grid">
                                    <div class="form-group">
                                        <label>Next of Kin Name *</label>
                                        <input type="text" name="next_of_kin_name" required>
                                    </div>
                                    <div class="form-group">
                                        <label>Next of Kin Phone *</label>
                                        <input type="tel" name="next_of_kin_phone" required>
                                    </div>
                                    <div class="form-group">
                                        <label>Next of Kin Email</label>
                                        <input type="email" name="next_of_kin_email">
                                    </div>
                                    <div class="form-group">
                                        <label>Next of Kin Address *</label>
                                        <textarea name="next_of_kin_address" required></textarea>
                                    </div>
                                    <div class="form-group">
                                        <label>Relationship *</label>
                                        <input type="text" name="next_of_kin_relationship" required>
                                    </div>
                                </div>

                                <!-- Payment Options -->
                                <div class="fee-options">
                                    <h4>Registration Fees</h4>
                                    <div class="fee-option">
                                        <div>
                                            <strong>Form Fee (Required)</strong>
                                            <div class="small-meta">POST UTME registration form fee</div>
                                        </div>
                                        <div>₦<?= number_format($post_utme_form_fee, 2) ?></div>
                                    </div>
                                    <div class="fee-option">
                                        <div>
                                            <strong>Tutorial Fee (Optional)</strong>
                                            <div class="small-meta">Access to POST UTME preparation classes</div>
                                        </div>
                                        <div>
                                            <label class="checkbox-label">
                                                <input type="checkbox" name="include_tutorial" value="1">
                                                ₦<?= number_format($post_utme_tutor_fee, 2) ?>
                                            </label>
                                        </div>
                                    </div>
                                    <div class="fee-option" style="border-top: 2px solid #e5e7eb; margin-top: 12px; padding-top: 16px;">
                                        <div>
                                            <strong>Total Amount</strong>
                                            <div class="small-meta">Including selected options</div>
                                        </div>
                                        <div>
                                            <strong id="totalAmount">₦<?= number_format($post_utme_form_fee, 2) ?></strong>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Navigation buttons -->
                            <div class="step-navigation">
                                <button type="button" class="btn-secondary" id="prevStep" style="display: none">Previous</button>
                                <button type="button" class="btn-primary" id="nextStep">Next</button>
                                <button type="submit" class="btn-primary" id="submitForm" style="display: none">Submit Registration</button>
                            </div>
                        </form>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <script>
        // Form navigation
        const form = document.getElementById('postUtmeRegistration');
        if (form) {
            const steps = document.querySelectorAll('.step-content');
            const indicators = document.querySelectorAll('.step-indicator');
            const nextBtn = document.getElementById('nextStep');
            const prevBtn = document.getElementById('prevStep');
            const submitBtn = document.getElementById('submitForm');
            let currentStep = 1;

            function validateStep(step) {
                const currentStepEl = document.querySelector(`.step-content[data-step="${step}"]`);
                const requiredInputs = currentStepEl.querySelectorAll('input[required], select[required], textarea[required]');
                let isValid = true;

                requiredInputs.forEach(input => {
                    if (!input.value.trim()) {
                        isValid = false;
                        input.classList.add('error');
                    } else {
                        input.classList.remove('error');
                    }
                });

                return isValid;
            }

            function showStep(step) {
                if (step > currentStep && !validateStep(currentStep)) {
                    alert('Please fill in all required fields before proceeding.');
                    return;
                }

                steps.forEach(s => s.classList.remove('active'));
                indicators.forEach(i => i.classList.remove('active'));
                
                document.querySelector(`.step-content[data-step="${step}"]`).classList.add('active');
                for (let i = 1; i <= step; i++) {
                    document.querySelector(`.step-indicator[data-step="${i}"]`).classList.add('active');
                }

                prevBtn.style.display = step === 1 ? 'none' : 'block';
                if (step === steps.length) {
                    nextBtn.style.display = 'none';
                    submitBtn.style.display = 'block';
                } else {
                    nextBtn.style.display = 'block';
                    submitBtn.style.display = 'none';
                }
                
                currentStep = step;
            }

            nextBtn?.addEventListener('click', () => {
                if (currentStep < steps.length) {
                    showStep(currentStep + 1);
                }
            });

            prevBtn?.addEventListener('click', () => {
                if (currentStep > 1) {
                    showStep(currentStep - 1);
                }
            });

            // Passport photo preview
            const passportInput = document.querySelector('input[name="passport"]');
            const passportPreview = document.getElementById('passportPreview');

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