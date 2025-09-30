<?php
// tools/dump_public_header.php - include public header and dump $siteSettings
require __DIR__ . '/../vendor/autoload.php';
// Make sure environment loads correctly
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();
// include DB so header can connect
require __DIR__ . '/../public/config/db.php';
ob_start();
require __DIR__ . '/../public/includes/header.php';
ob_end_clean();
if (isset($siteSettings)) echo json_encode($siteSettings, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES);
else echo json_encode(['error'=>'$siteSettings not set']);
