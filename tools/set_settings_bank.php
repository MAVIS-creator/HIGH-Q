<?php
// tools/set_settings_bank.php - update the settings table JSON (key='system_settings') with bank fields
require __DIR__ . '/../vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();
$host = $_ENV['DB_HOST'];
$db = $_ENV['DB_NAME'];
$user = $_ENV['DB_USER'];
$pass = $_ENV['DB_PASS'];
$charset = $_ENV['DB_CHARSET'] ?? 'utf8mb4';
$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
try {
    $pdo = new PDO($dsn, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
} catch (Exception $e) {
    echo json_encode(['status'=>'error','message'=>'DB connect failed: '.$e->getMessage()]);
    exit(1);
}
$bankName = 'Moniepoint PBS';
$accountName = 'High Q Solid Academy';
$accountNumber = '5017167271';

try {
    // Load existing settings row
    $stmt = $pdo->prepare("SELECT id, `value` FROM settings WHERE `key` = ? LIMIT 1");
    $stmt->execute(['system_settings']);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row) {
        $id = $row['id'];
        $val = $row['value'];
        $data = json_decode($val, true);
        if (!is_array($data)) $data = [];
    } else {
        $id = null;
        $data = [];
    }

    // Ensure structure
    if (!isset($data['site']) || !is_array($data['site'])) $data['site'] = [];
    $data['site']['bank_name'] = $bankName;
    $data['site']['bank_account_name'] = $accountName;
    $data['site']['bank_account_number'] = $accountNumber;

    $json = json_encode($data, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES);
    if ($id) {
        $upd = $pdo->prepare("UPDATE settings SET `value` = ? WHERE id = ?");
        $upd->execute([$json, $id]);
    } else {
        $ins = $pdo->prepare("INSERT INTO settings (`key`,`value`) VALUES (?, ?)");
        $ins->execute(['system_settings', $json]);
    }

    // Output the merged result
    echo json_encode(['status'=>'ok','settings'=>$data], JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES);
} catch (Exception $e) {
    echo json_encode(['status'=>'error','message'=>$e->getMessage()]);
    exit(1);
}
