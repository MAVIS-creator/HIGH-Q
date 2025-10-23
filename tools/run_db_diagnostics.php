<?php
// tools/run_db_diagnostics.php
// Run simple diagnostic queries using the project's public/config/db.php PDO connection.
// Usage: php tools/run_db_diagnostics.php

require __DIR__ . '/../public/config/db.php'; // this should populate $pdo

header('Content-Type: text/plain; charset=utf-8');

echo "Running DB diagnostics using DSN: " . (isset($dsn) ? $dsn : "(unknown)") . "\n\n";

$queries = [
    "SHOW TABLES LIKE 'post_utme_registrations'",
    "SELECT COUNT(*) AS cnt_postutme FROM post_utme_registrations",
    "SELECT COUNT(*) AS cnt_student_registrations FROM student_registrations",
    "SHOW COLUMNS FROM post_utme_registrations",
];

foreach ($queries as $q) {
    echo "-- QUERY: $q\n";
    try {
        $st = $pdo->query($q);
        $rows = $st->fetchAll(PDO::FETCH_ASSOC);
        if (!$rows) {
            echo "(no rows returned)\n\n";
            continue;
        }
        foreach ($rows as $r) {
            echo json_encode($r, JSON_UNESCAPED_UNICODE) . "\n";
        }
        echo "\n";
    } catch (Throwable $e) {
        echo "ERROR: " . $e->getMessage() . "\n\n";
    }
}

echo "Diagnostics complete.\n";
