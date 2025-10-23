<?php
// tools/run_ajax_tests_with_checks.php
// Extends the previous runner to validate DB state changes after actions.
chdir(__DIR__ . '/..');
require_once 'tools/run_single_request.php'; // this script sets up session and includes page handler when invoked via CLI

// helper to query DB using admin includes
if (!file_exists(__DIR__ . '/../admin/includes/db.php')) {
    echo "DB include missing: admin/includes/db.php\n"; exit(1);
}
require_once __DIR__ . '/../admin/includes/db.php';

function callRunner($page, $action, $id) {
    $php = PHP_BINARY;
    $cmd = escapeshellcmd("$php tools/run_single_request.php --page={$page} --action={$action} --id={$id}");
    ob_start(); passthru($cmd, $ret); $out = ob_get_clean();
    return [$ret, $out];
}

$tests = [
    ['page'=>'payments','action'=>'confirm','id'=>1, 'check' => function($pdo,$id){
        $s = $pdo->prepare('SELECT status FROM payments WHERE id=?'); $s->execute([$id]); return $s->fetchColumn();
    }],
    ['page'=>'payments','action'=>'reject','id'=>1, 'check' => function($pdo,$id){
        $s = $pdo->prepare('SELECT status FROM payments WHERE id=?'); $s->execute([$id]); return $s->fetchColumn();
    }],
    ['page'=>'comments','action'=>'approve','id'=>1, 'check' => function($pdo,$id){
        $s = $pdo->prepare('SELECT status FROM comments WHERE id=?'); $s->execute([$id]); return $s->fetchColumn();
    }],
    ['page'=>'comments','action'=>'destroy','id'=>2, 'check' => function($pdo,$id){
        $s = $pdo->prepare('SELECT COUNT(*) FROM comments WHERE id=?'); $s->execute([$id]); return $s->fetchColumn();
    }]
];

foreach ($tests as $t) {
    echo "\n--- Running {$t['page']} {$t['action']} id={$t['id']} ---\n";
    list($ret,$out) = callRunner($t['page'],$t['action'],$t['id']);
    echo "Runner output:\n" . $out . "\n";
    echo "Checking DB state...\n";
    try {
        $res = $t['check']($pdo, $t['id']);
        echo "Check result: " . var_export($res, true) . "\n";
    } catch (Exception $e) {
        echo "DB check failed: " . $e->getMessage() . "\n";
    }
}

echo "\nCompleted tests.\n";
