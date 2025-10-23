<?php
// tools/apply_gender_and_seed_postutme.php
// Safe script to (1) add gender column to student_registrations if missing
// and (2) insert a single test Post-UTME registration for admin testing.
// Usage: php tools/apply_gender_and_seed_postutme.php

require __DIR__ . '/../public/config/db.php';

function columnExists(PDO $pdo, $table, $column) {
    try {
        $st = $pdo->prepare("SHOW COLUMNS FROM `$table` LIKE ?");
        $st->execute([$column]);
        return (bool)$st->fetch();
    } catch (Throwable $e) {
        return false;
    }
}

echo "Checking database schema...\n";

// 1) Add gender column to student_registrations if missing
$table = 'student_registrations';
$col = 'gender';
if (!columnExists($pdo, $table, $col)) {
    echo "Column '$col' not found on table '$table'. Adding...\n";
    try {
        $sql = "ALTER TABLE `$table` ADD COLUMN `$col` VARCHAR(16) DEFAULT NULL AFTER `first_name`";
        $pdo->exec($sql);
        echo "Added column '$col' to '$table'.\n";
    } catch (Throwable $e) {
        echo "Failed to add column: " . $e->getMessage() . "\n";
    }
} else {
    echo "Column '$col' already present on '$table'.\n";
}

// 2) Insert a test Post-UTME row if no test email exists
$testEmail = 'test.postutme+admin@hqacademy.test';
$check = $pdo->prepare('SELECT id FROM post_utme_registrations WHERE email = ? LIMIT 1');
$check->execute([$testEmail]);
if ($check->fetch()) {
    echo "A test Post-UTME registration with email $testEmail already exists. Skipping insert.\n";
    exit(0);
}

echo "Inserting a test Post-UTME registration...\n";
try {

    // Prepare insert statement (ensure waec_serial used)
    $ins = $pdo->prepare('INSERT INTO post_utme_registrations (user_id, status, institution, first_name, surname, other_name, gender, parent_phone, email, nin_number, state_of_origin, local_government, place_of_birth, marital_status, disability, nationality, religion, mode_of_entry, jamb_registration_number, jamb_score, jamb_subjects, course_first_choice, course_second_choice, institution_first_choice, father_name, father_phone, mother_name, mother_phone, exam_type, candidate_name, exam_number, exam_year_month, olevel_results, waec_token, waec_serial, passport_photo, payment_status, form_fee_paid, tutor_fee_paid, created_at) VALUES (NULL, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())');

    $olevel = json_encode([
        'subjects' => [
            ['subject'=>'English Language','grade'=>'A1'],
            ['subject'=>'Mathematics','grade'=>'B2'],
            ['subject'=>'Civic Education','grade'=>'B2'],
            ['subject'=>'Biology','grade'=>'C4'],
            ['subject'=>'Chemistry','grade'=>'C5']
        ],
        'waec_token' => 'TESTTOKEN123',
    'waec_serial' => 'TESTSERIAL123',
        'raw_text' => 'ENG A1\nMAT B2\nCIV B2\nBIO C4\nCHE C5'
    ], JSON_UNESCAPED_UNICODE);

    $ins->execute([
        'pending',
        'Test Institution',
        'Test',
        'Student',
        'TPS',
        'male',
        '08000000000',
        $testEmail,
        'NIN000000',
        'Lagos',
        'Ikeja',
        'Ikeja',
        'Single',
        null,
        'Nigeria',
        'Christian',
        'JAMB',
        'JAMB-TEST-123',
        200,
        null,
        'Computer Science',
        null,
        null,
        'Father Test',
        '08000000001',
        'Mother Test',
        '08000000002',
        'WAEC',
        'Test Candidate',
        'EX1234567',
        '2024-08',
        $olevel,
        'TESTTOKEN123',
        'TESTSERIAL123',
        null,
        'pending',
        0,
        0
    ]);

    $id = $pdo->lastInsertId();
    echo "Inserted test Post-UTME registration with id: $id and email: $testEmail\n";

} catch (Throwable $e) {
    echo "Failed to insert test row: " . $e->getMessage() . "\n";
}

echo "Done.\n";
