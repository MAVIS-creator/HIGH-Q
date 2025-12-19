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
    $tocHtml .= '<button class="toc-toggle d-md-none"><i class="bx bx-list-ul"></i> Contents</button>';
    $tocHtml .= '<aside class="post-toc" aria-label="Table of Contents">';
    $tocHtml .= '<h4>Table of Contents</h4><ul>';
    foreach ($tocItems as $it) {
      $indent = $it['tag'] === 'h3' ? ' style="margin-left:8px;"' : ($it['tag'] === 'h4' ? ' style="margin-left:14px;"' : '');
      $tocHtml .= '<li' . $indent . '><a href="#' . htmlspecialchars($it['id']) . '">' . htmlspecialchars($it['text']) . '</a></li>';
    }
    $tocHtml .= '</ul></aside>';
    
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

function hq_comment_initial($name) {
  $n = trim($name ?: 'G');
  $initial = strtoupper(substr($n, 0, 1));
  return htmlspecialchars($initial);
}

$pageTitle = $post['title'];
require_once __DIR__ . '/includes/header.php';
?>
<style>
  .post-shell {
    max-width: 1180px;
    margin: 26px auto;
    padding: 0 16px 28px;
  }

  .post-article-card {
    background: #ffffff;
    border-radius: 16px;
    border: 1px solid #e5e9f2;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
    padding: 26px 22px;
  }

  .post-header h1 {
    margin: 0 0 8px;
    font-size: clamp(1.9rem, 2.7vw, 2.6rem);
    font-weight: 800;
    color: var(--hq-black);
    line-height: 1.25;
  }

  .post-eyebrow {
    text-transform: uppercase;
    letter-spacing: 0.6px;
    font-size: 0.78rem;
    color: var(--hq-blue-white);
    font-weight: 700;
    margin: 0 0 4px;
  }

  .meta-row {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
    align-items: center;
  }

  .meta-chip {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 10px;
    background: #f4f6fb;
    border-radius: 999px;
    color: var(--hq-gray);
    font-size: 0.9rem;
    border: 1px solid #e6eaf3;
  }

  .post-layout {
    display: grid;
    grid-template-columns: minmax(0, 1fr) 280px;
    gap: 22px;
    align-items: start;
    margin-top: 18px;
  }

  .post-main-card {
    background: linear-gradient(180deg, #ffffff 0%, #fbfcff 100%);
    border: 1px solid #e7ecf5;
    border-radius: 14px;
    padding: 18px 18px 22px;
    box-shadow: 0 8px 22px rgba(0, 0, 0, 0.05);
  }

  .post-thumb {
    margin-bottom: 14px;
    border-radius: 10px;
    overflow: hidden;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.12);
  }

  .post-thumb img {
    width: 100%;
    height: auto;
    display: block;
    object-fit: cover;
  }

  .post-content-body {
    color: var(--hq-gray);
    line-height: 1.8;
    font-size: 1rem;
  }

  .post-content-body h2,
  .post-content-body h3,
  .post-content-body h4 {
    color: var(--hq-black);
    margin-top: 22px;
    margin-bottom: 10px;
  }

  .post-content-body p {
    margin: 0 0 14px;
  }

  .post-aside {
    position: relative;
  }

  .post-toc {
    position: sticky;
    top: 18px;
    background: #ffffff;
    border-radius: 12px;
    border: 1px solid #e7ecf5;
    padding: 16px 16px 12px;
    box-shadow: 0 8px 22px rgba(0, 0, 0, 0.05);
  }

  .post-toc h4 {
    margin: 0 0 10px;
    font-size: 1rem;
    font-weight: 700;
    color: var(--hq-black);
  }

  .post-toc ul {
    list-style: none;
    padding: 0;
    margin: 0;
    display: grid;
    gap: 8px;
  }

  .post-toc a {
    color: var(--hq-blue-white);
    text-decoration: none;
    font-weight: 600;
    font-size: 0.95rem;
  }

  .post-toc a:hover {
    color: var(--hq-yellow);
  }

  .toc-toggle {
    display: none;
    align-items: center;
    gap: 6px;
    padding: 8px 12px;
    border-radius: 10px;
    border: 1px solid #e7ecf5;
    background: #f4f6fb;
    color: var(--hq-black);
    cursor: pointer;
    margin-bottom: 10px;
    font-weight: 600;
  }

  .post-actions {
    display: flex;
    gap: 10px;
    align-items: center;
    margin-top: 14px;
  }

  .btn.btn-like,
  .btn.btn-comment {
    border: 1px solid #e6eaf3;
    background: #f8fafc;
    color: var(--hq-black);
    border-radius: 12px;
    padding: 10px 14px;
    font-weight: 700;
    box-shadow: 0 6px 14px rgba(0, 0, 0, 0.06);
    transition: transform 0.1s ease, box-shadow 0.15s ease;
  }

  .btn.btn-like:hover,
  .btn.btn-comment:hover {
    transform: translateY(-1px);
    box-shadow: 0 10px 18px rgba(0, 0, 0, 0.08);
  }

  .comments-shell {
    margin-top: 30px;
    background: #ffffff;
    border: 1px solid #e5e9f2;
    border-radius: 14px;
    box-shadow: 0 10px 26px rgba(0, 0, 0, 0.07);
    padding: 22px 20px 24px;
  }

  .comments-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 12px;
    flex-wrap: wrap;
    margin-bottom: 14px;
  }

  .comments-header h2 {
    margin: 2px 0 0;
  }

  .comment-stack {
    display: grid;
    gap: 12px;
  }

  .comment-card {
    border: 1px solid #e8edf5;
    border-radius: 12px;
    padding: 14px 14px 12px;
    background: linear-gradient(180deg, #ffffff 0%, #f9fbff 100%);
    box-shadow: 0 8px 18px rgba(0, 0, 0, 0.05);
  }

  .comment-head {
    display: flex;
    align-items: center;
    gap: 12px;
    flex-wrap: wrap;
  }

  .comment-avatar {
    width: 42px;
    height: 42px;
    border-radius: 12px;
    background: #eef1fb;
    color: var(--hq-blue-white);
    display: grid;
    place-items: center;
    font-weight: 800;
    font-size: 1.05rem;
  }

  .comment-meta {
    display: grid;
    gap: 2px;
  }

  .comment-author {
    font-weight: 700;
    color: var(--hq-black);
  }

  .comment-date {
    color: var(--hq-gray);
    font-size: 0.9rem;
  }

  .comment-body {
    margin-top: 10px;
    color: var(--hq-gray);
    line-height: 1.6;
  }

  .comment-replies {
    margin-top: 12px;
    padding-left: 16px;
    border-left: 2px solid #e8edf5;
    display: grid;
    gap: 10px;
  }

  .comment-reply {
    background: #f7f9fc;
    border: 1px solid #e6eaf3;
    border-radius: 10px;
    padding: 10px 12px;
  }

  .chip,
  .reply-btn.small {
    border: 1px solid #e6eaf3;
    background: #f4f6fb;
    color: var(--hq-black);
    border-radius: 999px;
    padding: 6px 12px;
    font-weight: 700;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 6px;
  }

  .chip:hover,
  .reply-btn.small:hover {
    border-color: var(--hq-blue-white);
  }

  .comment-form-card {
    margin-top: 18px;
    border: 1px solid #e6eaf3;
    border-radius: 12px;
    padding: 16px 14px 12px;
    background: #fafbff;
  }

  .comment-form-card h3 {
    margin-top: 0;
  }

  .comment-form-card .form-row {
    margin-bottom: 10px;
  }

  .comment-form-card .form-input,
  .comment-form-card .form-textarea {
    width: 100%;
    border-radius: 10px;
    border: 1px solid #e6eaf3;
    padding: 10px 12px;
  }

  .comment-form-card .form-actions {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
  }

  .btn-approve {
    background: var(--hq-blue-white);
    color: #ffffff;
    border: 1px solid var(--hq-blue-white);
    padding: 10px 14px;
    border-radius: 10px;
    font-weight: 700;
  }

  .btn-approve:hover {
    opacity: 0.92;
  }

  .btn-ghost {
    background: transparent;
    border: 1px dashed #cfd6e4;
    border-radius: 10px;
    padding: 9px 12px;
    font-weight: 700;
    color: var(--hq-gray);
  }

  @media (max-width: 992px) {
    .post-layout {
      grid-template-columns: 1fr;
    }

    .post-aside {
      order: -1;
    }

    .post-toc {
      position: static;
    }

    .toc-toggle {
      display: inline-flex;
    }
  }

  @media (max-width: 640px) {
    .post-article-card,
    .comments-shell {
      padding: 18px 14px;
    }

    .post-actions {
      flex-wrap: wrap;
    }

    .comment-head {
      align-items: flex-start;
    }
  }
</style>

<div class="post-shell">
  <article class="post-article-card">
    <header class="post-header">
      <p class="post-eyebrow">Insight</p>
      <h1><?= htmlspecialchars($post['title']) ?></h1>
      <div class="meta-row">
        <span class="meta-chip"><i class="fa-regular fa-calendar"></i> Published: <?= htmlspecialchars($post['published_at'] ?? $post['created_at']) ?></span>
        <span class="meta-chip"><i class="fa-regular fa-comment-dots"></i> <?= intval($comments_count ?? 0) ?> comments</span>
      </div>
    </header>

    <div class="post-layout">
      <div class="post-main-card">
        <?php if (!empty($post['featured_image'])): ?>
          <?php
            $fi = $post['featured_image'];
            if (preg_match('#^https?://#i', $fi) || strpos($fi,'//')===0 || strpos($fi,'/')===0) {
              $imgSrc = $fi;
            } else {
              // Use a document-relative path so assets resolve correctly when site is in a subfolder
              $imgSrc = './' . ltrim($fi, '/');
            }
          ?>
          <div class="post-thumb">
            <img src="<?= htmlspecialchars($imgSrc) ?>" alt="<?= htmlspecialchars($post['title']) ?>">
          </div>
        <?php endif; ?>

        <div class="post-content-body">
          <?= $renderedContent ?: format_plain_text_to_html($post['content']) ?: nl2br(htmlspecialchars($post['content'])) ?>
        </div>
      </div>

      <aside class="post-aside">
        <?php if ($tocHtml !== ''): ?>
          <?= $tocHtml ?>
        <?php else: ?>
          <div class="post-toc">
            <h4>Table of Contents</h4>
            <p class="muted">No sections found for this article.</p>
          </div>
        <?php endif; ?>
      </aside>
    </div>

    <div class="post-actions">
      <button id="likeBtn" class="btn btn-like" aria-pressed="false"><i class="fa-regular fa-heart"></i> <span class="btn-label">Like</span></button>
      <div class="meta small muted"><i class="fa-regular fa-heart"></i> <span id="likesCount"><?= htmlspecialchars($post['likes'] ?? 0) ?></span></div>
      <button id="commentToggle" class="btn btn-comment"><i class="fa-regular fa-comment-dots"></i> Comment</button>
      <div class="meta small muted"><i class="fa-regular fa-comment-dots"></i> <strong id="commentsCount"><?= intval($comments_count ?? 0) ?></strong></div>
    </div>
  </article>

  <section id="commentsSection" class="comments-shell">
    <div class="comments-header">
      <div>
        <p class="post-eyebrow" style="margin:0;">Join the discussion</p>
        <h2 style="margin:0;">Comments</h2>
      </div>
      <span class="meta-chip"><i class="fa-regular fa-comment-dots"></i> <strong id="commentsCountClone"><?= intval($comments_count ?? 0) ?></strong></span>
    </div>

    <div id="commentsList" class="comment-stack">
      <?php foreach($comments as $c): ?>
        <div class="comment-card" data-id="<?= $c['id'] ?>">
          <div class="comment-head">
            <div class="comment-avatar"><?= hq_comment_initial($c['name'] ?: 'Anonymous') ?></div>
            <div class="comment-meta">
              <div class="comment-author"><?= htmlspecialchars($c['name'] ?: 'Anonymous') ?></div>
              <div class="comment-date"><?= htmlspecialchars($c['created_at']) ?></div>
            </div>
            <button class="chip reply-btn small" data-id="<?= $c['id'] ?>"><i class="fa-regular fa-reply"></i> Reply</button>
          </div>
          <div class="comment-body"><?= nl2br(htmlspecialchars($c['content'])) ?></div>

          <?php
            $rstmt = $pdo->prepare('SELECT * FROM comments WHERE parent_id = ? AND status = "approved" ORDER BY created_at ASC');
            $rstmt->execute([$c['id']]);
            $replies = $rstmt->fetchAll(PDO::FETCH_ASSOC);
            if ($replies):
          ?>
            <div class="comment-replies">
              <?php foreach($replies as $rep): ?>
                <div class="comment-reply">
                  <div class="comment-author"><?= $rep['user_id'] ? 'Admin - ' . htmlspecialchars($rep['name']) : htmlspecialchars($rep['name'] ?: 'Anonymous') ?></div>
                  <div class="comment-date" style="font-size:0.88rem;"><?= htmlspecialchars($rep['created_at']) ?></div>
                  <div class="comment-body" style="margin-top:6px;"><?= nl2br(htmlspecialchars($rep['content'])) ?></div>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>

        </div>
      <?php endforeach; ?>
    </div>

    <div class="comment-form-card">
      <h3>Leave a comment</h3>
      <form id="commentForm" class="comment-form-wrap">
        <input type="hidden" name="post_id" value="<?= $post['id'] ?>">
        <input type="hidden" name="parent_id" id="parent_id" value="">
        <div class="form-row"><label class="form-label">Name</label><input class="form-input" type="text" name="name"></div>
        <div class="form-row"><label class="form-label">Email</label><input class="form-input" type="email" name="email"></div>
        <div class="form-row"><label class="form-label">Comment</label><textarea class="form-textarea" name="content" rows="5" required></textarea></div>
        <div class="form-actions"><button type="submit" class="btn-approve">Submit Comment</button> <button type="button" id="cancelReply" style="display:none;margin-left:8px;" class="btn-ghost">Cancel Reply</button></div>
      </form>
    </div>
  </section>
</div>

<script>window.POST_ID = <?= (int)$postId ?>;</script>
<script src="<?= app_url('assets/js/post.js') ?>"></script>

<?php require_once __DIR__ . '/includes/footer.php';
