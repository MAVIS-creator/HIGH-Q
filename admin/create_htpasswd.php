<?php
$username = 'admin';
$password = 'highq_solid_academy#.1<>2018';
$htpasswd_path = __DIR__ . '/.htpasswd';

// Generate password hash using bcrypt
$hashed_password = password_hash($password, PASSWORD_BCRYPT);

// Convert to Apache's format (username:hash)
$htpasswd_content = $username . ':' . $hashed_password;

// Write to .htpasswd file
if (file_put_contents($htpasswd_path, $htpasswd_content)) {
    echo "Successfully created .htpasswd file\n";
} else {
    echo "Error creating .htpasswd file\n";
}