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
    <p class="muted">Ask questions anonymously — no account needed.</p>
    <div style="margin-top:20px;">
      <div style="max-width:720px;">
        <form method="post" class="comment-form-wrap">
          <div class="form-row"><label class="form-label">Name (optional)</label><input class="form-input" type="text" name="name"></div>
          <div class="form-row"><label class="form-label">Your question</label><textarea class="form-textarea" name="content" rows="5" required></textarea></div>
          <div class="form-actions">
                <button class="btn btn-primary px-4" type="submit">Post Question</button>
              </div>
            </form>
          </div>
        </div>

        <h3 class="h4 fw-bold mb-4">Recent Questions</h3>
        <?php foreach($q as $qq): ?>
          <div class="card border-0 shadow-sm mb-4">
            <div class="card-body p-4">
              <div class="d-flex align-items-center mb-3">
                <div class="rounded-circle bg-warning bg-opacity-10 text-warning d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px;">
                  <?= strtoupper(substr($qq['name'],0,1)) ?>
                </div>
                <div>
                  <h6 class="mb-1 fw-bold"><?= htmlspecialchars($qq['name']) ?></h6>
                  <small class="text-muted"><?= date('F j, Y g:i A', strtotime($qq['created_at'])) ?></small>
                </div>
              </div>
            </div>
            <div class="mb-3 px-4">
              <p class="mb-3"><?= nl2br(htmlspecialchars($qq['content'])) ?></p>
              <button class="btn btn-outline-primary btn-sm reply-toggle" data-id="<?= $qq['id'] ?>">
                <i class='bx bx-message-square-dots me-1'></i> Reply
              </button>
            </div>

            <!-- Replies -->
            <div class="border-start border-4 mx-4 ps-4" id="replies-<?= $qq['id'] ?>">
              <?php
                $rstmt = $pdo->prepare('SELECT id,name,content,created_at FROM forum_replies WHERE question_id = ? ORDER BY created_at ASC');
                $rstmt->execute([$qq['id']]);
                $reps = $rstmt->fetchAll();
                foreach ($reps as $rep):
              ?>
                <div class="mb-4">
                  <div class="d-flex align-items-center mb-2">
                    <div class="rounded-circle bg-primary bg-opacity-10 text-primary d-flex align-items-center justify-content-center me-3" style="width: 32px; height: 32px; font-size: 0.875rem;">
                      <?= strtoupper(substr($rep['name'],0,1)) ?>
                    </div>
                    <div>
                      <h6 class="mb-1 fw-bold"><?= htmlspecialchars($rep['name']) ?></h6>
                      <small class="text-muted"><?= date('F j, Y g:i A', strtotime($rep['created_at'])) ?></small>
                    </div>
                  </div>
                  <div class="ps-5">
                    <p class="mb-0"><?= nl2br(htmlspecialchars($rep['content'])) ?></p>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>

            <!-- Reply form -->
            <form method="post" class="forum-reply-form px-4 pb-4" data-qid="<?= $qq['id'] ?>" style="display:none;">
              <input type="hidden" name="question_id" value="<?= $qq['id'] ?>">
              <div class="mb-3">
                <input class="form-control" type="text" name="rname" placeholder="Name (optional)">
              </div>
              <div class="mb-3">
                <textarea class="form-control" name="rcontent" rows="3" placeholder="Write your reply..." required></textarea>
              </div>
              <div class="text-end">
                <button class="btn btn-primary btn-sm px-3" type="submit">
                  <i class='bx bx-send me-1'></i> Post Reply
                </button>
              </div>
            </form>
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
