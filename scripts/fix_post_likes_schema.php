<?php
require __DIR__ . '/../public/config/db.php';
try {
    $cols = [];
    $q = $pdo->query("DESCRIBE post_likes");
    $rows = $q->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as $r) $cols[] = $r['Field'];

    if (!in_array('session_id', $cols)) {
        echo "Adding session_id column...\n";
        $pdo->exec("ALTER TABLE post_likes ADD COLUMN session_id VARCHAR(128) DEFAULT NULL AFTER post_id");
        echo "session_id added.\n";
    } else {
        echo "session_id already present.\n";
    }

    // check index
    $hasIndex = false;
    $q2 = $pdo->query("SHOW INDEX FROM post_likes");
    $idx = $q2->fetchAll(PDO::FETCH_ASSOC);
    foreach ($idx as $i) {
        if ($i['Key_name'] === 'ux_post_session_ip') { $hasIndex = true; break; }
        if ($i['Key_name'] === 'uniq_comment_like' && $i['Column_name'] === 'post_id') { $hasIndex = true; }
    }
    if (!$hasIndex) {
        echo "Adding unique index ux_post_session_ip...\n";
        $pdo->exec("ALTER TABLE post_likes ADD UNIQUE KEY ux_post_session_ip (post_id, session_id, ip)");
        echo "Index added.\n";
    } else {
        echo "Unique index already present.\n";
    }

    echo "Done.\n";
} catch (Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
