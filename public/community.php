<?php
require_once __DIR__ . '/config/db.php';
// handle new question post and replies
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (!empty($_POST['question_id'])) {
    // reply submission
    $qid = intval($_POST['question_id']);
    $rname = trim($_POST['rname'] ?? '');
    $rcontent = trim($_POST['rcontent'] ?? '');
    if ($rcontent !== '') {
      $rstmt = $pdo->prepare('INSERT INTO forum_replies (question_id, name, content, created_at) VALUES (?, ?, ?, NOW())');
      $rstmt->execute([$qid, $rname ?: 'Anonymous', $rcontent]);
    }
    header('Location: community.php'); exit;
  } else {
    $name = trim($_POST['name'] ?? '');
    $content = trim($_POST['content'] ?? '');
    if ($content !== '') {
      $stmt = $pdo->prepare('INSERT INTO forum_questions (name, content, created_at) VALUES (?, ?, NOW())');
      $stmt->execute([$name ?: 'Anonymous', $content]);
      header('Location: community.php'); exit;
    }
  }
}
$pageTitle = 'Community Q&A';
require_once __DIR__ . '/includes/header.php';
// fetch recent questions
$q = $pdo->query('SELECT id,name,content,created_at FROM forum_questions ORDER BY created_at DESC LIMIT 50')->fetchAll();
?>
<section style="padding:40px 0;">
  <div class="container">
    <h1>Community</h1>
    <p class="muted">Ask questions anonymously  no account needed.</p>
    <div class="row mt-4">
      <div class="col-lg-8">
        <form method="post" class="comment-form-wrap card p-3 mb-4">
          <div class="mb-3"><label class="form-label">Name (optional)</label><input class="form-control" type="text" name="name"></div>
          <div class="mb-3"><label class="form-label">Your question</label><textarea class="form-control" name="content" rows="5" required></textarea></div>
          <div class="form-actions"><button class="btn btn-primary" type="submit">Post Question</button></div>
        </form>

        <h3 class="mb-3">Recent questions</h3>
        <?php foreach($q as $qq): ?>
          <div class="forum-question card mb-3 p-3">
            <div class="d-flex align-items-start">
              <div class="avatar me-3"><?= strtoupper(substr($qq['name'],0,1)) ?></div>
              <div class="flex-fill">
                <div class="post-meta mb-1"><strong class="username"><?= htmlspecialchars($qq['name']) ?></strong> <span class="time text-muted ms-2"><?= $qq['created_at'] ?></span></div>
                <div class="post-body mb-2"><?= nl2br(htmlspecialchars($qq['content'])) ?></div>
                <div class="post-actions">
                  <button class="btn btn-sm btn-outline-secondary reply-toggle" data-id="<?= $qq['id'] ?>">4ac Reply</button>
                </div>

                <div class="post-replies mt-3" id="replies-<?= $qq['id'] ?>">
                  <?php
                    $rstmt = $pdo->prepare('SELECT id,name,content,created_at FROM forum_replies WHERE question_id = ? ORDER BY created_at ASC');
                    $rstmt->execute([$qq['id']]);
                    $reps = $rstmt->fetchAll();
                    foreach ($reps as $rep):
                  ?>
                    <div class="forum-reply border-top pt-2 mt-2">
                      <div class="post-meta"><strong class="username"><?= htmlspecialchars($rep['name']) ?></strong> <span class="time text-muted ms-2"><?= $rep['created_at'] ?></span></div>
                      <div class="post-body mt-1"><?= nl2br(htmlspecialchars($rep['content'])) ?></div>
                    </div>
                  <?php endforeach; ?>
                </div>

                <form method="post" class="forum-reply-form mt-3" data-qid="<?= $qq['id'] ?>" style="display:none;">
                  <input type="hidden" name="question_id" value="<?= $qq['id'] ?>">
                  <div class="mb-2"><input class="form-control" type="text" name="rname" placeholder="Name (optional)"></div>
                  <div class="mb-2"><textarea class="form-control" name="rcontent" rows="3" placeholder="Write your reply..." required></textarea></div>
                  <div class="form-actions"><button class="btn btn-primary" type="submit">Post Reply</button></div>
                </form>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</section>
<?php require_once __DIR__ . '/includes/footer.php';
?>
<script>
  // toggle reply form under questions
  (function(){
    document.addEventListener('click', function(e){
      var t = e.target.closest && e.target.closest('.reply-toggle');
      if (!t) return;
      var id = t.getAttribute('data-id');
      var form = document.querySelector('.forum-reply-form[data-qid="'+id+'"]');
      if (!form) return;
      if (form.style.display === 'none' || form.style.display === '') {
        form.style.display = 'block';
        form.scrollIntoView({behavior:'smooth', block:'center'});
      } else {
        form.style.display = 'none';
      }
    });
  })();
</script>
