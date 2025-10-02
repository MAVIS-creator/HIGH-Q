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
          <div class="form-actions"><button class="btn-approve" type="submit">Post Question</button></div>
        </form>

        <h3 style="margin-top:28px;">Recent questions</h3>
        <?php foreach($q as $qq): ?>
          <div class="forum-question">
            <!-- Question -->
            <div class="post-header">
              <div class="avatar"><?= strtoupper(substr($qq['name'],0,1)) ?></div>
              <div class="post-meta">
                <strong class="username"><?= htmlspecialchars($qq['name']) ?></strong>
                <span class="time"><?= $qq['created_at'] ?></span>
              </div>
            </div>
            <div class="post-body">
              <?= nl2br(htmlspecialchars($qq['content'])) ?>
            </div>
            <div class="post-actions">
              <button class="reply-toggle" data-id="<?= $qq['id'] ?>">💬 Reply</button>
            </div>

            <!-- Replies -->
            <div class="post-replies" id="replies-<?= $qq['id'] ?>">
              <?php
                $rstmt = $pdo->prepare('SELECT id,name,content,created_at FROM forum_replies WHERE question_id = ? ORDER BY created_at ASC');
                $rstmt->execute([$qq['id']]);
                $reps = $rstmt->fetchAll();
                foreach ($reps as $rep):
              ?>
                <div class="forum-reply">
                  <div class="post-header">
                    <div class="avatar"><?= strtoupper(substr($rep['name'],0,1)) ?></div>
                    <div class="post-meta">
                      <strong class="username"><?= htmlspecialchars($rep['name']) ?></strong>
                      <span class="time"><?= $rep['created_at'] ?></span>
                    </div>
                  </div>
                  <div class="post-body"><?= nl2br(htmlspecialchars($rep['content'])) ?></div>
                </div>
              <?php endforeach; ?>
            </div>

            <!-- Reply form -->
            <form method="post" class="forum-reply-form" data-qid="<?= $qq['id'] ?>" style="display:none;">
              <input type="hidden" name="question_id" value="<?= $qq['id'] ?>">
              <div class="form-row">
                <input class="form-input" type="text" name="rname" placeholder="Name (optional)">
              </div>
              <div class="form-row">
                <textarea class="form-textarea" name="rcontent" rows="3" placeholder="Write your reply..." required></textarea>
              </div>
              <div class="form-actions">
                <button class="btn-approve" type="submit">Post Reply</button>
              </div>
            </form>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</section>
<?php require_once __DIR__ . '/includes/footer.php';
