<?php
// admin/pages/chat.php
require_once __DIR__ . '/../includes/db.php';      // Load DB connection first
require_once __DIR__ . '/../includes/auth.php';    // Then auth
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/functions.php';

requirePermission('chat');

ini_set('display_errors', 1);
error_reporting(E_ALL);

// Claim thread (AJAX): handle XHR POSTs before any HTML header is output so we can return pure JSON
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
  $token = $_POST['_csrf'] ?? '';
  if (!verifyToken('chat_form', $token)) { echo json_encode(['status'=>'error','message'=>'Invalid CSRF']); exit; }
  $action = $_POST['action'] ?? '';
  $threadId = intval($_POST['thread_id'] ?? 0);
  if ($action === 'claim' && $threadId) {
    // attempt to set assigned_admin_id where null
    $stmt = $pdo->prepare('UPDATE chat_threads SET assigned_admin_id = ? WHERE id = ? AND assigned_admin_id IS NULL');
    $ok = $stmt->execute([$_SESSION['user']['id'], $threadId]);
    if ($stmt->rowCount() > 0) {
      // audit log: admin claimed thread
      logAction($pdo, $_SESSION['user']['id'], 'chat_claimed', ['thread_id' => $threadId]);
      echo json_encode(['status'=>'ok','message'=>'Claimed']);
    } else {
      // already claimed, return current assignment and admin name
      $q = $pdo->prepare('SELECT assigned_admin_id, u.name as assigned_admin_name FROM chat_threads ct LEFT JOIN users u ON ct.assigned_admin_id = u.id WHERE ct.id = ? LIMIT 1');
      $q->execute([$threadId]);
      $row = $q->fetch(PDO::FETCH_ASSOC);
      echo json_encode(['status'=>'taken','assigned_admin_id'=>$row['assigned_admin_id'] ?? null, 'assigned_admin_name' => $row['assigned_admin_name'] ?? null]);
    }
    exit;
  }
  if ($action === 'reply' && $threadId) {
    $msg = trim($_POST['message'] ?? '');
    if ($msg === '') { echo json_encode(['status'=>'error','message'=>'Empty']); exit; }
    $ins = $pdo->prepare('INSERT INTO chat_messages (thread_id, sender_id, sender_name, message, is_from_staff, created_at) VALUES (?, ?, ?, ?, 1, NOW())');
    $ins->execute([$threadId, $_SESSION['user']['id'], $_SESSION['user']['name'], $msg]);
    // update thread last_activity
    $u = $pdo->prepare('UPDATE chat_threads SET last_activity = NOW() WHERE id = ?'); $u->execute([$threadId]);
    // audit log: admin replied
    logAction($pdo, $_SESSION['user']['id'], 'chat_reply', ['thread_id' => $threadId, 'message_preview' => mb_substr($msg,0,120)]);
    echo json_encode(['status'=>'ok']); exit;
  }
  // Close thread (AJAX)
  if ($action === 'close' && $threadId) {
    $upd = $pdo->prepare("UPDATE chat_threads SET status = 'closed', last_activity = NOW() WHERE id = ?");
    $upd->execute([$threadId]);
    logAction($pdo, $_SESSION['user']['id'], 'chat_closed', ['thread_id' => $threadId]);
    echo json_encode(['status'=>'ok']); exit;
  }
  echo json_encode(['status'=>'error']); exit;
}

$pageTitle = 'Chat Support';
require_once __DIR__ . '/../includes/header.php';

// Load threads for admin view
$threads = $pdo->query(
  "SELECT ct.*, u.name as assigned_admin_name
   FROM chat_threads ct
   LEFT JOIN users u ON ct.assigned_admin_id = u.id
   WHERE ct.status != 'closed'
   ORDER BY ct.last_activity DESC
   LIMIT 100"
)->fetchAll(PDO::FETCH_ASSOC);


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
<!-- Include SweetAlert2 once -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
function claim(id){
  Swal.fire({
    title: 'Take this thread?',
    text: "You will become the assigned admin for this conversation.",
    icon: 'question',
    showCancelButton: true,
    confirmButtonText: 'Yes, claim it',
    cancelButtonText: 'Cancel'
  }).then((result) => {
    if (!result.isConfirmed) return;

    var fd = new FormData();
    fd.append('action','claim');
    fd.append('thread_id',id);
    fd.append('_csrf','<?= generateToken('chat_form') ?>');

  var xhr=new XMLHttpRequest();
  xhr.open('POST','../index.php?pages=chat',true);
  xhr.setRequestHeader('X-Requested-With','XMLHttpRequest');

    xhr.onload=function(){
      try{ var r=JSON.parse(xhr.responseText);}catch(e){
        Swal.fire('Error','Invalid server response.','error');
        return;
      }

      if(r.status==='ok'){
        Swal.fire('Claimed!','You are now assigned to this thread.','success');
        // update button to Open without reloading
        const li = document.querySelector('li[data-thread="'+id+'"]');
        if(li){
          const right = li.querySelector('div:last-child');
          right.innerHTML = '<a class="btn" href="?pages=chat_view&thread_id='+id+'">Open</a>';
        }
      }
      else if(r.status==='taken'){
        Swal.fire('Already Taken','This thread was claimed by ' + (r.assigned_admin_name || 'another admin') + '.','warning');
        // update UI to show Open link
        const li = document.querySelector('li[data-thread="'+id+'"]');
        if(li){
          const right = li.querySelector('div:last-child');
          right.innerHTML = '<a class="btn" href="?pages=chat_view&thread_id='+id+'">Open</a>';
        }
      }
      else {
        Swal.fire('Failed','Could not claim the thread.','error');
      }
    };
    xhr.send(fd);
  });
}

// Polling: use lightweight JSON API every 5 seconds
async function pollThreads(){
  try{
    const res = await fetch('/HIGH-Q/admin/api/threads.php');
    if(!res.ok) return;
    const j = await res.json();
    if(!j.threads) return;
    const ul = document.getElementById('threadList');
    if(!ul) return;

    // Rebuild list
    ul.innerHTML = '';
    j.threads.forEach(t=>{
      const li = document.createElement('li');
      li.dataset.thread = t.id;
      li.style.padding='12px';
      li.style.borderBottom='1px solid #eee';
      li.style.display='flex';
      li.style.justifyContent='space-between';
      li.style.alignItems='center';

      const left = document.createElement('div');
      left.innerHTML = `<strong>#${t.id}</strong> ${t.visitor_name}<div style="font-size:0.9rem;color:#666">Last: ${t.last_activity} Assigned: ${t.assigned_admin_id? t.assigned_admin_id : '—'}</div>`;

      const right = document.createElement('div');
      if(!t.assigned_admin_id){
        const btn = document.createElement('button');
        btn.className='btn';
        btn.textContent='Claim';
        btn.onclick = ()=>{ claim(t.id); };
        right.appendChild(btn);
      } else {
        const a = document.createElement('a');
        a.className='btn';
        a.href = `?pages=chat_view&thread_id=${t.id}`;
        a.textContent='Open';
        right.appendChild(a);
      }
      li.appendChild(left);
      li.appendChild(right);
      ul.appendChild(li);
    });
  }catch(e){ /* ignore */ }
}
pollThreads();
setInterval(pollThreads, 5000);
</script>

<?php require_once __DIR__ . '/../includes/footer.php';
