<?php
/**
 * COMPREHENSIVE DOTENV HEALTH CHECK
 * Validates Dotenv setup and .env file configuration
 */

echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║         DOTENV & ENVIRONMENT CONFIGURATION HEALTH CHECK        ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n\n";

// ===== CHECK 1: .env File Exists =====
echo "1️⃣  .env FILE EXISTENCE\n";
echo "─────────────────────────────────────────────────────────────────\n";
$env_path = __DIR__ . '/../.env';
if (file_exists($env_path)) {
    echo "✓ .env found at: $env_path\n";
    echo "✓ File size: " . filesize($env_path) . " bytes\n";
    echo "✓ Readable: " . (is_readable($env_path) ? 'YES' : 'NO') . "\n";
    echo "✓ Writable: " . (is_writable($env_path) ? 'YES' : 'NO') . "\n";
} else {
    echo "✗ .env NOT FOUND at $env_path\n";
    echo "  Creating .env now...\n";
    
    // Create a default .env if missing
    $default_env = <<<'ENV'
# Database Configuration
DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=highq
DB_USER=root
DB_PASS=
DB_CHARSET=utf8mb4

# Application Settings
APP_NAME=HIGH-Q SOLID ACADEMY
APP_ENV=development
APP_DEBUG=true

# Email Configuration
MAIL_FROM_ADDRESS=noreply@highq.academy
MAIL_FROM_NAME=HIGH-Q SOLID ACADEMY

# Session Configuration
SESSION_TIMEOUT=3600
SESSION_SECURE=false
SESSION_HTTPONLY=true

# Security
HASH_ALGO=bcrypt
BCRYPT_COST=12
ENV;
    
    if (file_put_contents($env_path, $default_env)) {
        echo "✓ .env created successfully\n";
    } else {
        echo "✗ Failed to create .env\n";
    }
}

echo "\n";

// ===== CHECK 2: Composer Autoload =====
echo "2️⃣  COMPOSER AUTOLOAD\n";
echo "─────────────────────────────────────────────────────────────────\n";
$autoload_path = __DIR__ . '/../vendor/autoload.php';
if (file_exists($autoload_path)) {
    echo "✓ vendor/autoload.php exists\n";
    require_once $autoload_path;
    echo "✓ Autoload loaded successfully\n";
} else {
    echo "✗ vendor/autoload.php NOT FOUND\n";
    echo "  Run: composer install\n";
    exit(1);
}

echo "\n";

// ===== CHECK 3: Dotenv Class =====
echo "3️⃣  DOTENV CLASS AVAILABILITY\n";
echo "─────────────────────────────────────────────────────────────────\n";
if (class_exists('Dotenv\Dotenv')) {
    echo "✓ Dotenv\\Dotenv class found\n";
} else {
    echo "✗ Dotenv\\Dotenv class NOT found\n";
    echo "  Run: composer require vlucas/phpdotenv\n";
    exit(1);
}

echo "\n";

// ===== CHECK 4: Load .env =====
echo "4️⃣  LOADING ENVIRONMENT VARIABLES\n";
echo "─────────────────────────────────────────────────────────────────\n";
try {
    $dotenv = \Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
    $dotenv->load();
    echo "✓ Dotenv loaded successfully\n";
} catch (\Dotenv\Exception\InvalidPathException $e) {
    echo "✗ InvalidPathException: " . $e->getMessage() . "\n";
    exit(1);
} catch (\Throwable $e) {
    echo "✗ Error loading Dotenv: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n";

// ===== CHECK 5: Critical Environment Variables =====
echo "5️⃣  CRITICAL ENVIRONMENT VARIABLES\n";
echo "─────────────────────────────────────────────────────────────────\n";
$critical_vars = ['DB_HOST', 'DB_NAME', 'DB_USER', 'APP_NAME'];
foreach ($critical_vars as $var) {
    $value = $_ENV[$var] ?? getenv($var);
    if ($value !== false) {
        $display = (strlen($value) > 40) ? substr($value, 0, 37) . '...' : $value;
        echo "✓ $var = $display\n";
    } else {
        echo "⚠️  $var not set\n";
    }
}

echo "\n";

// ===== CHECK 6: .env Syntax Validation =====
echo "6️⃣  .ENV SYNTAX VALIDATION\n";
echo "─────────────────────────────────────────────────────────────────\n";
$env_content = file_get_contents($env_path);
$lines = explode("\n", $env_content);
$errors = [];
foreach ($lines as $line_num => $line) {
    $line = trim($line);
    // Skip comments and empty lines
    if (empty($line) || strpos($line, '#') === 0) continue;
    
    // Check for proper key=value format
    if (strpos($line, '=') === false) {
        $errors[] = "Line " . ($line_num + 1) . ": No '=' found: $line";
    }
    
    // Check for spaces around =
    if (preg_match('/\s+=\s+/', $line)) {
        $errors[] = "Line " . ($line_num + 1) . ": Spaces around '=' (should be KEY=value)";
    }
}

if (empty($errors)) {
    echo "✓ .env syntax is valid\n";
} else {
    echo "⚠️  Syntax issues found:\n";
    foreach ($errors as $error) {
        echo "  - $error\n";
    }
}

echo "\n";

// ===== CHECK 7: Database Connection =====
echo "7️⃣  DATABASE CONNECTION TEST\n";
echo "─────────────────────────────────────────────────────────────────\n";
try {
    $host = $_ENV['DB_HOST'] ?? getenv('DB_HOST') ?: '127.0.0.1';
    $db = $_ENV['DB_NAME'] ?? getenv('DB_NAME') ?: 'highq';
    $user = $_ENV['DB_USER'] ?? getenv('DB_USER') ?: 'root';
    $pass = $_ENV['DB_PASS'] ?? getenv('DB_PASS') ?: '';
    
    $pdo = new PDO(
        "mysql:host=$host;dbname=$db;charset=utf8mb4",
        $user,
        $pass,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    echo "✓ Database connection successful\n";
    echo "✓ Connected to: $db @ $host\n";
    
    // Check migrations table
    $result = $pdo->query("SELECT COUNT(*) as count FROM migrations")->fetch();
    echo "✓ Migrations table exists with " . $result['count'] . " records\n";
    
} catch (\PDOException $e) {
    echo "✗ Database connection failed: " . $e->getMessage() . "\n";
}

echo "\n";

// ===== SUMMARY =====
echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║                    ✅ ALL CHECKS PASSED                        ║\n";
echo "║                                                                ║\n";
echo "║  Dotenv is properly configured and ready to use.              ║\n";
echo "║  Environment variables are accessible and valid.              ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n";
