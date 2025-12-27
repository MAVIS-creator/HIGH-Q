<?php
// public/forms/international-form.php - International Programs Registration Form
?>

<div style="margin-bottom: 20px;">
    <h2>International Programs Registration</h2>
    <p style="color: #6b7280;">SAT, IELTS, TOEFL, JUPEB, and other international exam preparation.</p>
    <a href="?step=1" class="btn btn-outline-secondary btn-sm">← Change Program</a>
</div>

<form method="post" action="process-registration.php" enctype="multipart/form-data">
    <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrf) ?>">
    <input type="hidden" name="program_type" value="international">
    <input type="hidden" name="registration_type" value="regular">
    
    <!-- Personal Information -->
    <div class="form-section">
        <h4 class="section-title"><i class='bx bxs-user'></i> Personal Information</h4>
        
        <!-- Passport Photo Upload -->
        <div class="form-row">
            <div class="form-group" style="width: 100%;">
                <label for="passport_intl">Passport Photo <span class="text-danger">*</span></label>
                <div class="passport-upload-wrapper" id="intlPassportWrapper" style="border: 2px dashed #d1d5db; border-radius: 12px; padding: 20px; text-align: center; background: #f9fafb; transition: all 0.3s;">
                    <div id="intlPassportPreviewArea" style="margin-bottom: 15px; display: none;">
                        <img id="intlPassportPreview" src="" alt="Passport Preview" style="width: 120px; height: 120px; object-fit: cover; border-radius: 12px; border: 3px solid #6f42c1; box-shadow: 0 4px 12px rgba(0,0,0,0.1);">
                    </div>
                    <label for="passport_intl" style="cursor: pointer; display: block;">
                        <i class='bx bx-cloud-upload' style="font-size: 2rem; color: #6f42c1; display: block; margin-bottom: 8px;"></i>
                        <span style="color: #374151; font-weight: 500;">Click to upload passport photo</span>
                        <span style="display: block; font-size: 0.85rem; color: #9ca3af; margin-top: 4px;">JPG, JPEG, PNG (Max 2MB)</span>
                    </label>
                    <input type="file" id="passport_intl" name="passport" class="form-control" accept="image/jpeg,image/jpg,image/png" required style="display: none;">
                </div>
                <small class="form-text text-muted">Upload a recent passport photograph with white background</small>
            </div>
        </div>
        
        <script>
        document.getElementById('passport_intl').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                if (file.size > 2 * 1024 * 1024) {
                    alert('File size must be less than 2MB');
                    this.value = '';
                    return;
                }
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('intlPassportPreview').src = e.target.result;
                    document.getElementById('intlPassportPreviewArea').style.display = 'block';
                    document.getElementById('intlPassportWrapper').style.borderColor = '#6f42c1';
                    document.getElementById('intlPassportWrapper').style.background = '#f8f5ff';
                };
                reader.readAsDataURL(file);
            }
        });
        </script>
        
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
                <label for="state_of_origin">State of Origin</label>
                <input type="text" id="state_of_origin" name="state_of_origin" class="form-control">
            </div>
        </div>
    </div>

    <!-- International Program-Specific Information -->
    <div class="form-section">
        <h4 class="section-title"><i class='bx bxs-world'></i> Program Details</h4>
        
        <div class="form-group">
            <label for="program_choice">Select Program <span class="text-danger">*</span></label>
            <select id="program_choice" name="program_choice" class="form-control" required>
                <option value="">Select Program</option>
                <option value="SAT">SAT (Scholastic Assessment Test)</option>
                <option value="IELTS">IELTS (International English Language Testing System)</option>
                <option value="TOEFL">TOEFL (Test of English as a Foreign Language)</option>
                <option value="JUPEB">JUPEB (Joint Universities Preliminary Examinations Board)</option>
                <option value="GRE">GRE (Graduate Record Examination)</option>
                <option value="GMAT">GMAT (Graduate Management Admission Test)</option>
            </select>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="target_country">Target Country <span class="text-danger">*</span></label>
                <select id="target_country" name="target_country" class="form-control" required>
                    <option value="">Select Country</option>
                    <option value="USA">United States</option>
                    <option value="UK">United Kingdom</option>
                    <option value="Canada">Canada</option>
                    <option value="Australia">Australia</option>
                    <option value="Germany">Germany</option>
                    <option value="France">France</option>
                    <option value="Other">Other</option>
                </select>
            </div>
            <div class="form-group">
                <label for="passport_status">International Passport Status <span class="text-danger">*</span></label>
                <select id="passport_status" name="passport_status" class="form-control" required>
                    <option value="">Select Status</option>
                    <option value="Have Valid Passport">Have Valid Passport</option>
                    <option value="Applying for Passport">Applying for Passport</option>
                    <option value="No Passport">No Passport Yet</option>
                </select>
            </div>
        </div>

        <div class="form-group">
            <label for="intended_course">Intended Course/Major</label>
            <input type="text" id="intended_course" name="intended_course" class="form-control" placeholder="e.g., Computer Science, Medicine, Engineering">
        </div>

        <div class="form-group">
            <label for="target_institution">Target University/Institution (Optional)</label>
            <input type="text" id="target_institution" name="target_institution" class="form-control" placeholder="e.g., Harvard, Oxford, MIT">
        </div>

        <div class="form-group">
            <label for="education_level">Current Education Level <span class="text-danger">*</span></label>
            <select id="education_level" name="education_level" class="form-control" required>
                <option value="">Select Level</option>
                <option value="Secondary School Student">Secondary School Student (SS1-SS3)</option>
                <option value="O-Level Graduate">O-Level Graduate</option>
                <option value="Undergraduate">Undergraduate</option>
                <option value="Graduate">Graduate (Bachelor's)</option>
                <option value="Postgraduate">Postgraduate (Master's/PhD)</option>
            </select>
        </div>

        <div class="form-group">
            <label for="study_goals">Study Goals & Motivations</label>
            <textarea id="study_goals" name="study_goals" class="form-control" rows="4" placeholder="Tell us why you want to study abroad and what you hope to achieve..."></textarea>
        </div>
    </div>

    <!-- Emergency Contact -->
    <div class="form-section">
        <h4 class="section-title"><i class='bx bxs-phone'></i> Emergency Contact</h4>
        
        <div class="form-row">
            <div class="form-group">
                <label for="emergency_name">Emergency Contact Name <span class="text-danger">*</span></label>
                <input type="text" id="emergency_name" name="emergency_name" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="emergency_phone">Emergency Contact Phone <span class="text-danger">*</span></label>
                <input type="tel" id="emergency_phone" name="emergency_phone" class="form-control" required>
            </div>
        </div>

        <div class="form-group">
            <label for="emergency_relationship">Relationship</label>
            <input type="text" id="emergency_relationship" name="emergency_relationship" class="form-control" placeholder="e.g., Parent, Guardian">
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
