<?php
// admin/pages/chat.php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
requirePermission('chat');
require_once __DIR__ . '/../includes/db.php';
// include public helper functions for logging
require_once __DIR__ . '/../../public/config/functions.php';

$pageTitle = 'Chat Support';
require_once __DIR__ . '/../includes/header.php';

// Claim thread (AJAX): first admin to call 'claim' will set assigned_admin_id if null
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
    $token = $_POST['_csrf'] ?? ''; if (!verifyToken('chat_form', $token)) { echo json_encode(['status'=>'error','message'=>'Invalid CSRF']); exit; }
    $action = $_POST['action'] ?? ''; $threadId = intval($_POST['thread_id'] ?? 0);
    if ($action === 'claim' && $threadId) {
        // attempt to set assigned_admin_id where null
        $stmt = $pdo->prepare('UPDATE chat_threads SET assigned_admin_id = ? WHERE id = ? AND assigned_admin_id IS NULL');
        $ok = $stmt->execute([$_SESSION['user']['id'], $threadId]);
    if ($stmt->rowCount() > 0) {
      // audit log: admin claimed thread
      logAction($pdo, $_SESSION['user']['id'], 'chat_claimed', ['thread_id' => $threadId]);
      echo json_encode(['status'=>'ok','message'=>'Claimed']);
    } else {
            // already claimed, return current assignment
            $q = $pdo->prepare('SELECT assigned_admin_id FROM chat_threads WHERE id = ? LIMIT 1'); $q->execute([$threadId]); $aid = $q->fetchColumn();
            echo json_encode(['status'=>'taken','assigned_admin_id'=>$aid]);
        }
        exit;
    }
    if ($action === 'reply' && $threadId) {
        $msg = trim($_POST['message'] ?? ''); if ($msg === '') { echo json_encode(['status'=>'error','message'=>'Empty']); exit; }
        $ins = $pdo->prepare('INSERT INTO chat_messages (thread_id, sender_id, sender_name, message, is_from_staff, created_at) VALUES (?, ?, ?, ?, 1, NOW())');
        $ins->execute([$threadId, $_SESSION['user']['id'], $_SESSION['user']['name'], $msg]);
        // update thread last_activity
        $u = $pdo->prepare('UPDATE chat_threads SET last_activity = NOW() WHERE id = ?'); $u->execute([$threadId]);
    // audit log: admin replied
    logAction($pdo, $_SESSION['user']['id'], 'chat_reply', ['thread_id' => $threadId, 'message_preview' => mb_substr($msg,0,120)]);
    echo json_encode(['status'=>'ok']); exit;
    }
    echo json_encode(['status'=>'error']); exit;
}

// Load threads for admin view
$threads = $pdo->query('SELECT ct.*, u.name as assigned_admin_name FROM chat_threads ct LEFT JOIN users u ON ct.assigned_admin_id = u.id ORDER BY ct.last_activity DESC LIMIT 100')->fetchAll(PDO::FETCH_ASSOC);

?>
<div class="roles-page">
  <div class="page-header"><h1><i class="bx bxs-message-dots"></i> Chat Support</h1></div>
  <div class="card">
    <h3>Open Threads</h3>
    <ul id="threadList" style="list-style:none;padding:0;margin:0">
      <?php foreach($threads as $t): ?>
        <li data-thread="<?= $t['id'] ?>" style="padding:12px;border-bottom:1px solid #eee;display:flex;justify-content:space-between;align-items:center">
          <div>
            <strong>#<?= htmlspecialchars($t['id']) ?></strong> <?= htmlspecialchars($t['visitor_name']?:'Guest') ?>
            <div style="font-size:0.9rem;color:#666">Last: <?= htmlspecialchars($t['last_activity']) ?> Assigned: <?= htmlspecialchars($t['assigned_admin_name']?:'—') ?></div>
          </div>
          <div>
            <?php if (empty($t['assigned_admin_id'])): ?>
              <button class="btn" onclick="claim(<?= $t['id'] ?>)">Claim</button>
            <?php else: ?>
              <a class="btn" href="?pages=chat_view&thread_id=<?= $t['id'] ?>">Open</a>
            <?php endif; ?>
          </div>
        </li>
      <?php endforeach; ?>
    </ul>
  </div>
</div>

<script>
function claim(id){ if(!confirm('Take this thread?')) return; var fd=new FormData(); fd.append('action','claim'); fd.append('thread_id',id); fd.append('_csrf','<?= generateToken('chat_form') ?>'); var xhr=new XMLHttpRequest(); xhr.open('POST',location.href,true); xhr.setRequestHeader('X-Requested-With','XMLHttpRequest'); xhr.onload=function(){ try{ var r=JSON.parse(xhr.responseText);}catch(e){alert('Error');return;} if(r.status==='ok') location.reload(); else alert('Taken'); }; xhr.send(fd); }

// Polling: refresh thread list every 5 seconds
setInterval(function(){
  var xhr = new XMLHttpRequest(); xhr.open('GET', location.pathname + '?pages=chat&ajax=1&_=' + Date.now(), true);
  xhr.onload = function(){ if (xhr.status !== 200) return; try{ var html = xhr.responseText; } catch(e){ return; } // replace thread list
    var parser = new DOMParser(); var doc = parser.parseFromString(html, 'text/html'); var newList = doc.getElementById('threadList'); if(newList){ document.getElementById('threadList').innerHTML = newList.innerHTML; }
  };
  xhr.send();
}, 5000);
</script>

<?php require_once __DIR__ . '/../includes/footer.php';
