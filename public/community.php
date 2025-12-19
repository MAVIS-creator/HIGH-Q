<?php
require_once __DIR__ . '/config/db.php';
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
require_once __DIR__ . '/includes/community_renderer.php';

$PAGE_SIZE = 6;
$page = max(1, (int)($_GET['page'] ?? 1));
$offset = ($page - 1) * $PAGE_SIZE;

// Topics catalogue (soft-config)
$TOPICS = ['Admissions', 'Exams', 'Payments', 'Courses', 'Tutorials', 'General'];

// handle new posts and replies
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (!empty($_POST['question_id'])) {
    $qid = (int)($_POST['question_id'] ?? 0);
    $parent = !empty($_POST['parent_id']) ? (int)$_POST['parent_id'] : null;
    $rname = trim($_POST['rname'] ?? '');
    $rcontent = trim($_POST['rcontent'] ?? '');
    if ($qid > 0 && $rcontent !== '') {
      $rstmt = $pdo->prepare('INSERT INTO forum_replies (question_id, parent_id, name, content, created_at) VALUES (?, ?, ?, ?, NOW())');
      $rstmt->execute([$qid, $parent, $rname ?: 'Anonymous', $rcontent]);
    }
    header('Location: community.php');
    exit;
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
      header('Location: community.php');
      exit;
    }
  }
}

$pageTitle = 'Community Q&A';
require_once __DIR__ . '/includes/header.php';

// Filters
$qterm = trim($_GET['q'] ?? '');
$ftopic = trim($_GET['topic'] ?? '');
$sortParam = $_GET['sort'] ?? null; // avoid undefined index warning
$sort = in_array($sortParam, ['newest', 'active'], true) ? $sortParam : 'newest';

// Build query for questions
$baseSql = ' FROM forum_questions q';
$where = [];
$params = [];
if ($qterm !== '') {
  $where[] = ' (q.content LIKE ? OR q.name LIKE ?) ';
  $params[] = "%$qterm%";
  $params[] = "%$qterm%";
}
if ($ftopic !== '') {
  $where[] = ' q.topic = ? ';
  $params[] = $ftopic;
}
if (!empty($where)) {
  $whereSql = ' WHERE ' . implode(' AND ', $where);
} else {
  $whereSql = '';
}

// Total count for pagination
$countStmt = $pdo->prepare('SELECT COUNT(*)' . $baseSql . $whereSql);
foreach ($params as $i => $p) {
  $countStmt->bindValue($i + 1, $p, PDO::PARAM_STR);
}
$countStmt->execute();
$totalQuestions = (int)$countStmt->fetchColumn();

$sql = 'SELECT
    q.id,
    q.name,
    q.topic,
    q.content,
    q.created_at,
    (SELECT COALESCE(SUM(vote),0) FROM forum_votes v WHERE v.question_id = q.id) AS vote_score,
    (SELECT COUNT(*) FROM forum_replies fr WHERE fr.question_id = q.id) AS replies_count,
    (SELECT COALESCE(MAX(created_at), q.created_at) FROM forum_replies fr2 WHERE fr2.question_id = q.id) AS last_activity
  ' . $baseSql . $whereSql;

$sql .= ' ';
$sql .= ($sort === 'active') ? ' ORDER BY last_activity DESC ' : ' ORDER BY q.created_at DESC ';
$sql .= ' LIMIT ? OFFSET ?';

$stmt = $pdo->prepare($sql);
foreach ($params as $i => $p) {
  $stmt->bindValue($i + 1, $p, PDO::PARAM_STR);
}
$stmt->bindValue(count($params) + 1, $PAGE_SIZE, PDO::PARAM_INT);
$stmt->bindValue(count($params) + 2, $offset, PDO::PARAM_INT);
$stmt->execute();
$questions = $stmt->fetchAll(PDO::FETCH_ASSOC);
$questionIds = array_column($questions, 'id');
$hasMore = ($offset + count($questions) < $totalQuestions);

// Fetch user votes for questions
$userQuestionVotes = [];
if (!empty($questionIds)) {
  $in = implode(',', array_fill(0, count($questionIds), '?'));
  $vq = $pdo->prepare("SELECT question_id, vote FROM forum_votes WHERE question_id IN ($in) AND (session_id = ? OR ip = ?)");
  foreach ($questionIds as $i => $qid) { $vq->bindValue($i + 1, $qid, PDO::PARAM_INT); }
  $vq->bindValue(count($questionIds) + 1, session_id(), PDO::PARAM_STR);
  $vq->bindValue(count($questionIds) + 2, $_SERVER['REMOTE_ADDR'] ?? null, PDO::PARAM_STR);
  $vq->execute();
  foreach ($vq->fetchAll(PDO::FETCH_ASSOC) as $row) { $userQuestionVotes[(int)$row['question_id']] = (int)$row['vote']; }
}

