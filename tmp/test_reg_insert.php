<?php
// Minimal test to check if student_registrations INSERT works
require_once __DIR__ . '/../public/config/db.php';

try {
    $pdo->beginTransaction();
    
    $reg = $pdo->prepare('INSERT INTO student_registrations (user_id, first_name, gender, last_name, email, date_of_birth, home_address, previous_education, academic_goals, emergency_contact_name, emergency_contact_phone, emergency_relationship, agreed_terms, status, created_at) VALUES (NULL, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())');
    
    $reg->execute([
        'TestFirst',
        'male',
        'TestLast',
        'test@example.com',
        '2000-01-01',
        'Test Address',
        'Test Education',
        'Test Goals',
        'Emergency Contact',
        '+234000000000',
        'Parent',
        '1',
        'pending'
    ]);
    
    $registrationId = $pdo->lastInsertId();
    echo "SUCCESS: Registration ID = $registrationId\n";
    
    $pdo->commit();
    echo "Transaction committed\n";
    
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "EXCEPTION: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    echo "Trace:\n" . $e->getTraceAsString() . "\n";
}
