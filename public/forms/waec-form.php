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
    </div>

    <!-- WAEC-Specific Information -->
    <div class="form-section">
        <h4 class="section-title"><i class='bx bxs-book-open'></i> Exam Details</h4>
        
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
            <label for="subjects">Subjects (separate with commas) <span class="text-danger">*</span></label>
            <textarea id="subjects" name="subjects" class="form-control" rows="3" required placeholder="e.g., Mathematics, English, Physics, Chemistry, Biology, Economics"></textarea>
            <small class="form-text">Enter all subjects you want to register for, separated by commas.</small>
        </div>

        <div class="form-group">
            <label for="current_class">Current Class</label>
            <select id="current_class" name="current_class" class="form-control">
                <option value="">Select Class</option>
                <option value="SS1">SS1</option>
                <option value="SS2">SS2</option>
                <option value="SS3">SS3</option>
                <option value="Graduate">Graduate</option>
            </select>
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
