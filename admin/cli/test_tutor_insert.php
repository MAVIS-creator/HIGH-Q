<?php
// admin/cli/test_tutor_insert.php
// Quick CLI test: try inserting a tutor record using the same SQL as the admin form.
// Run with: php admin/cli/test_tutor_insert.php

chdir(__DIR__ . '/..'); // make includes resolve the same way
require_once __DIR__ . '/../bootstrap.php';

echo "Running CLI tutor insert test...\n";

$name = 'CLI Test Tutor ' . date('YmdHis');
$slug = strtolower(preg_replace('/[^a-z0-9]+/', '-', $name));
$photo = null;
$years = null;
$long = 'Inserted via CLI test';
$title = 'CLI Test';
$subjects = json_encode(['Math', 'Physics']);
$email = 'cli@example.test';
$phone = '000';

try {
    if (!isset($pdo)) {
        throw new Exception('PDO connection not available (includes/db.php did not set $pdo)');
    }

    $stmt = $pdo->prepare("INSERT INTO tutors (name, slug, photo, short_bio, long_bio, qualifications, subjects, contact_email, phone, rating, is_featured) VALUES (?,?,?,?,?,?,?,?,?,?,?)");
    $stmt->execute([
        $name,
        $slug,
        $photo,
        $years,
        $long,
        $title,
        $subjects,
        $email,
        $phone,
        null,
        0
    ]);

    $id = $pdo->lastInsertId();
    echo "Inserted tutor with id={$id}\n";
    exit(0);
} catch (Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    // also write to tmp file for inspection
    @file_put_contents(__DIR__ . '/test_tutor_insert_error.log', date('c') . " - " . $e->getMessage() . "\n" . $e->getTraceAsString());
    exit(1);
}
