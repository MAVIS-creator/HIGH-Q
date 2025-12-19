<?php
// public/post-utme.php - Post-UTME registration moved from register.php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/csrf.php';
require_once __DIR__ . '/config/functions.php';
$cfg = require __DIR__ . '/../config/payments.php';
// Determine whether Paystack is actually configured (placeholder keys contain 'xxx')
$paystackEnabled = !empty($cfg['paystack']['public']) && strpos($cfg['paystack']['public'], 'xxx') === false;

$errors = [];
$success = '';

// Fees
$form_fee = 1000; // ₦1,000 form processing
$card_fee = 1500; // ₦1,500 card fee

function generatePaymentReference($prefix='PAY') {
    return $prefix . '-' . date('YmdHis') . '-' . substr(bin2hex(random_bytes(3)),0,6);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['_csrf_token'] ?? '';
    if (!verifyToken('signup_form', $token)) { $errors[] = 'Invalid CSRF token.'; }

    // Basic Post-UTME input checks performed below
    // Determine registration_type hint
    $posted_reg_type = isset($_POST['registration_type']) ? trim((string)$_POST['registration_type']) : '';
    $submitted_form_action = isset($_POST['form_action']) ? trim((string)$_POST['form_action']) : '';
    $detected_reg_type = 'postutme';

    // Validate minimal identity fields
    if (trim($_POST['first_name_post'] ?? '') === '' || trim($_POST['surname'] ?? '') === '') {
        $errors[] = 'First name and surname are required for Post-UTME registration.';
    }
    if (trim($_POST['jamb_registration_number'] ?? '') === '' && trim((string)($_POST['jamb_score'] ?? '')) === '') {
        $errors[] = 'Provide a JAMB registration number or a JAMB score for Post-UTME registration.';
    }

    // Read a subset of Post-UTME fields (best-effort sanitization)
    $institution = trim($_POST['institution'] ?? '') ?: null;
    $first_name_post = trim($_POST['first_name_post'] ?? '') ?: null;
    $surname = trim($_POST['surname'] ?? '') ?: null;
    $other_name = trim($_POST['other_name'] ?? '') ?: null;
    $post_gender = trim($_POST['post_gender'] ?? '') ?: null;
    $address = trim($_POST['address'] ?? '') ?: null;
    $parent_phone = trim($_POST['parent_phone'] ?? '') ?: null;
    $email_post = trim($_POST['email_post'] ?? '') ?: null;
    $nin_number = trim($_POST['nin_number'] ?? '') ?: null;
    $state_of_origin = trim($_POST['state_of_origin'] ?? '') ?: null;
    $local_government = trim($_POST['local_government'] ?? '') ?: null;
    $place_of_birth = trim($_POST['place_of_birth'] ?? '') ?: null;
    $nationality = trim($_POST['nationality'] ?? '') ?: null;
    $religion = trim($_POST['religion'] ?? '') ?: null;

    $jamb_registration_number = trim($_POST['jamb_registration_number'] ?? '') ?: null;
    $jamb_score = ($_POST['jamb_score'] ?? '') !== '' ? intval($_POST['jamb_score']) : null;

    // Build jamb_subjects array and jamb_subjects_text
    $jamb_subjects = [];
    $jamb_pairs = [];
    for ($j=1;$j<=4;$j++) {
        $sj = trim($_POST['jamb_subj_' . $j] ?? '');
        $sc = trim($_POST['jamb_score_' . $j] ?? '');
        if ($sj !== '') {
            $jamb_subjects[] = ['subject' => $sj, 'score' => ($sc !== '' ? intval($sc) : null)];
            $jamb_pairs[] = $sj . ($sc !== '' ? ' (' . $sc . ')' : '');
        }
    }
    $jamb_subjects_text = !empty($jamb_pairs) ? implode('; ', $jamb_pairs) : null;

    // Server-side validation for JAMB scores
    if ($jamb_score !== null && ($jamb_score < 0 || $jamb_score > 100)) {
        $errors[] = 'JAMB score must be between 0 and 100.';
    }
    for ($jsi = 1; $jsi <= 4; $jsi++) {
        $sub = trim($_POST['jamb_subj_' . $jsi] ?? '');
        $scRaw = trim($_POST['jamb_score_' . $jsi] ?? '');
        if ($scRaw !== '') {
            if (!is_numeric($scRaw) || intval($scRaw) < 0 || intval($scRaw) > 100) {
                $errors[] = 'JAMB subject ' . $jsi . ' score must be a number between 0 and 100.';
            }
        }
        if ($jsi > 1 && $sub === '') {
            $errors[] = 'Please provide three other JAMB subjects in addition to English.';
        }
    }
    $sub1 = trim($_POST['jamb_subj_1'] ?? '');
    if ($sub1 === '' || !preg_match('/eng/i', $sub1)) {
        $errors[] = 'JAMB Subject 1 must be English.';
    }

    // Collect O'Level inputs (up to 8)
    $olevel_results = [];
    for ($i=1;$i<=8;$i++) {
        $sub = trim($_POST['olevel_subj_' . $i] ?? '');
        $gr = trim($_POST['olevel_grade_' . $i] ?? '');
        if ($sub !== '') $olevel_results[] = ['subject'=>$sub,'grade'=>$gr];
    }

    // Fees: compulsory form fee 1,000 and optional tutor fee 8,000
    $post_form_fee = 1000.00;
    $post_tutor_fee = (!empty($_POST['post_tutor_fee']) && $_POST['post_tutor_fee'] === '1') ? 8000.00 : 0.00;
    $service_charge = round(mt_rand(0, 16754) / 100.0, 2);
    $total_amount = $post_form_fee + $post_tutor_fee + $service_charge;

    // If no errors, insert record and create payment
    if (empty($errors)) {
        try {
            $pdo->beginTransaction();

            $insertSql = 'INSERT INTO post_utme_registrations (user_id, status, institution, first_name, surname, other_name, gender, address, parent_phone, email, nin_number, state_of_origin, local_government, place_of_birth, nationality, religion, mode_of_entry, marital_status, disability, jamb_registration_number, jamb_score, jamb_subjects, jamb_subjects_text, course_first_choice, course_second_choice, institution_first_choice, father_name, father_phone, father_email, father_occupation, mother_name, mother_phone, mother_occupation, primary_school, primary_year_ended, secondary_school, secondary_year_ended, exam_type, candidate_name, exam_number, exam_year_month, olevel_results, waec_token, waec_serial, sponsor_name, sponsor_address, sponsor_email, sponsor_phone, sponsor_relationship, next_of_kin_name, next_of_kin_address, next_of_kin_email, next_of_kin_phone, next_of_kin_relationship, passport_photo, payment_status, form_fee_paid, tutor_fee_paid, created_at) VALUES (NULL, :status, :institution, :first_name, :surname, :other_name, :gender, :address, :parent_phone, :email, :nin_number, :state_of_origin, :local_government, :place_of_birth, :nationality, :religion, :mode_of_entry, :marital_status, :disability, :jamb_registration_number, :jamb_score, :jamb_subjects, :jamb_subjects_text, :course_first_choice, :course_second_choice, :institution_first_choice, :father_name, :father_phone, :father_email, :father_occupation, :mother_name, :mother_phone, :mother_occupation, :primary_school, :primary_year_ended, :secondary_school, :secondary_year_ended, :exam_type, :candidate_name, :exam_number, :exam_year_month, :olevel_results, :waec_token, :waec_serial, :sponsor_name, :sponsor_address, :sponsor_email, :sponsor_phone, :sponsor_relationship, :next_of_kin_name, :next_of_kin_address, :next_of_kin_email, :next_of_kin_phone, :next_of_kin_relationship, :passport_photo, :payment_status, :form_fee_paid, :tutor_fee_paid, NOW())';

            $stmtIns = $pdo->prepare($insertSql);
            $stmtIns->execute([
                ':status' => 'pending',
                ':institution' => $institution,
                ':first_name' => $first_name_post,
                ':surname' => $surname,
                ':other_name' => $other_name,
                ':gender' => $post_gender,
                ':address' => $address,
                ':parent_phone' => $parent_phone,
                ':email' => $email_post ?: null,
                ':nin_number' => $nin_number,
                ':state_of_origin' => $state_of_origin,
                ':local_government' => $local_government,
                ':place_of_birth' => $place_of_birth,
                ':nationality' => $nationality,
                ':religion' => $religion,
                ':mode_of_entry' => trim($_POST['mode_of_entry'] ?? '') ?: null,
                ':marital_status' => trim($_POST['marital_status'] ?? '') ?: null,
                ':disability' => trim($_POST['disability'] ?? '') ?: null,
                ':jamb_registration_number' => $jamb_registration_number,
                ':jamb_score' => $jamb_score,
                ':jamb_subjects' => $jamb_subjects ? json_encode($jamb_subjects, JSON_UNESCAPED_UNICODE) : null,
                ':jamb_subjects_text' => $jamb_subjects_text,
                ':course_first_choice' => trim($_POST['course_first_choice'] ?? '') ?: null,
                ':course_second_choice' => trim($_POST['course_second_choice'] ?? '') ?: null,
                ':institution_first_choice' => trim($_POST['institution_first_choice'] ?? '') ?: null,
                ':father_name' => trim($_POST['father_name'] ?? '') ?: null,
                ':father_phone' => trim($_POST['father_phone'] ?? '') ?: null,
                ':father_email' => trim($_POST['father_email'] ?? '') ?: null,
                ':father_occupation' => trim($_POST['father_occupation'] ?? '') ?: null,
                ':mother_name' => trim($_POST['mother_name'] ?? '') ?: null,
                ':mother_phone' => trim($_POST['mother_phone'] ?? '') ?: null,
                ':mother_occupation' => trim($_POST['mother_occupation'] ?? '') ?: null,
                ':primary_school' => trim($_POST['primary_school'] ?? '') ?: null,
                ':primary_year_ended' => ($_POST['primary_year_ended'] ?? '') !== '' ? intval($_POST['primary_year_ended']) : null,
                ':secondary_school' => trim($_POST['secondary_school'] ?? '') ?: null,
                ':secondary_year_ended' => ($_POST['secondary_year_ended'] ?? '') !== '' ? intval($_POST['secondary_year_ended']) : null,
                ':exam_type' => trim($_POST['exam_type'] ?? '') ?: null,
                ':candidate_name' => trim($_POST['candidate_name'] ?? '') ?: null,
                ':exam_number' => trim($_POST['exam_number'] ?? '') ?: null,
                ':exam_year_month' => trim($_POST['exam_year_month'] ?? '') ?: null,
                ':olevel_results' => !empty($olevel_results) ? json_encode($olevel_results, JSON_UNESCAPED_UNICODE) : null,
                ':waec_token' => trim($_POST['waec_token'] ?? '') ?: null,
                ':waec_serial' => trim($_POST['waec_serial'] ?? '') ?: null,
                ':sponsor_name' => trim($_POST['sponsor_name'] ?? '') ?: null,
                ':sponsor_address' => trim($_POST['sponsor_address'] ?? '') ?: null,
                ':sponsor_email' => trim($_POST['sponsor_email'] ?? '') ?: null,
                ':sponsor_phone' => trim($_POST['sponsor_phone'] ?? '') ?: null,
                ':sponsor_relationship' => trim($_POST['sponsor_relationship'] ?? '') ?: null,
                ':next_of_kin_name' => trim($_POST['next_of_kin_name'] ?? '') ?: null,
                ':next_of_kin_address' => trim($_POST['next_of_kin_address'] ?? '') ?: null,
                ':next_of_kin_email' => trim($_POST['next_of_kin_email'] ?? '') ?: null,
                ':next_of_kin_phone' => trim($_POST['next_of_kin_phone'] ?? '') ?: null,
                ':next_of_kin_relationship' => trim($_POST['next_of_kin_relationship'] ?? '') ?: null,
                ':passport_photo' => null,
                ':payment_status' => 'pending',
                ':form_fee_paid' => 0,
                ':tutor_fee_paid' => (!empty($_POST['post_tutor_fee']) && $_POST['post_tutor_fee']==='1') ? 1 : 0,
            ]);
            $newId = $pdo->lastInsertId();

            // handle passport upload if present
            if (!empty($_FILES['passport']) && $_FILES['passport']['error'] === UPLOAD_ERR_OK) {
                $u = $_FILES['passport'];
                $ext = pathinfo($u['name'], PATHINFO_EXTENSION);
                $allowed = ['jpg','jpeg','png'];
                if (in_array(strtolower($ext), $allowed)) {
                    $dstDir = __DIR__ . '/uploads/passports';
                    if (!is_dir($dstDir)) @mkdir($dstDir, 0755, true);
                    $fname = 'postutme_passport_' . $newId . '_' . time() . '.' . $ext;
                    $dst = $dstDir . '/' . $fname;
                    if (move_uploaded_file($u['tmp_name'], $dst)) {
                        if (function_exists('app_url')) {
                            $base = rtrim(app_url(''), '/');
                            $fullUrl = $base . '/uploads/passports/' . $fname;
                        } else {
                            $proto = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
                            $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
                            $scriptDir = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
                            $fullUrl = rtrim($proto . '://' . $host, '/') . $scriptDir . '/uploads/passports/' . $fname;
                        }
                        $upd = $pdo->prepare('UPDATE post_utme_registrations SET passport_photo = ? WHERE id = ?');
                        $upd->execute([$fullUrl, $newId]);
                    }
                }
            }

            // create payment placeholder for Post-UTME
            $reference = generatePaymentReference('PTU');
            $paymentMetadata = json_encode([
                'components' => [
                    'post_form_fee' => number_format((float)$post_form_fee, 2, '.', ''),
                    'tutor_fee' => number_format((float)$post_tutor_fee, 2, '.', ''),
                    'service_charge' => number_format((float)$service_charge, 2, '.', ''),
                ],
                'total' => number_format((float)$total_amount, 2, '.', ''),
                'registration_type' => 'postutme'
            ], JSON_UNESCAPED_SLASHES);
            $insP = $pdo->prepare('INSERT INTO payments (student_id, amount, payment_method, reference, status, created_at, metadata, form_fee_paid, tutor_fee_paid, registration_type) VALUES (NULL, ?, ?, ?, "pending", NOW(), ?, ?, ?, "postutme")');
            $insP->execute([$total_amount, 'bank', $reference, $paymentMetadata, 0, (!empty($_POST['post_tutor_fee']) && $_POST['post_tutor_fee']==='1') ? 1 : 0]);
            $paymentId = $pdo->lastInsertId();

            $pdo->commit();

            // Create notification
            try { $insNotif = $pdo->prepare('INSERT INTO notifications (user_id, title, body, type, metadata, is_read, created_at) VALUES (NULL,?,?,?,?,0,NOW())'); $insNotif->execute(['New Post-UTME registration', ($first_name_post ?: '') . ' submitted a Post-UTME registration', 'postutme', json_encode(['id'=>$newId,'email'=>$email_post ?: null])]); } catch (Throwable $_) {}

            // set session and redirect to payment wait page
            if (session_status() !== PHP_SESSION_ACTIVE) session_start();
            $_SESSION['last_payment_id'] = $paymentId;
            $_SESSION['last_payment_reference'] = $reference;
            // Always use payments_wait to avoid missing /pay route on some hosts
            $redirect = function_exists('app_url')
                ? app_url('payments_wait.php?ref=' . urlencode($reference))
                : 'payments_wait.php?ref=' . urlencode($reference);
            header('Location: ' . $redirect);
            exit;
        } catch (Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $errors[] = 'Failed to submit Post-UTME registration: ' . $e->getMessage();
        }
    }
}

