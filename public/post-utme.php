<?php
// public/post-utme.php - Post-UTME registration form and handler
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/csrf.php';
require_once __DIR__ . '/config/functions.php';
$cfg = require __DIR__ . '/../config/payments.php';

$errors = [];
$success = '';

// Fixed fees for Post-UTME
$post_form_fee = 1000.00; // ₦1,000 compulsory form fee
$post_tutor_fee = 8000.00; // ₦8,000 optional tutor fee

// Load site settings to respect registration toggle
$siteSettings = [];
try {
    $stmt = $pdo->query("SELECT * FROM site_settings ORDER BY id ASC LIMIT 1");
    $siteSettings = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
} catch (Throwable $e) {
    try {
        $st = $pdo->prepare("SELECT value FROM settings WHERE `key` = ? LIMIT 1");
        $st->execute(['system_settings']);
        $val = $st->fetchColumn();
        $j = $val ? json_decode($val, true) : [];
        if (is_array($j)) $siteSettings = $j;
    } catch (Throwable $e2) { /* ignore */ }
}

// If registration is disabled, do not allow registrations
$registrationEnabled = true;
if (!empty($siteSettings)) {
    if (isset($siteSettings['registration'])) $registrationEnabled = (bool)$siteSettings['registration'];
    elseif (isset($siteSettings['security']['registration'])) $registrationEnabled = (bool)$siteSettings['security']['registration'];
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$registrationEnabled) {
    $errors[] = 'Registrations are temporarily closed by the site administrator.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['_csrf_token'] ?? '';
    if (!verifyToken('signup_form', $token)) {
        $errors[] = 'Invalid CSRF token.';
    }

    // Read and validate Post-UTME fields
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

    // Build jamb_subjects array and human-friendly text from the four subject/score inputs
    $jamb_subjects = [];
    $jamb_pairs = [];
    for ($j=1; $j<=4; $j++) {
        $sj = trim($_POST['jamb_subj_' . $j] ?? '');
        $sc = trim($_POST['jamb_score_' . $j] ?? '');
        if ($sj !== '') {
            $jamb_subjects[] = ['subject' => $sj, 'score' => ($sc !== '' ? intval($sc) : null)];
            $jamb_pairs[] = $sj . ($sc !== '' ? ' (' . $sc . ')' : '');
        }
    }
    $jamb_subjects_text = !empty($jamb_pairs) ? implode('; ', $jamb_pairs) : null;

    $agreed_terms = isset($_POST['agreed_terms']) ? 1 : 0;
    if (!$agreed_terms) {
        $errors[] = 'You must accept the terms and conditions to proceed.';
    }

    // Validate required fields
    if (trim($_POST['first_name_post'] ?? '') === '' || trim($_POST['surname'] ?? '') === '') {
        $errors[] = 'First name and surname are required for Post-UTME registration.';
    }
    
    // Require at least one JAMB identifier
    if (trim($_POST['jamb_registration_number'] ?? '') === '' && trim((string)($_POST['jamb_score'] ?? '')) === '') {
        $errors[] = 'Provide a JAMB registration number or a JAMB score for Post-UTME registration.';
    }

    // Validate JAMB score ranges and subject requirements
    if ($jamb_score !== null && ($jamb_score < 0 || $jamb_score > 100)) {
        $errors[] = 'JAMB score must be between 0 and 100.';
    }

    // Validate individual JAMB subject scores
    for ($jsi = 1; $jsi <= 4; $jsi++) {
        $sub = trim($_POST['jamb_subj_' . $jsi] ?? '');
        $scRaw = trim($_POST['jamb_score_' . $jsi] ?? '');
        if ($scRaw !== '') {
            if (!is_numeric($scRaw) || intval($scRaw) < 0 || intval($scRaw) > 100) {
                $errors[] = 'JAMB subject ' . $jsi . ' score must be a number between 0 and 100.';
            }
        }
        // Require three other subjects in addition to English (subj 2..4)
        if ($jsi > 1 && $sub === '') {
            $errors[] = 'Please provide three other JAMB subjects in addition to English.';
        }
    }

    // Ensure subject 1 is English
    $sub1 = trim($_POST['jamb_subj_1'] ?? '');
    if ($sub1 === '' || !preg_match('/eng/i', $sub1)) {
        $errors[] = 'JAMB Subject 1 must be English.';
    }

    $course_first_choice = trim($_POST['course_first_choice'] ?? '') ?: null;
    $course_second_choice = trim($_POST['course_second_choice'] ?? '') ?: null;
    $institution_first_choice = trim($_POST['institution_first_choice'] ?? '') ?: null;
    $father_name = trim($_POST['father_name'] ?? '') ?: null;
    $father_phone = trim($_POST['father_phone'] ?? '') ?: null;
    $father_email = trim($_POST['father_email'] ?? '') ?: null;
    $father_occupation = trim($_POST['father_occupation'] ?? '') ?: null;
    $mother_name = trim($_POST['mother_name'] ?? '') ?: null;
    $mother_phone = trim($_POST['mother_phone'] ?? '') ?: null;
    $mother_occupation = trim($_POST['mother_occupation'] ?? '') ?: null;
    $primary_school = trim($_POST['primary_school'] ?? '') ?: null;
    $primary_year_ended = ($_POST['primary_year_ended'] ?? '') !== '' ? intval($_POST['primary_year_ended']) : null;
    $secondary_school = trim($_POST['secondary_school'] ?? '') ?: null;
    $secondary_year_ended = ($_POST['secondary_year_ended'] ?? '') !== '' ? intval($_POST['secondary_year_ended']) : null;
    $exam_type = trim($_POST['exam_type'] ?? '') ?: null;
    $candidate_name = trim($_POST['candidate_name'] ?? '') ?: null;
    $exam_number = trim($_POST['exam_number'] ?? '') ?: null;
    $exam_year_month = trim($_POST['exam_year_month'] ?? '') ?: null;

    // Collect O'Level inputs (up to 8)
    $olevel_results = [];
    for ($i=1; $i<=8; $i++) {
        $sub = trim($_POST['olevel_subj_' . $i] ?? '');
        $gr = trim($_POST['olevel_grade_' . $i] ?? '');
        if ($sub !== '') {
            $olevel_results[] = ['subject'=>$sub, 'grade'=>$gr];
        }
    }

    // WAEC requirements check (English, Mathematics, Civic Education)
    $requiredWaec = [
        'english' => 'English Language',
        'mathematics' => 'Mathematics',
        'civic' => 'Civic Education'
    ];
    $foundWaec = ['english' => false, 'mathematics' => false, 'civic' => false];
    foreach ($olevel_results as $r) {
        $s = strtolower(trim($r['subject'] ?? ''));
        if ($s === '') continue;
        if (preg_match('/eng/i', $s)) $foundWaec['english'] = true;
        if (preg_match('/math|mth/i', $s)) $foundWaec['mathematics'] = true;
        if (preg_match('/civic/i', $s)) $foundWaec['civic'] = true;
    }
    foreach ($foundWaec as $k => $v) {
        if (!$v) {
            $errors[] = $requiredWaec[$k] . " is required in O'Level results.";
        }
    }

    // Optional fields
    $mode_of_entry = trim($_POST['mode_of_entry'] ?? '') ?: null;
    $marital_status = trim($_POST['marital_status'] ?? '') ?: null;
    $disability = trim($_POST['disability'] ?? '') ?: null;
    $waec_token = trim($_POST['waec_token'] ?? '') ?: null;
    $waec_serial = trim($_POST['waec_serial'] ?? '') ?: null;
    $sponsor_name = trim($_POST['sponsor_name'] ?? '') ?: null;
    $sponsor_address = trim($_POST['sponsor_address'] ?? '') ?: null;
    $sponsor_email = trim($_POST['sponsor_email'] ?? '') ?: null;
    $sponsor_phone = trim($_POST['sponsor_phone'] ?? '') ?: null;
    $sponsor_relationship = trim($_POST['sponsor_relationship'] ?? '') ?: null;
    $next_of_kin_name = trim($_POST['next_of_kin_name'] ?? '') ?: null;
    $next_of_kin_address = trim($_POST['next_of_kin_address'] ?? '') ?: null;
    $next_of_kin_email = trim($_POST['next_of_kin_email'] ?? '') ?: null;
    $next_of_kin_phone = trim($_POST['next_of_kin_phone'] ?? '') ?: null;
    $next_of_kin_relationship = trim($_POST['next_of_kin_relationship'] ?? '') ?: null;

    // Fees calculation
    $post_form_fee = 1000.00; // compulsory form fee
    $post_tutor_fee = (!empty($_POST['post_tutor_fee']) && $_POST['post_tutor_fee'] === '1') ? 8000.00 : 0.00;
    // Small random service charge <= 167.54
    $service_charge = round(mt_rand(0, 16754) / 100.0, 2);
    $total_amount = $post_form_fee + $post_tutor_fee + $service_charge;

    // Debug log for forensic analysis
    try {
        @file_put_contents(__DIR__ . '/../storage/logs/registration_payment_debug.log', 
            date('c') . " PTU FIELDS: first_name={$first_name_post} surname={$surname} jamb_reg={$jamb_registration_number} email=" . ($email_post ?: $email_post ?: 'NULL') . "\n", 
            FILE_APPEND | LOCK_EX
        );
    } catch (Throwable $_) { }

    if (empty($errors)) {
        try {
            $pdo->beginTransaction();

            // Insert Post-UTME registration
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
                ':email' => $email_post ?: $email_post ?: null,
                ':nin_number' => $nin_number,
                ':state_of_origin' => $state_of_origin,
                ':local_government' => $local_government,
                ':place_of_birth' => $place_of_birth,
                ':nationality' => $nationality,
                ':religion' => $religion,
                ':mode_of_entry' => $mode_of_entry,
                ':marital_status' => $marital_status,
                ':disability' => $disability,
                ':jamb_registration_number' => $jamb_registration_number,
                ':jamb_score' => $jamb_score,
                ':jamb_subjects' => $jamb_subjects ? json_encode($jamb_subjects, JSON_UNESCAPED_UNICODE) : null,
                ':jamb_subjects_text' => $jamb_subjects_text,
                ':course_first_choice' => $course_first_choice,
                ':course_second_choice' => $course_second_choice,
                ':institution_first_choice' => $institution_first_choice,
                ':father_name' => $father_name,
                ':father_phone' => $father_phone,
                ':father_email' => $father_email,
                ':father_occupation' => $father_occupation,
                ':mother_name' => $mother_name,
                ':mother_phone' => $mother_phone,
                ':mother_occupation' => $mother_occupation,
                ':primary_school' => $primary_school,
                ':primary_year_ended' => $primary_year_ended,
                ':secondary_school' => $secondary_school,
                ':secondary_year_ended' => $secondary_year_ended,
                ':exam_type' => $exam_type,
                ':candidate_name' => $candidate_name,
                ':exam_number' => $exam_number,
                ':exam_year_month' => $exam_year_month,
                ':olevel_results' => !empty($olevel_results) ? json_encode($olevel_results, JSON_UNESCAPED_UNICODE) : null,
                ':waec_token' => $waec_token,
                ':waec_serial' => $waec_serial,
                ':sponsor_name' => $sponsor_name,
                ':sponsor_address' => $sponsor_address,
                ':sponsor_email' => $sponsor_email,
                ':sponsor_phone' => $sponsor_phone,
                ':sponsor_relationship' => $sponsor_relationship,
                ':next_of_kin_name' => $next_of_kin_name,
                ':next_of_kin_address' => $next_of_kin_address,
                ':next_of_kin_email' => $next_of_kin_email,
                ':next_of_kin_phone' => $next_of_kin_phone,
                ':next_of_kin_relationship' => $next_of_kin_relationship,
                ':passport_photo' => null,
                ':payment_status' => 'pending',
                ':form_fee_paid' => 0,
                ':tutor_fee_paid' => (!empty($_POST['post_tutor_fee']) && $_POST['post_tutor_fee']==='1') ? 1 : 0
            ]);
            $newId = $pdo->lastInsertId();

            // Handle passport upload if present
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
                        // Use app_url() when available to respect subfolder installs
                        if (function_exists('app_url')) {
                            $base = rtrim(app_url(''), '/');
                            $fullUrl = $base . '/uploads/passports/' . $fname;
                        } else {
                            $proto = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
                            $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
                            $baseUrl = rtrim($proto . '://' . $host, '/');
                            $scriptDir = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
                            $fullUrl = $baseUrl . $scriptDir . '/uploads/passports/' . $fname;
                        }
                        $upd = $pdo->prepare('UPDATE post_utme_registrations SET passport_photo = ? WHERE id = ?');
                        $upd->execute([$fullUrl, $newId]);
                    }
                }
            }

            // Debug: log we're about to create PTU payment
            try {
                @file_put_contents(__DIR__ . '/../storage/logs/registration_payment_debug.log', 
                    date('c') . " CREATE PTU: id={$newId} amount={$total_amount} tutor_fee=" . ((!empty($_POST['post_tutor_fee']) && $_POST['post_tutor_fee']==='1') ? 'yes' : 'no') . "\n", 
                    FILE_APPEND | LOCK_EX
                );
            } catch (Throwable $_) { }

            // Create a PTU payment record
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
            $insP->execute([
                $total_amount, 
                'bank', 
                $reference, 
                $paymentMetadata, 
                0, 
                (!empty($_POST['post_tutor_fee']) && $_POST['post_tutor_fee']==='1') ? 1 : 0
            ]);
            $paymentId = $pdo->lastInsertId();

            $pdo->commit();

            // Send admin notification (best-effort)
            try {
                $insNotif = $pdo->prepare('INSERT INTO notifications (user_id, title, body, type, metadata, is_read, created_at) VALUES (NULL,?,?,?,?,0,NOW())');
                $insNotif->execute([
                    'New Post-UTME registration',
                    ($first_name_post ?: '') . ' submitted a Post-UTME registration',
                    'postutme',
                    json_encode(['id'=>$newId,'email'=>$email_post ?: $email_post])
                ]);
            } catch (Throwable $_) {}

            // Set session and redirect to payment wait page
            $_SESSION['last_payment_id'] = $paymentId;
            $_SESSION['last_payment_reference'] = $reference;

            // Use app_url() when available so redirects respect APP_URL
            if (function_exists('app_url')) {
                $redirect = app_url('pay/' . urlencode($reference));
            } else {
                $redirect = 'payments_wait.php?ref=' . urlencode($reference);
            }
            header('Location: ' . $redirect);
            exit;

        } catch (Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $errors[] = 'Failed to submit Post-UTME registration: ' . $e->getMessage();
        }
    }
}

