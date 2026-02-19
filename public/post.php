<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/functions.php';

$postId = (int)($_GET['id'] ?? 0);
if (!$postId) { header('Location: index.php'); exit; }

// fetch post with author info - try both author_id and created_by as schema varies
$stmt = $pdo->prepare('
  SELECT p.*, 
         COALESCE(u1.name, u2.name) as author_name, 
         COALESCE(u1.email, u2.email) as author_email
  FROM posts p 
  LEFT JOIN users u1 ON p.author_id = u1.id 
  LEFT JOIN users u2 ON p.created_by = u2.id 
  WHERE p.id = ? 
  LIMIT 1
');
$stmt->execute([$postId]);
$post = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$post) { echo "<p>Post not found.</p>"; exit; }

// Author display name (fallback to 'Admin' if not set)
$authorName = !empty($post['author_name']) ? $post['author_name'] : 'Admin';
$authorInitial = strtoupper(substr($authorName, 0, 1));

// fetch approved comments (top-level)
$cstmt = $pdo->prepare('SELECT * FROM comments WHERE post_id = ? AND parent_id IS NULL AND status = "approved" ORDER BY created_at DESC');
$cstmt->execute([$postId]);
$comments = $cstmt->fetchAll(PDO::FETCH_ASSOC);

// compute total approved comments count for display
$ccstmt = $pdo->prepare('SELECT COUNT(1) FROM comments WHERE post_id = ? AND status = "approved"');
$ccstmt->execute([$postId]);
$comments_count = (int)$ccstmt->fetchColumn();

// Calculate reading time (average 200 words per minute)
$wordCount = str_word_count(strip_tags($post['content'] ?? ''));
$readingTime = max(1, ceil($wordCount / 200));

function convert_plain_images($text) {
  $text = preg_replace_callback('/!\[([^\]]*)\]\((https?:\/\/[^\s)]+)\)/i', function($m) {
    $alt = htmlspecialchars($m[1]);
    $url = htmlspecialchars($m[2]);
    return '<img src="' . $url . '" alt="' . $alt . '" style="max-width:100%;height:auto;border-radius:8px;">';
  }, $text);

  $text = preg_replace_callback('/^\s*(https?:\/\/\S+\.(?:png|jpe?g|gif|webp))\s*$/im', function($m) {
    $url = htmlspecialchars($m[1]);
    return '<img src="' . $url . '" alt="Embedded image" style="max-width:100%;height:auto;border-radius:8px;">';
  }, $text);

  return $text;
}

// Build a server-side Table of Contents by scanning headings in the post content (if present)
$tocHtml = '';
$tocItems = [];
$renderedContent = '';
$contentRaw = $post['content'] ?? '';

// If the content is plain text (no HTML tags), convert simple Markdown-style headings
if ($contentRaw !== '' && strpos($contentRaw, '<') === false) {
  $contentProcessed = preg_replace('/^###\s*(.+)$/m', '<h4>$1</h4>', $contentRaw);
  $contentProcessed = preg_replace('/^##\s*(.+)$/m', '<h3>$1</h3>', $contentProcessed);
  $contentProcessed = preg_replace('/^#\s*(.+)$/m', '<h2>$1</h2>', $contentProcessed);
  $contentProcessed = convert_plain_images($contentProcessed);

  $paras = preg_split('/\n\s*\n/', $contentProcessed);
  $out = '';
  foreach ($paras as $p) {
    $p = trim($p);
    if ($p === '') continue;
    if (preg_match('/^\s*<h[2-4]>/i', $p)) {
      $out .= $p;
    } else {
      $out .= '<p>' . nl2br(htmlspecialchars($p)) . '</p>';
    }
  }
  $contentForDoc = $out;
} else {
  $contentForDoc = $contentRaw;
}

if ($contentForDoc !== '') {
  libxml_use_internal_errors(true);
  $doc = new DOMDocument();
  $wrapped = '<div>' . $contentForDoc . '</div>';
  $doc->loadHTML('<?xml encoding="utf-8" ?>' . $wrapped);
  $xpath = new DOMXPath($doc);
  $nodes = $xpath->query('//h2|//h3|//h4');
  if ($nodes->length > 0) {
    $ids = [];
    foreach ($nodes as $n) {
      $text = trim($n->textContent);
      if ($text === '') continue;
      $base = preg_replace('/[^a-z0-9\-]+/','-',strtolower(trim($text)));
      $id = 'toc-' . $postId . '-' . trim($base, '-');
      $suffix = 1;
      $orig = $id;
      while (in_array($id, $ids)) { $id = $orig . '-' . $suffix; $suffix++; }
      $ids[] = $id;
      if ($n instanceof DOMElement) {
        $n->setAttribute('id', $id);
      }
      $tocItems[] = ['id' => $id, 'text' => $text, 'tag' => $n->nodeName];
    }
  }

  $div = $doc->getElementsByTagName('div')->item(0);
  if ($div) {
    $html = '';
    foreach ($div->childNodes as $child) { $html .= $doc->saveHTML($child); }
    $renderedContent = $html;
  } else {
    $renderedContent = htmlspecialchars($contentForDoc);
  }
  libxml_clear_errors();
}

