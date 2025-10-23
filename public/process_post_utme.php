<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/functions.php';
require_once __DIR__ . '/config/csrf.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: register_post_utme.php');
    exit;
}

// Verify CSRF token
verifyCSRFToken($_POST['_csrf'] ?? '', 'post_utme_form');

// Handle file upload
$passportPath = '';
if (isset($_FILES['passport_photo']) && $_FILES['passport_photo']['error'] === 0) {
    $allowed = ['jpg', 'jpeg', 'png'];
    $filename = $_FILES['passport_photo']['name'];
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    
    if (!in_array($ext, $allowed)) {
        die('Invalid file type. Please upload JPG or PNG files only.');
    }
    
    if ($_FILES['passport_photo']['size'] > 2 * 1024 * 1024) {
        die('File is too large. Maximum size is 2MB.');
    }
    
    $newFilename = uniqid() . '.' . $ext;
    $uploadDir = __DIR__ . '/uploads/passports/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }
    
    if (move_uploaded_file($_FILES['passport_photo']['tmp_name'], $uploadDir . $newFilename)) {
        $passportPath = 'uploads/passports/' . $newFilename;
    }
}

// Process form data
$data = [
    'institution_name' => $_POST['institution_name'] ?? '',
    'first_name' => $_POST['first_name'] ?? '',
    'surname' => $_POST['surname'] ?? '',
    'other_name' => $_POST['other_name'] ?? '',
    'gender' => $_POST['gender'] ?? '',
    'address' => $_POST['address'] ?? '',
    'parents_phone' => $_POST['parents_phone'] ?? '',
    'email' => $_POST['email'] ?? '',
    'nin_number' => $_POST['nin_number'] ?? '',
    'state_of_origin' => $_POST['state_of_origin'] ?? '',
    'local_government' => $_POST['local_government'] ?? '',
    'place_of_birth' => $_POST['place_of_birth'] ?? '',
    'marital_status' => $_POST['marital_status'] ?? '',
    'disability' => $_POST['disability'] ?? '',
    'nationality' => $_POST['nationality'] ?? '',
    'religion' => $_POST['religion'] ?? '',
    'mode_of_entry' => $_POST['mode_of_entry'] ?? '',
    'jamb_registration_number' => $_POST['jamb_registration_number'] ?? '',
    'jamb_score' => $_POST['jamb_score'] ?? '',
    'jamb_subjects' => json_encode($_POST['jamb_subjects'] ?? []),
    'jamb_grades' => json_encode($_POST['jamb_scores'] ?? []),
    'course_first_choice' => $_POST['course_first_choice'] ?? '',
    'course_second_choice' => $_POST['course_second_choice'] ?? '',
    'institution_first_choice' => $_POST['institution_first_choice'] ?? '',
    'primary_school' => $_POST['primary_school'] ?? '',
    'primary_year_ended' => $_POST['primary_year_ended'] ?? '',
    'secondary_school' => $_POST['secondary_school'] ?? '',
    'secondary_year_ended' => $_POST['secondary_year_ended'] ?? '',
    'exam_type' => $_POST['exam_type'] ?? '',
    'candidate_name' => $_POST['candidate_name'] ?? '',
    'exam_number' => $_POST['exam_number'] ?? '',
    'exam_year_month' => $_POST['exam_year_month'] ?? '',
    'olevel_subjects' => json_encode($_POST['olevel_subjects'] ?? []),
    'olevel_grades' => json_encode($_POST['olevel_grades'] ?? []),
    'waec_token' => $_POST['waec_token'] ?? '',
    'waec_serial_no' => $_POST['waec_serial_no'] ?? '',
    'passport_photo' => $passportPath,
    'form_fee_paid' => false,
    'tutor_fee_paid' => false,
    'status' => 'pending'
];

try {
    // Start transaction
    $pdo->beginTransaction();
    
    // Insert registration data
    $sql = "INSERT INTO post_utme_registrations (" . implode(',', array_keys($data)) . ") 
            VALUES (:" . implode(',:', array_keys($data)) . ")";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($data);
    $registrationId = $pdo->lastInsertId();
    
    // Create payment records
    $formFeeAmount = 1000;
    $tutorFeeAmount = 8000;
    
    // Insert form fee payment
    $formFeeRef = 'PUTME-' . date('Ymd') . '-' . $registrationId . '-F';
    $stmt = $pdo->prepare("INSERT INTO payments (student_id, amount, type, reference, status) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$_SESSION['user_id'] ?? null, $formFeeAmount, 'post_utme_form', $formFeeRef, 'pending']);
    
    // Insert tutor fee payment if selected
    if (isset($_POST['tutor_fee']) && $_POST['tutor_fee'] === 'on') {
        $tutorFeeRef = 'PUTME-' . date('Ymd') . '-' . $registrationId . '-T';
        $stmt->execute([$_SESSION['user_id'] ?? null, $tutorFeeAmount, 'post_utme_tutor', $tutorFeeRef, 'pending']);
    }
    
    $pdo->commit();
    
    // Store reference for payment page
    $_SESSION['last_payment_reference'] = $formFeeRef;
    
    // Redirect to payment page
    header('Location: payments_wait.php?ref=' . urlencode($formFeeRef));
    exit;
    
} catch (Exception $e) {
    $pdo->rollBack();
    die('Registration failed. Please try again later.');
}