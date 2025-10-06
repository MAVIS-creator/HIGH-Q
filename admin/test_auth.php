<?php
if (!isset($_SERVER['PHP_AUTH_USER'])) {
    header('WWW-Authenticate: Basic realm="Admin Login"');
    header('HTTP/1.0 401 Unauthorized');
    echo 'Please enter credentials';
    exit;
}

if ($_SERVER['PHP_AUTH_USER'] === 'admin' && 
    $_SERVER['PHP_AUTH_PW'] === 'highq_solid_academy#.1<>2018') {
    echo "Login successful!<br>";
    echo "Username: " . htmlspecialchars($_SERVER['PHP_AUTH_USER']) . "<br>";
    echo "Auth Type: " . ($_SERVER['AUTH_TYPE'] ?? 'Not set') . "<br>";
} else {
    header('WWW-Authenticate: Basic realm="Admin Login"');
    header('HTTP/1.0 401 Unauthorized');
    echo 'Invalid credentials';
    exit;
}