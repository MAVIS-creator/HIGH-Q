<?php
// public/forms/waec-form.php - WAEC/NECO/GCE Registration Form
?>

<div style="margin-bottom: 20px;">
    <h2>WAEC/NECO/GCE Registration</h2>
    <p style="color: #6b7280;">Comprehensive O-Level exam preparation and tutoring.</p>
    <a href="?step=1" class="btn btn-outline-secondary btn-sm">← Change Program</a>
</div>

<form method="post" action="process-registration.php" enctype="multipart/form-data">
    <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrf) ?>">
    <input type="hidden" name="program_type" value="waec">
    <input type="hidden" name="registration_type" value="regular">
    
    <!-- Personal Information -->
    <div class="form-section">
        <h4 class="section-title"><i class='bx bxs-user'></i> Personal Information</h4>
        
        <div class="form-row">
            <div class="form-group">
                <label for="first_name">First Name <span class="text-danger">*</span></label>
                <input type="text" id="first_name" name="first_name" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="last_name">Last Name <span class="text-danger">*</span></label>
                <input type="text" id="last_name" name="last_name" class="form-control" required>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="email">Email Address <span class="text-danger">*</span></label>
                <input type="email" id="email" name="email" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="phone">Phone Number <span class="text-danger">*</span></label>
                <input type="tel" id="phone" name="phone" class="form-control" required>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="date_of_birth">Date of Birth <span class="text-danger">*</span></label>
                <input type="date" id="date_of_birth" name="date_of_birth" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="gender">Gender <span class="text-danger">*</span></label>
                <select id="gender" name="gender" class="form-control" required>
                    <option value="">Select Gender</option>
                    <option value="male">Male</option>
                    <option value="female">Female</option>
                </select>
            </div>
        </div>

        <div class="form-group">
            <label for="home_address">Home Address <span class="text-danger">*</span></label>
            <textarea id="home_address" name="home_address" class="form-control" rows="3" required></textarea>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="nationality">Nationality <span class="text-danger">*</span></label>
                <input type="text" id="nationality" name="nationality" class="form-control" required value="Nigerian">
            </div>
            <div class="form-group">
                <label for="state_of_origin">State of Origin <span class="text-danger">*</span></label>
                <input type="text" id="state_of_origin" name="state_of_origin" class="form-control" required placeholder="e.g., Lagos, Oyo">
            </div>
        </div>
    </div>

    <!-- WAEC-Specific Information -->
    <div class="form-section">
        <h4 class="section-title"><i class='bx bxs-book-open'></i> Exam Details & Subject Selection</h4>
        
        <div class="form-row">
            <div class="form-group">
                <label for="exam_type">Exam Type <span class="text-danger">*</span></label>
                <select id="exam_type" name="exam_type" class="form-control" required>
                    <option value="">Select Exam</option>
                    <option value="WAEC">WAEC</option>
                    <option value="NECO">NECO</option>
                    <option value="GCE">GCE</option>
                </select>
            </div>
            <div class="form-group">
                <label for="exam_year">Exam Year <span class="text-danger">*</span></label>
                <select id="exam_year" name="exam_year" class="form-control" required>
                    <option value="">Select Year</option>
                    <option value="2025">2025</option>
                    <option value="2026">2026</option>
                </select>
            </div>
        </div>

        <div class="form-group">
            <label>Select Your Subjects (Up to 8 subjects) <span class="text-danger">*</span></label>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="subjects" id="subj_english" value="English Language">
                    <label class="form-check-label" for="subj_english">English Language</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="subjects" id="subj_math" value="Mathematics">
                    <label class="form-check-label" for="subj_math">Mathematics</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="subjects" id="subj_physics" value="Physics">
                    <label class="form-check-label" for="subj_physics">Physics</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="subjects" id="subj_chemistry" value="Chemistry">
                    <label class="form-check-label" for="subj_chemistry">Chemistry</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="subjects" id="subj_biology" value="Biology">
                    <label class="form-check-label" for="subj_biology">Biology</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="subjects" id="subj_economics" value="Economics">
                    <label class="form-check-label" for="subj_economics">Economics</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="subjects" id="subj_gov" value="Government">
                    <label class="form-check-label" for="subj_gov">Government/Civic Education</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="subjects" id="subj_lit" value="Literature">
                    <label class="form-check-label" for="subj_lit">Literature in English</label>
                </div>
            </div>
            <small class="form-text" style="display: block; margin-top: 8px;">Select all subjects you're registering for</small>
        </div>

        <div class="form-group">
            <label for="current_class">Current Class <span class="text-danger">*</span></label>
            <select id="current_class" name="current_class" class="form-control" required>
                <option value="">Select Class</option>
                <option value="SS1">SS1</option>
                <option value="SS2">SS2</option>
                <option value="SS3">SS3</option>
                <option value="Graduate">Private Candidate / Graduate</option>
            </select>
        </div>

        <div class="form-group">
            <label for="current_school">Current School/Institution</label>
            <input type="text" id="current_school" name="current_school" class="form-control" placeholder="Name of your school (if still in school)">
        </div>

        <div class="form-group">
            <label for="career_goals">Career Goals & Aspirations <span class="text-danger">*</span></label>
            <textarea id="career_goals" name="career_goals" class="form-control" rows="3" required placeholder="What profession or field are you aiming for? e.g., Medicine, Law, Engineering, Education"></textarea>
        </div>

        <div class="form-group">
            <label for="study_goals">Study Goals & Objectives <span class="text-danger">*</span></label>
            <textarea id="study_goals" name="study_goals" class="form-control" rows="3" required placeholder="What do you want to achieve with these exams? What are your expectations from this program?"></textarea>
        </div>

        <div class="form-group">
            <label for="learning_preference">Preferred Learning Style <span class="text-danger">*</span></label>
            <select id="learning_preference" name="learning_preference" class="form-control" required>
                <option value="">Select Learning Style</option>
                <option value="Structured Classes">Structured Classes with Fixed Schedule</option>
                <option value="Flexible/Self-paced">Flexible/Self-paced Learning</option>
                <option value="Mixed Approach">Mixed Approach (Classes + Self-study)</option>
                <option value="One-on-One Tutoring">One-on-One Tutoring</option>
                <option value="Group Sessions">Group Sessions</option>
            </select>
        </div>

        <div class="form-group">
            <label for="weak_subjects">Subjects You Find Challenging (Optional)</label>
            <textarea id="weak_subjects" name="weak_subjects" class="form-control" rows="2" placeholder="Which subjects do you struggle with the most? This helps us provide better support"></textarea>
        </div>
    </div>

    <!-- Emergency Contact -->
    <div class="form-section">
        <h4 class="section-title"><i class='bx bxs-phone'></i> Emergency Contact</h4>
        
        <div class="form-row">
            <div class="form-group">
                <label for="parent_name">Parent/Guardian Name <span class="text-danger">*</span></label>
                <input type="text" id="parent_name" name="parent_name" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="parent_phone">Parent/Guardian Phone <span class="text-danger">*</span></label>
                <input type="tel" id="parent_phone" name="parent_phone" class="form-control" required>
            </div>
        </div>
    </div>

    <!-- Terms and Submit -->
    <div class="form-section">
        <div class="form-check">
            <input type="checkbox" id="terms" name="terms" class="form-check-input" required>
            <label for="terms" class="form-check-label">
                I agree to the <a href="/terms.php" target="_blank">terms and conditions</a> <span class="text-danger">*</span>
            </label>
        </div>

        <div style="margin-top: 30px; text-align: center;">
            <button type="submit" class="btn btn-primary btn-lg" style="background: #ffd600; color: #0b1a2c; border: none; padding: 15px 50px; font-weight: 700; border-radius: 8px;">
                Proceed to Payment
            </button>
        </div>
    </div>
</form>

<link rel="stylesheet" href="forms/form-styles.css">
