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

// detect whether posts table has a 'tags' column (some installs don't)
$hasTags = false;
try {
  $colStmt = $pdo->prepare("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'posts' AND COLUMN_NAME = 'tags'");
  $colStmt->execute();
  $hasTags = (bool)$colStmt->fetchColumn();
} catch (Throwable $e) {
  $hasTags = false;
}

// Build where clauses
$where = "WHERE status='published'";
$params = [];
if ($selectedCategory) {
  $where .= " AND category_id = ?";
  $params[] = $selectedCategory;
}
if ($selectedTag !== '') {
  if ($hasTags) {
    // match tag in comma-separated tags or as substring
    $where .= " AND (FIND_IN_SET(?, tags) OR tags LIKE ? )";
    $params[] = $selectedTag;
    $params[] = "%" . $selectedTag . "%";
  } else {
    // tags column not present: ignore tag filter to avoid SQL errors
    $selectedTag = '';
  }
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

$sql = "SELECT id, title, slug, excerpt, created_at FROM posts " . $where . " ORDER BY created_at DESC LIMIT ? OFFSET ?";
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
    <div class="ceo-heading mb-3">
      <h2>Latest <span class="highlight">News & Blog</span></h2>
    </div>

    <!-- Filters: category + search -->
    <form method="get" action="news.php" class="news-filters row g-2 align-items-center mb-3">
      <div class="col-auto">
        <select name="category" class="form-select">
          <option value="">All Categories</option>
          <?php foreach($categories as $c): ?>
            <option value="<?= $c['id'] ?>" <?= $selectedCategory == $c['id'] ? 'selected' : '' ?>><?= htmlspecialchars($c['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col">
        <input type="text" name="q" class="form-control" placeholder="Search title..." value="<?= htmlspecialchars($q) ?>">
      </div>
      <div class="col-auto">
        <button class="btn btn-outline-secondary">Filter</button>
      </div>
    </form>

    <?php if ($posts): ?>
      <div class="row posts-grid g-3">
        <?php foreach ($posts as $p): ?>
          <article class="post-card col-12 col-md-6 col-lg-4">
            <div class="card h-100">
              <?php if (!empty($p['featured_image'] ?? '')): ?>
                <img src="<?= htmlspecialchars($p['featured_image']) ?>" alt="" class="card-img-top img-fluid">
              <?php endif; ?>
              <div class="card-body d-flex flex-column">
                <h5 class="card-title"><a href="./post.php?id=<?= $p['id'] ?>"><?= htmlspecialchars($p['title']) ?></a></h5>
                <p class="text-muted small mb-2"><?= htmlspecialchars($p['created_at']) ?></p>
                <p class="card-text mb-3"><?= htmlspecialchars($p['excerpt']) ?></p>
                <?php if ($hasTags): ?>
                  <div class="mb-2">
                    <?php
                      $tags = array_filter(array_map('trim', explode(',', $p['tags'] ?? '')));
                      foreach ($tags as $t) {
                          echo '<a class="badge bg-light text-dark me-1" href="news.php?tag=' . urlencode($t) . '">' . htmlspecialchars($t) . '</a> ';
                      }
                    ?>
                  </div>
                <?php endif; ?>
                <div class="mt-auto"><a href="./post.php?id=<?= $p['id'] ?>" class="btn btn-sm btn-primary">Read More</a></div>
              </div>
            </div>
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

<!-- Newsletter subscribe form -->
<section class="newsletter" style="margin-top:18px;border-top:1px solid #f1f1f1;">
  <div class="container" style="max-width:900px;">
    <h3>Subscribe to our newsletter</h3>
    <p class="muted">Get the latest news and announcements delivered to your inbox.</p>
    <div class="newsletter-panel" style="max-width:600px;">
      <form id="newsletterForm" style="display:flex;gap:8px;">
        <input type="email" name="email" placeholder="Your email address" required class="form-input">
        <button type="submit" class="btn-primary">Subscribe</button>
      </form>
    </div>
    <div id="newsletterMsg" style="margin-top:8px;color:green;display:none"></div>
  </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

<script>
document.getElementById('newsletterForm').addEventListener('submit', function(e){
  e.preventDefault();
  var fd = new FormData(this);
  fetch('api/subscribe_newsletter.php', { method: 'POST', body: fd }).then(r=>r.json()).then(j=>{
    var msg = document.getElementById('newsletterMsg');
    if (j.status === 'ok') { msg.style.display='block'; msg.style.color='green'; msg.textContent = j.message || 'Subscribed'; this.reset(); }
    else { msg.style.display='block'; msg.style.color='crimson'; msg.textContent = j.message || 'Error'; }
  }).catch(()=>{ var msg = document.getElementById('newsletterMsg'); msg.style.display='block'; msg.style.color='crimson'; msg.textContent='Network error'; });
});
</script>
