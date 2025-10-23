<?php
require __DIR__ . '/../public/config/db.php';
try {
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $sql = "SELECT * FROM (
      SELECT sr.id, 'regular' AS registration_type,
        sr.first_name COLLATE utf8mb4_general_ci AS first_name,
        sr.last_name COLLATE utf8mb4_general_ci AS last_name,
        COALESCE(sr.email, u.email) COLLATE utf8mb4_general_ci AS email,
        sr.status COLLATE utf8mb4_general_ci AS status,
        sr.passport_path COLLATE utf8mb4_general_ci AS passport_path,
        sr.created_at AS created_at
        FROM student_registrations sr LEFT JOIN users u ON u.id = sr.user_id
      UNION ALL
      SELECT pr.id, 'post' AS registration_type,
        pr.first_name COLLATE utf8mb4_general_ci AS first_name,
        pr.surname COLLATE utf8mb4_general_ci AS last_name,
        pr.email COLLATE utf8mb4_general_ci AS email,
        pr.status COLLATE utf8mb4_general_ci AS status,
        pr.passport_photo COLLATE utf8mb4_general_ci AS passport_path,
        pr.created_at AS created_at
        FROM post_utme_registrations pr
    ) t ORDER BY t.created_at DESC LIMIT 5";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "OK — returned " . count($rows) . " rows\n";
    foreach ($rows as $r) {
        echo json_encode($r) . "\n";
    }
} catch (Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}
