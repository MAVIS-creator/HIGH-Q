<?php
// admin./pages/chat.php
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
    $messageId = $pdo->lastInsertId();
    // Handle attachments if sent with admin reply (allow images + pdf/docx)
    if (!empty($_FILES['attachments'])) {
      $files = $_FILES['attachments'];
      $allowed = ['image/jpeg','image/png','image/gif','image/webp','application/pdf','application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
      $uploadDir = __DIR__ . '/../../public/uploads/chat/';
      if (!is_dir($uploadDir)) @mkdir($uploadDir, 0755, true);
      $maxBytes = 100 * 1024 * 1024; // 100 MB
      for ($i = 0; $i < count($files['name']); $i++) {
        if ($files['error'][$i] !== UPLOAD_ERR_OK) continue;
        $size = $files['size'][$i] ?? 0; if ($size > $maxBytes) continue;
        $type = $files['type'][$i] ?? '';
        if (!in_array($type, $allowed, true)) continue;
        $tmp = $files['tmp_name'][$i] ?? null; if (!$tmp || !is_uploaded_file($tmp)) continue;

        // magic bytes
        $fh = fopen($tmp, 'rb'); $magic = $fh ? fread($fh, 8) : ''; if ($fh) fclose($fh);
        $valid = false;
        if (strpos($type, 'image/') === 0) {
          if (@getimagesize($tmp) !== false) $valid = true;
        } elseif ($type === 'application/pdf') {
          if (substr($magic, 0, 4) === '%PDF') $valid = true;
        } elseif ($type === 'application/vnd.openxmlformats-officedocument.wordprocessingml.document') {
          if (substr($magic, 0, 2) === "PK") $valid = true;
        }
        if (!$valid) continue;

        $ext = pathinfo($files['name'][$i], PATHINFO_EXTENSION);
        $nameSafe = bin2hex(random_bytes(8)) . '.' . $ext;
        $dest = $uploadDir . $nameSafe;
        if (move_uploaded_file($files['tmp_name'][$i], $dest)) {
          $url = 'uploads/chat/' . $nameSafe;
          try {
            $att = $pdo->prepare('INSERT INTO chat_attachments (message_id, file_url, original_name, mime_type, created_at) VALUES (?, ?, ?, ?, NOW())');
            $att->execute([$messageId, $url, $files['name'][$i], $type]);
          } catch (Throwable $_) {
            // fallback: append to message body if chat_attachments doesn't exist
            if (strpos($type, 'image/') === 0) {
              $updHtml = '<br><img src="' . $url . '" style="max-width:100%;border-radius:8px">';
            } else {
              $downloadPath = (isset($HQ_BASE_URL) ? rtrim($HQ_BASE_URL, '/') : '') . '/public/download_attachment.php?file=' . urlencode($nameSafe);
              $updHtml = '<br><a href="' . htmlspecialchars($downloadPath) . '" target="_blank">' . htmlspecialchars($files['name'][$i]) . '</a>';
            }
            $upd = $pdo->prepare('UPDATE chat_messages SET message = CONCAT(message, ?) WHERE id = ?');
            $upd->execute([$updHtml, $messageId]);
          }
        }
      }
    }
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
  xhr.open('POST', window.adminUrl('chat'), true);
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
    // prefer hqFetchCompat which returns a Response-like object when available
    var res = null;
    if (typeof window.hqFetchCompat === 'function') {
  res = await window.hqFetchCompat('api/threads.php');
      // hqFetchCompat wraps parsed result under _parsed when using hqFetch; handle both shapes
      var j = res && res._parsed ? res._parsed : (await (res.json ? res.json() : Promise.resolve(res)));
    } else {
  res = await fetch('api/threads.php');
      if(!res.ok) return;
      var j = await res.json();
    }
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
