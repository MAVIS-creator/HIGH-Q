<?php
// Normalize accidental duplicate /public/public in the URL early
if (isset($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], '/public/public/') !== false) {
	$normalized = preg_replace('#/public/public/#', '/public/', $_SERVER['REQUEST_URI'], 1);
	header('Location: ' . $normalized, true, 301);
	exit;
}

// Friendly payment links: /pay/{reference} -> /public/payments_wait.php?ref={reference}
$requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '';
if (preg_match('#/pay/([^/]+)$#', $requestPath, $m)) {
	$reference = urldecode($m[1]);
	$scriptName = $_SERVER['SCRIPT_NAME'] ?? '/public/index.php';
	$basePath = '';
	if (preg_match('#^(.*)/public/index\.php$#', $scriptName, $sm)) {
		$basePath = $sm[1];
	}
	$target = rtrim($basePath, '/') . '/public/payments_wait.php?ref=' . urlencode($reference);
	header('Location: ' . $target, true, 302);
	exit;
}
// public/index.php - canonical homepage loader
// We'll include the home content here. This file is the webserver's default index.
$pageTitle = 'High Q Solid Academy - Home';
include __DIR__ . '/includes/header.php';
include __DIR__ . '/home.php';
include __DIR__ . '/includes/footer.php';

?>
