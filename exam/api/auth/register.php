<?php

declare(strict_types=1);

require_once __DIR__ . '/../_bootstrap.php';

hq_exam_require_method('POST');

$pdo = hq_exam_db();
hq_exam_require_setup($pdo);

$data = hq_exam_request_data();
$fullName = hq_exam_value($data, 'full_name');
$email = strtolower(hq_exam_value($data, 'email'));
$password = (string)($data['password'] ?? '');
$passwordConfirmation = (string)($data['password_confirmation'] ?? '');
$phone = hq_exam_value($data, 'phone');
$classLevel = hq_exam_value($data, 'class_level');
$schoolName = hq_exam_value($data, 'school_name');

$errors = [];

if ($fullName === '') {
    $errors['full_name'] = 'Full name is required.';
}

if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors['email'] = 'A valid email address is required.';
}

if (strlen($password) < 8) {
    $errors['password'] = 'Password must be at least 8 characters.';
}

if ($passwordConfirmation !== '' && $password !== $passwordConfirmation) {
    $errors['password_confirmation'] = 'Password confirmation does not match.';
}

if ($errors !== []) {
    hq_exam_json('error', 'Validation failed.', null, $errors);
}

$stmt = $pdo->prepare('SELECT id FROM exam_students WHERE email = ? LIMIT 1');
$stmt->execute([$email]);
if ($stmt->fetchColumn()) {
    hq_exam_json('error', 'This email is already registered for the exam portal.', null, [
        'email' => 'Email already exists.',
    ]);
}

$passwordHash = password_hash($password, PASSWORD_DEFAULT);

try {
    $pdo->beginTransaction();

    $studentStmt = $pdo->prepare(
        'INSERT INTO exam_students (email, password_hash, status, created_at, updated_at)
         VALUES (?, ?, "active", NOW(), NOW())'
    );
    $studentStmt->execute([$email, $passwordHash]);
    $studentId = (int)$pdo->lastInsertId();

    $profileStmt = $pdo->prepare(
        'INSERT INTO exam_student_profiles (student_id, full_name, phone, class_level, school_name, created_at, updated_at)
         VALUES (?, ?, ?, ?, ?, NOW(), NOW())'
    );
    $profileStmt->execute([
        $studentId,
        $fullName,
        $phone !== '' ? $phone : null,
        $classLevel !== '' ? $classLevel : null,
        $schoolName !== '' ? $schoolName : null,
    ]);

    $pdo->commit();
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    hq_exam_json('error', 'Could not create the exam portal account right now.', null, [
        'server' => 'Registration failed while saving your account.',
    ]);
}

$studentStmt = $pdo->prepare(
    'SELECT id, email, status, email_verified_at, last_login_at, created_at
     FROM exam_students
     WHERE id = ?
     LIMIT 1'
);
$studentStmt->execute([$studentId]);
$student = $studentStmt->fetch(PDO::FETCH_ASSOC);

if (!$student) {
    hq_exam_json('error', 'Account was created but could not be loaded again.', null, [
        'server' => 'Please try logging in.',
    ]);
}

hq_exam_login_student($pdo, $student);

hq_exam_json('ok', 'Exam portal account created successfully.', [
    'authenticated' => true,
    'student' => hq_exam_student_summary($pdo, $student),
]);
