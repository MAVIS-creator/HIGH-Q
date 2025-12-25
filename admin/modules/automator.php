<?php
// admin/modules/automator.php - Sitemap & Automation Engine
if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['user'])) {
    http_response_code(401);
    die('Unauthorized');
}

require_once __DIR__ . '/../includes/db.php';

$message = '';
$error = '';
$action = $_POST['action'] ?? $_GET['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'generate_sitemap') {
    try {
        $rootPath = dirname(__DIR__, 2);
        $sitemapPath = $rootPath . '/sitemap.xml';
        
        // Get all published posts
        $stmt = $pdo->query("SELECT slug, updated_at FROM posts WHERE status='published' ORDER BY updated_at DESC");
        $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Build sitemap XML
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        
        // Homepage
        $xml .= "  <url>\n";
        $xml .= "    <loc>" . ($_ENV['APP_URL'] ?? 'http://localhost/HIGH-Q') . "/</loc>\n";
        $xml .= "    <changefreq>daily</changefreq>\n";
        $xml .= "    <priority>1.0</priority>\n";
        $xml .= "  </url>\n";
        
        // Posts
        foreach ($posts as $post) {
            $xml .= "  <url>\n";
            $xml .= "    <loc>" . ($_ENV['APP_URL'] ?? 'http://localhost/HIGH-Q') . "/post/" . htmlspecialchars($post['slug']) . "</loc>\n";
            $xml .= "    <lastmod>" . date('Y-m-d', strtotime($post['updated_at'])) . "</lastmod>\n";
            $xml .= "    <changefreq>weekly</changefreq>\n";
            $xml .= "    <priority>0.8</priority>\n";
            $xml .= "  </url>\n";
        }
        
        $xml .= '</urlset>';
        
        // Write sitemap
        if (file_put_contents($sitemapPath, $xml)) {
            $message = "Sitemap generated successfully with " . count($posts) . " posts!";
        } else {
            $error = "Failed to write sitemap.xml";
        }
    } catch (Exception $e) {
        $error = "Sitemap generation failed: " . $e->getMessage();
    }
}

// Check if sitemap exists
$rootPath = dirname(__DIR__, 2);
$sitemapPath = $rootPath . '/sitemap.xml';
$sitemapExists = file_exists($sitemapPath);
$sitemapDate = $sitemapExists ? date('Y-m-d H:i:s', filemtime($sitemapPath)) : 'Never';
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css">
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; padding: 20px; background: #fafbff; }
        .auto-card { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); margin-bottom: 20px; }
        .auto-form button { padding: 12px 24px; background: #5f27cd; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; margin-top: 10px; display: inline-flex; align-items: center; gap: 6px; }
        .auto-form button:hover { background: #481caf; }
        .message { padding: 12px; background: #d4edda; border: 1px solid #c3e6cb; color: #155724; border-radius: 6px; margin-bottom: 15px; }
        .error { padding: 12px; background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; border-radius: 6px; margin-bottom: 15px; }
        .stat { display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #eee; }
        .stat-value { display: inline-flex; align-items: center; gap: 6px; }
        .info-box { background: #e7f3ff; border: 1px solid #74b9ff; padding: 15px; border-radius: 6px; margin-bottom: 20px; }
        .info-box i { color: #5f27cd; }
        .auto-list { color:#666; line-height:1.8; list-style: none; padding-left: 0; }
        .auto-list li { display: flex; align-items: center; gap: 8px; padding: 4px 0; }
        .auto-list li i { color: #22c55e; }
        .view-link { display: inline-flex; align-items: center; gap: 6px; margin-top: 10px; color: #5f27cd; text-decoration: none; }
        .view-link:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="info-box">
        <strong><i class='bx bx-bot'></i> Automator:</strong> Automatically generates sitemap.xml for SEO when content changes.
    </div>

    <?php if ($message): ?>
        <div class="message"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="auto-card">
        <h3>Sitemap Generator</h3>
        <div class="stat">
            <span>Status:</span>
            <strong class="stat-value"><?= $sitemapExists ? '<i class="bx bx-check-circle" style="color:#22c55e;"></i> Active' : '<i class="bx bx-x-circle" style="color:#ef4444;"></i> Not Generated' ?></strong>
        </div>
        <div class="stat">
            <span>Last Updated:</span>
            <strong><?= htmlspecialchars($sitemapDate) ?></strong>
        </div>
        <div class="stat">
            <span>Location:</span>
            <strong>/sitemap.xml</strong>
        </div>
        <form method="POST" class="auto-form">
            <input type="hidden" name="action" value="generate_sitemap">
            <button type="submit"><i class='bx bx-map-alt'></i> Generate Sitemap Now</button>
        </form>
        <?php if ($sitemapExists): ?>
            <a href="../../sitemap.xml" target="_blank" class="view-link"><i class='bx bx-file'></i> View Sitemap</a>
        <?php endif; ?>
    </div>

    <div class="auto-card">
        <h3>Automation Rules</h3>
        <ul class="auto-list">
            <li><i class='bx bx-check'></i> Auto-generate sitemap when new post is published</li>
            <li><i class='bx bx-check'></i> Auto-update sitemap when post is edited</li>
            <li><i class='bx bx-check'></i> Notify search engines of sitemap changes</li>
        </ul>
    </div>
</body>
</html>
