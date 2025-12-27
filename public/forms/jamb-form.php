<?php
// public/forms/jamb-form.php - JAMB/UTME Registration Form
// This form uses the post-utme structure but hides the JAMB registration number field
// since students don't have it yet
?>

<div style="margin-bottom: 20px;">
    <h2>JAMB/UTME Registration</h2>
    <p style="color: #6b7280;">Fill out your information below. Your official JAMB registration number will be generated during biometric capture at our center.</p>
    <a href="?step=1" class="btn btn-outline-secondary btn-sm">← Change Program</a>
</div>

<form method="post" action="process-registration.php" enctype="multipart/form-data">
    <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrf) ?>">
    <input type="hidden" name="program_type" value="jamb">
    <input type="hidden" name="registration_type" value="jamb">
    
    <!-- Step 2: Core Profile Information -->
    <div class="form-section">
        <h4 class="section-title"><i class='bx bxs-user'></i> Personal Information</h4>
        
        <div class="form-row">
            <div class="form-group">
                <label for="first_name">First Name <span class="text-danger">*</span></label>
                <input type="text" id="first_name" name="first_name" class="form-control" required placeholder="Enter your first name">
            </div>
            <div class="form-group">
                <label for="surname">Surname <span class="text-danger">*</span></label>
                <input type="text" id="surname" name="surname" class="form-control" required placeholder="Enter your surname">
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="other_name">Other Names</label>
                <input type="text" id="other_name" name="other_name" class="form-control" placeholder="Middle name (optional)">
            </div>
            <div class="form-group">
                <label for="email">Email Address <span class="text-danger">*</span></label>
                <input type="email" id="email" name="email" class="form-control" required placeholder="your.email@example.com">
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="phone">Phone Number <span class="text-danger">*</span></label>
                <input type="tel" id="phone" name="phone" class="form-control" required placeholder="+234 XXX XXX XXXX">
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

        <div class="form-row">
            <div class="form-group">
                <label for="date_of_birth">Date of Birth <span class="text-danger">*</span></label>
                <input type="date" id="date_of_birth" name="date_of_birth" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="state_of_origin">State of Origin <span class="text-danger">*</span></label>
                <input type="text" id="state_of_origin" name="state_of_origin" class="form-control" required placeholder="e.g., Lagos">
            </div>
        </div>

        <div class="form-group">
            <label for="home_address">Home Address <span class="text-danger">*</span></label>
            <textarea id="home_address" name="home_address" class="form-control" rows="3" required placeholder="Enter your complete home address"></textarea>
        </div>
    </div>

    <!-- Step 3: JAMB-Specific Information -->
    <div class="form-section">
        <h4 class="section-title"><i class='bx bxs-graduation'></i> JAMB Preparation Details</h4>
        
        <div class="alert alert-info">
            <i class='bx bx-info-circle'></i> <strong>Note:</strong> Your official JAMB registration number will be generated during biometric capture at our HQ Academy center after payment confirmation.
        </div>

        <div class="form-group">
            <label for="intended_course">Intended Course of Study <span class="text-danger">*</span></label>
            <input type="text" id="intended_course" name="intended_course" class="form-control" required placeholder="e.g., Computer Science, Medicine, Engineering">
        </div>

        <div class="form-group">
            <label for="institution">Preferred University <span class="text-danger">*</span></label>
            <input type="text" id="institution" name="institution" class="form-control" required placeholder="e.g., University of Lagos, LAUTECH">
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="jamb_subject_1">JAMB Subject 1 (Use of English)</label>
                <input type="text" id="jamb_subject_1" name="jamb_subject_1" class="form-control" value="Use of English" readonly>
            </div>
            <div class="form-group">
                <label for="jamb_subject_2">JAMB Subject 2 <span class="text-danger">*</span></label>
                <input type="text" id="jamb_subject_2" name="jamb_subject_2" class="form-control" required placeholder="e.g., Mathematics">
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="jamb_subject_3">JAMB Subject 3 <span class="text-danger">*</span></label>
                <input type="text" id="jamb_subject_3" name="jamb_subject_3" class="form-control" required placeholder="e.g., Physics">
            </div>
            <div class="form-group">
                <label for="jamb_subject_4">JAMB Subject 4 <span class="text-danger">*</span></label>
                <input type="text" id="jamb_subject_4" name="jamb_subject_4" class="form-control" required placeholder="e.g., Chemistry">
            </div>
        </div>

        <div class="form-group">
            <label for="education_level">Current Education Level <span class="text-danger">*</span></label>
            <select id="education_level" name="education_level" class="form-control" required>
                <option value="">Select Education Level</option>
                <option value="SS2">SS2 (Year 12)</option>
                <option value="SS3">SS3 (Year 13)</option>
                <option value="Post-Secondary">Post-Secondary Student</option>
                <option value="Private Candidate">Private Candidate</option>
                <option value="Other">Other</option>
            </select>
        </div>

        <div class="form-group">
            <label for="career_goals">Career Goals & Aspirations <span class="text-danger">*</span></label>
            <textarea id="career_goals" name="career_goals" class="form-control" rows="3" required placeholder="What profession or field are you aiming for? Why did you choose these JAMB subjects?"></textarea>
        </div>

        <div class="form-group">
            <label for="study_goals">Study Goals & Motivations <span class="text-danger">*</span></label>
            <textarea id="study_goals" name="study_goals" class="form-control" rows="3" required placeholder="What do you want to achieve with JAMB? What are your university expectations? What drives your ambitions?"></textarea>
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
            <textarea id="weak_subjects" name="weak_subjects" class="form-control" rows="2" placeholder="Which JAMB subjects do you find most difficult? This helps us provide targeted support"></textarea>
        </div>
    </div>

    <!-- Emergency Contact -->
    <div class="form-section">
        <h4 class="section-title"><i class='bx bxs-phone'></i> Emergency Contact</h4>
        
        <div class="form-row">
            <div class="form-group">
                <label for="parent_name">Parent/Guardian Name <span class="text-danger">*</span></label>
                <input type="text" id="parent_name" name="parent_name" class="form-control" required placeholder="Full name">
            </div>
            <div class="form-group">
                <label for="parent_phone">Parent/Guardian Phone <span class="text-danger">*</span></label>
                <input type="tel" id="parent_phone" name="parent_phone" class="form-control" required placeholder="+234 XXX XXX XXXX">
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

