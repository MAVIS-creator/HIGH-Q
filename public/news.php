<?php
require_once __DIR__ . '/config/db.php';

$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 6;
$offset = ($page - 1) * $perPage;

include __DIR__ . '/includes/header.php';
?>

<section class="about-hero position-relative py-5">
  <div class="about-hero-overlay position-absolute top-0 start-0 w-100 h-100"></div>
  <div class="container about-hero-inner position-relative text-center py-5">
    <h1 class="display-4 fw-bold mb-3">Latest News</h1>
    <p class="lead mb-0 mx-auto" style="max-width: 700px;">Stay updated with our latest announcements, events, and success stories.</p>
  </div>
</section>

<section class="py-5">
  <div class="container">
    <!-- Filters -->
    <div class="row g-3 mb-5">
      <div class="col-md-4">
        <select class="form-select" name="category" onchange="this.form.submit()">
          <option value="0">All Categories</option>
          <?php foreach($categories as $cat): ?>
            <option value="<?= $cat['id'] ?>" <?= $selectedCategory == $cat['id'] ? 'selected' : '' ?>>
              <?= htmlspecialchars($cat['name']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <?php if ($hasTags): ?>
      <div class="col-md-4">
        <input type="text" class="form-control" name="tag" placeholder="Filter by tag..." value="<?= htmlspecialchars($selectedTag) ?>">
      </div>
      <?php endif; ?>
      <div class="col-md-4">
        <div class="input-group">
          <input type="text" class="form-control" name="q" placeholder="Search news..." value="<?= htmlspecialchars($q) ?>">
          <button class="btn btn-primary" type="submit">
            <i class="bx bx-search"></i>
          </button>
        </div>
      </div>
    </div>

    <!-- News Grid -->
    <div class="row g-4">
      <?php while ($post = $stmt->fetch(PDO::FETCH_ASSOC)): ?>
      <div class="col-md-6 col-lg-4">
        <article class="card h-100 border-0 shadow-sm hover-lift">
          <?php if ($post['featured_image']): ?>
          <div class="card-img-top overflow-hidden" style="max-height: 200px;">
            <img src="<?= htmlspecialchars($post['featured_image']) ?>" alt="<?= htmlspecialchars($post['title']) ?>" class="img-fluid w-100 object-cover">
          </div>
          <?php endif; ?>
          
          <div class="card-body">
            <?php if ($post['category_name']): ?>
            <div class="text-primary mb-2 small"><?= htmlspecialchars($post['category_name']) ?></div>
            <?php endif; ?>
            
            <h3 class="h4 card-title">
              <a href="post.php?id=<?= $post['id'] ?>" class="text-decoration-none text-dark stretched-link">
                <?= htmlspecialchars($post['title']) ?>
              </a>
            </h3>
            
            <p class="card-text text-muted">
              <?= htmlspecialchars(substr(strip_tags($post['content']), 0, 120)) ?>...
            </p>
          </div>
          
          <div class="card-footer bg-white border-0 pt-0">
            <div class="d-flex align-items-center text-muted small">
              <div class="me-3">
                <i class="bx bx-calendar me-1"></i>
                <?= date('M j, Y', strtotime($post['created_at'])) ?>
              </div>
              <?php if ($hasTags && !empty($post['tags'])): ?>
              <div>
                <i class="bx bx-purchase-tag me-1"></i>
                <?= htmlspecialchars($post['tags']) ?>
              </div>
              <?php endif; ?>
            </div>
          </div>
        </article>
      </div>
      <?php endwhile; ?>
    </div>

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
    <nav class="mt-5" aria-label="News pagination">
      <ul class="pagination justify-content-center">
        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
        <li class="page-item <?= $i === $page ? 'active' : '' ?>">
          <a class="page-link" href="?page=<?= $i ?><?= $selectedCategory ? '&category=' . $selectedCategory : '' ?><?= $selectedTag ? '&tag=' . urlencode($selectedTag) : '' ?><?= $q ? '&q=' . urlencode($q) : '' ?>">
            <?= $i ?>
          </a>
        </li>
        <?php endfor; ?>
      </ul>
    </nav>
    <?php endif; ?>

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
    <div class="ceo-heading">
      <h2>Latest <span class="highlight">News & Blog</span></h2>
    </div>

    <!-- Filters: category + search -->
    <form method="get" action="/news.php" class="news-filters" style="margin-bottom:18px;display:flex;gap:12px;align-items:center;flex-wrap:wrap;">
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
            <?php if (!empty($p['featured_image'] ?? '')): ?>
              <img src="<?= htmlspecialchars($p['featured_image']) ?>" alt="" class="thumb">
            <?php endif; ?>
            <h3><a href="./post.php?id=<?= $p['id'] ?>"><?= htmlspecialchars($p['title']) ?></a></h3>
            <p class="muted"><?= htmlspecialchars($p['created_at']) ?></p>
            <p><?= htmlspecialchars($p['excerpt']) ?></p>
            <?php if ($hasTags): ?>
            <p>
              <?php
                // show tags as links (comma separated)
                $tags = array_filter(array_map('trim', explode(',', $p['tags'] ?? '')));
                foreach ($tags as $t) {
                    echo '<a class="tag" href="news.php?tag=' . urlencode($t) . '">' . htmlspecialchars($t) . '</a> ';
                }
              ?>
            </p>
            <?php endif; ?>
            <a href="./post.php?id=<?= $p['id'] ?>" class="btn-ghost">Read More</a>
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
