<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/functions.php';
require_once __DIR__ . '/config/csrf.php';

$csrf = generateToken('post_utme_form');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>POST UTME Registration - HIGH Q SOLID ACADEMY</title>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="./assets/css/public.css">
    <style>
        .form-toggle {
            display: flex;
            gap: 20px;
            margin-bottom: 30px;
            justify-content: center;
        }
        
        .form-toggle button {
            padding: 10px 20px;
            border: none;
            background: none;
            font-size: 16px;
            color: #666;
            cursor: pointer;
            border-bottom: 2px solid transparent;
        }
        
        .form-toggle button.active {
            color: var(--hq-primary);
            border-bottom-color: var(--hq-primary);
        }
        
        .registration-form {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .form-section {
            display: none;
            margin-bottom: 30px;
        }
        
        .form-section.active {
            display: block;
        }
        
        .form-nav {
            display: flex;
            justify-content: space-between;
            margin-top: 20px;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-row {
            display: flex;
            gap: 20px;
            margin-bottom: 15px;
        }
        
        .form-col {
            flex: 1;
        }
        
        label {
            display: block;
            margin-bottom: 5px;
            color: #333;
        }
        
        input[type="text"],
        input[type="email"],
        input[type="tel"],
        input[type="file"],
        select {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }
        
        .nav-dots {
            display: flex;
            justify-content: center;
            gap: 8px;
            margin: 20px 0;
        }
        
        .nav-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: #ddd;
            cursor: pointer;
        }
        
        .nav-dot.active {
            background: var(--hq-primary);
        }
        
        .section-title {
            color: var(--hq-primary);
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #eee;
        }
        
        .form-notice {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
            font-size: 14px;
            color: #666;
        }
        
        .payment-options {
            margin: 20px 0;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 4px;
        }
        
        .checkbox-group {
            margin: 10px 0;
        }
    </style>
</head>
<body>
    <header class="main-header">
        <div class="container">
            <h1>Student Registration</h1>
        </div>
    </header>

    <main class="public-main">
        <div class="container">
            <div class="form-toggle">
                <button type="button" class="active" data-form="regular">Regular Registration</button>
                <button type="button" data-form="post-utme">POST UTME Registration</button>
            </div>

            <form id="postUtmeForm" class="registration-form" method="post" action="process_post_utme.php" enctype="multipart/form-data" style="display: none;">
                <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf) ?>">
                
                <!-- Form sections -->
                <div class="form-section active" data-section="1">
                    <h3 class="section-title">Personal Information</h3>
                    <div class="form-row">
                        <div class="form-col">
                            <label>Institution Name</label>
                            <input type="text" name="institution_name" required>
                        </div>
                        <div class="form-col">
                            <label>First Name</label>
                            <input type="text" name="first_name" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-col">
                            <label>Surname</label>
                            <input type="text" name="surname" required>
                        </div>
                        <div class="form-col">
                            <label>Other Name</label>
                            <input type="text" name="other_name">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-col">
                            <label>Gender</label>
                            <select name="gender" required>
                                <option value="">Select Gender</option>
                                <option value="male">Male</option>
                                <option value="female">Female</option>
                            </select>
                        </div>
                        <div class="form-col">
                            <label>Email Address</label>
                            <input type="email" name="email" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Address</label>
                        <input type="text" name="address" required>
                    </div>
                    <div class="form-row">
                        <div class="form-col">
                            <label>Parents Phone Number</label>
                            <input type="tel" name="parents_phone" required>
                        </div>
                        <div class="form-col">
                            <label>NIN Number</label>
                            <input type="text" name="nin_number">
                        </div>
                    </div>
                </div>

                <div class="form-section" data-section="2">
                    <h3 class="section-title">Additional Information</h3>
                    <div class="form-row">
                        <div class="form-col">
                            <label>State of Origin</label>
                            <input type="text" name="state_of_origin" required>
                        </div>
                        <div class="form-col">
                            <label>Local Government</label>
                            <input type="text" name="local_government" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-col">
                            <label>Place of Birth</label>
                            <input type="text" name="place_of_birth" required>
                        </div>
                        <div class="form-col">
                            <label>Nationality</label>
                            <input type="text" name="nationality" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-col">
                            <label>Religion</label>
                            <input type="text" name="religion">
                        </div>
                        <div class="form-col">
                            <label>Marital Status</label>
                            <select name="marital_status">
                                <option value="single">Single</option>
                                <option value="married">Married</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Disability (if any)</label>
                        <input type="text" name="disability">
                    </div>
                    <div class="form-group">
                        <label>Mode of Entry</label>
                        <input type="text" name="mode_of_entry" required>
                    </div>
                </div>

                <div class="form-section" data-section="3">
                    <h3 class="section-title">JAMB Details</h3>
                    <div class="form-row">
                        <div class="form-col">
                            <label>JAMB Registration Number</label>
                            <input type="text" name="jamb_registration_number" required>
                        </div>
                        <div class="form-col">
                            <label>JAMB Score</label>
                            <input type="number" name="jamb_score" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <h4>JAMB Subjects and Scores</h4>
                        <div id="jambSubjects">
                            <div class="form-row">
                                <div class="form-col">
                                    <label>Subject</label>
                                    <input type="text" name="jamb_subjects[]" required>
                                </div>
                                <div class="form-col">
                                    <label>Score</label>
                                    <input type="number" name="jamb_scores[]" required>
                                </div>
                            </div>
                        </div>
                        <button type="button" class="btn-secondary" onclick="addJambSubject()">Add Subject</button>
                    </div>
                </div>

                <div class="form-section" data-section="4">
                    <h3 class="section-title">Course Preferences</h3>
                    <div class="form-row">
                        <div class="form-col">
                            <label>First Choice Course</label>
                            <input type="text" name="course_first_choice" required>
                        </div>
                        <div class="form-col">
                            <label>Second Choice Course</label>
                            <input type="text" name="course_second_choice">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>First Choice Institution</label>
                        <input type="text" name="institution_first_choice" required>
                    </div>
                </div>

                <div class="form-section" data-section="5">
                    <h3 class="section-title">Education History</h3>
                    <div class="form-row">
                        <div class="form-col">
                            <label>Primary School Name</label>
                            <input type="text" name="primary_school" required>
                        </div>
                        <div class="form-col">
                            <label>Year Ended</label>
                            <input type="number" name="primary_year_ended" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-col">
                            <label>Secondary School Name</label>
                            <input type="text" name="secondary_school" required>
                        </div>
                        <div class="form-col">
                            <label>Year Ended</label>
                            <input type="number" name="secondary_year_ended" required>
                        </div>
                    </div>
                </div>

                <div class="form-section" data-section="6">
                    <h3 class="section-title">O'Level Details</h3>
                    <div class="form-row">
                        <div class="form-col">
                            <label>Exam Type</label>
                            <select name="exam_type" required>
                                <option value="WAEC">WAEC</option>
                                <option value="NECO">NECO</option>
                                <option value="GCE">GCE</option>
                            </select>
                        </div>
                        <div class="form-col">
                            <label>Candidate Name</label>
                            <input type="text" name="candidate_name" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-col">
                            <label>Exam Number</label>
                            <input type="text" name="exam_number" required>
                        </div>
                        <div class="form-col">
                            <label>Exam Year and Month</label>
                            <input type="text" name="exam_year_month" required>
                        </div>
                    </div>
                    <div id="olevelSubjects">
                        <div class="form-row">
                            <div class="form-col">
                                <label>Subject</label>
                                <input type="text" name="olevel_subjects[]" required>
                            </div>
                            <div class="form-col">
                                <label>Grade</label>
                                <input type="text" name="olevel_grades[]" required>
                            </div>
                        </div>
                    </div>
                    <button type="button" class="btn-secondary" onclick="addOlevelSubject()">Add Subject</button>
                </div>

                <div class="form-section" data-section="7">
                    <h3 class="section-title">Document Upload</h3>
                    <div class="form-group">
                        <label>Passport Photograph</label>
                        <input type="file" name="passport_photo" accept="image/*" required>
                        <small>Upload a recent passport photograph (JPG/PNG, max 2MB)</small>
                    </div>
                </div>

                <div class="form-section" data-section="8">
                    <h3 class="section-title">Payment Options</h3>
                    <div class="payment-options">
                        <div class="checkbox-group">
                            <input type="checkbox" id="formFee" name="form_fee" checked disabled>
                            <label for="formFee">Form Fee (₦1,000) - Compulsory</label>
                        </div>
                        <div class="checkbox-group">
                            <input type="checkbox" id="tutorFee" name="tutor_fee">
                            <label for="tutorFee">Tutor Fee (₦8,000) - Optional</label>
                        </div>
                        <div class="form-notice">
                            Total Amount: ₦<span id="totalAmount">1,000</span>
                        </div>
                    </div>
                </div>

                <div class="nav-dots">
                    <!-- Will be populated by JavaScript -->
                </div>

                <div class="form-nav">
                    <button type="button" class="btn-secondary" id="prevBtn">Previous</button>
                    <button type="button" class="btn-primary" id="nextBtn">Next</button>
                    <button type="submit" class="btn-primary" id="submitBtn" style="display: none;">Submit Registration</button>
                </div>
            </form>
        </div>
    </main>

    <script>
        // Form navigation
        let currentSection = 1;
        const totalSections = document.querySelectorAll('.form-section').length;
        
        function updateNavDots() {
            const dotsContainer = document.querySelector('.nav-dots');
            dotsContainer.innerHTML = '';
            for (let i = 1; i <= totalSections; i++) {
                const dot = document.createElement('div');
                dot.className = `nav-dot ${i === currentSection ? 'active' : ''}`;
                dot.onclick = () => goToSection(i);
                dotsContainer.appendChild(dot);
            }
        }
        
        function showSection(sectionNumber) {
            document.querySelectorAll('.form-section').forEach(section => {
                section.classList.remove('active');
            });
            document.querySelector(`[data-section="${sectionNumber}"]`).classList.add('active');
            
            // Update buttons
            const prevBtn = document.getElementById('prevBtn');
            const nextBtn = document.getElementById('nextBtn');
            const submitBtn = document.getElementById('submitBtn');
            
            prevBtn.style.display = sectionNumber === 1 ? 'none' : 'block';
            nextBtn.style.display = sectionNumber === totalSections ? 'none' : 'block';
            submitBtn.style.display = sectionNumber === totalSections ? 'block' : 'none';
            
            updateNavDots();
        }
        
        function goToSection(sectionNumber) {
            if (validateCurrentSection()) {
                currentSection = sectionNumber;
                showSection(currentSection);
            }
        }
        
        document.getElementById('prevBtn').onclick = () => {
            if (currentSection > 1) {
                currentSection--;
                showSection(currentSection);
            }
        };
        
        document.getElementById('nextBtn').onclick = () => {
            if (validateCurrentSection() && currentSection < totalSections) {
                currentSection++;
                showSection(currentSection);
            }
        };
        
        // Form toggle
        document.querySelectorAll('.form-toggle button').forEach(button => {
            button.addEventListener('click', () => {
                document.querySelectorAll('.form-toggle button').forEach(btn => btn.classList.remove('active'));
                button.classList.add('active');
                
                if (button.dataset.form === 'regular') {
                    window.location.href = 'register.php';
                } else {
                    document.getElementById('postUtmeForm').style.display = 'block';
                }
            });
        });
        
        // Payment calculation
        document.getElementById('tutorFee').addEventListener('change', function() {
            const formFee = 1000;
            const tutorFee = 8000;
            const total = formFee + (this.checked ? tutorFee : 0);
            document.getElementById('totalAmount').textContent = total.toLocaleString();
        });
        
        // Dynamic subject fields
        function addJambSubject() {
            const container = document.getElementById('jambSubjects');
            const newRow = container.children[0].cloneNode(true);
            newRow.querySelectorAll('input').forEach(input => input.value = '');
            container.appendChild(newRow);
        }
        
        function addOlevelSubject() {
            const container = document.getElementById('olevelSubjects');
            const newRow = container.children[0].cloneNode(true);
            newRow.querySelectorAll('input').forEach(input => input.value = '');
            container.appendChild(newRow);
        }
        
        // Form validation
        function validateCurrentSection() {
            const currentSectionEl = document.querySelector(`.form-section[data-section="${currentSection}"]`);
            const requiredFields = currentSectionEl.querySelectorAll('[required]');
            let isValid = true;
            
            requiredFields.forEach(field => {
                if (!field.value) {
                    field.style.borderColor = 'red';
                    isValid = false;
                } else {
                    field.style.borderColor = '';
                }
            });
            
            return isValid;
        }
        
        // Initialize
        updateNavDots();
        showSection(1);
    </script>
</body>
</html>