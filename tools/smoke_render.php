<?php
// Quick smoke renderer for public/tutors.php
chdir(__DIR__ . '/../');
ob_start();
include __DIR__ . '/../public/tutors.php';
$out = ob_get_clean();
if (strlen($out) > 0) {
    echo "RENDERED";
    exit(0);
}
echo "EMPTY";
exit(2);
