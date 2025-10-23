<?php
require_once __DIR__ . '/includes/header.php';
?>

<div class="container" style="min-height:60vh;padding:40px 20px;text-align:center;">
    <h1 style="font-size:72px;color:var(--hq-red);margin:0 0 20px">404</h1>
    <h2>Page Not Found</h2>
    <p style="color:#666;margin:20px 0 30px">The page you're looking for doesn't exist or has been moved.</p>
    <div>
    <?php $appBase = rtrim($_ENV['APP_URL'] ?? '', '/'); if ($appBase === '') { $proto = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http'; $host = $_SERVER['HTTP_HOST'] ?? 'localhost'; $appBase = rtrim($proto . '://' . $host, '/'); } ?>
    <a href="<?= htmlspecialchars($appBase) ?>/public/home.php" class="btn-primary">Go Home</a>
    </div>
</div>

<?php
require_once __DIR__ . '/includes/footer.php';
?>