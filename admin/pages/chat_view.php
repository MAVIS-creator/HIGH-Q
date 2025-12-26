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
<link rel="stylesheet" href="../assets/css/modern-tables.css">
<style>
.chat-thread-wrap{max-width:1100px;margin:0 auto;padding:2rem;}
.chat-thread-header{background:linear-gradient(135deg,#fbbf24 0%,#f59e0b 100%);padding:2rem;border-radius:1rem;margin-bottom:1.5rem;color:#1e293b;display:flex;justify-content:space-between;align-items:center;box-shadow:0 8px 24px rgba(251,191,36,.25)}
.chat-thread-header h1{margin:0;font-size:2rem;font-weight:800;display:flex;align-items:center;gap:12px}
.chat-thread-header .meta{font-size:.95rem;opacity:.85;text-align:right}
.messages-box{max-height:520px;overflow:auto;padding:16px;border:1px solid #e2e8f0;border-radius:12px;background:#fff;margin-bottom:12px}
.msg-row{display:flex;flex-direction:column;margin-bottom:14px}
.msg-meta{font-size:.8rem;color:#64748b;margin-bottom:6px}
.msg-bubble{max-width:80%;padding:12px 14px;border-radius:14px;line-height:1.45;box-shadow:0 2px 10px rgba(0,0,0,.06)}
.from-visitor .msg-bubble{background:#f8fafc;color:#0f172a;border:1px solid #e2e8f0;border-top-left-radius:6px;align-self:flex-start}
.from-staff .msg-bubble{background:#fff7e6;color:#1e293b;border:1px solid rgba(245,158,11,.35);border-top-right-radius:6px;align-self:flex-end}
.chat-reply-form textarea{width:100%;min-height:110px;padding:12px;border-radius:10px;border:1px solid #e2e8f0;resize:vertical}
.chat-reply-actions{display:flex;gap:10px;align-items:center;margin-top:10px}
.chat-reply-actions .btn{padding:10px 14px;border-radius:10px;font-weight:800}
.chat-close-wrap{text-align:right;margin-top:10px}
.chat-close-wrap .btn-close{background:#ef4444;color:#fff;border:none;padding:10px 14px;border-radius:10px;font-weight:800;cursor:pointer}
</style>

<div class="chat-thread-wrap">
  <div class="chat-thread-header">
    <div>
      <h1><i class="bx bxs-message-dots"></i> Thread #<?= htmlspecialchars($threadId) ?></h1>
      <div style="margin-top:6px;font-size:1rem;opacity:.85;">Support conversation view</div>
    </div>
    <div class="meta">
      <div><strong>Assigned:</strong> <?= htmlspecialchars($thread['assigned_admin_name'] ?? '—') ?></div>
      <div><strong>Status:</strong> <?= htmlspecialchars($thread['status'] ?? '') ?></div>
    </div>
  </div>
  <div class="card">
    <div id="messagesBox" class="messages-box">
      <?php foreach($msgs as $m): ?>
        <div class="msg-row <?= $m['is_from_staff'] ? 'from-staff' : 'from-visitor' ?>">
          <div class="msg-meta"><?= htmlspecialchars($m['created_at']) ?> — <?= $m['is_from_staff'] ? htmlspecialchars($m['sender_name']) : htmlspecialchars($m['sender_name']?:'Visitor') ?></div>
          <div class="msg-bubble">
            <?= nl2br(htmlspecialchars($m['message'])) ?>
            <?php
              // If message has inline image tags saved as part of message HTML (older approach), render raw
              // Also check for attachments stored in a separate attachments table (if exists)
              try {
                $attStmt = $pdo->prepare('SELECT id, file_url, original_name, mime_type, created_at FROM chat_attachments WHERE message_id = ?');
                $attStmt->execute([$m['id']]);
                $atts = $attStmt->fetchAll(PDO::FETCH_ASSOC);
                if (!empty($atts)) {
                    echo '<div style="margin-top:8px;display:flex;flex-direction:column;gap:8px;">';
                    foreach ($atts as $att) {
                      $a = $att['file_url'];
                      $downloadUrl = app_url('public/download_attachment.php?file=' . urlencode(basename($a)));
                      $origName = $att['original_name'] ?: basename($a);
                      $mime = $att['mime_type'] ?: '';
                      $created = $att['created_at'] ?? '';
                      // compute file size on disk if available
                      $fsPath = realpath(__DIR__ . '/../../public/' . $a);
                      $sizeHuman = '';
                      if ($fsPath && is_file($fsPath)) {
                        $sz = filesize($fsPath);
                        if ($sz >= 1024*1024) $sizeHuman = round($sz / (1024*1024), 2) . ' MB';
                        elseif ($sz >= 1024) $sizeHuman = round($sz / 1024, 2) . ' KB';
                        else $sizeHuman = $sz . ' B';
                      }

                      $ext = strtolower(pathinfo($a, PATHINFO_EXTENSION));
                      $isImage = in_array($ext, ['jpg','jpeg','png','gif','webp']);

                      echo '<div style="display:flex;gap:8px;align-items:center;padding:8px;border:1px solid #eee;border-radius:8px;background:#fff">';
                      if ($isImage) {
                        // Use app_url() so image URLs include any subdirectory (preserve APP_URL)
                        echo '<a href="' . htmlspecialchars($downloadUrl) . '" target="_blank"><img src="' . htmlspecialchars(app_url($a)) . '" style="max-width:120px;border-radius:6px"></a>';
                      } else {
                        echo '<div style="width:48px;height:48px;display:flex;align-items:center;justify-content:center;border-radius:6px;background:#f5f5f5"><i class="fas fa-file" style="font-size:20px;color:#666"></i></div>';
                      }

                      echo '<div style="flex:1">';
                      echo '<div style="font-weight:600">' . htmlspecialchars($origName) . '</div>';
                      echo '<div style="font-size:0.85rem;color:#666">' . htmlspecialchars($mime) . ' ' . ($sizeHuman ? '• ' . $sizeHuman : '') . ' ' . ($created ? '• ' . htmlspecialchars($created) : '') . '</div>';
                      echo '</div>';

                      echo '<div style="display:flex;gap:6px">';
                      echo '<a class="btn" href="' . htmlspecialchars($downloadUrl) . '" target="_blank" style="padding:6px 8px">Download</a>';
                      echo '<button class="btn-delete-attachment" data-id="' . intval($att['id']) . '" style="background:#f44336;color:#fff;border:none;padding:6px 8px;border-radius:6px;cursor:pointer">Delete</button>';
                      echo '</div>';

                      echo '</div>';
                    }
                    echo '</div>';
                  }
              } catch (Throwable $_) {
                // if chat_attachments does not exist, ignore
              }
            ?>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
    <form id="replyForm" class="chat-reply-form" enctype="multipart/form-data">
      <textarea name="message" placeholder="Write a reply..."></textarea>
      <div class="chat-reply-actions">
        <input type="file" name="attachments[]" multiple>
        <div style="margin-left:auto"><button class="btn" type="submit"><i class='bx bxs-send'></i> Send</button></div>
      </div>
      <input type="hidden" name="_csrf" value="<?= htmlspecialchars(generateToken('chat_form')) ?>">
    </form>
    <div class="chat-close-wrap">
      <button id="closeThreadBtn" class="btn-close" type="button"><i class='bx bxs-lock'></i> Close Thread</button>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.getElementById('replyForm').addEventListener('submit', function(e){
  e.preventDefault();
  var fd=new FormData(this); 
  fd.append('action','reply'); 
  fd.append('thread_id','<?= $threadId ?>');
  var csrfEl = document.querySelector('input[name="_csrf"]'); 
  if (csrfEl) fd.append('_csrf', csrfEl.value);

  var xhr=new XMLHttpRequest(); 
  xhr.open('POST', 'index.php?pages=chat',true);
  xhr.setRequestHeader('X-Requested-With','XMLHttpRequest');
  xhr.onload=function(){ 
    try{var r=JSON.parse(xhr.responseText);}catch(e){ 
      Swal.fire('Error', 'Invalid server response.', 'error'); 
      return; 
    } 
    if(r.status==='ok'){ 
      Swal.fire('Sent!', 'Your reply has been posted.', 'success')
        .then(()=>{ location.reload(); });
    } else {
      Swal.fire('Failed', 'Could not send your reply.', 'error'); 
    } 
  };
  xhr.send(fd);
});

// Poll messages every 5 seconds
setInterval(function(){
  var xhr = new XMLHttpRequest(); 
  xhr.open('GET', 'index.php?pages=chat_view&thread_id=<?= $threadId ?>&ajax=1&_=' + Date.now(), true);
  xhr.onload = function(){ 
    if (xhr.status !== 200) return; 
    try{ var html = xhr.responseText; } catch(e){ return; }
    var parser = new DOMParser(); 
    var doc = parser.parseFromString(html, 'text/html');
    var messagesContainer = doc.querySelector('#messagesBox');
    if (messagesContainer) {
      var target = document.querySelector('#messagesBox');
      if (target) target.innerHTML = messagesContainer.innerHTML;
    }
  };
  xhr.send();
}, 5000);

// Close thread via AJAX with SweetAlert
document.getElementById('closeThreadBtn').addEventListener('click', function(){
  Swal.fire({
    title: 'Are you sure?',
    text: "This will mark the thread as closed.",
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#3085d6',
    cancelButtonColor: '#d33',
    confirmButtonText: 'Yes, close it'
  }).then((result) => {
    if (!result.isConfirmed) return;

    var fd = new FormData(); 
    fd.append('action','close'); 
    fd.append('thread_id','<?= $threadId ?>');
    var csrfEl = document.querySelector('input[name="_csrf"]');
    if (csrfEl) fd.append('_csrf', csrfEl.value);

  var xhr = new XMLHttpRequest(); 
  xhr.open('POST', 'index.php?pages=chat', true); 
    xhr.setRequestHeader('X-Requested-With','XMLHttpRequest');
    xhr.onload = function(){ 
      try{ var r = JSON.parse(xhr.responseText); }
      catch(e){ 
        Swal.fire('Error', 'Invalid server response.', 'error'); 
        return; 
      } 
      if(r.status==='ok'){ 
        Swal.fire('Closed!', 'The thread has been closed.', 'success')
          .then(()=>{ window.location.href='index.php?pages=chat'; });
      } else {
        Swal.fire('Failed', 'Could not close the thread.', 'error'); 
      }
    };
    xhr.send(fd);
  });
});
</script>


<?php require_once __DIR__ . '/../includes/footer.php';

// Add small script to handle delete buttons (placed after footer include so DOM is ready)
echo "<script>
document.addEventListener('click', function(e) {
    if (!e.target.classList.contains('btn-delete-attachment')) return;
    var id = e.target.dataset.id;
    if (!id) return;
    
    Swal.fire({
        title: 'Delete Attachment?',
        text: 'Are you sure you want to delete this attachment?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, delete it',
        cancelButtonText: 'Cancel',
        reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed) {
            var fd = new FormData();
            fd.append('id', id);
            fd.append('_csrf', '".generateToken('chat_form')."');
            
      fetch((window.HQ_ADMIN_BASE || '') + '/api/delete_attachment.php', {
                method: 'POST',
                body: fd
            })
            .then(r => r.json())
            .then(j => {
                if (j.status === 'ok') {
                    Swal.fire('Deleted!', 'Attachment has been deleted.', 'success')
                    .then(() => location.reload());
                } else {
                    Swal.fire('Error', 'Delete failed: ' + (j.message || 'unknown'), 'error');
                }
            })
            .catch(e => {
                Swal.fire('Error', 'Delete failed', 'error');
            });
        }
    });
}, false);</script>";
