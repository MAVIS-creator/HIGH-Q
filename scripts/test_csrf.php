<?php
require __DIR__ . '/../admin/includes/csrf.php';
$tok = generateToken('x');
echo strlen($tok) > 0 ? "CSRFOK\n" : "CSRFFAIL\n";
