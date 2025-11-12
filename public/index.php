<?php
// Normalize accidental duplicate /public/public in the URL early
if (isset($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], '/public/public/') !== false) {
	$normalized = preg_replace('#/public/public/#', '/public/', $_SERVER['REQUEST_URI'], 1);
	header('Location: ' . $normalized, true, 301);
	exit;
}
// public/index.php - canonical homepage loader
// We'll include the home content here. This file is the webserver's default index.
$pageTitle = 'High Q Solid Academy - Home';
include __DIR__ . '/includes/header.php';
include __DIR__ . '/home.php';
include __DIR__ . '/includes/footer.php';

?>
