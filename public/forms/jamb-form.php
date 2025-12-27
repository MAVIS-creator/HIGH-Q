<?php
// public/forms/jamb-form.php - JAMB/UTME Registration Form
// Required fields: Surname, First name, Last name, DOB, Address, Phone, Email, NIN, Profile code,
// State of origin, Local govt, Sponsor info, Next of kin info
?>

<div style="margin-bottom: 20px;">
    <h2>JAMB/UTME Registration</h2>
    <p style="color: #6b7280;">Fill out your information below. Your official JAMB registration will be completed at our center.</p>
    <a href="?step=1" class="btn btn-outline-secondary btn-sm">← Change Program</a>
</div>

<form method="post" action="process-registration.php" enctype="multipart/form-data">
    <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrf) ?>">
    <input type="hidden" name="program_type" value="jamb">
    <input type="hidden" name="registration_type" value="jamb">
    
    <!-- Personal Information -->
    <div class="form-section">
        <h4 class="section-title"><i class='bx bxs-user'></i> Personal Information</h4>
        
        <div class="form-row">
            <div class="form-group">
                <label for="surname">Surname <span class="text-danger">*</span></label>
                <input type="text" id="surname" name="surname" class="form-control" required placeholder="Enter your surname">
            </div>
            <div class="form-group">
                <label for="first_name">First Name <span class="text-danger">*</span></label>
                <input type="text" id="first_name" name="first_name" class="form-control" required placeholder="Enter your first name">
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="last_name">Last Name / Other Names</label>
                <input type="text" id="last_name" name="last_name" class="form-control" placeholder="Middle name (optional)">
            </div>
            <div class="form-group">
                <label for="date_of_birth">Date of Birth <span class="text-danger">*</span></label>
                <input type="date" id="date_of_birth" name="date_of_birth" class="form-control" required>
            </div>
        </div>

        <div class="form-group">
            <label for="home_address">Home Address <span class="text-danger">*</span></label>
            <textarea id="home_address" name="home_address" class="form-control" rows="3" required placeholder="Enter your complete home address"></textarea>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="phone">Phone Number <span class="text-danger">*</span></label>
                <input type="tel" id="phone" name="phone" class="form-control" required placeholder="+234 XXX XXX XXXX">
            </div>
            <div class="form-group">
                <label for="email">Email Address <span class="text-danger">*</span></label>
                <input type="email" id="email" name="email" class="form-control" required placeholder="your.email@example.com">
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="nin">NIN (National Identification Number) <span class="text-danger">*</span></label>
                <input type="text" id="nin" name="nin" class="form-control" required placeholder="11-digit NIN" maxlength="11" pattern="[0-9]{11}">
                <small class="form-text">Your 11-digit National Identification Number (required for JAMB registration)</small>
            </div>
            <div class="form-group">
                <label for="profile_code">JAMB Profile Code</label>
                <input type="text" id="profile_code" name="profile_code" class="form-control" placeholder="e.g., 25XXXXXXXXXX" maxlength="15">
                <small class="form-text">If you already have a JAMB profile code. Leave blank if not yet created.</small>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="gender">Gender <span class="text-danger">*</span></label>
                <select id="gender" name="gender" class="form-control" required>
                    <option value="">Select Gender</option>
                    <option value="male">Male</option>
                    <option value="female">Female</option>
                </select>
            </div>
            <div class="form-group">
                <label for="marital_status">Marital Status <span class="text-danger">*</span></label>
                <select id="marital_status" name="marital_status" class="form-control" required>
                    <option value="">Select Status</option>
                    <option value="Single">Single</option>
                    <option value="Married">Married</option>
                </select>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="state_of_origin">State of Origin <span class="text-danger">*</span></label>
                <select id="state_of_origin" name="state_of_origin" class="form-control" required>
                    <option value="">Select State</option>
                    <option value="Abia">Abia</option>
                    <option value="Adamawa">Adamawa</option>
                    <option value="Akwa Ibom">Akwa Ibom</option>
                    <option value="Anambra">Anambra</option>
                    <option value="Bauchi">Bauchi</option>
                    <option value="Bayelsa">Bayelsa</option>
                    <option value="Benue">Benue</option>
                    <option value="Borno">Borno</option>
                    <option value="Cross River">Cross River</option>
                    <option value="Delta">Delta</option>
                    <option value="Ebonyi">Ebonyi</option>
                    <option value="Edo">Edo</option>
                    <option value="Ekiti">Ekiti</option>
                    <option value="Enugu">Enugu</option>
                    <option value="FCT">FCT - Abuja</option>
                    <option value="Gombe">Gombe</option>
                    <option value="Imo">Imo</option>
                    <option value="Jigawa">Jigawa</option>
                    <option value="Kaduna">Kaduna</option>
                    <option value="Kano">Kano</option>
                    <option value="Katsina">Katsina</option>
                    <option value="Kebbi">Kebbi</option>
                    <option value="Kogi">Kogi</option>
                    <option value="Kwara">Kwara</option>
                    <option value="Lagos">Lagos</option>
                    <option value="Nasarawa">Nasarawa</option>
                    <option value="Niger">Niger</option>
                    <option value="Ogun">Ogun</option>
                    <option value="Ondo">Ondo</option>
                    <option value="Osun">Osun</option>
                    <option value="Oyo">Oyo</option>
                    <option value="Plateau">Plateau</option>
                    <option value="Rivers">Rivers</option>
                    <option value="Sokoto">Sokoto</option>
                    <option value="Taraba">Taraba</option>
                    <option value="Yobe">Yobe</option>
                    <option value="Zamfara">Zamfara</option>
                </select>
            </div>
            <div class="form-group">
                <label for="local_government">Local Government <span class="text-danger">*</span></label>
                <input type="text" id="local_government" name="local_government" class="form-control" required placeholder="Enter your LGA">
            </div>
        </div>
    </div>

    <!-- JAMB-Specific Information -->
    <div class="form-section">
        <h4 class="section-title"><i class='bx bxs-graduation'></i> JAMB/UTME Details</h4>
        
        <div class="alert alert-info">
            <i class='bx bx-info-circle'></i> <strong>Note:</strong> Your official JAMB registration number will be generated during biometric capture at our HQ Academy center after payment confirmation.
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="intended_course">Intended Course of Study <span class="text-danger">*</span></label>
                <input type="text" id="intended_course" name="intended_course" class="form-control" required placeholder="e.g., Computer Science, Medicine, Engineering">
            </div>
            <div class="form-group">
                <label for="institution">Preferred University <span class="text-danger">*</span></label>
                <input type="text" id="institution" name="institution" class="form-control" required placeholder="e.g., University of Lagos, LAUTECH">
            </div>
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
    </div>

    <!-- Sponsor Information -->
    <div class="form-section">
        <h4 class="section-title"><i class='bx bxs-wallet'></i> Sponsor Information</h4>
        
        <div class="form-row">
            <div class="form-group">
                <label for="sponsor_name">Sponsor Name <span class="text-danger">*</span></label>
                <input type="text" id="sponsor_name" name="sponsor_name" class="form-control" required placeholder="Full name of sponsor/parent">
            </div>
            <div class="form-group">
                <label for="sponsor_phone">Sponsor Phone Number <span class="text-danger">*</span></label>
                <input type="tel" id="sponsor_phone" name="sponsor_phone" class="form-control" required placeholder="+234 XXX XXX XXXX">
            </div>
        </div>

        <div class="form-group">
            <label for="sponsor_address">Sponsor Address <span class="text-danger">*</span></label>
            <textarea id="sponsor_address" name="sponsor_address" class="form-control" rows="2" required placeholder="Enter sponsor's complete address"></textarea>
        </div>
    </div>

    <!-- Next of Kin Information -->
    <div class="form-section">
        <h4 class="section-title"><i class='bx bxs-user-detail'></i> Next of Kin Information</h4>
        
        <div class="form-row">
            <div class="form-group">
                <label for="next_of_kin_name">Next of Kin Name <span class="text-danger">*</span></label>
                <input type="text" id="next_of_kin_name" name="next_of_kin_name" class="form-control" required placeholder="Full name">
            </div>
            <div class="form-group">
                <label for="next_of_kin_phone">Next of Kin Phone Number <span class="text-danger">*</span></label>
                <input type="tel" id="next_of_kin_phone" name="next_of_kin_phone" class="form-control" required placeholder="+234 XXX XXX XXXX">
            </div>
        </div>

        <div class="form-group">
            <label for="next_of_kin_address">Next of Kin Address <span class="text-danger">*</span></label>
            <textarea id="next_of_kin_address" name="next_of_kin_address" class="form-control" rows="2" required placeholder="Enter next of kin's complete address"></textarea>
        </div>

        <div class="form-group">
            <label for="next_of_kin_relationship">Relationship <span class="text-danger">*</span></label>
            <select id="next_of_kin_relationship" name="next_of_kin_relationship" class="form-control" required>
                <option value="">Select Relationship</option>
                <option value="Parent">Parent</option>
                <option value="Guardian">Guardian</option>
                <option value="Sibling">Sibling</option>
                <option value="Uncle/Aunt">Uncle/Aunt</option>
                <option value="Other">Other</option>
            </select>
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

.form-text {
    font-size: 0.85rem;
    color: #6b7280;
}

@media (max-width: 768px) {
    .form-row {
        grid-template-columns: 1fr;
    }
}
</style>
