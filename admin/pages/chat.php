<?php
// admin/pages/chat.php
// Handle AJAX requests FIRST before any includes to prevent HTML output
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
    header('Content-Type: application/json');
    header('Cache-Control: no-cache, must-revalidate');
    
    require_once __DIR__ . '/../includes/db.php';
    require_once __DIR__ . '/../includes/auth.php';
    require_once __DIR__ . '/../includes/csrf.php';
    require_once __DIR__ . '/../includes/functions.php';
    
    if (empty($_SESSION['user'])) {
        http_response_code(401);
        echo json_encode(['status'=>'error','message'=>'Unauthenticated']);
        exit;
    }
    
    // Check permission - use try/catch to handle permission check gracefully
    try {
        requirePermission('chat');
    } catch (Exception $e) {
        http_response_code(403);
        echo json_encode(['status'=>'error','message'=>'Forbidden']);
        exit;
    }

    // Claim thread (AJAX): handle XHR POSTs and return pure JSON
  $token = $_POST['_csrf'] ?? '';
  if (!verifyToken('chat_form', $token)) { echo json_encode(['status'=>'error','message'=>'Invalid CSRF']); exit; }
  $action = $_POST['action'] ?? '';
  $threadId = intval($_POST['thread_id'] ?? 0);
  if ($action === 'claim' && $threadId) {
    // attempt to set assigned_admin_id where null
    $stmt = $pdo->prepare('UPDATE chat_threads SET assigned_admin_id = ? WHERE id = ? AND (assigned_admin_id IS NULL OR assigned_admin_id = 0)');
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
              $downloadLink = app_url('public/download_attachment.php?file=' . urlencode($nameSafe));
              $updHtml = '<br><a href="' . htmlspecialchars($downloadLink) . '" target="_blank">' . htmlspecialchars($files['name'][$i]) . '</a>';
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
  echo json_encode(['status'=>'error','message'=>'Invalid action']); exit;
}

// Normal HTML page flow - load requirements if not already loaded
if (!defined('DB_HOST')) {
    require_once __DIR__ . '/../includes/db.php';
    require_once __DIR__ . '/../includes/auth.php';
    require_once __DIR__ . '/../includes/csrf.php';
    require_once __DIR__ . '/../includes/functions.php';
    requirePermission('chat');
}

ini_set('display_errors', 1);
error_reporting(E_ALL);

$pageTitle = 'Chat Support';
$skipMainClose = true;
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';

// Pagination
$perPage = 12;
$page = max(1, (int)($_GET['page'] ?? 1));
$offset = ($page - 1) * $perPage;
$totalThreads = 0;
$totalPages = 1;
try {
  $totalThreads = (int)$pdo->query("SELECT COUNT(*) FROM chat_threads WHERE status != 'closed'")->fetchColumn();
  $totalPages = max(1, (int)ceil($totalThreads / $perPage));
  if ($page > $totalPages) {
    $page = $totalPages;
    $offset = ($page - 1) * $perPage;
  }
} catch (Throwable $e) {
  $totalThreads = 0;
  $totalPages = 1;
}

