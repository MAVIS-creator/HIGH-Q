<?php
// public/forms/regular-form.php - Basic Regular Registration Form
// Simple registration without program/skill selection - for general inquiries and basic registration
?>

<div style="margin-bottom: 20px;">
    <h2>General Registration</h2>
    <p style="color: #6b7280;">Register with us to stay updated on our latest programs and opportunities.</p>
    <a href="?step=1" class="btn btn-outline-secondary btn-sm">← Change Program</a>
</div>

<form method="post" action="process-registration.php" enctype="multipart/form-data">
    <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrf) ?>">
    <input type="hidden" name="program_type" value="regular">
    <input type="hidden" name="registration_type" value="regular">
    
    <!-- Personal Information -->
    <div class="form-section">
        <h4 class="section-title"><i class='bx bxs-user'></i> Personal Information</h4>
        
        <div class="form-row">
            <div class="form-group">
                <label for="first_name">First Name <span class="text-danger">*</span></label>
                <input type="text" id="first_name" name="first_name" class="form-control" required placeholder="Enter your first name">
            </div>
            <div class="form-group">
                <label for="last_name">Last Name <span class="text-danger">*</span></label>
                <input type="text" id="last_name" name="last_name" class="form-control" required placeholder="Enter your last name">
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="email">Email Address <span class="text-danger">*</span></label>
                <input type="email" id="email" name="email" class="form-control" required placeholder="your.email@example.com">
            </div>
            <div class="form-group">
                <label for="phone">Phone Number <span class="text-danger">*</span></label>
                <input type="tel" id="phone" name="phone" class="form-control" required placeholder="+234 XXX XXX XXXX">
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
                    <option value="other">Other</option>
                </select>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="state_of_origin">State of Origin <span class="text-danger">*</span></label>
                <input type="text" id="state_of_origin" name="state_of_origin" class="form-control" required placeholder="e.g., Lagos">
            </div>
            <div class="form-group">
                <label for="nationality">Nationality <span class="text-danger">*</span></label>
                <input type="text" id="nationality" name="nationality" class="form-control" required value="Nigerian">
            </div>
        </div>

        <div class="form-group">
            <label for="home_address">Home Address <span class="text-danger">*</span></label>
            <textarea id="home_address" name="home_address" class="form-control" rows="3" required placeholder="Enter your complete home address"></textarea>
        </div>
    </div>

    <!-- Contact Information -->
    <div class="form-section">
        <h4 class="section-title"><i class='bx bxs-envelope'></i> How Would You Like to Hear From Us?</h4>
        
        <div class="form-group">
            <label for="preferred_contact">Preferred Contact Method <span class="text-danger">*</span></label>
            <select id="preferred_contact" name="preferred_contact" class="form-control" required>
                <option value="">Select Contact Method</option>
                <option value="Email">Email</option>
                <option value="Phone">Phone Call</option>
                <option value="SMS">Text Message (SMS)</option>
                <option value="WhatsApp">WhatsApp</option>
            </select>
        </div>

        <div class="form-group">
            <label>Interests (Check all that apply)</label>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="interests" id="int_jamb" value="JAMB">
                    <label class="form-check-label" for="int_jamb">JAMB/UTME Preparation</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="interests" id="int_waec" value="WAEC">
                    <label class="form-check-label" for="int_waec">WAEC/NECO/GCE Exams</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="interests" id="int_postutme" value="Post-UTME">
                    <label class="form-check-label" for="int_postutme">Post-UTME Screening</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="interests" id="int_digital" value="Digital Skills">
                    <label class="form-check-label" for="int_digital">Digital Skills Training</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="interests" id="int_intl" value="International">
                    <label class="form-check-label" for="int_intl">International Programs (SAT, IELTS, TOEFL)</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="interests" id="int_other" value="Other">
                    <label class="form-check-label" for="int_other">Other Services</label>
                </div>
            </div>
        </div>

        <div class="form-group">
            <label for="message">Additional Message (Optional)</label>
            <textarea id="message" name="message" class="form-control" rows="3" placeholder="Any questions or additional information you'd like to share..."></textarea>
        </div>
    </div>

    <!-- Emergency Contact -->
    <div class="form-section">
        <h4 class="section-title"><i class='bx bxs-phone'></i> Emergency Contact</h4>
        
        <div class="form-row">
            <div class="form-group">
                <label for="emergency_name">Emergency Contact Name <span class="text-danger">*</span></label>
                <input type="text" id="emergency_name" name="emergency_name" class="form-control" required placeholder="Full name">
            </div>
            <div class="form-group">
                <label for="emergency_phone">Emergency Contact Phone <span class="text-danger">*</span></label>
                <input type="tel" id="emergency_phone" name="emergency_phone" class="form-control" required placeholder="+234 XXX XXX XXXX">
            </div>
        </div>

        <div class="form-group">
            <label for="emergency_relationship">Relationship <span class="text-danger">*</span></label>
            <input type="text" id="emergency_relationship" name="emergency_relationship" class="form-control" required placeholder="e.g., Parent, Guardian, Spouse">
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

        <div class="form-check" style="margin-top: 10px;">
            <input type="checkbox" id="newsletter" name="newsletter" class="form-check-input">
            <label for="newsletter" class="form-check-label">
                Subscribe me to the High Q Tutorial newsletter for updates and offers
            </label>
        </div>

        <div style="margin-top: 30px; text-align: center;">
            <button type="submit" class="btn btn-primary btn-lg" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: #fff; border: none; padding: 15px 50px; font-weight: 700; border-radius: 8px;">
                Complete Registration
            </button>
        </div>
    </div>
</form>

<link rel="stylesheet" href="forms/form-styles.css">
