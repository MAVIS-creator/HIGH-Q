<?php
// public/forms/postutme-form.php - Post-UTME Registration Form (Full form with JAMB details)
?>

<div style="margin-bottom: 20px;">
    <h2>Post-UTME Registration</h2>
    <p style="color: #6b7280;">University screening exam preparation for students who have written JAMB.</p>
    <a href="?step=1" class="btn btn-outline-secondary btn-sm">← Change Program</a>
</div>

<form method="post" action="process-registration.php" enctype="multipart/form-data">
    <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrf) ?>">
    <input type="hidden" name="program_type" value="postutme">
    <input type="hidden" name="registration_type" value="postutme">
    
    <!-- Personal Information -->
    <div class="form-section">
        <h4 class="section-title"><i class='bx bxs-user'></i> Personal Information</h4>
        
        <div class="form-row">
            <div class="form-group">
                <label for="first_name">First Name <span class="text-danger">*</span></label>
                <input type="text" id="first_name" name="first_name" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="surname">Surname <span class="text-danger">*</span></label>
                <input type="text" id="surname" name="surname" class="form-control" required>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="other_name">Other Names</label>
                <input type="text" id="other_name" name="other_name" class="form-control">
            </div>
            <div class="form-group">
                <label for="email">Email Address <span class="text-danger">*</span></label>
                <input type="email" id="email" name="email" class="form-control" required>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="phone">Phone Number <span class="text-danger">*</span></label>
                <input type="tel" id="phone" name="phone" class="form-control" required>
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
                <input type="text" id="state_of_origin" name="state_of_origin" class="form-control" required>
            </div>
        </div>

        <div class="form-group">
            <label for="home_address">Home Address <span class="text-danger">*</span></label>
            <textarea id="home_address" name="home_address" class="form-control" rows="3" required></textarea>
        </div>
    </div>

    <!-- JAMB Information -->
    <div class="form-section">
        <h4 class="section-title"><i class='bx bxs-graduation'></i> JAMB Details</h4>
        
        <div class="form-row">
            <div class="form-group">
                <label for="jamb_registration_number">JAMB Registration Number <span class="text-danger">*</span></label>
                <input type="text" id="jamb_registration_number" name="jamb_registration_number" class="form-control" required placeholder="e.g., 12345678ABC">
            </div>
            <div class="form-group">
                <label for="jamb_score">JAMB Score <span class="text-danger">*</span></label>
                <input type="number" id="jamb_score" name="jamb_score" class="form-control" required min="0" max="400" placeholder="e.g., 280">
            </div>
        </div>

        <div class="form-group">
            <label for="intended_course">Intended Course of Study <span class="text-danger">*</span></label>
            <input type="text" id="intended_course" name="intended_course" class="form-control" required>
        </div>

        <div class="form-group">
            <label for="institution">Preferred University <span class="text-danger">*</span></label>
            <input type="text" id="institution" name="institution" class="form-control" required>
        </div>

        <div class="row">
            <div class="col-md-6 mb-3">
                <label>JAMB Subject 1 <span class="text-danger">*</span></label>
                <input type="text" name="jamb_subject_1" class="form-control" required>
            </div>
            <div class="col-md-6 mb-3">
                <label>Subject 1 Score</label>
                <input type="number" name="jamb_score_1" class="form-control" min="0" max="100">
            </div>
        </div>

        <div class="row">
            <div class="col-md-6 mb-3">
                <label>JAMB Subject 2 <span class="text-danger">*</span></label>
                <input type="text" name="jamb_subject_2" class="form-control" required>
            </div>
            <div class="col-md-6 mb-3">
                <label>Subject 2 Score</label>
                <input type="number" name="jamb_score_2" class="form-control" min="0" max="100">
            </div>
        </div>

        <div class="row">
            <div class="col-md-6 mb-3">
                <label>JAMB Subject 3 <span class="text-danger">*</span></label>
                <input type="text" name="jamb_subject_3" class="form-control" required>
            </div>
            <div class="col-md-6 mb-3">
                <label>Subject 3 Score</label>
                <input type="number" name="jamb_score_3" class="form-control" min="0" max="100">
            </div>
        </div>

        <div class="row">
            <div class="col-md-6 mb-3">
                <label>JAMB Subject 4 <span class="text-danger">*</span></label>
                <input type="text" name="jamb_subject_4" class="form-control" required>
            </div>
            <div class="col-md-6 mb-3">
                <label>Subject 4 Score</label>
                <input type="number" name="jamb_score_4" class="form-control" min="0" max="100">
            </div>
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
