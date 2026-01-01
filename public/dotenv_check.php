<?php
require_once __DIR__ . '/../bootstrap.php';
header('Content-Type: application/json');

$out = [
    'dotnev_class_loaded' => class_exists('\Dotenv\\Dotenv'),
    'env_DB_HOST' => app_env('DB_HOST', null),
    'env_DB_NAME' => app_env('DB_NAME', null),
    'env_APP_NAME' => app_env('APP_NAME', null),
    'env_APP_DEBUG' => app_env('APP_DEBUG', null),
    'server_php_ini' => php_ini_loaded_file(),
];

echo json_encode($out, JSON_PRETTY_PRINT);
