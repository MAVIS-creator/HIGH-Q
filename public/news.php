<?php
require_once __DIR__ . '/config/db.php';

// Fetch published posts
$stmt = $pdo->prepare("SELECT id, title, slug, excerpt, featured_image, created_at FROM posts WHERE status='published' ORDER BY created_at DESC");
$stmt->execute();
$posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = 'HIGH Q News Forum';
require_once __DIR__ . '/includes/header.php';
?>

<section class="about-hero">
  <div class="about-hero-overlay"></div>
  <div class="container about-hero-inner">
    <h1>HIGH Q NEWS FORUM</h1>
    <p class="lead">Stay up to date with announcements, tips, and stories from HIGH Q Academy. Check back often for the latest news.</p>
  </div>
</section>

<section class="programs-content">
  <div class="container">
    <div class="ceo-heading">
      <h2>Latest <span class="highlight">News & Blog</span></h2>
    </div>

    <?php if ($posts): ?>
      <div class="posts-grid">
        <?php foreach ($posts as $p): ?>
          <article class="post-card">
            <?php if (!empty($p['featured_image'])): ?>
              <img src="<?= htmlspecialchars($p['featured_image']) ?>" alt="" class="thumb">
            <?php endif; ?>
            <h3><a href="post.php?id=<?= $p['id'] ?>"><?= htmlspecialchars($p['title']) ?></a></h3>
            <p class="muted"><?= htmlspecialchars($p['created_at']) ?></p>
            <p><?= htmlspecialchars($p['excerpt']) ?></p>
            <a href="post.php?id=<?= $p['id'] ?>" class="btn-ghost">Read More</a>
          </article>
        <?php endforeach; ?>
      </div>
    <?php else: ?>
      <p class="muted">No news posts available at the moment. Check back later for updates!</p>
    <?php endif; ?>
  </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php';