// Helper: format plain text to HTML
function format_plain_text_to_html($txt) {
  $txt = (string)$txt;
  if ($txt === '') return '';
  $txt = preg_replace(['/^###\s*(.+)$/m','/^##\s*(.+)$/m','/^#\s*(.+)$/m'], ['<h4>$1</h4>','<h3>$1</h3>','<h2>$1</h2>'], $txt);
  $txt = convert_plain_images($txt);
  $blocks = preg_split('/\n\s*\n/', $txt);
  $out = '';
  foreach ($blocks as $b) {
    $b = trim($b);
    if ($b === '') continue;
    if (preg_match('/<[^>]+>/', $b)) {
      $out .= $b;
    } else {
      $out .= '<p>' . nl2br(htmlspecialchars($b)) . '</p>';
    }
  }
  return $out;
}

// Format the publish date nicely
$publishDate = $post['published_at'] ?? $post['created_at'];
$formattedDate = date('F j, Y', strtotime($publishDate));

$pageTitle = $post['title'];
require_once __DIR__ . '/includes/header.php';
?>

<!-- Reading Progress Bar -->
<div class="reading-progress" id="readingProgress"></div>

<div class="post-page-container">
  
  <!-- Hero Section -->
  <?php if (!empty($post['featured_image'])): ?>
    <?php
      $fi = $post['featured_image'];
      if (preg_match('#^https?://#i', $fi) || strpos($fi,'//')===0 || strpos($fi,'/')===0) {
        $imgSrc = $fi;
      } else {
        $imgSrc = './' . ltrim($fi, '/');
      }
    ?>
    <div class="post-hero">
      <img src="<?= htmlspecialchars($imgSrc) ?>" alt="<?= htmlspecialchars($post['title']) ?>" class="post-hero-image">
      <div class="post-hero-overlay"></div>
      <div class="post-hero-content">
        <h1 class="post-hero-title"><?= htmlspecialchars($post['title']) ?></h1>
        <div class="post-hero-meta">
          <div class="post-author-badge">
            <div class="post-author-avatar"><?= $authorInitial ?></div>
            <span class="post-author-name">By <?= htmlspecialchars($authorName) ?></span>
          </div>
          <span><i class="bx bx-calendar"></i> <?= $formattedDate ?></span>
          <span><i class="bx bx-time-five"></i> <?= $readingTime ?> min read</span>
        </div>
      </div>
    </div>
  <?php else: ?>
    <div class="post-hero post-hero-fallback">
      <div class="post-hero-overlay"></div>
      <div class="post-hero-content">
        <h1 class="post-hero-title"><?= htmlspecialchars($post['title']) ?></h1>
        <div class="post-hero-meta">
          <div class="post-author-badge">
            <div class="post-author-avatar"><?= $authorInitial ?></div>
            <span class="post-author-name">By <?= htmlspecialchars($authorName) ?></span>
          </div>
          <span><i class="bx bx-calendar"></i> <?= $formattedDate ?></span>
          <span><i class="bx bx-time-five"></i> <?= $readingTime ?> min read</span>
        </div>
      </div>
    </div>
  <?php endif; ?>

  <!-- Main Article -->
  <div class="post-article-wrap">
    <article class="post-article">
      <div class="post-layout">
        
        <!-- Main Content -->
        <div class="post-main">
          <div class="post-content">
            <?= $renderedContent ?: format_plain_text_to_html($post['content']) ?: nl2br(htmlspecialchars($post['content'])) ?>
          </div>
        </div>

        <!-- Table of Contents Sidebar -->
        <aside class="post-toc-sidebar" id="tocSidebar">
          <h4>Contents</h4>
          <?php if (!empty($tocItems)): ?>
            <ul>
              <?php foreach ($tocItems as $it): ?>
                <li class="toc-<?= $it['tag'] ?>">
                  <a href="#<?= htmlspecialchars($it['id']) ?>"><?= htmlspecialchars($it['text']) ?></a>
                </li>
              <?php endforeach; ?>
            </ul>
          <?php else: ?>
            <p class="post-toc-empty">No sections in this article.</p>
          <?php endif; ?>
        </aside>

      </div>

      <!-- Action Bar -->
      <div class="post-actions-bar">
        <button id="likeBtn" class="post-action-btn" aria-pressed="false">
          <i class="bx bx-heart"></i>
          <span>Like</span>
        </button>
        <div class="post-action-count">
          <i class="bx bxs-heart"></i>
          <span id="likesCount"><?= htmlspecialchars($post['likes'] ?? 0) ?></span>
        </div>
        
        <button id="commentToggle" class="post-action-btn">
          <i class="bx bx-comment-dots"></i>
          <span>Comment</span>
        </button>
        <div class="post-action-count">
          <i class="bx bxs-comment-dots"></i>
          <span id="commentsCount"><?= intval($comments_count ?? 0) ?></span>
        </div>

        <div class="post-actions-spacer"></div>

        <button id="shareBtn" type="button" class="post-action-btn post-share-btn" onclick="return sharePost(event)">
          <i class="bx bx-share-alt"></i>
          <span>Share</span>
        </button>
      </div>
    </article>
  </div>

  <!-- Comments Section -->
  <section class="post-comments-section" id="commentsSection">
    <div class="post-comments-header">
      <h2>Comments</h2>
      <span class="post-comments-count" id="commentsCountBadge"><?= intval($comments_count ?? 0) ?></span>
    </div>

    <div id="commentsList">
      <?php foreach($comments as $c): ?>
        <div class="comment-card" data-id="<?= $c['id'] ?>">
          <div class="comment-header">
            <div class="comment-avatar"><?= strtoupper(substr($c['name'] ?: 'A', 0, 1)) ?></div>
            <div class="comment-meta">
              <div class="comment-author">
                <?= htmlspecialchars($c['name'] ?: 'Anonymous') ?>
              </div>
              <div class="comment-date"><?= date('M j, Y \a\t g:i A', strtotime($c['created_at'])) ?></div>
            </div>
          </div>
          <div class="comment-body"><?= nl2br(htmlspecialchars($c['content'])) ?></div>
          <div class="comment-actions">
            <button class="comment-action-btn like-btn" data-id="<?= $c['id'] ?>">
              <i class="bx bx-heart"></i> <span class="like-count"><?= $c['likes'] ?? 0 ?></span>
            </button>
            <button class="comment-action-btn reply-btn" data-id="<?= $c['id'] ?>">
              <i class="bx bx-reply"></i> Reply
            </button>
          </div>

          <?php
            // fetch replies for this comment
            $rstmt = $pdo->prepare('SELECT * FROM comments WHERE parent_id = ? AND status = "approved" ORDER BY created_at ASC');
            $rstmt->execute([$c['id']]);
            $replies = $rstmt->fetchAll(PDO::FETCH_ASSOC);
            if ($replies):
          ?>
            <div class="comment-replies">
              <?php foreach($replies as $rep): ?>
                <div class="comment-card">
                  <div class="comment-header">
                    <div class="comment-avatar <?= $rep['user_id'] ? 'admin' : '' ?>"><?= strtoupper(substr($rep['name'] ?: 'A', 0, 1)) ?></div>
                    <div class="comment-meta">
                      <div class="comment-author">
                        <?= htmlspecialchars($rep['name'] ?: 'Anonymous') ?>
                        <?php if ($rep['user_id']): ?>
                          <span class="admin-badge">Admin</span>
                        <?php endif; ?>
                      </div>
                      <div class="comment-date"><?= date('M j, Y \a\t g:i A', strtotime($rep['created_at'])) ?></div>
                    </div>
                  </div>
                  <div class="comment-body"><?= nl2br(htmlspecialchars($rep['content'])) ?></div>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    </div>

    <!-- Comment Form -->
    <form id="commentForm" class="post-comment-form">
      <h3>Leave a Comment</h3>
      <input type="hidden" name="post_id" value="<?= $post['id'] ?>">
      <input type="hidden" name="parent_id" id="parent_id" value="">
      
      <div class="form-row">
        <label class="form-label">Name</label>
        <input class="form-input" type="text" name="name" placeholder="Your name">
      </div>
      <div class="form-row">
        <label class="form-label">Email</label>
        <input class="form-input" type="email" name="email" placeholder="your@email.com">
      </div>
      <div class="form-row">
        <label class="form-label">Comment</label>
        <textarea class="form-textarea" name="content" rows="5" placeholder="Write your thoughts..." required></textarea>
      </div>
      <div class="form-actions">
        <button type="submit" class="btn-submit-comment">
          <i class="bx bx-send"></i> Submit Comment
        </button>
        <button type="button" id="cancelReply" class="btn-cancel-reply" style="display:none;">Cancel Reply</button>
      </div>
    </form>
  </section>
