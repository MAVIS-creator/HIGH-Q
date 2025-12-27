<?php
// public/forms/postutme-form-comprehensive.php - Complete Post-UTME Registration Form
// Based on post-utme.php with all comprehensive fields
?>

<div style="margin-bottom: 20px;">
    <h2>Post-UTME Registration</h2>
    <p style="color: #6b7280;">Complete Post-UTME screening exam registration with JAMB validation</p>
    <a href="?step=1" class="btn btn-outline-secondary btn-sm">← Change Program</a>
</div>

<form method="post" action="process-registration.php" enctype="multipart/form-data" id="postutmeForm">
    <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrf) ?>">
    <input type="hidden" name="program_type" value="postutme">
    <input type="hidden" name="registration_type" value="postutme">
    <input type="hidden" name="form_action" value="postutme">
    
    <!-- Personal Information -->
    <div class="form-section">
        <h4 class="section-title"><i class='bx bxs-user'></i> Personal Information</h4>
        
        <div class="form-row">
            <div class="form-group">
                <label for="institution">Name of Institution <span class="text-danger">*</span></label>
                <input type="text" id="institution" name="institution" class="form-control" required placeholder="University where you're applying">
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="passport">Passport Photo <span class="text-danger">*</span></label>
                <input type="file" id="passport" name="passport" class="form-control" accept="image/jpeg,image/jpg,image/png" required>
                <small class="form-text">Upload a recent passport photograph (JPG/PNG, max 2MB)</small>
            </div>
        </div>
        
        <div class="form-row">
            <div class="form-group">
                <label for="first_name_post">First Name <span class="text-danger">*</span></label>
                <input type="text" id="first_name_post" name="first_name_post" class="form-control" required placeholder="First name">
            </div>
            <div class="form-group">
                <label for="surname">Surname <span class="text-danger">*</span></label>
                <input type="text" id="surname" name="surname" class="form-control" required placeholder="Surname">
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="other_name">Other Names</label>
                <input type="text" id="other_name" name="other_name" class="form-control" placeholder="Middle name (optional)">
            </div>
            <div class="form-group">
                <label for="post_gender">Gender <span class="text-danger">*</span></label>
                <select id="post_gender" name="post_gender" class="form-control" required>
                    <option value="">Select Gender</option>
                    <option value="male">Male</option>
                    <option value="female">Female</option>
                </select>
            </div>
        </div>

        <div class="form-group">
            <label for="address">Home Address <span class="text-danger">*</span></label>
            <textarea id="address" name="address" class="form-control" rows="3" required placeholder="Enter your complete home address"></textarea>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="parent_phone">Parent/Guardian Phone <span class="text-danger">*</span></label>
                <input type="tel" id="parent_phone" name="parent_phone" class="form-control" required placeholder="+234 XXX XXX XXXX">
            </div>
            <div class="form-group">
                <label for="email_post">Email Address <span class="text-danger">*</span></label>
                <input type="email" id="email_post" name="email_post" class="form-control" required placeholder="your.email@example.com">
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="nin_number">NIN Number</label>
                <input type="text" id="nin_number" name="nin_number" class="form-control" placeholder="National Identification Number">
            </div>
            <div class="form-group">
                <label for="state_of_origin">State of Origin <span class="text-danger">*</span></label>
                <input type="text" id="state_of_origin" name="state_of_origin" class="form-control" required placeholder="e.g., Lagos">
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="local_government">Local Government</label>
                <input type="text" id="local_government" name="local_government" class="form-control" placeholder="Local government area">
            </div>
            <div class="form-group">
                <label for="place_of_birth">Place of Birth</label>
                <input type="text" id="place_of_birth" name="place_of_birth" class="form-control" placeholder="Place of birth">
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="nationality">Nationality <span class="text-danger">*</span></label>
                <input type="text" id="nationality" name="nationality" class="form-control" required value="Nigerian">
            </div>
            <div class="form-group">
                <label for="religion">Religion</label>
                <input type="text" id="religion" name="religion" class="form-control" placeholder="Religion">
            </div>
        </div>
    </div>

    <!-- JAMB Information -->
    <div class="form-section">
        <h4 class="section-title"><i class='bx bxs-graduation'></i> JAMB Information</h4>
        
        <div class="form-row">
            <div class="form-group">
                <label for="jamb_registration_number">JAMB Registration Number <span class="text-danger">*</span></label>
                <input type="text" id="jamb_registration_number" name="jamb_registration_number" class="form-control" required placeholder="e.g., 12345678AA">
            </div>
            <div class="form-group">
                <label for="jamb_score">JAMB Score <span class="text-danger">*</span></label>
                <input type="number" id="jamb_score" name="jamb_score" class="form-control" required placeholder="Total score (0-400)" min="0" max="400">
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="jamb_subj_1">JAMB Subject 1 (Must be English) <span class="text-danger">*</span></label>
                <input type="text" id="jamb_subj_1" name="jamb_subj_1" class="form-control" required placeholder="e.g., English Language" value="English Language">
            </div>
            <div class="form-group">
                <label for="jamb_score_1">Score for Subject 1 <span class="text-danger">*</span></label>
                <input type="number" id="jamb_score_1" name="jamb_score_1" class="form-control" required placeholder="0-100" min="0" max="100">
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="jamb_subj_2">JAMB Subject 2 <span class="text-danger">*</span></label>
                <input type="text" id="jamb_subj_2" name="jamb_subj_2" class="form-control" required placeholder="Second subject">
            </div>
            <div class="form-group">
                <label for="jamb_score_2">Score for Subject 2 <span class="text-danger">*</span></label>
                <input type="number" id="jamb_score_2" name="jamb_score_2" class="form-control" required placeholder="0-100" min="0" max="100">
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="jamb_subj_3">JAMB Subject 3 <span class="text-danger">*</span></label>
                <input type="text" id="jamb_subj_3" name="jamb_subj_3" class="form-control" required placeholder="Third subject">
            </div>
            <div class="form-group">
                <label for="jamb_score_3">Score for Subject 3 <span class="text-danger">*</span></label>
                <input type="number" id="jamb_score_3" name="jamb_score_3" class="form-control" required placeholder="0-100" min="0" max="100">
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="jamb_subj_4">JAMB Subject 4 <span class="text-danger">*</span></label>
                <input type="text" id="jamb_subj_4" name="jamb_subj_4" class="form-control" required placeholder="Fourth subject">
            </div>
            <div class="form-group">
                <label for="jamb_score_4">Score for Subject 4 <span class="text-danger">*</span></label>
                <input type="number" id="jamb_score_4" name="jamb_score_4" class="form-control" required placeholder="0-100" min="0" max="100">
            </div>
        </div>
    </div>

    <!-- O'Level Results -->
    <div class="form-section">
        <h4 class="section-title"><i class='bx bxs-book-content'></i> O'Level Results (WAEC/NECO)</h4>
        
        <div class="form-row">
            <div class="form-group">
                <label for="exam_type">Exam Type <span class="text-danger">*</span></label>
                <select id="exam_type" name="exam_type" class="form-control" required>
                    <option value="">Select exam type</option>
                    <option value="WAEC">WAEC</option>
                    <option value="NECO">NECO</option>
                    <option value="GCE">GCE</option>
                </select>
            </div>
            <div class="form-group">
                <label for="candidate_name">Candidate Name (as on certificate) <span class="text-danger">*</span></label>
                <input type="text" id="candidate_name" name="candidate_name" class="form-control" required placeholder="Full name on certificate">
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="exam_number">Exam Number <span class="text-danger">*</span></label>
                <input type="text" id="exam_number" name="exam_number" class="form-control" required placeholder="Examination number">
            </div>
            <div class="form-group">
                <label for="exam_year_month">Exam Year/Month <span class="text-danger">*</span></label>
                <input type="text" id="exam_year_month" name="exam_year_month" class="form-control" required placeholder="e.g., May/June 2023">
            </div>
        </div>

        <h5 style="margin-top: 20px; margin-bottom: 15px;">O'Level Subjects & Grades</h5>
        
        <?php for ($i = 1; $i <= 8; $i++): ?>
        <div class="form-row">
            <div class="form-group">
                <label for="olevel_subj_<?= $i ?>">Subject <?= $i ?> <?= $i <= 5 ? '<span class="text-danger">*</span>' : '' ?></label>
                <input type="text" id="olevel_subj_<?= $i ?>" name="olevel_subj_<?= $i ?>" class="form-control" <?= $i <= 5 ? 'required' : '' ?> placeholder="Subject name">
            </div>
            <div class="form-group">
                <label for="olevel_grade_<?= $i ?>">Grade <?= $i ?> <?= $i <= 5 ? '<span class="text-danger">*</span>' : '' ?></label>
                <select id="olevel_grade_<?= $i ?>" name="olevel_grade_<?= $i ?>" class="form-control" <?= $i <= 5 ? 'required' : '' ?>>
                    <option value="">Select Grade</option>
                    <option value="A1">A1 (Excellent)</option>
                    <option value="B2">B2 (Very Good)</option>
                    <option value="B3">B3 (Good)</option>
                    <option value="C4">C4 (Credit)</option>
                    <option value="C5">C5 (Credit)</option>
                    <option value="C6">C6 (Credit)</option>
                    <option value="D7">D7 (Pass)</option>
                    <option value="E8">E8 (Pass)</option>
                    <option value="F9">F9 (Fail)</option>
                </select>
            </div>
        </div>
        <?php endfor; ?>

        <div class="form-row">
            <div class="form-group">
                <label for="waec_token">WAEC Token</label>
                <input type="text" id="waec_token" name="waec_token" class="form-control" placeholder="WAEC scratch card token (optional)">
            </div>
            <div class="form-group">
                <label for="waec_serial">WAEC Serial Number</label>
                <input type="text" id="waec_serial" name="waec_serial" class="form-control" placeholder="WAEC serial number (optional)">
            </div>
        </div>
    </div>

    <!-- Course Choices -->
    <div class="form-section">
        <h4 class="section-title"><i class='bx bxs-school'></i> Course Choices</h4>
        
        <div class="form-row">
            <div class="form-group">
                <label for="course_first_choice">First Choice Course <span class="text-danger">*</span></label>
                <input type="text" id="course_first_choice" name="course_first_choice" class="form-control" required placeholder="e.g., Computer Science">
            </div>
            <div class="form-group">
                <label for="course_second_choice">Second Choice Course</label>
                <input type="text" id="course_second_choice" name="course_second_choice" class="form-control" placeholder="Alternative course">
            </div>
        </div>

        <div class="form-group">
            <label for="institution_first_choice">First Choice Institution <span class="text-danger">*</span></label>
            <input type="text" id="institution_first_choice" name="institution_first_choice" class="form-control" required placeholder="Preferred university/polytechnic">
        </div>
    </div>

    <!-- Parent/Guardian Information -->
    <div class="form-section">
        <h4 class="section-title"><i class='bx bxs-user-detail'></i> Parent/Guardian Information</h4>
        
        <div class="form-row">
            <div class="form-group">
                <label for="father_name">Father's Name</label>
                <input type="text" id="father_name" name="father_name" class="form-control" placeholder="Father's full name">
            </div>
            <div class="form-group">
                <label for="father_phone">Father's Phone</label>
                <input type="tel" id="father_phone" name="father_phone" class="form-control" placeholder="+234 XXX XXX XXXX">
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="father_email">Father's Email</label>
                <input type="email" id="father_email" name="father_email" class="form-control" placeholder="father@example.com">
            </div>
            <div class="form-group">
                <label for="father_occupation">Father's Occupation</label>
                <input type="text" id="father_occupation" name="father_occupation" class="form-control" placeholder="Occupation">
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="mother_name">Mother's Name</label>
                <input type="text" id="mother_name" name="mother_name" class="form-control" placeholder="Mother's full name">
            </div>
            <div class="form-group">
                <label for="mother_phone">Mother's Phone</label>
                <input type="tel" id="mother_phone" name="mother_phone" class="form-control" placeholder="+234 XXX XXX XXXX">
            </div>
        </div>

        <div class="form-group">
            <label for="mother_occupation">Mother's Occupation</label>
            <input type="text" id="mother_occupation" name="mother_occupation" class="form-control" placeholder="Occupation">
        </div>
    </div>

    <!-- Educational Background -->
    <div class="form-section">
        <h4 class="section-title"><i class='bx bxs-school'></i> Educational Background</h4>
        
        <div class="form-row">
            <div class="form-group">
                <label for="primary_school">Primary School Attended</label>
                <input type="text" id="primary_school" name="primary_school" class="form-control" placeholder="Primary school name">
            </div>
            <div class="form-group">
                <label for="primary_year_ended">Year Ended</label>
                <input type="number" id="primary_year_ended" name="primary_year_ended" class="form-control" placeholder="e.g., 2015" min="1990" max="2025">
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="secondary_school">Secondary School Attended</label>
                <input type="text" id="secondary_school" name="secondary_school" class="form-control" placeholder="Secondary school name">
            </div>
            <div class="form-group">
                <label for="secondary_year_ended">Year Ended</label>
                <input type="number" id="secondary_year_ended" name="secondary_year_ended" class="form-control" placeholder="e.g., 2023" min="1990" max="2025">
            </div>
        </div>
    </div>

    <!-- Optional Tutor Fee -->
    <div class="form-section">
        <h4 class="section-title"><i class='bx bx-money'></i> Payment Options</h4>
        
        <div class="form-check" style="margin-bottom: 15px;">
            <input type="checkbox" id="post_tutor_fee" name="post_tutor_fee" class="form-check-input" value="1">
            <label for="post_tutor_fee" class="form-check-label">
                Add optional tutor fee (₦8,000)
            </label>
        </div>

        <div class="alert alert-info" style="background: #e0f2fe; border: 1px solid #0ea5e9; border-radius: 8px; padding: 15px; margin-bottom: 20px;">
            <i class='bx bx-info-circle' style="font-size: 24px; color: #0ea5e9; margin-right: 10px;"></i>
            <strong>Compulsory Fees:</strong>
            <ul style="margin: 10px 0 0 30px;">
                <li>Form processing fee: <strong>₦1,000</strong></li>
                <li>Card transaction fee: <strong>₦1,500</strong></li>
                <li>Total compulsory: <strong>₦2,500</strong></li>
            </ul>
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
            <button type="submit" class="btn btn-primary btn-lg" style="background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%); color: white; border: none; padding: 15px 50px; font-weight: 700; border-radius: 8px;">
                Submit Post-UTME Registration
            </button>
        </div>
    </div>
</form>

<link rel="stylesheet" href="forms/form-styles.css">

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
    border-bottom: 2px solid #dc2626;
    display: flex;
    align-items: center;
    gap: 10px;
}

.section-title i {
    font-size: 1.5rem;
    color: #dc2626;
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
    border-color: #dc2626;
    box-shadow: 0 0 0 3px rgba(220, 38, 38, 0.1);
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