// Render form when GET or errors
$csrf = generateToken('signup_form');
?>
<?php include __DIR__ . '/includes/header.php'; ?>

<section class="about-hero">
    <div class="about-hero-overlay"></div>
    <div class="container about-hero-inner">
        <h1>Post-UTME Registration</h1>
        <p class="lead">Submit your Post-UTME application and pay the compulsory form fee.</p>
    </div>
</section>

<div class="container register-layout">
<main class="register-main">
    <div class="card">
        <h3>Post-UTME Registration Form</h3>
        <p class="card-desc">Fill out this form to begin your Post-UTME registration process.</p>
        <?php if (!empty($errors)): ?>
            <script>
            document.addEventListener('DOMContentLoaded', function(){
                try {
                    var errHtml = <?= json_encode(implode('<br>', array_map('htmlspecialchars', $errors))) ?>;
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({ icon:'error', title:'There was a problem', html: errHtml, confirmButtonText: 'OK', customClass: { popup: 'hq-swal' } });
                    } else { alert(errHtml.replace(/<br\s*\/?>/g, "\n")); }
                } catch(e) { console.error(e); }
            });
            </script>
        <?php endif; ?>

        <form id="postutmeForm" class="registration-form form-postutme" method="post" enctype="multipart/form-data">
            <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrf) ?>">
            <input type="hidden" name="client_total" value="0">
            <input type="hidden" name="method" value="bank">
            <input type="hidden" name="registration_type" value="postutme">

            <div class="form-row payment-method-selector" style="margin:8px 0;padding:8px;border-radius:6px;background:#fafafa;border:1px solid #eee;">
                <label style="margin-right:12px;"><input type="radio" name="payment_method_choice_post" value="bank" checked> High Q Transfer (Bank Transfer)</label>
                <label><input type="radio" name="payment_method_choice_post" value="online" disabled> Online Card Payment <small style="color:#a33;margin-left:6px">(Not available for Post-UTME)</small></label>
            </div>

            <div class="form-row" style="margin-bottom:8px;"><a href="register.php" class="btn btn-secondary go-back">Change Type</a></div>

            <div id="postUtmeFields" style="margin-top:12px;padding:12px;border-radius:6px;background:#fff;border:1px solid #f0f0f0">
                <h4 class="section-title"><i class="bx bxs-book"></i> Post-UTME Registration Details</h4>
                <div class="form-row"><label>Name of Institution</label><input type="text" name="institution" placeholder="Name of Institution where you're applying" value="<?= htmlspecialchars($_POST['institution'] ?? '') ?>"></div>
                <div class="form-row post-passport-row" style="display:block"><label>Passport Photo (Post-UTME applicants)</label>
                    <div class="hq-file-input post-passport-input">
                        <button type="button" class="btn">Choose file</button>
                        <input type="file" name="passport" id="passport_input_post" accept="image/*" style="display:none">
                        <span id="passport_chosen_post" style="margin-left:10px;color:#444;font-size:0.95rem">No file chosen</span>
                    </div>
                </div>
                <div class="form-row form-inline"><div><label>First Name *</label><input type="text" name="first_name_post" placeholder="First name" value="<?= htmlspecialchars($_POST['first_name_post'] ?? '') ?>"></div><div><label>Surname *</label><input type="text" name="surname" placeholder="Surname" value="<?= htmlspecialchars($_POST['surname'] ?? '') ?>"></div></div>
                
                <div class="form-row"><label>Other Name</label><input type="text" name="other_name" placeholder="Other names (optional)" value="<?= htmlspecialchars($_POST['other_name'] ?? '') ?>"></div>
                
                <div class="form-row">
                    <label>Gender</label>
                    <select name="post_gender">
                        <option value="">Select gender</option>
                        <option value="male" <?= (($_POST['post_gender'] ?? '') === 'male') ? 'selected' : '' ?>>Male</option>
                        <option value="female" <?= (($_POST['post_gender'] ?? '') === 'female') ? 'selected' : '' ?>>Female</option>
                    </select>
                </div>

                <div class="form-row"><label>Home Address</label><textarea name="address" placeholder="Complete home address"><?= htmlspecialchars($_POST['address'] ?? '') ?></textarea></div>
                
                <div class="form-row form-inline">
                    <div><label>Parent/Guardian Phone</label><input type="tel" name="parent_phone" placeholder="+234 XXX XXX XXXX" value="<?= htmlspecialchars($_POST['parent_phone'] ?? '') ?>"></div>
                    <div><label>Email Address</label><input type="email" name="email_post" placeholder="your.email@example.com" value="<?= htmlspecialchars($_POST['email_post'] ?? '') ?>"></div>
                </div>

                <div class="form-row form-inline">
                    <div><label>NIN Number</label><input type="text" name="nin_number" placeholder="National Identification Number" value="<?= htmlspecialchars($_POST['nin_number'] ?? '') ?>"></div>
                    <div><label>State of Origin</label><input type="text" name="state_of_origin" placeholder="State of origin" value="<?= htmlspecialchars($_POST['state_of_origin'] ?? '') ?>"></div>
                </div>

                <div class="form-row form-inline">
                    <div><label>Local Government</label><input type="text" name="local_government" placeholder="Local government area" value="<?= htmlspecialchars($_POST['local_government'] ?? '') ?>"></div>
                    <div><label>Place of Birth</label><input type="text" name="place_of_birth" placeholder="Place of birth" value="<?= htmlspecialchars($_POST['place_of_birth'] ?? '') ?>"></div>
                </div>

                <div class="form-row form-inline">
                    <div><label>Nationality</label><input type="text" name="nationality" placeholder="Nationality" value="<?= htmlspecialchars($_POST['nationality'] ?? 'Nigerian') ?>"></div>
                    <div><label>Religion</label><input type="text" name="religion" placeholder="Religion" value="<?= htmlspecialchars($_POST['religion'] ?? '') ?>"></div>
                </div>

                <h4 class="section-title" style="margin-top:20px;"><i class="bx bxs-graduation"></i> JAMB Information</h4>
                
                <div class="form-row form-inline">
                    <div><label>JAMB Registration Number</label><input type="text" name="jamb_registration_number" placeholder="e.g. 12345678AA" value="<?= htmlspecialchars($_POST['jamb_registration_number'] ?? '') ?>"></div>
                    <div><label>JAMB Score</label><input type="number" name="jamb_score" placeholder="Total score (0-400)" min="0" max="400" value="<?= htmlspecialchars($_POST['jamb_score'] ?? '') ?>"></div>
                </div>

                <div class="form-row"><label>JAMB Subject 1 (Must be English) *</label><input type="text" name="jamb_subj_1" placeholder="e.g. English Language" value="<?= htmlspecialchars($_POST['jamb_subj_1'] ?? '') ?>"></div>
                <div class="form-row"><label>Score for Subject 1</label><input type="number" name="jamb_score_1" placeholder="0-100" min="0" max="100" value="<?= htmlspecialchars($_POST['jamb_score_1'] ?? '') ?>"></div>

                <div class="form-row"><label>JAMB Subject 2 *</label><input type="text" name="jamb_subj_2" placeholder="Second subject" value="<?= htmlspecialchars($_POST['jamb_subj_2'] ?? '') ?>"></div>
                <div class="form-row"><label>Score for Subject 2</label><input type="number" name="jamb_score_2" placeholder="0-100" min="0" max="100" value="<?= htmlspecialchars($_POST['jamb_score_2'] ?? '') ?>"></div>

                <div class="form-row"><label>JAMB Subject 3 *</label><input type="text" name="jamb_subj_3" placeholder="Third subject" value="<?= htmlspecialchars($_POST['jamb_subj_3'] ?? '') ?>"></div>
                <div class="form-row"><label>Score for Subject 3</label><input type="number" name="jamb_score_3" placeholder="0-100" min="0" max="100" value="<?= htmlspecialchars($_POST['jamb_score_3'] ?? '') ?>"></div>

                <div class="form-row"><label>JAMB Subject 4 *</label><input type="text" name="jamb_subj_4" placeholder="Fourth subject" value="<?= htmlspecialchars($_POST['jamb_subj_4'] ?? '') ?>"></div>
                <div class="form-row"><label>Score for Subject 4</label><input type="number" name="jamb_score_4" placeholder="0-100" min="0" max="100" value="<?= htmlspecialchars($_POST['jamb_score_4'] ?? '') ?>"></div>

                <h4 class="section-title" style="margin-top:20px;"><i class="bx bxs-book-content"></i> O'Level Results (WAEC/NECO)</h4>
                
                <div class="form-row"><label>Exam Type</label><select name="exam_type"><option value="">Select exam type</option><option value="WAEC" <?= (($_POST['exam_type'] ?? '') === 'WAEC') ? 'selected' : '' ?>>WAEC</option><option value="NECO" <?= (($_POST['exam_type'] ?? '') === 'NECO') ? 'selected' : '' ?>>NECO</option></select></div>
                
                <div class="form-row"><label>Candidate Name (as on certificate)</label><input type="text" name="candidate_name" placeholder="Full name on certificate" value="<?= htmlspecialchars($_POST['candidate_name'] ?? '') ?>"></div>
                
                <div class="form-row form-inline">
                    <div><label>Exam Number</label><input type="text" name="exam_number" placeholder="Exam number" value="<?= htmlspecialchars($_POST['exam_number'] ?? '') ?>"></div>
                    <div><label>Exam Year/Month</label><input type="text" name="exam_year_month" placeholder="e.g. May/June 2024" value="<?= htmlspecialchars($_POST['exam_year_month'] ?? '') ?>"></div>
                </div>

                <div style="margin-top:12px;">
                    <p style="font-size:13px;color:#666;margin-bottom:8px;">Enter up to 8 subjects with grades:</p>
                    <?php for ($i = 1; $i <= 8; $i++): ?>
                        <div class="form-row form-inline">
                            <div><label>Subject <?= $i ?></label><input type="text" name="olevel_subj_<?= $i ?>" placeholder="Subject name" value="<?= htmlspecialchars($_POST['olevel_subj_' . $i] ?? '') ?>"></div>
                            <div><label>Grade <?= $i ?></label><select name="olevel_grade_<?= $i ?>">
                                <option value="">-</option>
                                <?php foreach (['A1','B2','B3','C4','C5','C6','D7','E8','F9'] as $grade): ?>
                                    <option value="<?= $grade ?>" <?= (($_POST['olevel_grade_' . $i] ?? '') === $grade) ? 'selected' : '' ?>><?= $grade ?></option>
                                <?php endforeach; ?>
                            </select></div>
                        </div>
                    <?php endfor; ?>
                </div>

                <h4 class="section-title" style="margin-top:20px;"><i class="bx bxs-school"></i> Course Choices</h4>
                
                <div class="form-row"><label>First Choice Course</label><input type="text" name="course_first_choice" placeholder="e.g. Computer Science" value="<?= htmlspecialchars($_POST['course_first_choice'] ?? '') ?>"></div>
                <div class="form-row"><label>Second Choice Course</label><input type="text" name="course_second_choice" placeholder="Alternative course" value="<?= htmlspecialchars($_POST['course_second_choice'] ?? '') ?>"></div>
                <div class="form-row"><label>First Choice Institution</label><input type="text" name="institution_first_choice" placeholder="Preferred university/polytechnic" value="<?= htmlspecialchars($_POST['institution_first_choice'] ?? '') ?>"></div>

                <h4 class="section-title" style="margin-top:20px;"><i class="bx bxs-user-detail"></i> Parent/Guardian Information</h4>
                
                <div class="form-row"><label>Father's Name</label><input type="text" name="father_name" placeholder="Father's full name" value="<?= htmlspecialchars($_POST['father_name'] ?? '') ?>"></div>
                <div class="form-row form-inline">
                    <div><label>Father's Phone</label><input type="tel" name="father_phone" placeholder="+234 XXX XXX XXXX" value="<?= htmlspecialchars($_POST['father_phone'] ?? '') ?>"></div>
                    <div><label>Father's Email</label><input type="email" name="father_email" placeholder="father@example.com" value="<?= htmlspecialchars($_POST['father_email'] ?? '') ?>"></div>
                </div>
                <div class="form-row"><label>Father's Occupation</label><input type="text" name="father_occupation" placeholder="Occupation" value="<?= htmlspecialchars($_POST['father_occupation'] ?? '') ?>"></div>

                <div class="form-row"><label>Mother's Name</label><input type="text" name="mother_name" placeholder="Mother's full name" value="<?= htmlspecialchars($_POST['mother_name'] ?? '') ?>"></div>
                <div class="form-row"><label>Mother's Phone</label><input type="tel" name="mother_phone" placeholder="+234 XXX XXX XXXX" value="<?= htmlspecialchars($_POST['mother_phone'] ?? '') ?>"></div>
                <div class="form-row"><label>Mother's Occupation</label><input type="text" name="mother_occupation" placeholder="Occupation" value="<?= htmlspecialchars($_POST['mother_occupation'] ?? '') ?>"></div>

                <h4 class="section-title" style="margin-top:20px;"><i class="bx bxs-school"></i> Educational Background</h4>
                
                <div class="form-row"><label>Primary School Attended</label><input type="text" name="primary_school" placeholder="Primary school name" value="<?= htmlspecialchars($_POST['primary_school'] ?? '') ?>"></div>
                <div class="form-row"><label>Year Ended</label><input type="number" name="primary_year_ended" placeholder="e.g. 2015" min="1990" max="2025" value="<?= htmlspecialchars($_POST['primary_year_ended'] ?? '') ?>"></div>

                <div class="form-row"><label>Secondary School Attended</label><input type="text" name="secondary_school" placeholder="Secondary school name" value="<?= htmlspecialchars($_POST['secondary_school'] ?? '') ?>"></div>
                <div class="form-row"><label>Year Ended</label><input type="number" name="secondary_year_ended" placeholder="e.g. 2020" min="1990" max="2025" value="<?= htmlspecialchars($_POST['secondary_year_ended'] ?? '') ?>"></div>

                <div style="margin-top:12px;"><label><input type="checkbox" name="post_tutor_fee" value="1" <?= !empty($_POST['post_tutor_fee']) ? 'checked' : '' ?>> Add optional tutor fee (₦8,000)</label></div>
                <p style="font-size:13px;color:#666;margin-top:8px">Post-UTME compulsory form fee: ₦1,000.</p>
            </div>

            <div class="form-row terms-row" style="margin-top:12px;">
                <div class="checkbox-wrapper">
                    <input type="checkbox" name="agreed_terms" id="agreed_terms_post" <?= !empty($agreed_terms) ? 'checked' : '' ?> required>
                    <label for="agreed_terms_post" class="terms-label"><span>I agree to the <a href="/terms.php" target="_blank">terms and conditions</a></span></label>
                </div>
            </div>

            <div class="submit-row" style="margin-top:8px;"><button class="btn-primary btn-submit" type="submit" name="form_action" value="postutme">Submit Post-UTME Registration</button></div>
        </form>
    </div>
</main>

<aside class="register-sidebar hq-aside-target">
    <div class="sidebar-card admission-box">
        <h4>Admission Requirements</h4>
        <hr>
        <ul style="margin:8px 0;padding-left:18px;color:#666;font-size:13px">
            <li>Completed O'Level certificate (for JAMB/Post‑UTME)</li>
            <li>Valid identification document</li>
            <li>Passport photograph (2 copies)</li>
            <li>Registration fee payment</li>
            <li>Commitment to academic excellence</li>
        </ul>
    </div>
</aside>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>

<script>
// minimal client helpers for post-utme page: passport input wiring and simple validation
document.addEventListener('DOMContentLoaded', function(){
    var postBtn = document.querySelector('.post-passport-input button');
    var postInput = document.getElementById('passport_input_post');
    var postChosen = document.getElementById('passport_chosen_post');
    if (postBtn && postInput) {
        postBtn.addEventListener('click', function(){ postInput.click(); });
        postInput.addEventListener('change', function(){ if (postInput.files && postInput.files.length) postChosen.textContent = postInput.files[0].name; else postChosen.textContent = 'No file chosen'; });
    }
});
</script>