<style>
.form-section {
    margin-bottom: 40px;
}

.section-title {
    font-size: 1.3rem;
    font-weight: 700;
    color: #0b1a2c;
    margin-bottom: 20px;
    padding-bottom: 10px;
    border-bottom: 2px solid #ffd600;
    display: flex;
    align-items: center;
    gap: 10px;
}

.section-title i {
    font-size: 1.5rem;
    color: #ffd600;
}

.form-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-bottom: 20px;
}

.form-group {
    display: flex;
    flex-direction: column;
}

.form-group label {
    font-weight: 600;
    margin-bottom: 8px;
    color: #374151;
}

.form-control {
    padding: 12px;
    border: 1px solid #d1d5db;
    border-radius: 8px;
    font-size: 1rem;
    transition: all 0.3s ease;
}

.form-control:focus {
    outline: none;
    border-color: #ffd600;
    box-shadow: 0 0 0 3px rgba(255, 214, 0, 0.1);
}

.alert-info {
    background: #e0f2fe;
    border: 1px solid #0ea5e9;
    border-radius: 8px;
    padding: 15px;
    margin-bottom: 20px;
    display: flex;
    align-items: flex-start;
    gap: 10px;
}

.alert-info i {
    font-size: 24px;
    color: #0ea5e9;
}

.form-check {
    display: flex;
    align-items: center;
    gap: 10px;
}

.form-check-input {
    width: 20px;
    height: 20px;
    cursor: pointer;
}

.text-danger {
    color: #dc2626;
}

@media (max-width: 768px) {
    .form-row {
        grid-template-columns: 1fr;
    }
}
</style>
