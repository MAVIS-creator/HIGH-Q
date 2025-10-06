<?php
header('Content-Type: text/plain');
echo "Auth Test\n";
echo "--------\n";
echo "AUTH_TYPE: " . ($_SERVER['AUTH_TYPE'] ?? 'Not set') . "\n";
echo "PHP_AUTH_USER: " . ($_SERVER['PHP_AUTH_USER'] ?? 'Not set') . "\n";
echo "PHP_AUTH_PW: [hidden]\n";
echo "\nServer Variables:\n";
echo "----------------\n";
print_r($_SERVER);