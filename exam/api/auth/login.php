<?php

declare(strict_types=1);

require_once __DIR__ . '/../_bootstrap.php';

hq_exam_require_method('POST');

$pdo = hq_exam_db();
hq_exam_require_setup($pdo);

$data = hq_exam_request_data();
$email = strtolower(hq_exam_value($data, 'email'));
$password = (string)($data['password'] ?? '');

$errors = [];
if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors['email'] = 'A valid email address is required.';
}
if ($password === '') {
    $errors['password'] = 'Password is required.';
}
if ($errors !== []) {
    hq_exam_json('error', 'Validation failed.', null, $errors);
}

$stmt = $pdo->prepare(
    'SELECT id, email, password_hash, status, email_verified_at, last_login_at, created_at
     FROM exam_students
     WHERE email = ?
     LIMIT 1'
);
$stmt->execute([$email]);
$student = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$student || !password_verify($password, (string)$student['password_hash'])) {
    hq_exam_json('error', 'Invalid email or password.', null, [
        'auth' => 'The provided login details are incorrect.',
    ]);
}

if (($student['status'] ?? '') !== 'active') {
    hq_exam_json('error', 'This exam portal account is not active.', null, [
        'auth' => 'Your account is currently unavailable.',
    ]);
}

$updateStmt = $pdo->prepare('UPDATE exam_students SET last_login_at = NOW() WHERE id = ?');
$updateStmt->execute([(int)$student['id']]);
$student['last_login_at'] = date('Y-m-d H:i:s');

hq_exam_login_student($pdo, $student);

hq_exam_json('ok', 'Login successful.', [
    'authenticated' => true,
    'student' => hq_exam_student_summary($pdo, $student),
]);
