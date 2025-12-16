<?php
// scripts/admin_smoke_tests.php
// CLI smoke tests for admin endpoints (notifications, threads, chat page).
// Usage: php scripts/admin_smoke_tests.php --base=http://127.0.0.1/HIGH-Q/public/admin --cookie="PHPSESSID=xyz"

$options = getopt('', ['base:', 'cookie::', 'help::']);
if (isset($options['help'])) {
    echo "Usage: php scripts/admin_smoke_tests.php --base=http://127.0.0.1/HIGH-Q/public/admin --cookie=PHPSESSID=xyz\n";
    echo "Provide an authenticated admin session cookie to hit protected endpoints.\n";
    exit(0);
}

$base = rtrim($options['base'] ?? '', '/');
$cookie = $options['cookie'] ?? '';
if ($base === '') {
    fwrite(STDERR, "Missing --base. Example: --base=http://127.0.0.1/HIGH-Q/public/admin\n");
    exit(1);
}

function http_request(string $method, string $url, array $opts = []): array {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    if (!empty($opts['headers'])) curl_setopt($ch, CURLOPT_HTTPHEADER, $opts['headers']);
    if (!empty($opts['cookie'])) curl_setopt($ch, CURLOPT_HTTPHEADER, array_merge($opts['headers'] ?? [], ['Cookie: ' . $opts['cookie']]));
    if (!empty($opts['body'])) curl_setopt($ch, CURLOPT_POSTFIELDS, $opts['body']);
    curl_setopt($ch, CURLOPT_TIMEOUT, 20);

    $resp = curl_exec($ch);
    $err  = curl_error($ch);
    $info = curl_getinfo($ch);
    curl_close($ch);
    if ($resp === false) return ['ok' => false, 'error' => $err, 'http' => null, 'body' => null];
    $headerSize = $info['header_size'] ?? 0;
    $body = substr($resp, $headerSize);
    return ['ok' => true, 'http' => $info['http_code'] ?? null, 'body' => $body, 'info' => $info];
}

$tests = [
    ['name' => 'threads_api', 'method' => 'GET', 'url' => $base . '/api/threads.php'],
    ['name' => 'notifications_api', 'method' => 'GET', 'url' => $base . '/api/notifications.php'],
    ['name' => 'chat_page_ajax', 'method' => 'GET', 'url' => $base . '/index.php?pages=chat', 'headers' => ['X-Requested-With: XMLHttpRequest']],
    ['name' => 'students_regular', 'method' => 'GET', 'url' => $base . '/index.php?pages=students'],
    ['name' => 'students_postutme', 'method' => 'GET', 'url' => $base . '/index.php?pages=students&source=postutme'],
    ['name' => 'payment_link_page', 'method' => 'GET', 'url' => $base . '/index.php?pages=payment'],
    ['name' => 'settings_page', 'method' => 'GET', 'url' => $base . '/index.php?pages=settings'],
    ['name' => 'appointments_page', 'method' => 'GET', 'url' => $base . '/index.php?pages=appointments'],
    ['name' => 'news_blog_page', 'method' => 'GET', 'url' => $base . '/index.php?pages=post'],
];

$results = [];
foreach ($tests as $t) {
    $res = http_request($t['method'], $t['url'], [
        'headers' => $t['headers'] ?? [],
        'cookie'  => $cookie,
        'body'    => $t['body'] ?? null,
    ]);
    $status = 'fail';
    $detail = '';
    if (!$res['ok']) {
        $detail = $res['error'];
    } else {
        $status = ($res['http'] ?? 0) >= 200 && ($res['http'] ?? 0) < 300 ? 'ok' : 'fail';
        $detail = 'HTTP ' . ($res['http'] ?? '?');
        $json = json_decode($res['body'] ?? '', true);
        if (json_last_error() === JSON_ERROR_NONE) {
            $detail .= ' (json)';
        } else {
            $trim = trim($res['body'] ?? '');
            if ($trim !== '') $detail .= ' body_len=' . strlen($trim);
        }
    }
    $results[] = ['name' => $t['name'], 'status' => $status, 'detail' => $detail];
}

foreach ($results as $r) {
    echo str_pad($r['name'], 22) . ': ' . strtoupper($r['status']) . ' - ' . $r['detail'] . "\n";
}

$failed = array_filter($results, fn($r) => $r['status'] !== 'ok');
exit($failed ? 1 : 0);