// Load threads for admin view
$stmt = $pdo->prepare(
  "SELECT ct.*, u.name as assigned_admin_name
   FROM chat_threads ct
   LEFT JOIN users u ON ct.assigned_admin_id = u.id
   WHERE ct.status != 'closed'
   ORDER BY ct.last_activity DESC
   LIMIT ? OFFSET ?"
);
$stmt->bindValue(1, (int)$perPage, PDO::PARAM_INT);
$stmt->bindValue(2, (int)$offset, PDO::PARAM_INT);
$stmt->execute();
$threads = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<main class="main-content" style="padding: 2rem; max-width: 1600px; margin: 0 auto;">
<div class="chat-page">
  <div class="page-header" style="background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%); padding: 2.5rem; border-radius: 1rem; margin-bottom: 2.5rem; color: #1e293b; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 8px 24px rgba(251, 191, 36, 0.25);">
    <div>
      <h1 style="font-size: 2.5rem; font-weight: 800; margin: 0 0 0.5rem 0; display: flex; align-items: center; gap: 12px;"><i class='bx bxs-message-dots' style="font-size: 2.5rem;"></i> Chat Support</h1>
      <p style="font-size: 1.1rem; opacity: 0.85; margin: 0;">Manage customer support conversations</p>
    </div>
    <div style="text-align: right;">
      <div style="font-size: 3rem; font-weight: 800; color: #1e293b;"><?= (int)$totalThreads ?></div>
      <div style="font-size: 0.9rem; color: #1e293b; opacity: 0.85;">Active Threads</div>
    </div>
  </div>
  <div class="card">
    <h3>Open Threads</h3>
    <div id="threadList" class="chat-threads">
      <?php foreach($threads as $t): ?>
        <div class="thread-card" data-thread="<?= $t['id'] ?>">
          <div class="thread-header">
            <div class="thread-id"><i class='bx bxs-conversation'></i> <?= htmlspecialchars($t['id']) ?></div>
            <div class="thread-visitor">
              <strong><?= htmlspecialchars($t['visitor_name']?:'Guest') ?></strong>
              <div class="visitor-label"><i class='bx bxs-user'></i> Customer</div>
            </div>
          </div>
          <div class="thread-status">
            <?php if (empty($t['assigned_admin_id'])): ?>
              <span class="badge badge-unassigned"><i class='bx bxs-hourglass'></i> Unassigned</span>
            <?php else: ?>
              <span class="badge badge-assigned"><i class='bx bxs-check-circle'></i> Assigned</span>
            <?php endif; ?>
          </div>
          <div class="thread-meta">
            <div><strong>Last Activity:</strong><br><small><?= htmlspecialchars($t['last_activity']) ?></small></div>
            <div style="margin-top:0.5rem;"><strong>Assigned to:</strong><br><small><?= htmlspecialchars($t['assigned_admin_name']?:'No admin') ?></small></div>
          </div>
          <div class="thread-actions">
            <?php if (empty($t['assigned_admin_id'])): ?>
              <button class="btn btn-claim" onclick="claim(<?= $t['id'] ?>)"><i class='bx bxs-hand'></i> Claim</button>
            <?php else: ?>
              <a class="btn btn-open" href="?pages=chat_view&thread_id=<?= $t['id'] ?>"><i class='bx bxs-message'></i> Open</a>
            <?php endif; ?>
          </div>
        </div>
      <?php endforeach; ?>
    </div>

    <?php if ($totalPages > 1): ?>
      <?php
        $qp = $_GET;
        $qp['pages'] = $qp['pages'] ?? 'chat';
        $makeLink = function($pnum) use ($qp) {
          $qp['page'] = $pnum;
          return 'index.php?' . http_build_query($qp);
        };
        $window = 2;
        $start = max(1, $page - $window);
        $end = min($totalPages, $page + $window);
      ?>
      <nav aria-label="Chat pagination" style="margin-top:16px;display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
        <?php if ($page > 1): ?>
          <a class="btn" href="<?= htmlspecialchars($makeLink($page - 1)) ?>">Prev</a>
        <?php endif; ?>

        <?php if ($start > 1): ?>
          <a class="btn" href="<?= htmlspecialchars($makeLink(1)) ?>">1</a>
          <?php if ($start > 2): ?><span style="padding:6px 8px;color:#666">&hellip;</span><?php endif; ?>
        <?php endif; ?>

        <?php for ($i = $start; $i <= $end; $i++): ?>
          <?php if ($i == $page): ?>
            <span style="padding:6px 10px;background:#111;color:#fff;border-radius:4px"><?= (int)$i ?></span>
          <?php else: ?>
            <a class="btn" href="<?= htmlspecialchars($makeLink($i)) ?>"><?= (int)$i ?></a>
          <?php endif; ?>
        <?php endfor; ?>

        <?php if ($end < $totalPages): ?>
          <?php if ($end < $totalPages - 1): ?><span style="padding:6px 8px;color:#666">&hellip;</span><?php endif; ?>
          <a class="btn" href="<?= htmlspecialchars($makeLink($totalPages)) ?>"><?= (int)$totalPages ?></a>
        <?php endif; ?>

        <?php if ($page < $totalPages): ?>
          <a class="btn" href="<?= htmlspecialchars($makeLink($page + 1)) ?>">Next</a>
        <?php endif; ?>
      </nav>
    <?php endif; ?>
  </div>
