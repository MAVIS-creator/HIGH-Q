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
    $tocHtml .= '<aside class="post-toc" aria-label="Table of Contents">';
    $tocHtml .= '<h4>Table of Contents</h4><ul>';
    foreach ($tocItems as $it) {
      $indent = $it['tag'] === 'h3' ? ' style="margin-left:8px;"' : ($it['tag'] === 'h4' ? ' style="margin-left:14px;"' : '');
      $tocHtml .= '<li' . $indent . '><a href="#' . htmlspecialchars($it['id']) . '">' . htmlspecialchars($it['text']) . '</a></li>';
    }
    $tocHtml .= '</ul></aside>';
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
<div class="container" style="max-width:1150px;margin:24px auto;padding:0 12px;">
  <article class="post-article">
    <h1><?= htmlspecialchars($post['title']) ?></h1>
    <div class="meta muted">Published: <?= htmlspecialchars($post['published_at'] ?? $post['created_at']) ?></div>

    <div class="post-top" style="display:flex;gap:20px;align-items:flex-start;margin-top:12px;">
      <div class="post-main" style="flex:1;">
        <div class="post-content">
      <?php if (!empty($post['featured_image'])): ?>
        <?php
          $fi = $post['featured_image'];
          if (preg_match('#^https?://#i', $fi) || strpos($fi,'//')===0 || strpos($fi,'/')===0) {
            $imgSrc = $fi;
          } else {
            $imgSrc = '/HIGH-Q/' . ltrim($fi, '/');
          }
        ?>
        <div class="post-thumb" style="margin-bottom:12px;">
          <img src="<?= htmlspecialchars($imgSrc) ?>" alt="<?= htmlspecialchars($post['title']) ?>" style="width:100%;height:auto;display:block;border-radius:6px;object-fit:cover">
        </div>
      <?php endif; ?>

  <?= $renderedContent ?: format_plain_text_to_html($post['content']) ?: nl2br(htmlspecialchars($post['content'])) ?>
        </div>
      </div>

      <aside class="post-toc" style="width:260px;">
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
      <div class="post-actions" style="display:flex;gap:12px;align-items:center;margin-top:12px;">
        <button id="likeBtn" class="btn btn-like" aria-pressed="false"><i class="fa-regular fa-heart"></i> <span class="btn-label">Like</span></button>
        <div class="meta small muted"><i class="fa-regular fa-heart"></i> <span id="likesCount"><?= htmlspecialchars($post['likes'] ?? 0) ?></span></div>
        <button id="commentToggle" class="btn btn-comment"><i class="fa-regular fa-comment-dots"></i> Comment</button>
        <div class="meta small muted" style="margin-left:8px;"><i class="fa-regular fa-comment-dots"></i> <strong id="commentsCount"><?= intval($comments_count ?? 0) ?></strong></div>
      </div>
  </article>

  <section id="commentsSection" style="margin-top:28px;">
    <h2>Comments</h2>

    <div id="commentsList">
      <?php foreach($comments as $c): ?>
        <div class="comment" data-id="<?= $c['id'] ?>" style="border-bottom:1px solid #eee;padding:12px 0;">
          <div class="comment-header">
            <div><strong><?= htmlspecialchars($c['name'] ?: 'Anonymous') ?></strong> <span class="muted">at <?= htmlspecialchars($c['created_at']) ?></span></div>
            <div>
              <button class="reply-btn small" data-id="<?= $c['id'] ?>">Reply</button>
            </div>
          </div>
          <div class="comment-body"><?= nl2br(htmlspecialchars($c['content'])) ?></div>

          <?php
            // fetch replies for this comment
            $rstmt = $pdo->prepare('SELECT * FROM comments WHERE parent_id = ? AND status = "approved" ORDER BY created_at ASC');
            $rstmt->execute([$c['id']]);
            $replies = $rstmt->fetchAll(PDO::FETCH_ASSOC);
            foreach($replies as $rep):
          ?>
            <div class="comment reply">
              <div><strong><?= $rep['user_id'] ? 'Admin - ' . htmlspecialchars($rep['name']) : htmlspecialchars($rep['name'] ?: 'Anonymous') ?></strong> <span class="muted">at <?= htmlspecialchars($rep['created_at']) ?></span></div>
              <div class="comment-body" style="margin-top:6px;"><?= nl2br(htmlspecialchars($rep['content'])) ?></div>
            </div>
          <?php endforeach; ?>

        </div>
      <?php endforeach; ?>
    </div>

    <div style="margin-top:18px;">
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

<!-- comment and like handling moved to external script to reduce inline JS -->
<script>window.POST_ID = <?= (int)$postId ?>;</script>
<script src="./assets/js/post.js"></script>

<?php require_once __DIR__ . '/includes/footer.php';
