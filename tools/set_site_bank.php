<?php
// tools/set_site_bank.php - upsert bank details into site_settings
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
    // Check for existing row
    $stmt = $pdo->query('SELECT id FROM site_settings ORDER BY id ASC LIMIT 1');
    $id = $stmt->fetchColumn();
    if ($id) {
        $upd = $pdo->prepare('UPDATE site_settings SET bank_name = ?, bank_account_name = ?, bank_account_number = ? WHERE id = ?');
        $upd->execute([$bankName, $accountName, $accountNumber, $id]);
    } else {
        $ins = $pdo->prepare('INSERT INTO site_settings (site_name, tagline, logo_url, vision, about, bank_name, bank_account_name, bank_account_number, maintenance, registration, email_verification, two_factor, comment_moderation, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())');
        $ins->execute(['HIGH Q SOLID ACADEMY','', '', '', '', $bankName, $accountName, $accountNumber, 0,1,1,0,1]);
    }

    // Return the row
    $stmt = $pdo->query('SELECT * FROM site_settings ORDER BY id ASC LIMIT 1');
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    echo json_encode(['status'=>'ok','row'=>$row], JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES);
} catch (Exception $e) {
    echo json_encode(['status'=>'error','message'=>$e->getMessage()]);
    exit(1);
}
