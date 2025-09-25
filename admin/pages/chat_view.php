<?php
// admin/pages/chat_view.php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
requirePermission('chat');
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

$threadId = intval($_GET['thread_id'] ?? 0);
if (!$threadId) { header('Location: ?pages=chat'); exit; }

// If unassigned, claim it for this admin
$claim = $pdo->prepare('UPDATE chat_threads SET assigned_admin_id = ? WHERE id = ? AND assigned_admin_id IS NULL');
$claim->execute([$_SESSION['user']['id'], $threadId]);
if ($claim->rowCount() > 0) {
  logAction($pdo, $_SESSION['user']['id'], 'chat_claimed', ['thread_id'=>$threadId]);
}

$thread = $pdo->prepare('SELECT ct.*, u.name as assigned_admin_name FROM chat_threads ct LEFT JOIN users u ON ct.assigned_admin_id = u.id WHERE ct.id = ? LIMIT 1');
$thread->execute([$threadId]); $thread = $thread->fetch(PDO::FETCH_ASSOC);
$messages = $pdo->prepare('SELECT * FROM chat_messages WHERE thread_id = ? ORDER BY created_at ASC'); $messages->execute([$threadId]); $msgs = $messages->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = 'Chat Thread #' . $threadId;
require_once __DIR__ . '/../includes/header.php';
ini_set('display_errors', 1);
error_reporting(E_ALL);

?>
<div class="roles-page">
  <div class="page-header"><h1><i class="bx bxs-message-dots"></i> Thread #<?= htmlspecialchars($threadId) ?></h1></div>
  <div class="card">
    <div style="max-height:400px;overflow:auto;padding:8px;border:1px solid #eee;margin-bottom:12px">
      <?php foreach($msgs as $m): ?>
        <div style="margin-bottom:10px">
          <div style="font-size:0.85rem;color:#666"><?= htmlspecialchars($m['created_at']) ?> — <?= $m['is_from_staff'] ? htmlspecialchars($m['sender_name']) : htmlspecialchars($m['sender_name']?:'Visitor') ?></div>
          <div style="background:<?= $m['is_from_staff'] ? '#fff3cf' : '#f6f6f6' ?>;padding:8px;border-radius:6px;margin-top:4px"><?= nl2br(htmlspecialchars($m['message'])) ?></div>
        </div>
      <?php endforeach; ?>
    </div>
    <form id="replyForm">
      <textarea name="message" style="width:100%;height:100px;padding:8px" placeholder="Reply..."></textarea>
      <div style="text-align:right;margin-top:8px"><button class="btn" type="submit">Send</button></div>
      <input type="hidden" name="_csrf" value="<?= htmlspecialchars(generateToken('chat_form')) ?>">
    </form>
    <div style="text-align:right;margin-top:8px">
      <button id="closeThreadBtn" class="btn" style="background:#f44336;color:#fff;border:none;padding:8px 12px;border-radius:6px;">Close Thread</button>
    </div>
  </div>
</div>

<script>
document.getElementById('replyForm').addEventListener('submit', function(e){
  e.preventDefault();
  var fd=new FormData(this); fd.append('action','reply'); fd.append('thread_id','<?= $threadId ?>');
  var csrfEl = document.querySelector('input[name="_csrf"]'); if (csrfEl) fd.append('_csrf', csrfEl.value);
  var xhr=new XMLHttpRequest(); xhr.open('POST', '/HIGH-Q/admin/pages/chat.php',true);
  xhr.setRequestHeader('X-Requested-With','XMLHttpRequest');
  xhr.onload=function(){ try{var r=JSON.parse(xhr.responseText);}catch(e){alert('Error');return;} if(r.status==='ok'){ location.reload(); } else alert('Failed'); };
  xhr.send(fd);
});

// Poll messages every 5 seconds
setInterval(function(){
  var xhr = new XMLHttpRequest(); xhr.open('GET', location.pathname + '?pages=chat_view&thread_id=<?= $threadId ?>&ajax=1&_=' + Date.now(), true);
  xhr.onload = function(){ if (xhr.status !== 200) return; try{ var html = xhr.responseText; } catch(e){ return; }
    var parser = new DOMParser(); var doc = parser.parseFromString(html, 'text/html');
    var messagesContainer = doc.querySelector('.card > div');
    if (messagesContainer) {
      var target = document.querySelector('.card > div');
      target.innerHTML = messagesContainer.innerHTML;
    }
  };
  xhr.send();
}, 5000);

// Close thread via AJAX
document.getElementById('closeThreadBtn').addEventListener('click', function(){
  if(!confirm('Close this thread? This will mark it closed and hide it from open lists.')) return;
  var fd = new FormData(); fd.append('action','close'); fd.append('thread_id','<?= $threadId ?>');
  // include CSRF token expected by the server
  var csrfEl = document.querySelector('input[name="_csrf"]');
  if (csrfEl) fd.append('_csrf', csrfEl.value);
  var xhr = new XMLHttpRequest(); xhr.open('POST', '/HIGH-Q/admin/pages/chat.php', true); xhr.setRequestHeader('X-Requested-With','XMLHttpRequest');
  xhr.onload = function(){ try{ var r = JSON.parse(xhr.responseText); }catch(e){ alert('Error'); return; } if(r.status==='ok'){ alert('Thread closed'); } else alert('Failed to close'); };
  xhr.send(fd);
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php';
