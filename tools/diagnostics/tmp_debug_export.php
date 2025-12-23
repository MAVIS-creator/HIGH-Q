<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');
chdir(__DIR__ . '/admin/api');
$_GET['id'] = 22;
// simulate server vars
$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['SCRIPT_NAME'] = '/admin/api/export_registration.php';
include __DIR__ . '/admin/api/export_registration.php';