</div>

<!-- Mobile TOC Toggle -->
<button class="toc-mobile-toggle" id="tocMobileToggle" aria-label="Table of Contents">
  <i class="bx bx-list-ul"></i>
</button>

<!-- Mobile TOC Panel -->
<div class="toc-mobile-overlay" id="tocMobileOverlay"></div>
<div class="toc-mobile-panel" id="tocMobilePanel">
  <button class="toc-mobile-close" id="tocMobileClose"><i class="bx bx-x"></i></button>
  <h4>Contents</h4>
  <?php if (!empty($tocItems)): ?>
    <ul class="post-toc-sidebar">
      <?php foreach ($tocItems as $it): ?>
        <li class="toc-<?= $it['tag'] ?>">
          <a href="#<?= htmlspecialchars($it['id']) ?>" class="mobile-toc-link"><?= htmlspecialchars($it['text']) ?></a>
        </li>
      <?php endforeach; ?>
    </ul>
  <?php else: ?>
    <p class="post-toc-empty">No sections in this article.</p>
  <?php endif; ?>
</div>

<script>
// Share functionality
function sharePost(event) {
  if (event) {
    event.preventDefault();
    event.stopPropagation();
  }

  const trigger = (event && event.currentTarget) ? event.currentTarget : document.getElementById('shareBtn');
  const shareData = {
    title: <?= json_encode($post['title']) ?>,
    url: window.location.href
  };

  const onCopied = () => {
    if (typeof Swal !== 'undefined') {
      Swal.fire({title: 'Link Copied!', text: 'Share this post with others', icon: 'success', timer: 2000, showConfirmButton: false});
    } else {
      alert('Link copied to clipboard!');
    }
  };

  if (navigator.share) {
    navigator.share(shareData)
      .catch(() => {
        if (navigator.clipboard && navigator.clipboard.writeText) {
          navigator.clipboard.writeText(window.location.href).then(onCopied).catch(() => {});
        }
      })
      .finally(() => { if (trigger) trigger.blur(); });
  } else if (navigator.clipboard && navigator.clipboard.writeText) {
    navigator.clipboard.writeText(window.location.href)
      .then(onCopied)
      .finally(() => { if (trigger) trigger.blur(); });
  } else {
    if (trigger) trigger.blur();
  }

  return false;
}