// Fetch replies grouped by question, with vote scores and parent_id
$repliesByQuestion = [];
$replyIds = [];
if (!empty($questionIds)) {
  $in = implode(',', array_fill(0, count($questionIds), '?'));
  $rs = $pdo->prepare("SELECT id, question_id, parent_id, name, content, created_at, (SELECT COALESCE(SUM(vote),0) FROM forum_votes v WHERE v.reply_id = fr.id) AS vote_score FROM forum_replies fr WHERE fr.question_id IN ($in) ORDER BY fr.created_at ASC");
  foreach ($questionIds as $i => $qid) { $rs->bindValue($i + 1, $qid, PDO::PARAM_INT); }
  $rs->execute();
  $rows = $rs->fetchAll(PDO::FETCH_ASSOC);
  foreach ($rows as $r) {
    $replyIds[] = (int)$r['id'];
    $repliesByQuestion[(int)$r['question_id']][] = $r;
  }
}

// User votes for replies
$userReplyVotes = [];
if (!empty($replyIds)) {
  $in = implode(',', array_fill(0, count($replyIds), '?'));
  $vr = $pdo->prepare("SELECT reply_id, vote FROM forum_votes WHERE reply_id IN ($in) AND (session_id = ? OR ip = ?)");
  foreach ($replyIds as $i => $rid) { $vr->bindValue($i + 1, $rid, PDO::PARAM_INT); }
  $vr->bindValue(count($replyIds) + 1, session_id(), PDO::PARAM_STR);
  $vr->bindValue(count($replyIds) + 2, $_SERVER['REMOTE_ADDR'] ?? null, PDO::PARAM_STR);
  $vr->execute();
  foreach ($vr->fetchAll(PDO::FETCH_ASSOC) as $row) { $userReplyVotes[(int)$row['reply_id']] = (int)$row['vote']; }
}

