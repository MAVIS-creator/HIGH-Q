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
      <?= $post['content'] ?>
    </div>
  </article>

  <section id="commentsSection" style="margin-top:28px;">
    <h2>Comments</h2>

    <div id="commentsList">
      <?php foreach($comments as $c): ?>
        <div class="comment" data-id="<?= $c['id'] ?>" style="border-bottom:1px solid #eee;padding:12px 0;">
          <div style="display:flex;justify-content:space-between;align-items:center;">
            <div><strong><?= htmlspecialchars($c['name'] ?: 'Anonymous') ?></strong> <span class="muted">at <?= htmlspecialchars($c['created_at']) ?></span></div>
            <div>
              <button class="btn-reply small" data-id="<?= $c['id'] ?>">Reply</button>
            </div>
          </div>
          <div style="margin-top:8px;"><?= nl2br(htmlspecialchars($c['content'])) ?></div>

          <?php
            // fetch replies for this comment
            $rstmt = $pdo->prepare('SELECT * FROM comments WHERE parent_id = ? AND status = "approved" ORDER BY created_at ASC');
            $rstmt->execute([$c['id']]);
            $replies = $rstmt->fetchAll(PDO::FETCH_ASSOC);
            foreach($replies as $rep):
          ?>
            <div class="comment reply" style="margin-left:22px;margin-top:10px;padding:8px;background:#fbfbfb;border-radius:6px;">
              <div><strong><?= $rep['user_id'] ? 'Admin - ' . htmlspecialchars($rep['name']) : htmlspecialchars($rep['name'] ?: 'Anonymous') ?></strong> <span class="muted">at <?= htmlspecialchars($rep['created_at']) ?></span></div>
              <div style="margin-top:6px;"><?= nl2br(htmlspecialchars($rep['content'])) ?></div>
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

<script>
// handle reply button: set parent_id and scroll to form
document.querySelectorAll('.btn-reply').forEach(b=>b.addEventListener('click',function(){
  var id = this.dataset.id; document.getElementById('parent_id').value = id; window.scrollTo({top: document.getElementById('commentForm').offsetTop - 80, behavior: 'smooth'});
}));

// submit comment form via fetch to public/api/comments.php
document.getElementById('commentForm').addEventListener('submit', function(e){
  e.preventDefault();
  var fd = new FormData(this);
  fetch('/HIGH-Q/public/api/comments.php',{method:'POST',body:fd}).then(r=>r.json()).then(j=>{
    if (j.status === 'ok') { alert(j.message||'Submitted'); location.reload(); } else { alert(j.message||'Error'); }
  }).catch(()=>alert('Network error'));
});
</script>

<?php require_once __DIR__ . '/includes/footer.php';
