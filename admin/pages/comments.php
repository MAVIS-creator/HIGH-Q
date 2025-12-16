<?php
// admin/pages/comments.php
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
    
    // Check permission
    $hasPermission = checkPermission('comments');
    if (!$hasPermission) {
        http_response_code(403);
        echo json_encode(['status'=>'error','message'=>'Forbidden']);
        exit;
    }

    // AJAX actions: approve, reject, reply
    $token = $_POST['_csrf'] ?? '';
    if (!verifyToken('comments_form', $token)) { echo json_encode(['status'=>'error','message'=>'Invalid CSRF']); exit; }
    $action = $_POST['action'] ?? '';
    $id = intval($_POST['id'] ?? 0);
    if ($action === 'approve') {
    $upd = $pdo->prepare('UPDATE comments SET status = "approved" WHERE id = ?'); $ok = $upd->execute([$id]);
    // debug log
    try { @file_put_contents(__DIR__ . '/../../storage/comments-debug.log', date('c') . " APPROVE id={$id} ok=" . ($ok?1:0) . "\n", FILE_APPEND | LOCK_EX); } catch(Throwable $e) {}
    if ($ok) logAction($pdo, $_SESSION['user']['id'], 'comment_approved', ['comment_id'=>$id]);
    echo json_encode(['status'=>$ok ? 'ok':'error']); exit;
    }
    if ($action === 'reject') {
  $upd = $pdo->prepare('UPDATE comments SET status = "deleted" WHERE id = ?'); $ok = $upd->execute([$id]);
    try { @file_put_contents(__DIR__ . '/../../storage/comments-debug.log', date('c') . " REJECT id={$id} ok=" . ($ok?1:0) . "\n", FILE_APPEND | LOCK_EX); } catch(Throwable $e) {}
    if ($ok) logAction($pdo, $_SESSION['user']['id'], 'comment_deleted', ['comment_id'=>$id]);
    echo json_encode(['status'=>$ok ? 'ok':'error']); exit;
    }

  // Permanent destroy (admin-only hard delete) - action 'destroy'
  if ($action === 'destroy') {
    $del = $pdo->prepare('DELETE FROM comments WHERE id = ?');
    $ok = $del->execute([$id]);
    if ($ok) logAction($pdo, $_SESSION['user']['id'], 'comment_destroyed', ['comment_id'=>$id]);
    echo json_encode(['status'=>'ok']); exit;
  }

    // Admin reply action: insert a new comment row as a reply and mark parent admin_reply_by
    if ($action === 'reply') {
        $replyContent = trim($_POST['content'] ?? '');
        if ($id <= 0 || $replyContent === '') { echo json_encode(['status'=>'error','message'=>'Missing fields']); exit; }

        // Fetch parent to get post_id
        $pstmt = $pdo->prepare('SELECT post_id FROM comments WHERE id = ? LIMIT 1');
        $pstmt->execute([$id]);
        $parent = $pstmt->fetch(PDO::FETCH_ASSOC);
        if (!$parent) { echo json_encode(['status'=>'error','message'=>'Parent not found']); exit; }

        $adminId = $_SESSION['user']['id'];
        $adminName = $_SESSION['user']['name'] ?? '';

        // Insert reply as approved and link by parent_id
        $ins = $pdo->prepare('INSERT INTO comments (post_id, parent_id, user_id, name, email, content, status, created_at) VALUES (?, ?, ?, ?, NULL, ?, "approved", NOW())');
        $ok = $ins->execute([$parent['post_id'], $id, $adminId, $adminName, $replyContent]);
        if ($ok) {
            // mark that parent received an admin reply (optional meta column)
            try { $upd = $pdo->prepare('UPDATE comments SET admin_reply_by = ? WHERE id = ?'); $upd->execute([$adminId, $id]); } catch (Exception $e) {}
            logAction($pdo, $adminId, 'comment_replied', ['parent_id'=>$id]);
      try { @file_put_contents(__DIR__ . '/../../storage/comments-debug.log', date('c') . " REPLY parent={$id} new_ok=1 admin={$adminId}\n", FILE_APPEND | LOCK_EX); } catch(Throwable $e) {}
        }
        echo json_encode(['status'=>$ok ? 'ok':'error']); exit;
    }

    echo json_encode(['status'=>'error','message'=>'Invalid action']); exit;
}

// Normal HTML page flow - load requirements if not already loaded
if (!defined('DB_HOST')) {
    require_once __DIR__ . '/../includes/db.php';
    require_once __DIR__ . '/../includes/auth.php';
    require_once __DIR__ . '/../includes/csrf.php';
    require_once __DIR__ . '/../includes/functions.php';
    requirePermission('comments');
}

