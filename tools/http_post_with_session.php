<?php
// tools/http_post_with_session.php
// Usage: php tools/http_post_with_session.php --page=payments --action=confirm --id=1 --url=http://127.0.0.1:8002
chdir(__DIR__ . '/..');
$options = getopt('', ['page:', 'action::', 'id::', 'url::']);
$page = $options['page'] ?? null;
action:
$action = $options['action'] ?? null;
id = isset($options['id']) ? (int)$options['id'] : 0;
$base = $options['url'] ?? 'http://127.0.0.1:8002';

if (!$page) {
    echo "Usage: php tools/http_post_with_session.php --page=payments|comments --action=<action> --id=<id> --url=http://127.0.0.1:8002\n";
    exit(1);
}

// start a session and inject an admin user
session_start();
$_SESSION['user'] = [
    'id' => 1,
    'name' => 'HTTP CLI',
    'email' => 'cli@example.local',
];

// include CSRF helper if available so we can create tokens
$csrfPath = __DIR__ . '/../admin/includes/csrf.php';
if (file_exists($csrfPath)) require_once $csrfPath;
$token = function_exists('generateToken') ? generateToken('payments_form') : '';

// persist session to disk
session_write_close();
$sid = session_id();

// build POST data depending on page
$postData = ['_csrf' => $token, 'action' => $action ?? 'confirm', 'id' => $id ?: 1];

$url = rtrim($base, '/') . '/admin/index.php?pages=' . urlencode($page);

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'X-Requested-With: XMLHttpRequest',
    'Content-Type: application/x-www-form-urlencoded'
]);
// send PHP session cookie so server will read our session
curl_setopt($ch, CURLOPT_COOKIE, 'PHPSESSID=' . $sid);

$res = curl_exec($ch);
$info = curl_getinfo($ch);
$err = curl_error($ch);
curl_close($ch);

echo "Request to: $url\n";
echo "SID used: $sid\n";
echo "HTTP status: " . ($info['http_code'] ?? 'n/a') . "\n";
if ($err) echo "cURL error: $err\n";
echo "Response:\n";
echo $res . "\n";

?>