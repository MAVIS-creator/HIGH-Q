<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/functions.php';

$postId = (int)($_GET['id'] ?? 0);
if (!$postId) { header('Location: index.php'); exit; }

// fetch post
$stmt = $pdo->prepare('SELECT * FROM posts WHERE id = ? LIMIT 1');
$stmt->execute([$postId]);
$post = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$post) { echo "<p>Post not found.</p>"; exit; }

// fetch approved comments (top-level)
$cstmt = $pdo->prepare('SELECT * FROM comments WHERE post_id = ? AND parent_id IS NULL AND status = "approved" ORDER BY created_at DESC');
$cstmt->execute([$postId]);
$comments = $cstmt->fetchAll(PDO::FETCH_ASSOC);

// compute total approved comments count for display
$ccstmt = $pdo->prepare('SELECT COUNT(1) FROM comments WHERE post_id = ? AND status = "approved"');
$ccstmt->execute([$postId]);
$comments_count = (int)$ccstmt->fetchColumn();

// Build a server-side Table of Contents by scanning headings in the post content (if present)
$tocHtml = '';
$renderedContent = '';
$contentRaw = $post['content'] ?? '';
// If the content is plain text (no HTML tags), convert simple Markdown-style headings (#, ##, ###)
// into <h2>/<h3>/<h4> and wrap paragraphs in <p> blocks so the TOC and spacing work.
if ($contentRaw !== '' && strpos($contentRaw, '<') === false) {
  // Convert heading markers
  $contentProcessed = preg_replace('/^###\s*(.+)$/m', '<h4>$1</h4>', $contentRaw);
  $contentProcessed = preg_replace('/^##\s*(.+)$/m', '<h3>$1</h3>', $contentProcessed);
  $contentProcessed = preg_replace('/^#\s*(.+)$/m', '<h2>$1</h2>', $contentProcessed);

  // Split into paragraphs on blank lines
  $paras = preg_split('/\n\s*\n/', $contentProcessed);
  $out = '';
  foreach ($paras as $p) {
    $p = trim($p);
    if ($p === '') continue;
    // if paragraph already contains a heading tag, keep as-is; otherwise wrap in <p>
    if (preg_match('/^\s*<h[2-4]>/i', $p)) {
      $out .= $p;
    } else {
      $out .= '<p>' . nl2br(htmlspecialchars($p)) . '</p>';
    }
  }
  $contentForDoc = $out;
} else {
  // contains HTML already or is empty; use as-is
  $contentForDoc = $contentRaw;
}

