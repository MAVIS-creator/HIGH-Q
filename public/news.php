<?php
require_once __DIR__ . '/config/db.php';

$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 6;
$offset = ($page - 1) * $perPage;

// filters
$selectedCategory = (int)($_GET['category'] ?? 0);
$selectedTag = trim($_GET['tag'] ?? '');
$q = trim($_GET['q'] ?? '');

// fetch categories for filter UI
try {
  $catsStmt = $pdo->query("SELECT id,name,slug FROM categories ORDER BY name");
  $categories = $catsStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
  $categories = [];
}

// Build where clauses
$where = "WHERE status='published'";
$params = [];
if ($selectedCategory) {
  $where .= " AND category_id = ?";
  $params[] = $selectedCategory;
}
if ($selectedTag !== '') {
  // match tag in comma-separated tags or as substring
  $where .= " AND (FIND_IN_SET(?, tags) OR tags LIKE ? )";
  $params[] = $selectedTag;
  $params[] = "%" . $selectedTag . "%";
}
if ($q !== '') {
  $where .= " AND title LIKE ?";
  $params[] = "%{$q}%";
}

// total count for pagination
$countSql = "SELECT COUNT(*) FROM posts " . $where;
$countStmt = $pdo->prepare($countSql);
$countStmt->execute($params);
$total = (int)$countStmt->fetchColumn();

// fetch page of posts
$sql = "SELECT id, title, slug, excerpt, featured_image, created_at FROM posts " . $where . " ORDER BY created_at DESC LIMIT ? OFFSET ?";
$pageParams = $params;
$pageParams[] = $perPage;
$pageParams[] = $offset;
$stmt = $pdo->prepare($sql);
$stmt->execute($pageParams);
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

    <!-- Filters: category + search -->
    <form method="get" action="news.php" class="news-filters" style="margin-bottom:18px;display:flex;gap:12px;align-items:center;flex-wrap:wrap;">
      <select name="category">
        <option value="">All Categories</option>
        <?php foreach($categories as $c): ?>
          <option value="<?= $c['id'] ?>" <?= $selectedCategory == $c['id'] ? 'selected' : '' ?>><?= htmlspecialchars($c['name']) ?></option>
        <?php endforeach; ?>
      </select>
      <input type="text" name="q" placeholder="Search title..." value="<?= htmlspecialchars($q) ?>">
      <button class="btn-ghost">Filter</button>
    </form>

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
            <p>
              <?php
                // show tags as links (comma separated)
                $tags = array_filter(array_map('trim', explode(',', $p['tags'] ?? '')));
                foreach ($tags as $t) {
                    echo '<a class="tag" href="news.php?tag=' . urlencode($t) . '">' . htmlspecialchars($t) . '</a> ';
                }
              ?>
            </p>
            <a href="post.php?id=<?= $p['id'] ?>" class="btn-ghost">Read More</a>
          </article>
        <?php endforeach; ?>
      </div>

      <!-- Pagination -->
      <?php
        $totalPages = max(1, ceil($total / $perPage));
        $base = 'news.php?';
        // preserve filters in links
        $qs = [];
        if ($selectedCategory) $qs[] = 'category=' . $selectedCategory;
        if ($selectedTag !== '') $qs[] = 'tag=' . urlencode($selectedTag);
        if ($q !== '') $qs[] = 'q=' . urlencode($q);
        $base .= implode('&', $qs);
      ?>
      <div class="pagination" style="margin-top:20px;text-align:center;">
        <?php if ($page > 1): ?>
          <a class="btn-ghost" href="<?= $base ?>&page=<?= $page-1 ?>">&laquo; Prev</a>
        <?php endif; ?>
        <span style="margin:0 12px;">Page <?= $page ?> of <?= $totalPages ?></span>
        <?php if ($page < $totalPages): ?>
          <a class="btn-ghost" href="<?= $base ?>&page=<?= $page+1 ?>">Next &raquo;</a>
        <?php endif; ?>
      </div>

    <?php else: ?>
      <p class="muted">No news posts available at the moment. Check back later for updates!</p>
    <?php endif; ?>
  </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php';