</div>
<!-- Include SweetAlert2 once -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
const ADMIN_BASE = (function(){
  const raw = window.HQ_ADMIN_BASE || '';
  const path = window.HQ_ADMIN_PATH || '';
  try {
    if (!raw) return window.location.origin + path;
    const u = new URL(raw, window.location.origin);
    if (u.origin !== window.location.origin) return window.location.origin + (u.pathname ? u.pathname.replace(/\/$/, '') : path);
    return u.origin + u.pathname.replace(/\/$/, '');
  } catch(e){
    return window.location.origin + path;
  }
})();

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
  xhr.open('POST', ADMIN_BASE + '/index.php?pages=chat',true);
  xhr.setRequestHeader('X-Requested-With','XMLHttpRequest');
  xhr.setRequestHeader('Accept','application/json');

    xhr.onload=function(){
      try{ var r=JSON.parse(xhr.responseText);}catch(e){
        Swal.fire('Error','Invalid server response.','error');
        return;
      }

        if(r.status==='ok'){
        Swal.fire('Claimed!','You are now assigned to this thread.','success');
        // update button to Open without reloading
        const card = document.querySelector('.thread-card[data-thread="'+id+'"]');
        if(card){
          const actions = card.querySelector('.thread-actions');
          if (actions) actions.innerHTML = '<a class="btn btn-open" href="' + ADMIN_BASE + '/index.php?pages=chat_view&thread_id='+id+'"><i class="bx bxs-message"></i> Open</a>';
        }
      }
        else if(r.status==='taken'){
        Swal.fire('Already Taken','This thread was claimed by ' + (r.assigned_admin_name || 'another admin') + '.','warning');
        // update UI to show Open link
        const card = document.querySelector('.thread-card[data-thread="'+id+'"]');
        if(card){
          const actions = card.querySelector('.thread-actions');
          if (actions) actions.innerHTML = '<a class="btn btn-open" href="' + ADMIN_BASE + '/index.php?pages=chat_view&thread_id='+id+'"><i class="bx bxs-message"></i> Open</a>';
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
    const params = new URLSearchParams(window.location.search || '');
    const page = params.get('page') || '1';
    const res = await fetch(ADMIN_BASE + '/api/threads.php?page=' + encodeURIComponent(page));
    if(!res.ok) return;
    const j = await res.json();
    if(!j.threads) return;
    const wrap = document.getElementById('threadList');
    if(!wrap) return;

    // Rebuild grid
    wrap.innerHTML = '';
    j.threads.forEach(t=>{
      const card = document.createElement('div');
      card.className = 'thread-card';
      card.dataset.thread = t.id;

      const assignedName = (t.assigned_admin_name || '').trim();
      const assigned = t.assigned_admin_id ? true : false;

      card.innerHTML = `
        <div class="thread-header">
          <div class="thread-id"><i class='bx bxs-conversation'></i> ${t.id}</div>
          <div class="thread-visitor">
            <strong>${t.visitor_name || 'Guest'}</strong>
            <div class="visitor-label"><i class='bx bxs-user'></i> Customer</div>
          </div>
        </div>
        <div class="thread-status">
          ${assigned ? '<span class="badge badge-assigned"><i class="bx bxs-check-circle"></i> Assigned</span>' : '<span class="badge badge-unassigned"><i class="bx bxs-hourglass"></i> Unassigned</span>'}
        </div>
        <div class="thread-meta">
          <div><strong>Last Activity:</strong><br><small>${t.last_activity || ''}</small></div>
          <div style="margin-top:0.5rem;"><strong>Assigned to:</strong><br><small>${assignedName || (assigned ? ('Admin #' + t.assigned_admin_id) : 'No admin')}</small></div>
        </div>
        <div class="thread-actions"></div>
      `;

      const actions = card.querySelector('.thread-actions');
      if (!assigned) {
        const btn = document.createElement('button');
        btn.className = 'btn btn-claim';
        btn.innerHTML = "<i class='bx bxs-hand'></i> Claim";
        btn.onclick = ()=>{ claim(t.id); };
        actions.appendChild(btn);
      } else {
        const a = document.createElement('a');
        a.className = 'btn btn-open';
        a.href = ADMIN_BASE + '/index.php?pages=chat_view&thread_id=' + t.id;
        a.innerHTML = "<i class='bx bxs-message'></i> Open";
        actions.appendChild(a);
      }

      wrap.appendChild(card);
    });
  }catch(e){ /* ignore */ }
}
pollThreads();
setInterval(pollThreads, 5000);
</script>

<?php require_once __DIR__ . '/../includes/footer.php';
