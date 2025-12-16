#!/usr/bin/env php
<?php
// Final test - simulate actual browser request

// Test if routing returns landing page or loads page file
echo "=== Testing index.php routing ===\n\n";

// Test 1: No pages parameter - should show landing page
echo "Test 1: No pages parameter\n";
$_GET = [];
ob_start();
$output1 = file_get_contents('http://127.0.0.1/HIGH-Q/admin/index.php');
if (strpos($output1, 'landing-page') !== false) {
    echo "✓ Shows landing page (correct)\n";
} else {
    echo "✗ Doesn't show landing page\n";
}
echo "\n";

// Test 2: With pages=comments but GET request - should show comments page HTML
echo "Test 2: GET request to pages=comments\n";
$output2 = file_get_contents('http://127.0.0.1/HIGH-Q/admin/index.php?pages=comments');
if (strpos($output2, 'landing-page') !== false) {
    echo "✗ ERROR: Still showing landing page!\n";
} elseif (strpos($output2, 'Comments Management') !== false || strpos($output2, 'comments') !== false) {
    echo "✓ Shows comments page (correct)\n";
} else {
    echo "? Unknown response\n";
    echo "First 200 chars: " . substr($output2, 0, 200) . "\n";
}
echo "\n";

// Test 3: POST with X-Requested-With to pages=comments - should return JSON
echo "Test 3: AJAX POST to pages=comments\n";

// Need to use curl for POST with headers
$ch = curl_init('http://127.0.0.1/HIGH-Q/admin/index.php?pages=comments');
curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => http_build_query([
        'action' => 'approve',
        'id' => 1,
        '_csrf' => 'test_token'
    ]),
    CURLOPT_HTTPHEADER => [
        'X-Requested-With: XMLHttpRequest'
    ],
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => false
]);

$output3 = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Status: $httpCode\n";
echo "Response length: " . strlen($output3) . " bytes\n";
echo "First 200 chars: " . substr($output3, 0, 200) . "\n";

if (strpos($output3, 'landing-page') !== false) {
    echo "✗ ERROR: Returning landing page HTML!\n";
} elseif (strpos($output3, '{"status"') !== false || strpos($output3, '"status"') !== false) {
    echo "✓ Returns JSON (correct!)\n";
    $json = @json_decode($output3, true);
    if ($json) {
        echo "JSON: " . json_encode($json) . "\n";
    }
} else {
    echo "? Unknown response type\n";
}

echo "\n=== Tests complete ===\n";
