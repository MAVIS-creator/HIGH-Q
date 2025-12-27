<?php
// public/forms/waec-form.php - WAEC/NECO/GCE Registration Form
// Required fields: Surname, First name, Last name, DOB, Address, Phone, Email, NIN, State of origin, Local Government
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
                <small class="form-text">Your 11-digit National Identification Number</small>
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
        
        <!-- Passport Photo Upload -->
        <div class="form-group">
            <label for="passport_photo">Passport Photograph <span class="text-danger">*</span></label>
            <div class="passport-upload-area" style="border: 2px dashed #d1d5db; border-radius: 12px; padding: 20px; text-align: center; background: #fafafa;">
                <input type="file" id="passport_photo" name="passport_photo" class="form-control" accept="image/jpeg,image/png,image/jpg" required style="display: none;">
                <div class="upload-preview" id="passport-preview" style="margin-bottom: 10px;">
                    <i class='bx bxs-camera' style="font-size: 48px; color: #9ca3af;"></i>
                </div>
                <label for="passport_photo" style="cursor: pointer; display: inline-block; background: linear-gradient(135deg, #059669, #10b981); color: white; padding: 10px 20px; border-radius: 8px; font-weight: 600;">
                    <i class='bx bx-upload'></i> Upload Passport Photo
                </label>
                <p style="font-size: 12px; color: #6b7280; margin-top: 10px;">
                    <strong>Requirements:</strong> Recent passport photograph, white background, JPEG/PNG format, max 2MB
                </p>
            </div>
        </div>
        
        <script>
        document.getElementById('passport_photo').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                if (file.size > 2 * 1024 * 1024) {
                    alert('File size must be less than 2MB');
                    e.target.value = '';
                    return;
                }
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('passport-preview').innerHTML = '<img src="' + e.target.result + '" style="max-width: 150px; max-height: 180px; border-radius: 8px; border: 3px solid #10b981;">';
                };
                reader.readAsDataURL(file);
            }
        });
        </script>
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
            <label>Select Your Subjects (Up to 9 subjects) <span class="text-danger">*</span></label>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 10px;">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="subjects[]" id="subj_english" value="English Language">
                    <label class="form-check-label" for="subj_english">English Language</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="subjects[]" id="subj_math" value="Mathematics">
                    <label class="form-check-label" for="subj_math">Mathematics</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="subjects[]" id="subj_physics" value="Physics">
                    <label class="form-check-label" for="subj_physics">Physics</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="subjects[]" id="subj_chemistry" value="Chemistry">
                    <label class="form-check-label" for="subj_chemistry">Chemistry</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="subjects[]" id="subj_biology" value="Biology">
                    <label class="form-check-label" for="subj_biology">Biology</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="subjects[]" id="subj_economics" value="Economics">
                    <label class="form-check-label" for="subj_economics">Economics</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="subjects[]" id="subj_gov" value="Government">
                    <label class="form-check-label" for="subj_gov">Government/Civic Education</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="subjects[]" id="subj_lit" value="Literature">
                    <label class="form-check-label" for="subj_lit">Literature in English</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="subjects[]" id="subj_agric" value="Agricultural Science">
                    <label class="form-check-label" for="subj_agric">Agricultural Science</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="subjects[]" id="subj_further_math" value="Further Mathematics">
                    <label class="form-check-label" for="subj_further_math">Further Mathematics</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="subjects[]" id="subj_commerce" value="Commerce">
                    <label class="form-check-label" for="subj_commerce">Commerce</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="subjects[]" id="subj_accounting" value="Financial Accounting">
                    <label class="form-check-label" for="subj_accounting">Financial Accounting</label>
                </div>
            </div>
            <small class="form-text" style="display: block; margin-top: 8px;">Select all subjects you're registering for</small>
        </div>

        <div class="form-row">
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
        </div>
    </div>

    <!-- Emergency Contact -->
    <div class="form-section">
        <h4 class="section-title"><i class='bx bxs-phone'></i> Parent/Guardian Information</h4>
        
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
