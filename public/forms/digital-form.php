<?php
// public/forms/digital-form.php - Digital Skills Registration Form
?>

<div style="margin-bottom: 20px;">
    <h2>Digital Skills Program Registration</h2>
    <p style="color: #6b7280;">Web development, cybersecurity, and professional tech training.</p>
    <a href="?step=1" class="btn btn-outline-secondary btn-sm">← Change Program</a>
</div>

<form method="post" action="process-registration.php" enctype="multipart/form-data">
    <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrf) ?>">
    <input type="hidden" name="program_type" value="digital">
    <input type="hidden" name="registration_type" value="regular">
    
    <!-- Personal Information -->
    <div class="form-section">
        <h4 class="section-title"><i class='bx bxs-user'></i> Personal Information</h4>
        
        <!-- Passport Photo Upload -->
        <div class="form-row">
            <div class="form-group" style="width: 100%;">
                <label for="passport_digital">Passport Photo <span class="text-danger">*</span></label>
                <div class="passport-upload-wrapper" id="digitalPassportWrapper" style="border: 2px dashed #d1d5db; border-radius: 12px; padding: 20px; text-align: center; background: #f9fafb; transition: all 0.3s;">
                    <div id="digitalPassportPreviewArea" style="margin-bottom: 15px; display: none;">
                        <img id="digitalPassportPreview" src="" alt="Passport Preview" style="width: 120px; height: 120px; object-fit: cover; border-radius: 12px; border: 3px solid #17a2b8; box-shadow: 0 4px 12px rgba(0,0,0,0.1);">
                    </div>
                    <label for="passport_digital" style="cursor: pointer; display: block;">
                        <i class='bx bx-cloud-upload' style="font-size: 2rem; color: #17a2b8; display: block; margin-bottom: 8px;"></i>
                        <span style="color: #374151; font-weight: 500;">Click to upload passport photo</span>
                        <span style="display: block; font-size: 0.85rem; color: #9ca3af; margin-top: 4px;">JPG, JPEG, PNG (Max 2MB)</span>
                    </label>
                    <input type="file" id="passport_digital" name="passport" class="form-control" accept="image/jpeg,image/jpg,image/png" required style="display: none;">
                </div>
                <small class="form-text text-muted">Upload a recent passport photograph with white background</small>
            </div>
        </div>
        
        <script>
        document.getElementById('passport_digital').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                if (file.size > 2 * 1024 * 1024) {
                    alert('File size must be less than 2MB');
                    this.value = '';
                    return;
                }
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('digitalPassportPreview').src = e.target.result;
                    document.getElementById('digitalPassportPreviewArea').style.display = 'block';
                    document.getElementById('digitalPassportWrapper').style.borderColor = '#17a2b8';
                    document.getElementById('digitalPassportWrapper').style.background = '#f0f9ff';
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
    </div>

    <!-- Digital Skills-Specific Information -->
    <div class="form-section">
        <h4 class="section-title"><i class='bx bxs-devices'></i> Program Details</h4>
        
        <div class="form-group">
            <label for="skill_track">Skill Track <span class="text-danger">*</span></label>
            <select id="skill_track" name="skill_track" class="form-control" required>
                <option value="">Select Track</option>
                <option value="Web Development">Web Development (HTML, CSS, JavaScript, PHP)</option>
                <option value="Cybersecurity">Cybersecurity & Ethical Hacking</option>
                <option value="Graphic Design">Graphic Design & UI/UX</option>
                <option value="Digital Marketing">Digital Marketing</option>
                <option value="Data Analysis">Data Analysis & Excel</option>
                <option value="Mobile App Development">Mobile App Development</option>
            </select>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="skill_level">Current Skill Level <span class="text-danger">*</span></label>
                <select id="skill_level" name="skill_level" class="form-control" required>
                    <option value="">Select Level</option>
                    <option value="Beginner">Beginner (No prior experience)</option>
                    <option value="Intermediate">Intermediate (Some experience)</option>
                    <option value="Advanced">Advanced (Experienced)</option>
                </select>
            </div>
            <div class="form-group">
                <label for="has_laptop">Do you have a laptop? <span class="text-danger">*</span></label>
                <select id="has_laptop" name="has_laptop" class="form-control" required>
                    <option value="">Select Option</option>
                    <option value="Yes">Yes</option>
                    <option value="No">No (I'll need to use academy computers)</option>
                </select>
            </div>
        </div>

        <div class="form-group">
            <label for="career_goals">Career Goals & Aspirations <span class="text-danger">*</span></label>
            <textarea id="career_goals" name="career_goals" class="form-control" rows="3" required placeholder="Tell us about your career aspirations and what you hope to achieve with this program..."></textarea>
        </div>

        <div class="form-group">
            <label for="study_goals">Why This Program? What Are Your Expectations? <span class="text-danger">*</span></label>
            <textarea id="study_goals" name="study_goals" class="form-control" rows="3" required placeholder="What specific outcomes do you want from this digital skills training? Where do you see yourself after completion?"></textarea>
        </div>

        <div class="form-group">
            <label for="learning_preference">Preferred Learning Style <span class="text-danger">*</span></label>
            <select id="learning_preference" name="learning_preference" class="form-control" required>
                <option value="">Select Learning Style</option>
                <option value="Structured Classes">Structured Classes with Fixed Schedule</option>
                <option value="Flexible/Self-paced">Flexible/Self-paced Learning</option>
                <option value="Mixed Approach">Mixed Approach (Classes + Self-study)</option>
                <option value="One-on-One Mentoring">One-on-One Mentoring</option>
                <option value="Group Projects">Project-Based Group Learning</option>
            </select>
        </div>

        <div class="form-group">
            <label for="tech_interests">Other Tech Interests (Optional)</label>
            <textarea id="tech_interests" name="tech_interests" class="form-control" rows="2" placeholder="Any other tech areas you're interested in? (AI, Blockchain, Cloud Computing, etc.)"></textarea>
        </div>

        <div class="form-group">
            <label for="previous_experience">Previous Tech Experience <span class="text-danger">*</span></label>
            <textarea id="previous_experience" name="previous_experience" class="form-control" rows="3" required placeholder="Any prior courses, self-study, or projects you've worked on..."></textarea>
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
            <input type="text" id="emergency_relationship" name="emergency_relationship" class="form-control" placeholder="e.g., Parent, Guardian, Spouse">
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
