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
        .forum-question{border:1px solid #e2e8f0;background:#fff;border-radius:10px;padding:16px;margin:10px 0;transition:box-shadow .2s,border-color .2s}
        .forum-question:hover{box-shadow:0 4px 14px rgba(0,0,0,.08);border-color:#cbd5e1}
        .post-header{display:flex;align-items:flex-start;gap:12px}
        .avatar{width:40px;height:40px;border-radius:50%;display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;font-size:16px;flex-shrink:0}
        .post-meta{display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-bottom:6px}
        .post-meta .username{font-weight:600;color:#0f172a}
        .post-meta .time{color:#6b7280;font-size:13px}
        .badge{display:inline-flex;align-items:center;gap:4px;padding:3px 10px;border-radius:999px;background:#e0f2fe;color:#0369a1;font-size:12px;font-weight:500}
        .counter{display:inline-flex;align-items:center;gap:5px;padding:3px 9px;border-radius:999px;background:#f1f5f9;border:1px solid #e2e8f0;font-size:13px;color:#475569;font-weight:500}
        .post-content{flex:1;min-width:0}
        .post-body{margin:4px 0 10px 0;line-height:1.65;color:#1e293b;font-size:15px}
        .post-actions{display:flex;align-items:center;gap:10px;margin-top:8px}
        .post-actions .btn-lite{background:transparent;border:1px solid #e2e8f0;border-radius:6px;padding:6px 12px;font-size:13px;color:#475569;cursor:pointer;transition:all .2s;display:inline-flex;align-items:center;gap:6px}
        .post-actions .btn-lite:hover{background:#f8fafc;border-color:#cbd5e1;color:#0f172a}
        .post-actions .btn-lite i{font-size:16px}
        .post-replies{background:#f8fafc;border-left:3px solid #e2e8f0;padding:12px 14px;margin:12px 0 0 52px}
        .forum-reply{border-bottom:1px solid #e5e7eb;padding:10px 0;margin:0}
        .forum-reply:last-child{border-bottom:none}
        .forum-reply .avatar{width:32px;height:32px;font-size:14px}
        .forum-reply .post-body{font-size:14px;margin:4px 0 6px 0}
        .expand-replies{background:#e0f2fe;color:#0369a1;border:none;padding:6px 12px;border-radius:6px;font-size:13px;cursor:pointer;margin:8px 0;font-weight:500}
        .expand-replies:hover{background:#bae6fd}
        .hidden-reply{display:none}
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
        <div class="forum-question" id="q<?= (int)$qq['id'] ?>">
          <div class="post-header">
            <div class="avatar" style="background:linear-gradient(135deg,hsl(<?= $hue ?> 75% 55%),hsl(<?= ($hue+30)%360 ?> 75% 45%))">
              <?= strtoupper(substr($qq['name'],0,1)) ?>
            </div>
            <div class="post-content">
              <div class="post-meta">
                <strong class="username"><?= htmlspecialchars($qq['name']) ?></strong>
                <span class="time" data-time="<?= $iso ?>"><?= htmlspecialchars($qq['created_at']) ?></span>
                <?php if (!empty($qq['topic'])): ?><span class="badge"><i class='bx bx-purchase-tag-alt'></i><?= htmlspecialchars($qq['topic']) ?></span><?php endif; ?>
              </div>
              <div class="post-body">
                <?= nl2br(htmlspecialchars($qq['content'])) ?>
              </div>
              <div class="post-actions">
                <button class="btn-lite reply-toggle" data-id="<?= $qq['id'] ?>"><i class='bx bx-message-rounded-dots'></i>Reply (<?= (int)($qq['replies_count'] ?? 0) ?>)</button>
                <button class="btn-lite" onclick="navigator.clipboard.writeText(location.origin+location.pathname+'#q<?= (int)$qq['id'] ?>'); this.innerHTML='<i class=\'bx bx-check\'></i>Copied'; setTimeout(()=>this.innerHTML='<i class=\'bx bx-link-alt\'></i>Share',1500)"><i class='bx bx-link-alt'></i>Share</button>
              </div>
            </div>
          </div>

          <?php
            $rstmt = $pdo->prepare('SELECT id,name,content,created_at FROM forum_replies WHERE question_id = ? ORDER BY created_at ASC');
            $rstmt->execute([$qq['id']]);
            $reps = $rstmt->fetchAll();
            if (!empty($reps)):
          ?>
          <div class="post-replies" id="replies-<?= $qq['id'] ?>">
            <?php
              $showCount = 3;
              foreach ($reps as $idx => $rep):
                $h2 = (int)(hexdec(substr(md5(($rep['name'] ?? 'A')),0,2))/255*360);
                $isoR = htmlspecialchars(date('c', strtotime($rep['created_at'])));
                $hideClass = ($idx >= $showCount && count($reps) > $showCount) ? ' hidden-reply' : '';
            ?>
              <div class="forum-reply<?= $hideClass ?>" data-qid="<?= $qq['id'] ?>">
                <div class="post-header">
                  <div class="avatar" style="background:linear-gradient(135deg,hsl(<?= $h2 ?> 75% 55%),hsl(<?= ($h2+30)%360 ?> 75% 45%))"><?= strtoupper(substr($rep['name'],0,1)) ?></div>
                  <div class="post-content">
                    <div class="post-meta">
                      <strong class="username"><?= htmlspecialchars($rep['name']) ?></strong>
                      <span class="time" data-time="<?= $isoR ?>"><?= htmlspecialchars($rep['created_at']) ?></span>
                    </div>
                    <div class="post-body"><?= nl2br(htmlspecialchars($rep['content'])) ?></div>
                  </div>
                </div>
              </div>
            <?php endforeach; ?>
            <?php if (count($reps) > $showCount): ?>
              <button class="expand-replies" data-qid="<?= $qq['id'] ?>"><i class='bx bx-chevron-down'></i> Show <?= count($reps) - $showCount ?> more replies</button>
            <?php endif; ?>
          </div>
          <?php endif; ?>

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
    // Expand/collapse replies
    document.addEventListener('click', function(e){
      var btn = e.target.closest && e.target.closest('.expand-replies');
      if (!btn) return;
      var qid = btn.getAttribute('data-qid');
      var hidden = document.querySelectorAll('.hidden-reply[data-qid="'+qid+'"]');
      var isExpanded = btn.classList.contains('expanded');
      if (isExpanded) {
        hidden.forEach(function(el){ el.style.display = 'none'; });
        btn.innerHTML = '<i class="bx bx-chevron-down"></i> Show '+hidden.length+' more replies';
        btn.classList.remove('expanded');
      } else {
        hidden.forEach(function(el){ el.style.display = 'block'; });
        btn.innerHTML = '<i class="bx bx-chevron-up"></i> Show less';
        btn.classList.add('expanded');
      }
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