$perPage = 30; 
$page = max(1,(int)($_GET['page']??1)); 
$offset = ($page-1)*$perPage;

try {
    $stmt = $pdo->prepare('SELECT * FROM comments ORDER BY created_at DESC LIMIT ? OFFSET ?'); 
    $stmt->bindValue(1, $perPage, PDO::PARAM_INT); 
    $stmt->bindValue(2, $offset, PDO::PARAM_INT); 
    $stmt->execute();
    $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Comments query error: " . $e->getMessage());
    $comments = [];
}

// If requested via AJAX fragment (polling), return only the tbody rows BEFORE header
if (!empty($_GET['ajax'])) {
  foreach($comments as $c){
    echo "<tr>";
    echo "<td>".htmlspecialchars($c['id'])."</td>";
    echo "<td>".htmlspecialchars($c['post_id'])."</td>";
    echo "<td>".htmlspecialchars(($c['name']?:'').' / '.($c['email']?:''))."</td>";
    echo "<td>".htmlspecialchars(mb_strimwidth($c['content'],0,180,'...'))."</td>";
    echo "<td>".htmlspecialchars($c['status'])."</td>";
    echo "<td>".htmlspecialchars($c['created_at'])."</td>";

    // build action buttons safely using json_encode for the preview string
    $preview = htmlspecialchars(mb_strimwidth($c['content'],0,120,'...'), ENT_QUOTES);
    // use data-action buttons to allow delegated handlers
    $replyBtn = "<button class='btn' data-action='reply' data-id='{$c['id']}' data-preview='{$preview}'>Reply</button>";
    if ($c['status'] === 'pending') {
      $actions = "<button class='btn' data-action='approve' data-id='{$c['id']}'>Approve</button> <button class='btn' data-action='reject' data-id='{$c['id']}'>Delete</button> <button class='btn' data-action='destroy' data-id='{$c['id']}'>Destroy</button> " . $replyBtn;
    } else {
      $actions = "<button class='btn' data-action='destroy' data-id='{$c['id']}'>Destroy</button> " . $replyBtn;
    }

    echo "<td>" . $actions . "</td>";
    echo "</tr>";

    // print any replies for this comment
    $rstmt = $pdo->prepare('SELECT * FROM comments WHERE parent_id = ? ORDER BY created_at ASC');
    $rstmt->execute([$c['id']]);
    $replies = $rstmt->fetchAll(PDO::FETCH_ASSOC);
    foreach($replies as $rep) {
      $displayName = ($rep['user_id'] ? 'Admin - ' . ($rep['name']?:'') : ($rep['name']?:''));
      echo "<tr class='reply-row'>";
      echo "<td>".htmlspecialchars($rep['id'])."</td>";
      echo "<td>".htmlspecialchars($rep['post_id'])."</td>";
      echo "<td>".htmlspecialchars($displayName)."</td>";
      echo "<td>".htmlspecialchars(mb_strimwidth($rep['content'],0,180,'...'))."</td>";
      echo "<td>".htmlspecialchars($rep['status'])."</td>";
      echo "<td>".htmlspecialchars($rep['created_at'])."</td>";
      echo "<td>&mdash;</td>";
      echo "</tr>";
    }
  }
  exit;
}

// Load header AFTER all AJAX handling
$pageTitle = 'Comments';
require_once __DIR__ . '/../includes/header.php';

