<?php
// tools/check_postutme_schema.php
// Compares expected columns for post_utme_registrations with actual schema
// Usage: php tools/check_postutme_schema.php
require __DIR__ . '/../public/config/db.php';

$expected = [
    'id','user_id','status','institution','first_name','surname','other_name','gender','address',
    'parent_phone','email','nin_number','state_of_origin','local_government','place_of_birth',
    'marital_status','disability','nationality','religion','mode_of_entry',
    'jamb_registration_number','jamb_score','jamb_subjects',
    'course_first_choice','course_second_choice','institution_first_choice',
    'father_name','father_phone','father_email','father_occupation',
    'mother_name','mother_phone','mother_occupation',
    'primary_school','primary_year_ended','secondary_school','secondary_year_ended',
    'sponsor_name','sponsor_address','sponsor_email','sponsor_phone','sponsor_relationship',
    'next_of_kin_name','next_of_kin_address','next_of_kin_email','next_of_kin_phone','next_of_kin_relationship',
    'exam_type','candidate_name','exam_number','exam_year_month',
    'olevel_results','waec_token','waec_serial','passport_photo',
    'payment_status','form_fee_paid','tutor_fee_paid','created_at','updated_at'
];

// Get actual columns
$stmt = $pdo->prepare("SHOW COLUMNS FROM post_utme_registrations");
try {
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    echo "ERROR: Could not read columns: " . $e->getMessage() . "\n";
    exit(1);
}

$actual = [];
foreach ($rows as $r) $actual[] = $r['Field'];

echo "post_utme_registrations columns (" . count($actual) . "):\n" . implode(', ', $actual) . "\n\n";

$missing = [];
$present = [];
foreach ($expected as $col) {
    if (in_array($col, $actual)) $present[] = $col; else $missing[] = $col;
}

$extra = array_values(array_diff($actual, $expected));

echo "Expected columns checked: " . count($expected) . "\n";
echo "Present (" . count($present) . "): " . implode(', ', $present) . "\n\n";

if (!empty($missing)) {
    echo "MISSING (" . count($missing) . "): " . implode(', ', $missing) . "\n\n";
} else {
    echo "No expected columns missing.\n\n";
}

if (!empty($extra)) {
    echo "Extra columns present in DB that are not in our expected list (" . count($extra) . "): " . implode(', ', $extra) . "\n\n";
} else {
    echo "No extra columns detected.\n\n";
}

// Note legacy column
if (!in_array('waec_serial', $actual) && in_array('waec_serial_no', $actual)) {
    echo "Note: 'waec_serial' not found, but legacy 'waec_serial_no' exists. You can run tools/drop_waec_serial_no_and_backup.php after verifying backups.\n";
}

// Final note
echo "Schema check complete.\n";
