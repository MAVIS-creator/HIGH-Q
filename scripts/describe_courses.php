<?php
// scripts/describe_courses.php
require __DIR__ . '/../admin/includes/db.php';
try {
    $stmt = $pdo->query('DESCRIBE courses');
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "DESCRIBE courses:\n";
    foreach ($rows as $r) {
        printf("%-20s %-20s %-6s %-6s %-12s %s\n", $r['Field'], $r['Type'], $r['Null'], $r['Key'], $r['Default'] ?? 'NULL', $r['Extra']);
    }
} catch (Throwable $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

?>