?>
<div class="roles-page">
  <div class="page-header"><h1><i class="bx bxs-comment-detail"></i> Comments</h1></div>
  <!-- CSRF token for AJAX actions; include name so various scripts can select it reliably -->
  <input type="hidden" id="comments_csrf" name="_csrf" value="<?= generateToken('comments_form') ?>">
  
  <?php if (empty($comments)): ?>
    <div class="card">
      <p style="text-align: center; padding: 20px; color: #666;">
        <i class="bx bx-info-circle" style="font-size: 48px; display: block; margin-bottom: 10px;"></i>
        No comments found. Comments will appear here once visitors start commenting on your posts.
      </p>
    </div>
  <?php else: ?>
  
  <table class="roles-table">
    <thead><tr><th>ID</th><th>Post</th><th>Name/Email</th><th>Content</th><th>Status</th><th>Created</th><th>Actions</th></tr></thead>
    <tbody>
    <?php foreach($comments as $c): ?>
        <tr>
          <td><?= htmlspecialchars($c['id']) ?></td>
          <td><?= htmlspecialchars($c['post_id']) ?></td>
          <td><?= htmlspecialchars(($c['name']?:''). ' / ' . ($c['email']?:'')) ?></td>
          <td><?= htmlspecialchars(mb_strimwidth($c['content'],0,180,'...')) ?></td>
          <td><?= htmlspecialchars($c['status']) ?></td>
          <td><?= htmlspecialchars($c['created_at']) ?></td>
          <td><?php if ($c['status'] === 'pending'): ?>
              <button class="btn" data-action="approve" data-id="<?= $c['id'] ?>">Approve</button>
              <button class="btn" data-action="reject" data-id="<?= $c['id'] ?>">Delete</button>
              <button class="btn" data-action="destroy" data-id="<?= $c['id'] ?>">Destroy</button>
              <button class="btn" data-action="reply" data-id="<?= $c['id'] ?>" data-preview="<?= htmlspecialchars(mb_strimwidth($c['content'],0,120,'...'), ENT_QUOTES) ?>">Reply</button>
            <?php else: ?>
              <button class="btn" data-action="destroy" data-id="<?= $c['id'] ?>">Destroy</button>
              <button class="btn" data-action="reply" data-id="<?= $c['id'] ?>" data-preview="<?= htmlspecialchars(mb_strimwidth($c['content'],0,120,'...'), ENT_QUOTES) ?>">Reply</button>
            <?php endif; ?></td>
        </tr>
        <?php
          // fetch and display replies inline under parent
          $rstmt = $pdo->prepare('SELECT * FROM comments WHERE parent_id = ? ORDER BY created_at ASC');
          $rstmt->execute([$c['id']]);
          $replies = $rstmt->fetchAll(PDO::FETCH_ASSOC);
          foreach($replies as $rep):
            $displayName = ($rep['user_id'] ? 'Admin - ' . ($rep['name']?:'') : ($rep['name']?:''));
        ?>
        <tr class="reply-row">
          <td><?= htmlspecialchars($rep['id']) ?></td>
          <td><?= htmlspecialchars($rep['post_id']) ?></td>
          <td><?= htmlspecialchars($displayName) ?></td>
          <td><?= htmlspecialchars(mb_strimwidth($rep['content'],0,180,'...')) ?></td>
          <td><?= htmlspecialchars($rep['status']) ?></td>
          <td><?= htmlspecialchars($rep['created_at']) ?></td>
          <td>&mdash;</td>
        </tr>
        <?php endforeach; ?>
      <?php endforeach; ?>
    </tbody>
  </table>
  <?php endif; ?>
</div>

<!-- Include SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
function doAction(action,id){
  var token = document.getElementById('comments_csrf').value || '';
  Swal.fire({ title: 'Confirm', text: 'Are you sure?', icon: 'question', showCancelButton: true }).then(function(res){
    if (!res.isConfirmed) return;
  var fd = new FormData(); fd.append('action', action); fd.append('id', id); fd.append('_csrf', token);
  var xhr = new XMLHttpRequest(); 
  xhr.open('POST', (window.HQ_ADMIN_BASE || '') + '/index.php?pages=comments', true); 
  xhr.setRequestHeader('X-Requested-With','XMLHttpRequest');
    xhr.onload = function(){ 
      try{ var r = JSON.parse(xhr.responseText); } catch(e){ 
        console.error('Parse error:', e, xhr.responseText);
        Swal.fire('Error','Invalid server response','error'); 
        return; 
      }
      if (r.status === 'ok') { Swal.fire('Success','Action completed','success').then(()=> location.reload()); }
      else { Swal.fire('Failed', r.message || 'Operation failed', 'error'); }
    };
    xhr.onerror = function(){ Swal.fire('Error','Network error','error'); };
    xhr.send(fd);
  });
}

