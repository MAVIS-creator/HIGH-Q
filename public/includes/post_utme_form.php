<?php
// This file contains the POST-UTME form inner markup. It is designed to be
// included inside a surrounding .card container so it does NOT output an
// outer .card wrapper. Expects the following variables to be set by the
// including page: $post_utme_form_fee, $post_utme_tutor_fee, $post_csrf
// and optionally $post_registration_action (defaults to './api/register_post_utme.php').

if (!isset($post_registration_action)) $post_registration_action = './api/register_post_utme.php';
if (!isset($post_utme_form_fee)) $post_utme_form_fee = 1000;
if (!isset($post_utme_tutor_fee)) $post_utme_tutor_fee = 8000;
if (!isset($post_csrf)) $post_csrf = '';
?>

<!-- POST-UTME form (include) -->
<div id="postUtmeForm" class="form-section" style="display:none;">
    <h3>POST UTME Registration Form</h3>
    <p class="card-desc">Complete this form to register for POST UTME preparation. Required fields are marked with *</p>

    <form action="<?= htmlspecialchars($post_registration_action) ?>" method="POST" enctype="multipart/form-data" id="postUtmeRegistration">
        <input type="hidden" name="_csrf" value="<?= htmlspecialchars($post_csrf) ?>">

        <div class="form-steps" aria-hidden="false">
            <div class="step-indicator active" data-step="1"></div>
            <div class="step-indicator" data-step="2"></div>
            <div class="step-indicator" data-step="3"></div>
            <div class="step-indicator" data-step="4"></div>
            <div class="step-indicator" data-step="5"></div>
        </div>

        <!-- Steps (kept compact) -->
        <div class="step-content active" data-step="1">
            <h4>Personal Information</h4>
            <div class="passport-upload">
                <img id="passportPreview" class="passport-preview" alt="Passport preview">
                <label class="upload-btn">
                    <i class="bx bx-upload"></i>
                    Upload Passport Photo
                    <input type="file" name="passport" accept="image/*" style="display: none" required>
                </label>
                <div class="small-meta">Upload a passport-size photo with visible face</div>
            </div>

            <div class="form-grid">
                <div class="form-group"><label>Institution *</label><input type="text" name="institution" required></div>
                <div class="form-group"><label>First Name *</label><input type="text" name="first_name" required></div>
                <div class="form-group"><label>Surname *</label><input type="text" name="surname" required></div>
                <div class="form-group"><label>Other Names</label><input type="text" name="other_name"></div>
                <div class="form-group"><label>Gender *</label><select name="gender" required><option value="">Select Gender</option><option value="male">Male</option><option value="female">Female</option></select></div>
                <div class="form-group"><label>Contact Email *</label><input type="email" name="email" required></div>
                <div class="form-group"><label>Parent Phone Number *</label><input type="tel" name="parent_phone" required></div>
                <div class="form-group"><label>Address *</label><textarea name="address" required></textarea></div>
            </div>
        </div>

        <div class="step-content" data-step="2">
            <h4>Additional Information</h4>
            <div class="form-grid">
                <div class="form-group"><label>NIN Number</label><input type="text" name="nin_number"></div>
                <div class="form-group"><label>State of Origin *</label><input type="text" name="state_of_origin" required></div>
                <div class="form-group"><label>Local Government *</label><input type="text" name="local_government" required></div>
                <div class="form-group"><label>Place of Birth</label><input type="text" name="place_of_birth"></div>
                <div class="form-group"><label>Nationality *</label><input type="text" name="nationality" required></div>
                <div class="form-group"><label>Religion</label><input type="text" name="religion"></div>
                <div class="form-group"><label>Marital Status</label><select name="marital_status"><option value="single">Single</option><option value="married">Married</option><option value="other">Other</option></select></div>
                <div class="form-group"><label>Any Disability</label><input type="text" name="disability" placeholder="Leave blank if none"></div>
            </div>
        </div>

        <div class="step-content" data-step="3">
            <h4>JAMB and Course Details</h4>
            <div class="form-grid">
                <div class="form-group"><label>JAMB Registration Number *</label><input type="text" name="jamb_registration_number" required></div>
                <div class="form-group"><label>JAMB Score *</label><input type="number" name="jamb_score" required></div>
                <div class="form-group"><label>Mode of Entry</label><input type="text" name="mode_of_entry"></div>
                <div class="form-group"><label>First Choice Course *</label><input type="text" name="course_first_choice" required></div>
                <div class="form-group"><label>Second Choice Course</label><input type="text" name="course_second_choice"></div>
                <div class="form-group"><label>First Choice Institution *</label><input type="text" name="institution_first_choice" required></div>
            </div>

            <h4>JAMB Subjects and Scores</h4>
            <div id="jambSubjects" class="form-grid">
                <div class="form-group"><label>English Language *</label><input type="number" name="jamb_subjects[english]" required min="0" max="100"></div>
                <div class="form-group"><label>Mathematics</label><input type="number" name="jamb_subjects[mathematics]" min="0" max="100"></div>
                <div class="form-group"><label>Third Subject</label><input type="number" name="jamb_subjects[subject3]" min="0" max="100"></div>
                <div class="form-group"><label>Fourth Subject</label><input type="number" name="jamb_subjects[subject4]" min="0" max="100"></div>
            </div>
        </div>

        <div class="step-content" data-step="4">
            <h4>Educational Background</h4>
            <div class="form-grid">
                <div class="form-group"><label>Primary School Name</label><input type="text" name="primary_school"></div>
                <div class="form-group"><label>Year Ended</label><input type="number" name="primary_year_ended" min="2000" max="2025"></div>
                <div class="form-group"><label>Secondary School Name *</label><input type="text" name="secondary_school" required></div>
                <div class="form-group"><label>Year Ended *</label><input type="number" name="secondary_year_ended" required min="2000" max="2025"></div>
            </div>

            <h4>O'Level Details</h4>
            <div class="form-grid">
                <div class="form-group"><label>Exam Type *</label><select name="exam_type" required><option value="WAEC">WAEC</option><option value="NECO">NECO</option><option value="GCE">GCE</option></select></div>
                <div class="form-group"><label>Candidate Name *</label><input type="text" name="candidate_name" required></div>
                <div class="form-group"><label>Exam Number *</label><input type="text" name="exam_number" required></div>
                <div class="form-group"><label>Exam Year and Month *</label><input type="text" name="exam_year_month" required></div>
            </div>

            <h4>O'Level Results</h4>
            <div id="olevelSubjects" class="form-grid">
                <div class="form-group"><label>English Language *</label><input type="text" name="olevel_results[english]" required placeholder="Enter grade"></div>
                <div class="form-group"><label>Mathematics *</label><input type="text" name="olevel_results[mathematics]" required placeholder="Enter grade"></div>
                <div class="form-group"><label>Biology</label><input type="text" name="olevel_results[biology]" placeholder="Enter grade"></div>
                <div class="form-group"><label>Chemistry</label><input type="text" name="olevel_results[chemistry]" placeholder="Enter grade"></div>
                <div class="form-group"><label>Physics</label><input type="text" name="olevel_results[physics]" placeholder="Enter grade"></div>
                <div class="form-group"><label>Government</label><input type="text" name="olevel_results[government]" placeholder="Enter grade"></div>
            </div>
        </div>

        <div class="step-content" data-step="5">
            <h4>Parent Information</h4>
            <div class="form-grid">
                <div class="form-group"><label>Father's Name</label><input type="text" name="father_name"></div>
                <div class="form-group"><label>Father's Phone</label><input type="tel" name="father_phone"></div>
                <div class="form-group"><label>Father's Email</label><input type="email" name="father_email"></div>
                <div class="form-group"><label>Father's Occupation</label><input type="text" name="father_occupation"></div>
                <div class="form-group"><label>Mother's Name</label><input type="text" name="mother_name"></div>
                <div class="form-group"><label>Mother's Phone</label><input type="tel" name="mother_phone"></div>
                <div class="form-group"><label>Mother's Occupation</label><input type="text" name="mother_occupation"></div>
            </div>

            <h4>Next of Kin Details</h4>
            <div class="form-grid">
                <div class="form-group"><label>Next of Kin Name *</label><input type="text" name="next_of_kin_name" required></div>
                <div class="form-group"><label>Next of Kin Phone *</label><input type="tel" name="next_of_kin_phone" required></div>
                <div class="form-group"><label>Next of Kin Email</label><input type="email" name="next_of_kin_email"></div>
                <div class="form-group"><label>Next of Kin Address *</label><textarea name="next_of_kin_address" required></textarea></div>
                <div class="form-group"><label>Relationship *</label><input type="text" name="next_of_kin_relationship" required></div>
            </div>

            <div class="fee-options">
                <h4>Registration Fees</h4>
                <div class="fee-option"><div><strong>Form Fee (Required)</strong><div class="small-meta">POST UTME registration form fee</div></div><div>₦<?= number_format($post_utme_form_fee,2) ?></div></div>
                <div class="fee-option"><div><strong>Tutorial Fee (Optional)</strong><div class="small-meta">Access to POST UTME preparation classes</div></div><div><label class="checkbox-label"><input type="checkbox" name="include_tutorial" value="1"> ₦<?= number_format($post_utme_tutor_fee,2) ?></label></div></div>
                <div class="fee-option" style="border-top: 2px solid #e5e7eb; margin-top: 12px; padding-top: 16px;"><div><strong>Total Amount</strong><div class="small-meta">Including selected options</div></div><div><strong id="totalAmount">₦<?= number_format($post_utme_form_fee,2) ?></strong></div></div>
            </div>

        </div>

        <div class="step-navigation">
            <button type="button" class="btn-secondary" id="prevStep" style="display:none">Previous</button>
            <button type="button" class="btn-primary" id="nextStep">Next</button>
            <button type="submit" class="btn-primary" id="submitForm" style="display:none">Submit Registration</button>
        </div>
    </form>