// Reading progress bar
document.addEventListener('scroll', function() {
  const article = document.querySelector('.post-article');
  if (!article) return;
  const rect = article.getBoundingClientRect();
  const scrolled = Math.max(0, -rect.top);
  const total = article.offsetHeight - window.innerHeight;
  const progress = Math.min(100, (scrolled / total) * 100);
  document.getElementById('readingProgress').style.width = progress + '%';
});

// TOC active state highlighting
document.addEventListener('DOMContentLoaded', function() {
  const tocLinks = document.querySelectorAll('.post-toc-sidebar a');
  const headings = [];
  tocLinks.forEach(link => {
    const id = link.getAttribute('href').slice(1);
    const el = document.getElementById(id);
    if (el) headings.push({id, el, link});
  });

  function updateActiveLink() {
    let current = null;
    headings.forEach(h => {
      if (h.el.getBoundingClientRect().top <= 120) current = h;
    });
    tocLinks.forEach(l => l.classList.remove('active'));
    if (current) current.link.classList.add('active');
  }

  window.addEventListener('scroll', updateActiveLink);
  updateActiveLink();

  // Smooth scroll for TOC links
  tocLinks.forEach(link => {
    link.addEventListener('click', function(e) {
      e.preventDefault();
      const id = this.getAttribute('href').slice(1);
      const target = document.getElementById(id);
      if (target) {
        target.scrollIntoView({behavior: 'smooth', block: 'start'});
        // Close mobile TOC if open
        document.getElementById('tocMobileOverlay').classList.remove('active');
        document.getElementById('tocMobilePanel').classList.remove('active');
      }
    });
  });

  // Mobile TOC toggle
  const tocToggle = document.getElementById('tocMobileToggle');
  const tocOverlay = document.getElementById('tocMobileOverlay');
  const tocPanel = document.getElementById('tocMobilePanel');
  const tocClose = document.getElementById('tocMobileClose');

  tocToggle.addEventListener('click', function() {
    tocOverlay.classList.add('active');
    tocPanel.classList.add('active');
  });

  tocOverlay.addEventListener('click', function() {
    tocOverlay.classList.remove('active');
    tocPanel.classList.remove('active');
  });

  tocClose.addEventListener('click', function() {
    tocOverlay.classList.remove('active');
    tocPanel.classList.remove('active');
  });

  // Scroll to comments when comment button clicked
  document.getElementById('commentToggle').addEventListener('click', function() {
    document.getElementById('commentsSection').scrollIntoView({behavior: 'smooth'});
  });
});
</script>

<!-- Comment and like handling -->
<script>window.POST_ID = <?= (int)$postId ?>;</script>
<script src="<?= app_url('assets/js/post.js') ?>"></script>

<?php require_once __DIR__ . '/includes/footer.php';
