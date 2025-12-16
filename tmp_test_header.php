<?php
// Test if public header renders fully
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Testing public header rendering...\n\n";

ob_start();
try {
    include 'public/includes/header.php';
    $output = ob_get_clean();
    
    echo "Header output length: " . strlen($output) . " bytes\n";
    
    // Check for CSS
    if (strpos($output, 'stylesheet') !== false) {
        echo "✓ Contains CSS links\n";
    } else {
        echo "✗ NO CSS links found\n";
    }
    
    // Check for app_url
    if (strpos($output, 'app_url') !== false) {
        echo "✗ ERROR: app_url() not being called - raw PHP left in output\n";
    } else {
        echo "✓ app_url() calls are processed\n";
    }
    
    // Show first 1000 chars
    echo "\nFirst 1000 chars of output:\n";
    echo substr($output, 0, 1000) . "\n";
    
} catch (Throwable $e) {
    ob_end_clean();
    echo "✗ ERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
}