</div>

<script>
// Scoped JS for the POST-UTME include. Uses IDs that are unique to the include.
(function(){
    const form = document.getElementById('postUtmeRegistration');
    if (!form) return;
    const steps = Array.from(document.querySelectorAll('.step-content'));
    const indicators = Array.from(document.querySelectorAll('.step-indicator'));
    const nextBtn = document.getElementById('nextStep');
    const prevBtn = document.getElementById('prevStep');
    const submitBtn = document.getElementById('submitForm');
    let currentStep = 1;

    function validateStep(step) {
        const currentStepEl = document.querySelector(`.step-content[data-step="${step}"]`);
        if (!currentStepEl) return true;
        const requiredInputs = currentStepEl.querySelectorAll('input[required], select[required], textarea[required]');
        let isValid = true;
        requiredInputs.forEach(input => { if (!input.value.trim()) { isValid = false; input.classList.add('error'); } else input.classList.remove('error'); });
        return isValid;
    }

    function showStep(step) {
        if (step > currentStep && !validateStep(currentStep)) { alert('Please fill in all required fields before proceeding.'); return; }
        steps.forEach(s => s.classList.remove('active'));
        indicators.forEach(i => i.classList.remove('active'));
        const el = document.querySelector(`.step-content[data-step="${step}"]`);
        if (el) el.classList.add('active');
        for (let i=1;i<=step;i++){ const ind = document.querySelector(`.step-indicator[data-step="${i}"]`); if (ind) ind.classList.add('active'); }
        prevBtn.style.display = step === 1 ? 'none' : 'inline-block';
        if (step === steps.length) { nextBtn.style.display = 'none'; submitBtn.style.display = 'inline-block'; } else { nextBtn.style.display = 'inline-block'; submitBtn.style.display = 'none'; }
        currentStep = step;
    }

    nextBtn?.addEventListener('click', ()=>{ if (currentStep < steps.length) showStep(currentStep+1); });
    prevBtn?.addEventListener('click', ()=>{ if (currentStep>1) showStep(currentStep-1); });

    // passport preview
    const passportInput = form.querySelector('input[name="passport"]');
    const passportPreview = document.getElementById('passportPreview');
    passportInput?.addEventListener('change', function(){ const file = this.files[0]; if (file){ const reader = new FileReader(); reader.onload = function(e){ passportPreview.src = e.target.result; passportPreview.style.display = 'block'; }; reader.readAsDataURL(file); } });

    // tutorial fee calculator
    const tutorialCheckbox = form.querySelector('input[name="include_tutorial"]');
    const totalAmountEl = document.getElementById('totalAmount');
    const formFee = <?= intval($post_utme_form_fee) ?>;
    const tutorialFee = <?= intval($post_utme_tutor_fee) ?>;
    tutorialCheckbox?.addEventListener('change', function(){ const total = formFee + (this.checked ? tutorialFee : 0); totalAmountEl.textContent = '₦' + total.toLocaleString('en-NG',{minimumFractionDigits:2,maximumFractionDigits:2}); });

    // submit via fetch
    form.addEventListener('submit', async function(e){ e.preventDefault(); if (!validateStep(currentStep)) { alert('Please fill in all required fields before submitting.'); return; } const formData = new FormData(this); try{ const res = await fetch(this.action,{method:'POST',body:formData,credentials:'same-origin'}); const json = await res.json(); if (json.status === 'ok') { window.location.href = json.payment_url || json.redirect || './payments_wait.php?ref='+encodeURIComponent(json.reference||''); } else { alert(json.message || 'Registration failed.'); } } catch(err){ alert('An error occurred. Please try again.'); } });
})();
</script>
