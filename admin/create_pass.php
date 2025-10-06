<?php
$username = 'admin';
$password = 'highq_solid_academy#.1<>2018';

// Create Apache password hash
$hash = crypt($password, base64_encode($password));
$line = $username . ':' . $hash;

// Write to .htpasswd file
file_put_contents(__DIR__ . '/.htpasswd', $line);
echo "Created .htpasswd file with credentials\n";