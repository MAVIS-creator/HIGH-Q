<?php
echo "<h1>Apache Configuration Test</h1>";
echo "<pre>";
echo "Document Root: " . $_SERVER['DOCUMENT_ROOT'] . "\n";
echo "Script Filename: " . $_SERVER['SCRIPT_FILENAME'] . "\n";
echo "Server Software: " . $_SERVER['SERVER_SOFTWARE'] . "\n";
echo "Auth Type: " . (isset($_SERVER['AUTH_TYPE']) ? $_SERVER['AUTH_TYPE'] : 'Not set') . "\n";
echo "PHP Version: " . phpversion() . "\n";
echo "\nLoaded Apache Modules:\n";
if(function_exists('apache_get_modules')) {
    print_r(apache_get_modules());
}
echo "</pre>";