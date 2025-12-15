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
$sortParam = $_GET['sort'] ?? null; // avoid undefined index warning
$sort = in_array($sortParam, ['newest','active'], true) ? $sortParam : 'newest';

// Build query for questions
$sql = 'SELECT q.id,q.name,q.topic,q.content,q.created_at,
  COALESCE(MAX(r.created_at), q.created_at) AS last_activity,
  COUNT(r.id) AS replies_count
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

      <style>
        .forum-question{border:1px solid #eee;background:#fff;border-radius:12px;padding:14px 14px 10px 14px;margin:12px 0;transition:box-shadow .2s,border-color .2s}
        .forum-question:hover{box-shadow:0 6px 18px rgba(0,0,0,.06);border-color:#e2e8f0}
        .post-header{display:flex;align-items:center;gap:12px}
        .avatar{width:36px;height:36px;border-radius:50%;display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;letter-spacing:.5px}
        .post-meta{display:flex;align-items:center;gap:8px;flex-wrap:wrap}
        .post-meta .username{font-weight:600}
        .post-meta .time{color:#6b7280;font-size:12px}
        .badge{display:inline-block;padding:2px 8px;border-radius:999px;background:#f1f5f9;color:#0f172a;font-size:12px;border:1px solid #e2e8f0}
        .counter{display:inline-flex;align-items:center;gap:6px;padding:2px 8px;border-radius:999px;background:#f8fafc;border:1px solid #e5e7eb;font-size:12px;color:#334155}
        .post-body{margin:10px 0 6px 0;line-height:1.7;color:#111827}
        .post-actions{display:flex;align-items:center;gap:8px;margin-top:4px}
        .post-actions .btn-lite{background:#f8fafc;border:1px solid #e5e7eb;border-radius:8px;padding:6px 10px;font-size:13px;color:#334155}
        .post-replies{background:#fafafa;border:1px solid #eee;border-radius:10px;padding:10px;margin-top:10px}
        .forum-reply{border-top:1px dashed #e5e7eb;padding-top:10px;margin-top:10px}
        .forum-reply:first-child{border-top:none;padding-top:0;margin-top:0}
      </style>

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
        <?php $hue = (int)(hexdec(substr(md5(($qq['name'] ?? 'A')),0,2))/255*360); $iso = htmlspecialchars(date('c', strtotime($qq['created_at']))); ?>
        <div class="forum-question">
          <div class="post-header">
            <div class="avatar" style="background:linear-gradient(135deg,hsl(<?= $hue ?> 75% 55%),hsl(<?= ($hue+30)%360 ?> 75% 45%))">
              <?= strtoupper(substr($qq['name'],0,1)) ?>
            </div>
            <div class="post-meta">
              <strong class="username"><?= htmlspecialchars($qq['name']) ?></strong>
              <span class="time" data-time="<?= $iso ?>"><?= htmlspecialchars($qq['created_at']) ?></span>
              <?php if (!empty($qq['topic'])): ?><span class="badge" style="margin-left:6px">#<?= htmlspecialchars($qq['topic']) ?></span><?php endif; ?>
              <span class="counter" title="Replies"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M21 15a4 4 0 0 1-4 4H9l-6 4V7a4 4 0 0 1 4-4h10a4 4 0 0 1 4 4v8z" stroke="#475569" stroke-width="1.5" fill="none"/></svg><?= (int)($qq['replies_count'] ?? 0) ?></span>
            </div>
          </div>
          <div class="post-body">
            <?= nl2br(htmlspecialchars($qq['content'])) ?>
          </div>
          <div class="post-actions">
            <button class="btn-lite reply-toggle" data-id="<?= $qq['id'] ?>">💬 Reply</button>
            <a class="btn-lite" href="#q<?= (int)$qq['id'] ?>" onclick="navigator.clipboard.writeText(location.origin+location.pathname+'#q<?= (int)$qq['id'] ?>'); return false;">🔗 Copy link</a>
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
                  <?php $h2 = (int)(hexdec(substr(md5(($rep['name'] ?? 'A')),0,2))/255*360); $isoR = htmlspecialchars(date('c', strtotime($rep['created_at']))); ?>
                  <div class="avatar" style="background:linear-gradient(135deg,hsl(<?= $h2 ?> 75% 55%),hsl(<?= ($h2+30)%360 ?> 75% 45%))"><?= strtoupper(substr($rep['name'],0,1)) ?></div>
                  <div class="post-meta">
                    <strong class="username"><?= htmlspecialchars($rep['name']) ?></strong>
                    <span class="time" data-time="<?= $isoR ?>"><?= htmlspecialchars($rep['created_at']) ?></span>
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
    // Relative time formatter for timestamps
    function rel(t){
      try{
        var d = new Date(t);
        var s = Math.floor((Date.now()-d.getTime())/1000);
        if (isNaN(s)) return t;
        var a = [
          ['year',31536000],['month',2592000],['week',604800],['day',86400],['hour',3600],['minute',60],['second',1]
        ];
        for (var i=0;i<a.length;i++){
          var n = Math.floor(s/a[i][1]);
          if (n >= 1) return n+' '+a[i][0]+(n>1?'s':'')+' ago';
        }
        return 'just now';
      }catch(_){ return t; }
    }
    document.querySelectorAll('.time[data-time]').forEach(function(el){
      var iso = el.getAttribute('data-time');
      el.textContent = rel(iso);
      el.title = iso;
    });
  })();
</script>
