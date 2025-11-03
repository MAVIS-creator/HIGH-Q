<?php
// tools/simulate_registration.php
// CLI helper to simulate Regular and Post-UTME registrations against local dev server
// Usage: php tools/simulate_registration.php

$base = getenv('HQ_BASE_URL') ?: 'http://localhost/HIGH-Q/public';
$cookieJar = sys_get_temp_dir() . '/hq_sim_cookie.txt';
if (file_exists($cookieJar)) @unlink($cookieJar);

function http_get($url, $cookieJar) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
    curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieJar);
    curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieJar);
    // set a user agent
    curl_setopt($ch, CURLOPT_USERAGENT, 'HQSim/1.0');
    $res = curl_exec($ch);
    $info = curl_getinfo($ch);
    curl_close($ch);
    return [$res, $info];
}

function http_post($url, $data, $cookieJar) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieJar);
    curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieJar);
    curl_setopt($ch, CURLOPT_USERAGENT, 'HQSim/1.0');
    $res = curl_exec($ch);
    $info = curl_getinfo($ch);
    curl_close($ch);
    return [$res, $info];
}

function extract_csrf($html) {
    // Use ~ as delimiter and allow both single/double quotes for attribute values
    if (preg_match("~name=['\"]?_csrf_token['\"]?\s+value=['\"]([^'\"]+)['\"]~i", $html, $m)) return $m[1];
    if (preg_match("~name=['\"]?_csrf['\"]?\s+value=['\"]([^'\"]+)['\"]~i", $html, $m)) return $m[1];
    // fallback search for token input
    if (preg_match("~<input[^>]*name=['\"]?_csrf(?:_token)?['\"]?[^>]*value=['\"]([^'\"]+)['\"]~i", $html, $m)) return $m[1];
    return null;
}

// Step 0: quick check base URL
list($h, $info) = http_get($base . '/register.php', $cookieJar);
if ($info['http_code'] >= 400) {
    echo "Failed to fetch register.php at $base/register.php (HTTP {$info['http_code']}).\n";
    echo "If your app is under a different base path, set HQ_BASE_URL env var.\n";
    exit(1);
}
$csrf = extract_csrf($h);
if (!$csrf) {
    echo "Could not extract CSRF token from register page. Aborting.\n";
    exit(1);
}
echo "CSRF token fetched: $csrf\n";

// Include DB config locally to query courses and inspect payments
// This script runs in CLI; include path relative to repo root
require_once __DIR__ . '/../public/config/db.php';
// pick a fixed-price course if available
$courseId = null; $coursePrice = 0.0;
try {
    $rows = $pdo->query("SELECT id, price FROM courses WHERE is_active=1 AND price IS NOT NULL AND price != '' ORDER BY id ASC LIMIT 1")->fetchAll(PDO::FETCH_ASSOC);
    if (!empty($rows)) { $courseId = $rows[0]['id']; $coursePrice = floatval($rows[0]['price']); }
} catch (Throwable $e) { /* ignore */ }
if (!$courseId) {
    echo "No fixed-price course found in DB. Can't simulate regular flow accurately.\n";
}

// Helper to fetch last payment
function last_payments($pdo, $limit=5) {
    $st = $pdo->query('SELECT id, amount, payment_method, reference, status, metadata, registration_type, created_at FROM payments ORDER BY id DESC LIMIT ' . (int)$limit);
    return $st->fetchAll(PDO::FETCH_ASSOC);
}

// Snapshot before
echo "Existing recent payments:\n"; print_r(last_payments($pdo,5));

// 1) Simulate Regular registration (if courseId available)
if ($courseId) {
    echo "\n=== Simulating REGULAR registration (course id: $courseId) ===\n";
    $post = [
        '_csrf_token' => $csrf,
        'client_total' => number_format($coursePrice + 1000 + 1500, 2, '.', ''), // include form & card fee
        'method' => 'bank',
        'registration_type' => 'regular',
        'payment_method_choice_regular' => 'bank',
        'first_name' => 'CliReg',
        'last_name' => 'Test',
        'email_contact' => 'cli.reg@example.test',
        'phone' => '+2340000000000',
        'date_of_birth' => '2000-01-01',
        'gender' => '',
        'home_address' => 'CLI Address',
        'programs[]' => $courseId,
        'previous_education' => 'CLI Test',
        'academic_goals' => 'Learn',
        'emergency_name' => 'Parent',
        'emergency_phone' => '+2341111111111',
        'emergency_relationship' => 'Parent',
        'agreed_terms' => '1',
        'form_action' => 'regular',
    ];
    list($res, $info) = http_post($base . '/register.php', $post, $cookieJar);
    echo "POST returned HTTP: {$info['http_code']} (Location: " . ($info['redirect_url'] ?? '') . ")\n";
    // If redirect, follow once
    if (!empty($info['redirect_url'])) {
        echo "Redirected to: {$info['redirect_url']}\n";
        list($res2,$i2) = http_get($info['redirect_url'], $cookieJar);
    }
    echo "Recent payments after REGULAR attempt:\n";
    print_r(last_payments($pdo,5));
}

// 2) Simulate Post-UTME registration
echo "\n=== Simulating POST-UTME registration ===\n";
$post2 = [
    '_csrf_token' => $csrf,
    'client_total' => '1000.00',
    'method' => 'bank',
    'registration_type' => 'postutme',
    'payment_method_choice_post' => 'bank',
    'institution' => 'CLI Institute',
    'first_name_post' => 'PostCli',
    'surname' => 'Tester',
    'post_gender' => 'male',
    'address' => 'CLI Addr',
    'post_phone' => '+2342222222222',
    'email_post' => 'post.cli@example.test',
    'jamb_registration_number' => 'JAMB12345',
    'jamb_score' => '70',
    'olevel_subj_1' => 'English',
    'olevel_grade_1' => 'A1',
    'post_tutor_fee' => '0',
    'agreed_terms' => '1',
    'form_action' => 'postutme',
];
list($r2,$i2) = http_post($base . '/register.php', $post2, $cookieJar);
echo "POST returned HTTP: {$i2['http_code']} (Location: " . ($i2['redirect_url'] ?? '') . ")\n";
if (!empty($i2['redirect_url'])) { list($r3,$i3) = http_get($i2['redirect_url'], $cookieJar); }

echo "Recent payments after POST-UTME attempt:\n";
print_r(last_payments($pdo,5));

echo "\nDone. Cookie jar: $cookieJar\n";

