<?php
require_once __DIR__ . '/config/db.php';
$pageTitle = 'Community Q&A';
require_once __DIR__ . '/includes/header.php';
// handle new question post via AJAX from the styled form; preserve legacy POST fallback
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (isset($_POST['ajax']) || isset($_SERVER['HTTP_X_REQUESTED_WITH']))) {
  $name = trim($_POST['name'] ?? '');
  $content = trim($_POST['content'] ?? '');
  if ($content !== '') {
    $stmt = $pdo->prepare('INSERT INTO forum_questions (name, content, created_at) VALUES (?, ?, NOW())');
    $stmt->execute([$name ?: 'Anonymous', $content]);
    header('Content-Type: application/json'); echo json_encode(['status'=>'ok']); exit;
  }
  header('Content-Type: application/json'); echo json_encode(['status'=>'error','message'=>'Missing content']); exit;
}

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
            <div class="forum-card" data-id="<?= $qq['id'] ?>" style="border:1px solid #eee;padding:12px;border-radius:8px;margin-bottom:10px;display:flex;gap:12px;align-items:flex-start;">
              <div style="width:44px;"><div class="avatar-circle"><?= strtoupper(substr($qq['name']?:'A',0,1)) ?></div></div>
              <div style="flex:1">
                <div style="font-weight:700"><?= htmlspecialchars($qq['name']) ?> <small class="muted" style="font-weight:400">· <?= htmlspecialchars($qq['created_at']) ?></small></div>
                <div style="margin-top:6px"><?= nl2br(htmlspecialchars($qq['content'])) ?></div>
                <div style="margin-top:8px;"><button class="btn-link btn-reply-question" data-id="<?= $qq['id'] ?>">Reply</button></div>
                <div class="forum-replies" data-id="replies-<?= $qq['id'] ?>">
                  <?php
                    // load up to 20 replies
                    $rstmt = $pdo->prepare('SELECT id,name,content,created_at FROM forum_replies WHERE question_id = ? ORDER BY created_at ASC LIMIT 20');
                    $rstmt->execute([$qq['id']]);
                    $reps = $rstmt->fetchAll(PDO::FETCH_ASSOC);
                    foreach($reps as $rp):
                  ?>
                    <div class="forum-reply" style="margin-top:8px;display:flex;gap:8px;align-items:flex-start;">
                      <div style="width:36px;"><div class="avatar-circle muted"><?= strtoupper(substr($rp['name']?:'A',0,1)) ?></div></div>
                      <div>
                        <div style="font-weight:700;font-size:13px"><?= htmlspecialchars($rp['name']) ?> <small class="muted">· <?= htmlspecialchars($rp['created_at']) ?></small></div>
                        <div style="margin-top:6px"><?= nl2br(htmlspecialchars($rp['content'])) ?></div>
                      </div>
                    </div>
                  <?php endforeach; ?>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
    </div>
  </div>
</section>
  <script>
    // Post new question via AJAX
    document.querySelector('form').addEventListener('submit', function(e){
      e.preventDefault();
      var fd = new FormData(this); fd.append('ajax', '1');
      fetch('community.php',{method:'POST',body:fd}).then(r=>r.json()).then(j=>{
        if (j.status === 'ok') { location.reload(); } else { alert(j.message || 'Error'); }
      }).catch(()=>alert('Network error'));
    });

    // Reply to question
    document.querySelectorAll('.btn-reply-question').forEach(btn=>btn.addEventListener('click', function(){
      var id = this.dataset.id;
      var form = document.createElement('div');
      form.innerHTML = '<div style="margin-top:8px"><textarea rows="3" placeholder="Write your reply" style="width:100%;padding:8px;border-radius:6px;border:1px solid #eee"></textarea><div style="margin-top:6px"><button class="btn-approve small">Reply</button> <button class="btn-link small btn-cancel">Cancel</button></div></div>';
      var container = document.querySelector('.forum-card[data-id="'+id+'"] .forum-replies');
      if (container) container.insertBefore(form, container.firstChild);
      var replyBtn = form.querySelector('.btn-approve');
      var cancelBtn = form.querySelector('.btn-cancel');
      cancelBtn.addEventListener('click', function(){ form.remove(); });
      replyBtn.addEventListener('click', function(){
        var txt = form.querySelector('textarea').value.trim(); if (!txt) { alert('Please write a reply'); return; }
        var fd = new FormData(); fd.append('question_id', id); fd.append('name','Anonymous'); fd.append('content', txt);
        fetch('api/forum_reply.php',{method:'POST',body:fd}).then(r=>r.json()).then(j=>{
          if (j.status === 'ok') { location.reload(); } else { alert(j.message || 'Error'); }
        }).catch(()=>alert('Network error'));
      });
    }));
  </script>
<?php require_once __DIR__ . '/includes/footer.php';
