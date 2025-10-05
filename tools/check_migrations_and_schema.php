<?php
// tools/check_migrations_and_schema.php
require __DIR__ . '/../public/config/db.php';
function out($s){ echo $s . "\n"; }
try {
    out("DESCRIBE site_settings;");
    $r = $pdo->query('DESCRIBE site_settings')->fetchAll(PDO::FETCH_ASSOC);
    foreach ($r as $row) {
        out(implode(' | ', [$row['Field'],$row['Type'],$row['Null'],$row['Key'],$row['Default']]));
    }
} catch (Throwable $e) { out('DESCRIBE failed: ' . $e->getMessage()); }

try {
    out("\nSHOW TABLES LIKE 'chat_attachments';");
    $r = $pdo->query("SHOW TABLES LIKE 'chat_attachments'")->fetchAll(PDO::FETCH_ASSOC);
    if (empty($r)) out('No table named chat_attachments found'); else out('chat_attachments exists');
} catch (Throwable $e) { out('SHOW TABLES failed: ' . $e->getMessage()); }

try {
    out("\nSELECT * FROM migrations ORDER BY applied_at DESC LIMIT 10;");
    $r = $pdo->query("SELECT filename, applied_at FROM migrations ORDER BY applied_at DESC LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);
    if (empty($r)) out('No migrations recorded'); else {
        foreach ($r as $row) out($row['applied_at'] . ' | ' . $row['filename']);
    }
} catch (Throwable $e) { out('SELECT migrations failed: ' . $e->getMessage()); }

echo "\nDone.\n";
