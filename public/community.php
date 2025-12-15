<?php
require_once __DIR__ . '/config/db.php';

// Topics catalogue (soft-config)
$TOPICS = ['Admissions','Exams','Payments','Courses','Tutorials','General'];

// handle new posts and replies
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (!empty($_POST['question_id'])) {
    $qid = (int)($_POST['question_id'] ?? 0);
    $rname = trim($_POST['rname'] ?? '');
    $rcontent = trim($_POST['rcontent'] ?? '');
    if ($qid > 0 && $rcontent !== '') {
      $rstmt = $pdo->prepare('INSERT INTO forum_replies (question_id, name, content, created_at) VALUES (?, ?, ?, NOW())');
      $rstmt->execute([$qid, $rname ?: 'Anonymous', $rcontent]);
    }
    header('Location: community.php'); exit;
  } else {
    $name = trim($_POST['name'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $topic = trim($_POST['topic'] ?? '');
    if ($content !== '') {
      // Insert with topic when column exists
      try {
        $pdo->query("SELECT topic FROM forum_questions LIMIT 1");
        $stmt = $pdo->prepare('INSERT INTO forum_questions (name, topic, content, created_at) VALUES (?, ?, ?, NOW())');
        $stmt->execute([$name ?: 'Anonymous', ($topic !== '' ? $topic : null), $content]);
      } catch (Throwable $e) {
        // Fallback if topic column missing
        $stmt = $pdo->prepare('INSERT INTO forum_questions (name, content, created_at) VALUES (?, ?, NOW())');
        $stmt->execute([$name ?: 'Anonymous', $content]);
      }
      header('Location: community.php'); exit;
    }
  }
}

$pageTitle = 'Community Q&A';
require_once __DIR__ . '/includes/header.php';

// Filters
$qterm = trim($_GET['q'] ?? '');
$ftopic = trim($_GET['topic'] ?? '');
$sort = in_array(($_GET['sort'] ?? 'newest'), ['newest','active'], true) ? $_GET['sort'] : 'newest';

// Build query for questions
$sql = 'SELECT q.id,q.name,q.topic,q.content,q.created_at,
        COALESCE(MAX(r.created_at), q.created_at) AS last_activity
        FROM forum_questions q
        LEFT JOIN forum_replies r ON r.question_id = q.id';
$where = [];
$params = [];
if ($qterm !== '') { $where[] = ' (q.content LIKE ? OR q.name LIKE ?) '; $params[] = "%$qterm%"; $params[] = "%$qterm%"; }
if ($ftopic !== '') { $where[] = ' q.topic = ? '; $params[] = $ftopic; }
if (!empty($where)) { $sql .= ' WHERE ' . implode(' AND ', $where); }
$sql .= ' GROUP BY q.id ';
$sql .= ($sort === 'active') ? ' ORDER BY last_activity DESC ' : ' ORDER BY q.created_at DESC ';
$sql .= ' LIMIT 100';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$questions = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Recent topics
$recentTopics = [];
try {
  $rt = $pdo->query('SELECT topic, MAX(created_at) AS last_time, COUNT(*) AS cnt FROM forum_questions WHERE topic IS NOT NULL AND topic <> "" GROUP BY topic ORDER BY last_time DESC LIMIT 8');
  $recentTopics = $rt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) { $recentTopics = []; }
?>
<section style="padding:32px 0;">
  <div class="container" style="display:grid;grid-template-columns: 1fr 320px;gap:20px;align-items:start;">
    <main>
      <h1 style="margin:0 0 8px 0;">Community</h1>
      <p class="muted">Ask questions anonymously — no account needed.</p>

      <!-- Filters -->
      <form method="get" style="display:flex;gap:10px;flex-wrap:wrap;margin:14px 0 18px 0;">
        <input class="form-input" style="flex:2;min-width:200px" type="search" name="q" placeholder="Search questions..." value="<?= htmlspecialchars($qterm) ?>">
        <select class="form-input" style="flex:1;min-width:160px" name="topic">
          <option value="">All topics</option>
          <?php foreach ($TOPICS as $t): ?>
            <option value="<?= htmlspecialchars($t) ?>" <?= $ftopic === $t ? 'selected' : '' ?>><?= htmlspecialchars($t) ?></option>
          <?php endforeach; ?>
        </select>
        <select class="form-input" style="flex:0.8;min-width:140px" name="sort">
          <option value="newest" <?= $sort==='newest'?'selected':'' ?>>Newest</option>
          <option value="active" <?= $sort==='active'?'selected':'' ?>>Active</option>
        </select>
        <button class="btn" type="submit">Apply</button>
      </form>

      <!-- Ask box -->
      <div class="card" style="padding:14px 14px 8px 14px;">
        <form method="post" class="comment-form-wrap">
          <div class="form-row"><label class="form-label">Name (optional)</label><input class="form-input" type="text" name="name"></div>
          <div class="form-row"><label class="form-label">Topic</label>
            <select class="form-input" name="topic">
              <option value="">Select a topic</option>
              <?php foreach ($TOPICS as $t): ?><option value="<?= htmlspecialchars($t) ?>"><?= htmlspecialchars($t) ?></option><?php endforeach; ?>
            </select>
          </div>
          <div class="form-row"><label class="form-label">Your question</label><textarea class="form-textarea" name="content" rows="5" required></textarea></div>
          <div class="form-actions"><button class="btn-approve" type="submit">Post Question</button></div>
        </form>
      </div>

      <h3 style="margin-top:22px;">Recent Questions</h3>
      <?php foreach($questions as $qq): ?>
        <div class="forum-question">
          <div class="post-header">
            <div class="avatar"><?= strtoupper(substr($qq['name'],0,1)) ?></div>
            <div class="post-meta">
              <strong class="username"><?= htmlspecialchars($qq['name']) ?></strong>
              <span class="time"><?= htmlspecialchars($qq['created_at']) ?></span>
              <?php if (!empty($qq['topic'])): ?><span class="badge" style="margin-left:6px">#<?= htmlspecialchars($qq['topic']) ?></span><?php endif; ?>
            </div>
          </div>
          <div class="post-body">
            <?= nl2br(htmlspecialchars($qq['content'])) ?>
          </div>
          <div class="post-actions">
            <button class="reply-toggle" data-id="<?= $qq['id'] ?>">💬 Reply</button>
          </div>

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
                    <span class="time"><?= htmlspecialchars($rep['created_at']) ?></span>
                  </div>
                </div>
                <div class="post-body"><?= nl2br(htmlspecialchars($rep['content'])) ?></div>
              </div>
            <?php endforeach; ?>
          </div>

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
    </main>

    <aside>
      <div class="card" style="padding:12px;">
        <h3 style="margin-top:0">Recent Topics</h3>
        <div style="display:flex;flex-wrap:wrap;gap:8px;">
          <?php if (empty($recentTopics)): ?>
            <div class="muted">No topics yet.</div>
          <?php else: foreach ($recentTopics as $rt): ?>
            <a class="badge" href="community.php?topic=<?= urlencode($rt['topic']) ?>">#<?= htmlspecialchars($rt['topic']) ?></a>
          <?php endforeach; endif; ?>
        </div>
      </div>
    </aside>
  </div>
</section>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
<script>
  (function(){
    document.addEventListener('click', function(e){
      var t = e.target.closest && e.target.closest('.reply-toggle');
      if (!t) return;
      var id = t.getAttribute('data-id');
      var form = document.querySelector('.forum-reply-form[data-qid="'+id+'"]');
      if (!form) return;
      var isHidden = (form.style.display === 'none' || form.style.display === '');
      form.style.display = isHidden ? 'block' : 'none';
      if (isHidden) form.scrollIntoView({behavior:'smooth', block:'center'});
    });
  })();
</script>