if ($contentForDoc !== '') {
  libxml_use_internal_errors(true);
  $doc = new DOMDocument();
  // Wrap in a div to get fragment handling and set UTF-8
  $wrapped = '<div>' . $contentForDoc . '</div>';
  $doc->loadHTML('<?xml encoding="utf-8" ?>' . $wrapped);
  $xpath = new DOMXPath($doc);
  $nodes = $xpath->query('//h2|//h3|//h4');
  if ($nodes->length > 0) {
    $ids = [];
    $tocItems = [];
    foreach ($nodes as $n) {
      $text = trim($n->textContent);
      if ($text === '') continue;
      $base = preg_replace('/[^a-z0-9\-]+/','-',strtolower(trim($text)));
      $id = 'toc-' . $postId . '-' . trim($base, '-');
      // ensure unique
      $suffix = 1;
      $orig = $id;
      while (in_array($id, $ids)) { $id = $orig . '-' . $suffix; $suffix++; }
      $ids[] = $id;
      // set id attribute
      if ($n instanceof DOMElement) {
        $n->setAttribute('id', $id);
      }
      $tocItems[] = ['id' => $id, 'text' => $text, 'tag' => $n->nodeName];
    }
    // build TOC HTML
    $tocHtml .= '<button class="btn btn-outline-primary d-lg-none w-100 mb-3"><i class="bx bx-list-ul me-2"></i> Show Contents</button>';
    $tocHtml .= '<aside class="card bg-light border-0" aria-label="Table of Contents">';
    $tocHtml .= '<div class="card-body">';
    $tocHtml .= '<h4 class="h5 fw-bold mb-3">Table of Contents</h4><ul class="list-unstyled mb-0">';
    foreach ($tocItems as $it) {
      $indent = $it['tag'] === 'h3' ? ' ps-3' : ($it['tag'] === 'h4' ? ' ps-4' : '');
      $tocHtml .= '<li class="mb-2' . $indent . '"><a href="#' . htmlspecialchars($it['id']) . '" class="text-decoration-none text-body">' . htmlspecialchars($it['text']) . '</a></li>';
    }
    $tocHtml .= '</ul></div></aside>';
    
    // Add TOC toggle script
    $tocHtml .= '
    <script>
    document.addEventListener("DOMContentLoaded", function() {
        const tocToggle = document.querySelector(".toc-toggle");
        const tocAside = document.querySelector(".post-toc");
        
        if (tocToggle && tocAside) {
            tocToggle.addEventListener("click", function() {
                tocAside.classList.toggle("active");
                if (tocAside.classList.contains("active")) {
                    tocToggle.innerHTML = \'<i class="bx bx-x"></i> Close\';
                } else {
                    tocToggle.innerHTML = \'<i class="bx bx-list-ul"></i> Contents\';
                }
            });
        }
    });
    </script>';
  }

  // extract inner HTML of wrapped div as rendered content
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

// Helper: if DOM processing did not produce rendered HTML, format plain text reliably
function format_plain_text_to_html($txt) {
  $txt = (string)$txt;
  if ($txt === '') return '';
  // Convert simple Markdown headings
  $txt = preg_replace(['/^###\s*(.+)$/m','/^##\s*(.+)$/m','/^#\s*(.+)$/m'], ['<h4>$1</h4>','<h3>$1</h3>','<h2>$1</h2>'], $txt);
  // Split by blank lines into blocks
  $blocks = preg_split('/\n\s*\n/', $txt);
  $out = '';
  foreach ($blocks as $b) {
    $b = trim($b);
    if ($b === '') continue;
    if (preg_match('/<[^>]+>/', $b)) {
      // includes HTML tag — trust it
      $out .= $b;
    } else {
      $out .= '<p>' . nl2br(htmlspecialchars($b)) . '</p>';
    }
  }
  return $out;
}

$pageTitle = $post['title'];
require_once __DIR__ . '/includes/header.php';
?>
<section class="py-5">
  <div class="container">
    <div class="row g-4">
      <div class="col-lg-8">
        <article class="card border-0 shadow-sm">
          <?php if (!empty($post['featured_image'])): ?>
            <?php
              $fi = $post['featured_image'];
              if (preg_match('#^https?://#i', $fi) || strpos($fi,'//')===0 || strpos($fi,'/')===0) {
                $imgSrc = $fi;
              } else {
                $imgSrc = '/HIGH-Q/' . ltrim($fi, '/');
              }
            ?>
            <img src="<?= htmlspecialchars($imgSrc) ?>" 
                 class="card-img-top" 
                 alt="<?= htmlspecialchars($post['title']) ?>" 
                 style="max-height: 400px; object-fit: cover;">
          <?php endif; ?>
          
          <div class="card-body p-4 p-md-5">
            <header class="text-center mb-5">
              <h1 class="display-4 fw-bold mb-3"><?= htmlspecialchars($post['title']) ?></h1>
              <div class="text-muted d-flex align-items-center justify-content-center gap-2">
                <i class='bx bx-calendar'></i>
                <time datetime="<?= $post['published_at'] ?? $post['created_at'] ?>">
                  <?= date('F j, Y', strtotime($post['published_at'] ?? $post['created_at'])) ?>
                </time>
              </div>
            </header>

            <div class="post-content">
              <?= $renderedContent ?: format_plain_text_to_html($post['content']) ?: nl2br(htmlspecialchars($post['content'])) ?>
            </div>

          </div>

          <div class="card-footer bg-white border-0 p-4">
            <div class="d-flex align-items-center gap-4">
              <button id="likeBtn" class="btn btn-outline-primary d-flex align-items-center gap-2">
                <i class='bx bx-heart'></i> <span class="btn-label">Like</span>
                <span class="badge bg-primary rounded-pill ms-1"><?= htmlspecialchars($post['likes'] ?? 0) ?></span>
              </button>
              <button id="commentToggle" class="btn btn-outline-primary d-flex align-items-center gap-2">
                <i class='bx bx-comment'></i> Comment
                <span class="badge bg-primary rounded-pill ms-1"><?= intval($comments_count ?? 0) ?></span>
              </button>
            </div>
          </div>
        </article>
      </div>

      <div class="col-lg-4">
        <div class="sticky-top pt-4">
          <?php if ($tocHtml !== ''): ?>
            <?= $tocHtml ?>
          <?php else: ?>
            <div class="card bg-light border-0">
              <div class="card-body">
                <h4 class="h5 fw-bold mb-3">Table of Contents</h4>
                <p class="text-muted mb-0">No sections found for this article.</p>
              </div>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
      <div class="post-actions" style="display:flex;gap:12px;align-items:center;margin-top:12px;">
        <button id="likeBtn" class="btn btn-like" aria-pressed="false"><i class="fa-regular fa-heart"></i> <span class="btn-label">Like</span></button>
        <div class="meta small muted"><i class="fa-regular fa-heart"></i> <span id="likesCount"><?= htmlspecialchars($post['likes'] ?? 0) ?></span></div>
        <button id="commentToggle" class="btn btn-comment"><i class="fa-regular fa-comment-dots"></i> Comment</button>
        <div class="meta small muted" style="margin-left:8px;"><i class="fa-regular fa-comment-dots"></i> <strong id="commentsCount"><?= intval($comments_count ?? 0) ?></strong></div>
      </div>
  </article>

    <div class="row mt-5">
      <div class="col-lg-8">
        <section id="commentsSection" class="card border-0 shadow-sm">
          <div class="card-body p-4 p-md-5">
            <h2 class="h3 fw-bold mb-4">Comments</h2>

            <div id="commentsList">
              <?php foreach($comments as $c): ?>
                <div class="comment card bg-light border-0 mb-4" data-id="<?= $c['id'] ?>">
                  <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                      <div class="d-flex align-items-center">
                        <div class="rounded-circle bg-primary bg-opacity-10 text-primary d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px;">
                          <?= strtoupper(substr($c['name'] ?: 'A', 0, 1)) ?>
                        </div>
                        <div>
                          <h6 class="mb-1 fw-bold"><?= htmlspecialchars($c['name'] ?: 'Anonymous') ?></h6>
                          <small class="text-muted"><?= date('F j, Y g:i A', strtotime($c['created_at'])) ?></small>
                        </div>
                      </div>
                      <button class="btn btn-sm btn-outline-primary reply-btn" data-id="<?= $c['id'] ?>">
                        <i class='bx bx-reply me-1'></i> Reply
                      </button>
                    </div>
                    <p class="mb-0"><?= nl2br(htmlspecialchars($c['content'])) ?></p>

          <?php
            // fetch replies for this comment
            $rstmt = $pdo->prepare('SELECT * FROM comments WHERE parent_id = ? AND status = "approved" ORDER BY created_at ASC');
            $rstmt->execute([$c['id']]);
            $replies = $rstmt->fetchAll(PDO::FETCH_ASSOC);
            foreach($replies as $rep) {
          ?>
                    <div class="border-start border-4 ms-4 ps-4 mt-4">
                      <div class="d-flex align-items-center mb-2">
                        <div class="rounded-circle bg-primary bg-opacity-10 text-primary d-flex align-items-center justify-content-center me-3" style="width: 32px; height: 32px; font-size: 0.875rem;">
                          <?= strtoupper(substr($rep['name'] ?: 'A', 0, 1)) ?>
                        </div>
                        <div>
                          <h6 class="mb-1 fw-bold">
                            <?= $rep['user_id'] ? '<span class="badge bg-primary me-2">Admin</span>' : '' ?>
                            <?= htmlspecialchars($rep['name'] ?: 'Anonymous') ?>
                          </h6>
                          <small class="text-muted"><?= date('F j, Y g:i A', strtotime($rep['created_at'])) ?></small>
                        </div>
                      </div>
                      <div class="ps-5">
                        <p class="mb-0"><?= nl2br(htmlspecialchars($rep['content'])) ?></p>
                      </div>
                    </div>
          <?php
            }
          ?>
          <?php endforeach; ?>

        </div>
      <?php endforeach; ?>
    </div>

                  </div>
                </div>
              <?php endforeach; ?>
            </div>

            <div class="card border-0 shadow-sm mt-5">
              <div class="card-body p-4">
                <h3 class="h4 fw-bold mb-4">Leave a comment</h3>
                <form id="commentForm">
                  <input type="hidden" name="post_id" value="<?= $post['id'] ?>">
                  <input type="hidden" name="parent_id" id="parent_id" value="">
                  
                  <div class="row g-3">
                    <div class="col-md-6">
                      <label class="form-label">Name</label>
                      <input class="form-control" type="text" name="name" placeholder="Your name">
                    </div>
                    <div class="col-md-6">
                      <label class="form-label">Email</label>
                      <input class="form-control" type="email" name="email" placeholder="your@email.com">
                    </div>
                    <div class="col-12">
                      <label class="form-label">Comment</label>
                      <textarea class="form-control" name="content" rows="5" required placeholder="Write your comment here..."></textarea>
                    </div>
                    <div class="col-12 text-end">
                      <button type="button" id="cancelReply" class="btn btn-light me-2" style="display:none;">Cancel Reply</button>
                      <button type="submit" class="btn btn-primary px-4">
                        <i class='bx bx-send me-2'></i> Submit Comment
                      </button>
                    </div>
                  </div>
                </form>
              </div>
            </div>
          </div>
        </section>
      </div>
    </div>
  </div>
</section>

<!-- comment and like handling moved to external script to reduce inline JS -->
<script>window.POST_ID = <?= (int)$postId ?>;</script>
<script src="./assets/js/post.js"></script>

<?php require_once __DIR__ . '/includes/footer.php';
