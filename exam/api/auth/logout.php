<?php

declare(strict_types=1);

require_once __DIR__ . '/../_bootstrap.php';

hq_exam_require_method('POST');

$pdo = hq_exam_db();
hq_exam_require_setup($pdo);

hq_exam_logout_student($pdo);

hq_exam_json('ok', 'Exam portal session closed.', [
    'authenticated' => false,
]);
