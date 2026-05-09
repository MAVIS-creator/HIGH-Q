<?php
// Test logo base64 encoding for email
$logoPath = __DIR__ . '/public/assets/images/hq-logo.jpeg';

if (!file_exists($logoPath)) {
    die("Logo file not found at: $logoPath\n");
}

echo "Logo file found: $logoPath\n";
echo "File size: " . filesize($logoPath) . " bytes\n";

$logoData = file_get_contents($logoPath);
$logoBase64 = 'data:image/jpeg;base64,' . base64_encode($logoData);

echo "Base64 length: " . strlen($logoBase64) . " characters\n";
echo "First 100 chars: " . substr($logoBase64, 0, 100) . "...\n\n";

// Test in HTML
$html = <<<HTML
<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"></head>
<body>
<h1>Logo Test</h1>
<img src="$logoBase64" alt="HQ Logo" width="100" height="100" style="border:2px solid #FFD600;">
<p>If you see the logo above, base64 encoding works!</p>
</body>
</html>
HTML;

file_put_contents(__DIR__ . '/storage/test-logo-base64.html', $html);
echo "✓ Test HTML saved to: storage/test-logo-base64.html\n";
echo "Open this file in a browser to verify the logo displays.\n";
