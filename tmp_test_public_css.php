<?php
// Test public page access
echo "Testing public page CSS loading...\n\n";

// Test URL
$url = 'http://127.0.0.1/HIGH-Q/public/home.php';
echo "Fetching: $url\n";

$html = file_get_contents($url);

if ($html === false) {
    echo "✗ Failed to fetch page!\n";
    exit(1);
}

echo "✓ Page loaded successfully\n";
echo "Page length: " . strlen($html) . " bytes\n\n";

// Check for CSS links
$cssLinks = [];
preg_match_all('/<link[^>]+rel=["\']stylesheet["\'][^>]*>/i', $html, $matches);
if (!empty($matches[0])) {
    echo "✓ Found " . count($matches[0]) . " CSS links:\n";
    foreach ($matches[0] as $link) {
        preg_match('/href=["\']([^"\']+)["\']/', $link, $href);
        if (!empty($href[1])) {
            echo "  - " . $href[1] . "\n";
            $cssLinks[] = $href[1];
        }
    }
} else {
    echo "✗ NO CSS links found!\n";
}

// Check if public.css is loaded
$hasPublicCSS = false;
foreach ($cssLinks as $link) {
    if (strpos($link, 'public.css') !== false) {
        $hasPublicCSS = true;
        break;
    }
}

if ($hasPublicCSS) {
    echo "\n✓ public.css is loaded\n";
} else {
    echo "\n✗ public.css is NOT loaded\n";
}

// Try to fetch one CSS file to see if it's accessible
if (!empty($cssLinks)) {
    $testCSS = $cssLinks[0];
    // Make relative URLs absolute
    if (strpos($testCSS, 'http') !== 0) {
        if ($testCSS[0] === '/') {
            $testCSS = 'http://127.0.0.1' . $testCSS;
        } else {
            $testCSS = 'http://127.0.0.1/HIGH-Q/public/' . $testCSS;
        }
    }
    
    echo "\nTesting CSS accessibility: $testCSS\n";
    $headers = @get_headers($testCSS);
    if ($headers && strpos($headers[0], '200') !== false) {
        echo "✓ CSS file is accessible\n";
    } else {
        echo "✗ CSS file returns: " . ($headers[0] ?? 'No response') . "\n";
    }
}
