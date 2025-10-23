<?php
// tools/run_ajax_tests.php
// Runs a sequence of simulated admin AJAX requests using the CLI runner.
// Usage: php run_ajax_tests.php

$php = PHP_BINARY;
$runner = __DIR__ . '/run_single_request.php';
$tests = [
    ['page'=>'payments','action'=>'confirm','id'=>1],
    ['page'=>'payments','action'=>'reject','id'=>1],
    ['page'=>'comments','action'=>'approve','id'=>1],
    ['page'=>'comments','action'=>'reject','id'=>1],
    ['page'=>'comments','action'=>'destroy','id'=>2],
];

foreach ($tests as $t) {
    $cmd = escapeshellcmd("$php $runner --page={$t['page']} --action={$t['action']} --id={$t['id']}");
    echo "\n--- Running: {$t['page']} {$t['action']} id={$t['id']} ---\n";
    passthru($cmd, $ret);
    echo "(exit=$ret)\n";
}

echo "\nDone.\n";
