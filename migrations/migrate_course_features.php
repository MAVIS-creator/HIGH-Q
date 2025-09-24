<?php
// migrations/migrate_course_features.php
// Run this script from the command line (php migrate_course_features.php)
// It will move newline-separated features stored in courses.features into course_features rows.

require_once __DIR__ . '/../includes/db.php';

echo "Starting course features migration...\n";

// Fetch courses with non-empty features
$rows = $pdo->query("SELECT id, features FROM courses WHERE features IS NOT NULL AND TRIM(features) <> ''")->fetchAll();
$inserted = 0;

foreach ($rows as $r) {
    $cid = (int)$r['id'];
    $features = explode("\n", str_replace(["\r\n","\r"], "\n", $r['features']));
    $pos = 0;
    foreach ($features as $f) {
        $f = trim($f);
        if ($f === '') continue;
        // sanitize length
        if (mb_strlen($f) > 500) $f = mb_substr($f, 0, 500);
        $stmt = $pdo->prepare("INSERT INTO course_features (course_id, feature_text, position) VALUES (?, ?, ?)");
        $stmt->execute([$cid, $f, $pos]);
        $pos++;
        $inserted++;
    }
}

echo "Inserted $inserted feature rows.\n";
echo "You may remove the old courses.features column after verifying the migration.\n";

// Optional: convert existing courses.icon filenames to icons.class where possible
try {
    $map = [];
    $res = $pdo->query("SELECT id, filename, `class` FROM icons")->fetchAll();
    foreach ($res as $ic) $map[$ic['filename']] = $ic['class'];

    $updated = 0;
    $courses = $pdo->query("SELECT id, icon FROM courses WHERE icon IS NOT NULL AND icon <> ''")->fetchAll();
    foreach ($courses as $c) {
        $fname = $c['icon'];
        if (isset($map[$fname]) && $map[$fname]) {
            $stmt = $pdo->prepare("UPDATE courses SET icon = ? WHERE id = ?");
            $stmt->execute([$map[$fname], $c['id']]);
            $updated++;
        }
    }
    echo "Updated $updated courses to use icon classes where mapping existed.\n";
} catch (\Exception $e) {
    echo "Icon mapping skipped: " . $e->getMessage() . "\n";
}

echo "Migration complete.\n";