// Recent topics
$recentTopics = [];
try {
  $rt = $pdo->query('SELECT topic, MAX(created_at) AS last_time, COUNT(*) AS cnt FROM forum_questions WHERE topic IS NOT NULL AND topic <> "" GROUP BY topic ORDER BY last_time DESC LIMIT 8');
  $recentTopics = $rt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
  $recentTopics = [];
}
?><section class="community-section">
  <div class="container community-grid">
    <main>
      <h1 style="margin:0 0 8px 0;">Community</h1>
      <p class="muted">Ask questions anonymously, no account needed.</p>

      <style>
        .community-section { padding:32px 0; }
        .community-grid { display:grid; grid-template-columns:minmax(0,1fr) 320px; gap:20px; align-items:start; }
        .community-grid aside .card { position:sticky; top:110px; }

        .forum-question { border:1px solid #e2e8f0; background:#fff; border-radius:12px; padding:16px; transition:box-shadow .2s, border-color .2s; }
        .forum-question:hover { box-shadow:0 4px 14px rgba(0,0,0,.08); border-color:#cbd5e1; }
        .post-header { display:flex; align-items:flex-start; gap:12px; }
        .avatar { width:40px; height:40px; border-radius:50%; display:flex; align-items:center; justify-content:center; color:#fff; font-weight:700; font-size:16px; flex-shrink:0; }
        .post-meta { display:flex; align-items:center; gap:8px; flex-wrap:wrap; margin-bottom:6px; }
        .post-meta .username { font-weight:600; color:#0f172a; }
        .post-meta .time { color:#6b7280; font-size:13px; }
        .badge { display:inline-flex; align-items:center; gap:4px; padding:3px 10px; border-radius:999px; background:#e0f2fe; color:#0369a1; font-size:12px; font-weight:500; }
        .post-content { flex:1; min-width:0; }
        .post-body { margin:4px 0 10px 0; line-height:1.65; color:#1e293b; font-size:15px; }
        .post-actions { display:flex; align-items:center; gap:10px; margin-top:8px; flex-wrap:wrap; }
        .post-actions .btn-lite { background:transparent; border:1px solid #e2e8f0; border-radius:6px; padding:6px 12px; font-size:13px; color:#475569; cursor:pointer; transition:all .2s; display:inline-flex; align-items:center; gap:6px; }
        .post-actions .btn-lite:hover { background:#f8fafc; border-color:#cbd5e1; color:#0f172a; }
        .post-actions .btn-lite i { font-size:16px; }

        .post-replies { background:#f8fafc; border-left:3px solid #e2e8f0; padding:12px 14px; margin:12px 0 0 48px; border-radius:10px; }
        .vote-stack { display:flex; flex-direction:column; align-items:center; gap:6px; margin-right:10px; }
        .vote-btn { width:32px; height:32px; border:1px solid #e2e8f0; border-radius:8px; background:#fff; display:flex; align-items:center; justify-content:center; color:#475569; cursor:pointer; transition:all .15s; }
        .vote-btn:hover { background:#f8fafc; border-color:#cbd5e1; color:#0f172a; }
        .vote-btn.active { background:#0ea5e9; color:#fff; border-color:#0284c7; }
        .vote-score { font-weight:700; color:#0f172a; font-size:13px; }

        .forum-reply { border-bottom:1px solid #e5e7eb; padding:10px 0; margin:0; }
        .forum-reply:last-child { border-bottom:none; }
        .forum-reply .avatar { width:32px; height:32px; font-size:14px; }
        .forum-reply .post-body { font-size:14px; margin:4px 0 6px 0; }
        .expand-replies { background:#e0f2fe; color:#0369a1; border:none; padding:6px 12px; border-radius:6px; font-size:13px; cursor:pointer; margin:8px 0; font-weight:500; }
        .expand-replies:hover { background:#bae6fd; }
        .hidden-reply { display:none; }

        .questions-shell h3 { margin-top:22px; margin-bottom:10px; }
        .questions-list { display:flex; flex-direction:column; gap:14px; }
        .feed-loader, .no-more { display:flex; align-items:center; gap:12px; margin-top:14px; padding:14px; border-radius:14px; border:1px dashed #d8e2ec; background:#f8fafc; }
        .feed-loader strong, .no-more strong { display:block; margin-bottom:4px; }
        .loader-logo { width:68px; height:68px; border-radius:18px; display:flex; align-items:center; justify-content:center; background:radial-gradient(circle at 30% 30%, rgba(255,214,79,0.45), rgba(255,193,7,0.15)); box-shadow:0 0 24px rgba(255,193,7,0.35); position:relative; overflow:hidden; }
        .loader-logo img { width:54px; height:54px; object-fit:contain; filter:drop-shadow(0 0 10px rgba(255,193,7,.35)); }
        .loader-logo::after { content:''; position:absolute; inset:-30%; background:radial-gradient(circle, rgba(255,255,255,0.2) 0%, transparent 55%); animation:glow 2s ease-in-out infinite; }
        @keyframes glow { 0%{transform:scale(0.8) rotate(0deg);} 50%{transform:scale(1.05) rotate(8deg);} 100%{transform:scale(0.8) rotate(0deg);} }
        .no-more { background:#f1f5f9; color:#334155; border:1px dashed #cbd5e1; }
        .empty-state { display:flex; align-items:center; gap:14px; padding:16px; border:1px dashed #e2e8f0; border-radius:14px; background:linear-gradient(135deg,#f8fafc,#f1f5f9); margin-top:10px; }
        .empty-state h4 { margin:0; }
        .feed-end { height:1px; }

        .filters { display:flex; gap:10px; flex-wrap:wrap; margin:14px 0 18px 0; }
        .filters .form-input { flex:1; min-width:160px; }
        .filters .form-input[type="search"] { flex:2; min-width:200px; }
        .filters .btn { flex:0 0 auto; }

        .forum-reply-form { display:none; margin:10px 0 0 48px; border:1px solid #e2e8f0; border-radius:12px; padding:12px; background:#fff; }
        .forum-reply-form.active { display:block; }
        @media (max-width:1100px) { .community-grid { grid-template-columns:1fr; } .community-grid aside { order:-1; } .community-grid aside .card { position:relative; top:auto; } .forum-reply-form { margin-left:0; } }
        @media (max-width: 768px) {
          .forum-question { padding:12px; }
          .post-header { align-items:flex-start; gap:10px; }
          .post-replies { margin:12px 0 0 0; }
          .vote-stack { flex-direction:row; gap:8px; margin-right:0; }
          .vote-btn { width:30px; height:30px; border-radius:6px; }
          .vote-score { font-size:12px; }
        }
        @media (max-width:520px) {
          .filters { flex-direction:column; }
          .filters .btn { width:100%; }
          .post-actions { gap:8px; }
        }
      </style>

      <!-- Filters -->
      <form method="get" class="filters">
        <input class="form-input" type="search" name="q" placeholder="Search questions..." value="<?= htmlspecialchars($qterm) ?>">
        <select class="form-input" name="topic">
          <option value="">All topics</option>
          <?php foreach ($TOPICS as $t): ?>
            <option value="<?= htmlspecialchars($t) ?>" <?= $ftopic === $t ? 'selected' : '' ?>><?= htmlspecialchars($t) ?></option>
          <?php endforeach; ?>
        </select>
        <select class="form-input" name="sort">
          <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>Newest</option>
          <option value="active" <?= $sort === 'active' ? 'selected' : '' ?>>Active</option>
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

      <div class="questions-shell">
        <h3>Recent Questions</h3>
        <div id="questions-list" class="questions-list" data-page="<?= $page ?>" data-has-more="<?= $hasMore ? '1' : '0' ?>">
          <?php if (empty($questions)): ?>
            <div class="empty-state">
              <div class="loader-logo">
                <img src="<?= app_url('assets/images/hq-logo.jpeg') ?>" alt="HQ Logo">
              </div>
              <div>
                <h4>No questions yet</h4>
                <p class="muted" style="margin:4px 0 0 0;">Be the first to start a conversation.</p>
              </div>
            </div>
          <?php else: ?>
            <?php foreach ($questions as $qq): ?>
              <?= hq_render_question_card($qq, $repliesByQuestion[$qq['id']] ?? [], $userQuestionVotes, $userReplyVotes); ?>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>

        <div id="feed-loader" class="feed-loader" style="display:none;">
          <div class="loader-logo">
            <img src="<?= app_url('assets/images/hq-logo.jpeg') ?>" alt="HQ Logo">
          </div>
          <div>
            <strong>Loading more...</strong>
            <p class="muted" style="margin:0;">Bringing in fresh questions.</p>
          </div>
        </div>

        <div id="no-more" class="no-more" style="<?= $hasMore ? 'display:none;' : '' ?>">
          <i class='bx bx-check-circle'></i>
          <div>
            <strong>No more posts for now</strong>
            <p class="muted" style="margin:0;">Check back soon for more updates.</p>
          </div>
        </div>

        <div id="feed-end" class="feed-end" aria-hidden="true"></div>
      </div>
    </main>

    <aside>
      <div class="card" style="padding:12px;">
        <h3 style="margin-top:0">Recent Topics</h3>
        <div style="display:flex;flex-wrap:wrap;gap:8px;">
          <?php if (empty($recentTopics)): ?>
            <div class="muted">No topics yet.</div>
            <?php else: foreach ($recentTopics as $rt): ?>
              <a class="badge" href="community.php?topic=<?= urlencode($rt['topic']) ?>">#<?= htmlspecialchars($rt['topic']) ?></a>
          <?php endforeach;
          endif; ?>
        </div>
      </div>
    </aside>
  </div>
</section>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
<script>
  (function() {
    var feed = document.getElementById('questions-list');
    var feedLoader = document.getElementById('feed-loader');
    var noMore = document.getElementById('no-more');
    var sentinel = document.getElementById('feed-end');
    var currentPage = feed ? parseInt(feed.getAttribute('data-page') || '1', 10) : 1;
    var hasMore = feed ? feed.getAttribute('data-has-more') === '1' : false;
    var loading = false;
    var qterm = <?= json_encode($qterm) ?>;
    var topic = <?= json_encode($ftopic) ?>;
    var sort = <?= json_encode($sort) ?>;

    document.addEventListener('click', function(e) {
      var t = e.target.closest && e.target.closest('.reply-toggle');
      if (!t) return;
      var id = t.getAttribute('data-id');
      var parent = t.getAttribute('data-parent') || '';
      var form = document.querySelector('.forum-reply-form[data-qid="' + id + '"]');
      if (!form) return;
      form.querySelector('input[name="parent_id"]').value = parent;
      var isHidden = !form.classList.contains('active');
      form.classList.toggle('active');
      if (isHidden) form.scrollIntoView({
        behavior: 'smooth',
        block: 'center'
      });
    });
    // Expand/collapse replies
    document.addEventListener('click', function(e) {
      var btn = e.target.closest && e.target.closest('.expand-replies');
      if (!btn) return;
      var qid = btn.getAttribute('data-qid');
      var hidden = document.querySelectorAll('.hidden-reply[data-qid="' + qid + '"]');
      var isExpanded = btn.classList.contains('expanded');
      if (isExpanded) {
        hidden.forEach(function(el) {
          el.style.display = 'none';
        });
        btn.innerHTML = '<i class="bx bx-chevron-down"></i> Show ' + hidden.length + ' more replies';
        btn.classList.remove('expanded');
      } else {
        hidden.forEach(function(el) {
          el.style.display = 'block';
        });
        btn.innerHTML = '<i class="bx bx-chevron-up"></i> Show less';
        btn.classList.add('expanded');
      }
    });
    // Voting
    document.addEventListener('click', function(e){
      var vbtn = e.target.closest && e.target.closest('.vote-btn');
      if (!vbtn) return;
      var type = vbtn.getAttribute('data-type');
      var id = vbtn.getAttribute('data-id');
      var vote = parseInt(vbtn.getAttribute('data-vote'), 10);
      if (!type || !id || !vote) return;

      fetch('api/community_vote.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: new URLSearchParams({target_type: type, id: id, vote: vote})
      }).then(r => r.json()).then(j => {
        if (!j || j.status !== 'ok') return;
        var scoreEl = document.getElementById((type === 'question' ? 'qscore-' : 'rscore-') + id);
        if (scoreEl) scoreEl.textContent = j.score;
        var group = document.querySelectorAll('.vote-btn[data-type="'+type+'"][data-id="'+id+'"]');
        group.forEach(function(btn){ btn.classList.remove('active'); });
        if (j.user_vote === 1) {
          var up = document.querySelector('.vote-btn.vote-up[data-type="'+type+'"][data-id="'+id+'"]');
          if (up) up.classList.add('active');
        } else if (j.user_vote === -1) {
          var dn = document.querySelector('.vote-btn.vote-down[data-type="'+type+'"][data-id="'+id+'"]');
          if (dn) dn.classList.add('active');
        }
      }).catch(()=>{});
    });

    function refreshTimes(root) {
      var scope = root || document;
      scope.querySelectorAll && scope.querySelectorAll('.time[data-time]').forEach(function(el) {
        var iso = el.getAttribute('data-time');
        el.textContent = rel(iso);
        el.title = iso;
      });
    }

    function toggleLoader(show) {
      if (!feedLoader) return;
      feedLoader.style.display = show ? 'flex' : 'none';
    }

    function loadMore() {
      if (!feed || loading || !hasMore) return;
      loading = true;
      toggleLoader(true);
      var nextPage = currentPage + 1;
      var params = new URLSearchParams({
        page: String(nextPage),
        q: qterm || '',
        topic: topic || '',
        sort: sort || 'newest'
      });
      fetch('api/community_feed.php?' + params.toString(), { credentials: 'same-origin' })
        .then(function(r){ return r.json(); })
        .then(function(res){
          if (!res || res.status !== 'ok') return;
          if (res.html && feed) {
            feed.insertAdjacentHTML('beforeend', res.html);
            refreshTimes(feed);
          }
          currentPage = nextPage;
          hasMore = !!res.has_more;
          if (!hasMore && noMore) { noMore.style.display = 'flex'; }
        })
        .catch(function(){})
        .finally(function(){ loading = false; toggleLoader(false); });
    }

    if (feed && sentinel && hasMore) {
      var io = new IntersectionObserver(function(entries){
        entries.forEach(function(entry){ if (entry.isIntersecting) loadMore(); });
      }, { rootMargin: '240px 0px' });
      io.observe(sentinel);
    }

    // Relative time formatter for timestamps
    function rel(t) {
      try {
        var d = new Date(t);
        var s = Math.floor((Date.now() - d.getTime()) / 1000);
        if (isNaN(s)) return t;
        var a = [
          ['year', 31536000],
          ['month', 2592000],
          ['week', 604800],
          ['day', 86400],
          ['hour', 3600],
          ['minute', 60],
          ['second', 1]
        ];
        for (var i = 0; i < a.length; i++) {
          var n = Math.floor(s / a[i][1]);
          if (n >= 1) return n + ' ' + a[i][0] + (n > 1 ? 's' : '') + ' ago';
        }
        return 'just now';
      } catch (_) {
        return t;
      }
    }
    refreshTimes(document);
  })();
</script>