// Delegated click handler for action buttons (approve/reject/reply)
document.querySelector('table.roles-table').addEventListener('click', function(e){
  var btn = e.target.closest('button[data-action]');
  if (!btn) return;
  var action = btn.getAttribute('data-action');
  var id = btn.getAttribute('data-id');
  if (action === 'approve' || action === 'reject' || action === 'destroy') {
    if (action === 'destroy') {
      Swal.fire({ 
        title: 'Permanently remove?', 
        text: 'This will permanently delete the comment.', 
        icon: 'warning', 
        showCancelButton: true, 
        confirmButtonText: 'Yes, delete', 
        confirmButtonColor: '#d33' 
      }).then(function(res){ 
        if (res.isConfirmed) {
          const fd = new FormData();
          fd.append('action', action);
          fd.append('id', id);
          fd.append('_csrf', document.getElementById('comments_csrf').value);
          
          fetch((window.HQ_ADMIN_BASE || '') + '/index.php?pages=comments', {
            method: 'POST',
            body: fd,
            headers: {
              'X-Requested-With': 'XMLHttpRequest'
            }
          })
          .then(response => response.json())
          .then(data => {
            if (data.status === 'ok') {
              Swal.fire('Deleted!', 'Comment has been deleted.', 'success')
              .then(() => location.reload());
            } else {
              throw new Error(data.message || 'Delete failed');
            }
          })
          .catch(error => {
            console.error('Fetch error:', error);
            Swal.fire('Error!', error.message || 'Something went wrong', 'error');
          });
        }
      });
    } else {
      doAction(action, id);
    }
    return;
  }
  if (action === 'reply') {
    var preview = btn.getAttribute('data-preview') || '';
    currentReplyParent = parseInt(id,10);
    document.getElementById('replyPreview').textContent = preview;
    document.getElementById('replyContent').value = '';
    document.getElementById('replyModal').classList.add('open');
    document.getElementById('replyOverlay').classList.add('open');
    return;
  }
});
// Poll the comments fragment every 5 seconds
setInterval(function(){
  var xhr = new XMLHttpRequest(); 
  xhr.open('GET', (window.HQ_ADMIN_BASE || '') + '/index.php?pages=comments&ajax=1&_=' + Date.now(), true);
  xhr.onload = function(){
    if (xhr.status !== 200) return;
    // basic check: if response looks like a table fragment (starts with <tr) then replace tbody
    var txt = xhr.responseText || '';
    if (txt.trim().startsWith('<tr')) {
      var tbody = document.querySelector('table.roles-table tbody');
      if (tbody) tbody.innerHTML = txt;
    } else {
      // ignore unexpected responses to avoid rendering full page inside the table
      console.warn('Ignored unexpected comments fragment during polling');
    }
  };
  xhr.onerror = function(){ /* ignore network errors during polling */ };
  xhr.send();
}, 5000);
</script>

<!-- Reply modal -->
<div id="replyModal" class="modal">
  <div class="modal-content">
    <span class="modal-close" id="replyModalClose"><i class='bx bx-x'></i></span>
    <h3>Reply to comment</h3>
    <p id="replyPreview" class="muted"></p>
    <textarea id="replyContent" rows="5" style="width:100%;padding:8px;border-radius:8px;border:1px solid #ddd;"></textarea>
    <div style="margin-top:8px;text-align:right;">
      <button id="replySend" class="btn-approve">Send Reply</button>
    </div>
  </div>
</div>
<div id="replyOverlay"></div>

<style>
.reply-row td { padding-left: 36px; background: #fbfbfb; }
.modal { display:none; position:fixed; inset:0; z-index:1200; align-items:center; justify-content:center; }
.modal.open { display:flex; }
.modal .modal-content { background:#fff; padding:18px; border-radius:8px; width:720px; max-width:92%; box-shadow:0 6px 30px rgba(0,0,0,.12);} 
#replyOverlay { display:none; position:fixed; inset:0; background:rgba(0,0,0,.45); z-index:1199; }
#replyOverlay.open { display:block; }
</style>

<script>
var currentReplyParent = null;
function openReply(id, preview) {
  currentReplyParent = id;
  document.getElementById('replyPreview').textContent = preview || '';
  document.getElementById('replyContent').value = '';
  document.getElementById('replyModal').classList.add('open');
  document.getElementById('replyOverlay').classList.add('open');
}
document.getElementById('replyModalClose').addEventListener('click', function(){ document.getElementById('replyModal').classList.remove('open'); document.getElementById('replyOverlay').classList.remove('open'); });
document.getElementById('replyOverlay').addEventListener('click', function(){ document.getElementById('replyModal').classList.remove('open'); document.getElementById('replyOverlay').classList.remove('open'); });

document.getElementById('replySend').addEventListener('click', function(){
  var content = document.getElementById('replyContent').value.trim();
  if (!content) { Swal.fire('Error','Please enter a reply','error'); return; }
  var fd = new FormData();
  fd.append('action', 'reply');
  fd.append('id', currentReplyParent);
  fd.append('content', content);
  fd.append('_csrf', document.getElementById('comments_csrf').value);
  var xhr = new XMLHttpRequest();
  xhr.open('POST', (window.HQ_ADMIN_BASE || '') + '/index.php?pages=comments', true);
  xhr.setRequestHeader('X-Requested-With','XMLHttpRequest');
  xhr.onload = function(){ 
    try{ var r = JSON.parse(xhr.responseText); } catch(e){ 
      console.error('Parse error:', e, xhr.responseText);
      Swal.fire('Error','Invalid server response','error'); 
      return; 
    } 
    if (r.status==='ok') { 
      Swal.fire('Success', 'Reply sent', 'success').then(() => location.reload()); 
    } else { 
      Swal.fire('Failed', r.message || 'Failed to send reply', 'error'); 
    } 
  };
  xhr.onerror = function(){ Swal.fire('Error','Network error','error'); };
  xhr.send(fd);
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php';
