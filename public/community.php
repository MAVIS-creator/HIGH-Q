<?php
require_once __DIR__ . '/config/db.php';
// handle new question post
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $name = trim($_POST['name'] ?? '');
  $content = trim($_POST['content'] ?? '');
  if ($content !== '') {
    $stmt = $pdo->prepare('INSERT INTO forum_questions (name, content, created_at) VALUES (?, ?, NOW())');
    $stmt->execute([$name ?: 'Anonymous', $content]);
    header('Location: community.php'); exit;
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
    <div style="display:flex;gap:24px;align-items:flex-start;margin-top:20px;">
      <div style="flex:1;">
        <form method="post">
          <div class="form-row"><label>Name (optional)</label><input type="text" name="name"></div>
          <div class="form-row"><label>Your question</label><textarea name="content" rows="5" required></textarea></div>
          <div class="form-actions"><button class="btn-approve" type="submit">Post Question</button></div>
        </form>
      </div>
      <div style="flex:1;">
        <h3>Recent questions</h3>
        <?php foreach($q as $qq): ?>
          <div style="border:1px solid #eee;padding:12px;border-radius:8px;margin-bottom:10px;">
            <div style="font-weight:700"><?= htmlspecialchars($qq['name']) ?> <small class="muted" style="font-weight:400">at <?= $qq['created_at'] ?></small></div>
            <div style="margin-top:6px"><?= nl2br(htmlspecialchars($qq['content'])) ?></div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</section>
<?php require_once __DIR__ . '/includes/footer.php';
