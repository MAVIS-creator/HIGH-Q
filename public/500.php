<?php
require_once __DIR__ . '/includes/header.php';
?>

<div class="container" style="min-height:60vh;padding:40px 20px;text-align:center;">
    <h1 style="font-size:72px;color:var(--hq-red);margin:0 0 20px">500</h1>
    <h2>Server Error</h2>
    <p style="color:#666;margin:20px 0 30px">Something went wrong on our end. Please try again later.</p>
    <div>
    <?php $appBase = rtrim($_ENV['APP_URL'] ?? '', '/'); if ($appBase === '') { $proto = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http'; $host = $_SERVER['HTTP_HOST'] ?? 'localhost'; $appBase = rtrim($proto . '://' . $host, '/'); } ?>
    <a href="<?= htmlspecialchars($appBase) ?>/public/home.php" class="btn-primary">Go Home</a>
    </div>
</div>

<?php
require_once __DIR__ . '/includes/footer.php';
?>