// Generate CSRF token for the form
$csrf = generateToken('signup_form');

include __DIR__ . '/includes/header.php';
?>

<!-- Hero section -->
<section class="about-hero">
    <div class="about-hero-overlay"></div>
    <div class="container about-hero-inner">
        <h1>Post-UTME Registration</h1>
        <p class="lead">Submit your Post-UTME application with High Q Academy for comprehensive exam preparation and support.</p>
    </div>
</section>

<div class="container register-layout">
    <main class="register-main">
        <div class="card">
            <div class="form-header" style="margin-bottom:20px;">
                <h3>Post-UTME Registration Form</h3>
                <p>For regular admissions, <a href="register.php">click here</a>.</p>
            </div>

            <?php if (!empty($errors)): ?>
                <script>
                document.addEventListener('DOMContentLoaded', function(){
                    try {
                        var errHtml = <?= json_encode(implode('<br>', array_map('htmlspecialchars', $errors))) ?>;
                        if (typeof Swal !== 'undefined') {
                            Swal.fire({
                                icon: 'error',
                                title: 'There was a problem',
                                html: errHtml,
                                confirmButtonText: 'OK',
                                customClass: { popup: 'hq-swal' }
                            });
                        } else {
                            alert(errHtml.replace(/<br\s*\/?>/g, "\n"));
                        }
                    } catch(e) { console.error(e); }
                });
                </script>
            <?php endif; ?>

            <form id="postutmeForm" class="registration-form" method="post" enctype="multipart/form-data">
                <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrf) ?>">
                <input type="hidden" name="registration_type" value="postutme">

                <!-- Post-UTME form fields here - large form content moved to form-fields-postutme.php -->
                <?php include __DIR__ . '/includes/form-fields-postutme.php'; ?>

                <div class="form-row terms-row">
                    <div class="checkbox-wrapper">
                        <input type="checkbox" name="agreed_terms" id="agreed_terms" <?= !empty($agreed_terms) ? 'checked' : '' ?> required>
                        <label for="agreed_terms" class="terms-label"><span>I agree to the <a href="/terms.php" target="_blank">terms and conditions</a></span></label>
                    </div>
                </div>

                <div class="submit-row">
                    <button class="btn-primary btn-submit" type="submit" name="form_action" value="postutme">Submit Post-UTME Registration</button>
                </div>
            </form>
        </div>
    </main>

    <aside class="register-sidebar">
        <div class="sidebar-card admission-box">
            <h4>Post-UTME Requirements</h4>
            <hr>
            <ul>
                <li>Valid JAMB result with registration number</li>
                <li>O'Level results (WAEC/NECO)</li>
                <li>Passport photograph</li>
                <li>Post-UTME form fee payment</li>
            </ul>
        </div>

        <div class="sidebar-card payment-box">
            <h4>Payment Information</h4>
            <div class="price-block">
                <div class="price-row">
                    <span>Form fee (compulsory):</span>
                    <strong>₦<?= number_format($post_form_fee, 2) ?></strong>
                </div>
                <div class="price-row">
                    <span>Optional tutor fee:</span>
                    <strong>₦<?= number_format($post_tutor_fee, 2) ?></strong>
                </div>
            </div>
            <p class="payment-note">Bank transfer details will be provided after form submission.</p>
        </div>

        <div class="sidebar-card help-box">
            <h4>Need Help?</h4>
            <p><strong>Call Us</strong><br><?= htmlspecialchars($siteSettings['contact_phone'] ?? '+234 807 208 8794') ?></p>
            <p><strong>Email Us</strong><br><?= htmlspecialchars($siteSettings['contact_email'] ?? 'highqsolidacademy@gmail.com') ?></p>
            <p><strong>Visit Us</strong><br><?= nl2br(htmlspecialchars($siteSettings['contact_address'] ?? "8 Pineapple Avenue\nAiyetoro, Maya")) ?></p>
        </div>
    </aside>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>