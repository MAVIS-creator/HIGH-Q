<?php
if (!isset($_SERVER['PHP_AUTH_USER'])) {
    header('WWW-Authenticate: Basic realm="Admin Login"');
    header('HTTP/1.0 401 Unauthorized');
    echo 'Authentication required';
    exit;
} else {
    echo "<pre>";
    echo "Authenticated as: " . htmlspecialchars($_SERVER['PHP_AUTH_USER']) . "\n";
    echo "AUTH_TYPE: " . ($_SERVER['AUTH_TYPE'] ?? 'Not set') . "\n";
    echo "Document Root: " . $_SERVER['DOCUMENT_ROOT'] . "\n";
    echo "Script Path: " . $_SERVER['SCRIPT_FILENAME'] . "\n";
    echo "</pre>";
}
?>