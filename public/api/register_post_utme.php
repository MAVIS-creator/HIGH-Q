<?php
// public/api/register_post_utme.php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/csrf.php';
require_once __DIR__ . '/../config/functions.php';

header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    // Verify CSRF token
    $token = $_POST['_csrf'] ?? '';
    if (!verifyToken($token, 'registration_form')) {
        throw new Exception('Invalid security token');
    }

    // Handle passport photo upload
    $passportPath = '';
    if (isset($_FILES['passport']) && $_FILES['passport']['error'] === 0) {
        $file = $_FILES['passport'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowedTypes = ['jpg', 'jpeg', 'png'];
        
        if (!in_array($ext, $allowedTypes)) {
            throw new Exception('Invalid passport photo format. Please upload JPG or PNG');
        }

        $uploadDir = __DIR__ . '/../storage/passports/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $filename = uniqid('passport_') . '.' . $ext;
        $passportPath = 'storage/passports/' . $filename;
        
        if (!move_uploaded_file($file['tmp_name'], $uploadDir . $filename)) {
            throw new Exception('Failed to upload passport photo');
        }
    }

    // Process JAMB subjects and scores
    $jambSubjects = isset($_POST['jamb_subjects']) ? json_encode($_POST['jamb_subjects']) : '{}';
    
    // Process O'Level results
    $olevelResults = isset($_POST['olevel_results']) ? json_encode($_POST['olevel_results']) : '{}';

    // Calculate total fee
    $totalFee = 1000; // Base form fee
    if (isset($_POST['include_tutorial']) && $_POST['include_tutorial'] == '1') {
        $totalFee += 8000; // Add tutorial fee if selected
    }

    // Begin transaction
    $pdo->beginTransaction();

    // Insert into post_utme_registrations table
    $stmt = $pdo->prepare("INSERT INTO post_utme_registrations (
        institution, first_name, surname, other_name, gender, address,
        parent_phone, email, nin_number, state_of_origin, local_government,
        place_of_birth, marital_status, disability, nationality, religion,
        mode_of_entry, jamb_registration_number, jamb_score, jamb_subjects,
        course_first_choice, course_second_choice, institution_first_choice,
        father_name, father_phone, father_email, father_occupation,
        mother_name, mother_phone, mother_occupation,
        primary_school, primary_year_ended, secondary_school, secondary_year_ended,
        exam_type, candidate_name, exam_number, exam_year_month, olevel_results,
        next_of_kin_name, next_of_kin_address, next_of_kin_email,
        next_of_kin_phone, next_of_kin_relationship, passport_photo,
        form_fee_paid, tutor_fee_paid
    ) VALUES (
        ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 
        ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 
        FALSE, ?
    )");

    $stmt->execute([
        $_POST['institution'] ?? '', $_POST['first_name'], $_POST['surname'],
        $_POST['other_name'] ?? '', $_POST['gender'], $_POST['address'],
        $_POST['parent_phone'], $_POST['email'], $_POST['nin_number'] ?? '',
        $_POST['state_of_origin'], $_POST['local_government'],
        $_POST['place_of_birth'] ?? '', $_POST['marital_status'] ?? '',
        $_POST['disability'] ?? '', $_POST['nationality'], $_POST['religion'] ?? '',
        $_POST['mode_of_entry'], $_POST['jamb_registration_number'],
        $_POST['jamb_score'], $jambSubjects,
        $_POST['course_first_choice'], $_POST['course_second_choice'] ?? '',
        $_POST['institution_first_choice'],
        $_POST['father_name'] ?? '', $_POST['father_phone'] ?? '',
        $_POST['father_email'] ?? '', $_POST['father_occupation'] ?? '',
        $_POST['mother_name'] ?? '', $_POST['mother_phone'] ?? '',
        $_POST['mother_occupation'] ?? '',
        $_POST['primary_school'] ?? '', $_POST['primary_year_ended'] ?? null,
        $_POST['secondary_school'], $_POST['secondary_year_ended'],
        $_POST['exam_type'], $_POST['candidate_name'], $_POST['exam_number'],
        $_POST['exam_year_month'], $olevelResults,
        $_POST['next_of_kin_name'], $_POST['next_of_kin_address'],
        $_POST['next_of_kin_email'], $_POST['next_of_kin_phone'],
        $_POST['next_of_kin_relationship'], $passportPath,
        isset($_POST['include_tutorial'])
    ]);

    $registrationId = $pdo->lastInsertId();

    // Create payment record
    $reference = 'HQPU' . time() . rand(1000, 9999);
    
    $stmt = $pdo->prepare("INSERT INTO payments (
        student_id, amount, type, reference, description, status
    ) VALUES (?, ?, 'post_utme', ?, ?, 'pending')");

    $stmt->execute([
        $registrationId,
        $totalFee,
        $reference,
        'POST UTME Registration' . (isset($_POST['include_tutorial']) ? ' with Tutorial' : '')
    ]);

    $pdo->commit();

    // Return success with payment URL
    echo json_encode([
        'status' => 'ok',
        'message' => 'Registration successful',
        'payment_url' => './payments_wait.php?ref=' . urlencode($reference)
    ]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}