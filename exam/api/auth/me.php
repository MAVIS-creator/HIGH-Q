<?php

declare(strict_types=1);

require_once __DIR__ . '/../_bootstrap.php';

hq_exam_require_method('GET');

$pdo = hq_exam_db();
hq_exam_require_setup($pdo);

$student = hq_exam_current_student($pdo);

if (!$student) {
    hq_exam_json('ok', 'No active exam portal session.', [
        'authenticated' => false,
        'student' => null,
    ]);
}

hq_exam_json('ok', 'Current exam portal session loaded.', [
    'authenticated' => true,
    'student' => hq_exam_student_summary($pdo, $student),
]);
