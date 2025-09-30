<?php
require __DIR__ . '/../vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();
$pdo = new PDO('mysql:host='.$_ENV['DB_HOST'].';dbname='.$_ENV['DB_NAME'].';charset='.$_ENV['DB_CHARSET'], $_ENV['DB_USER'], $_ENV['DB_PASS']);
$stmt=$pdo->query('SELECT site_name, bank_name, bank_account_name, bank_account_number, contact_phone FROM site_settings LIMIT 1');
$row=$stmt->fetch(PDO::FETCH_ASSOC);
echo json_encode($row, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES);
