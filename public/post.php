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

$pageTitle = $post['title'];
require_once __DIR__ . '/includes/header.php';
?>
<div class="container" style="max-width:900px;margin:24px auto;padding:0 12px;">
  <article class="post-article">
    <h1><?= htmlspecialchars($post['title']) ?></h1>
    <div class="meta muted">Published: <?= htmlspecialchars($post['published_at'] ?? $post['created_at']) ?></div>
    <div class="post-content" style="margin-top:12px;">
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

      <?= nl2br(htmlspecialchars($post['content'])) ?>
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
              <button class="btn-reply small" data-id="<?= $c['id'] ?>">Reply</button>
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
      <form id="commentForm">
        <input type="hidden" name="post_id" value="<?= $post['id'] ?>">
        <input type="hidden" name="parent_id" id="parent_id" value="">
        <div class="form-row"><label>Name</label><input type="text" name="name"></div>
        <div class="form-row"><label>Email</label><input type="email" name="email"></div>
        <div class="form-row"><label>Comment</label><textarea name="content" rows="5" required></textarea></div>
        <div class="form-actions"><button type="submit" class="btn-approve">Submit Comment</button></div>
      </form>
    </div>
  </section>
</div>

<!-- comment and like handling moved to external script to reduce inline JS -->
<script>window.POST_ID = <?= (int)$postId ?>;</script>
<script src="./assets/js/post.js"></script>

<?php require_once __DIR__ . '/includes/footer.php';
