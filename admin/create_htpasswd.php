<?php
$username = 'admin';
$password = 'highq_solid_academy#.1<>2018';

// Generate Apache-compatible password hash
$hash = crypt($password, base64_encode($password));

// Write to .htpasswd file
file_put_contents(__DIR__ . '/.htpasswd', $username . ':' . $hash . "\n");

echo "Password file created successfully.\n";