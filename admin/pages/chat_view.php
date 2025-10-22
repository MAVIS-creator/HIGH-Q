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
    <div id="messagesBox" style="max-height:420px;overflow:auto;padding:8px;border:1px solid #eee;margin-bottom:12px">
      <?php foreach($msgs as $m): ?>
        <div style="margin-bottom:12px">
          <div style="font-size:0.85rem;color:#666"><?= htmlspecialchars($m['created_at']) ?> — <?= $m['is_from_staff'] ? htmlspecialchars($m['sender_name']) : htmlspecialchars($m['sender_name']?:'Visitor') ?></div>
          <div style="background:<?= $m['is_from_staff'] ? '#fff3cf' : '#f6f6f6' ?>;padding:10px;border-radius:8px;margin-top:6px">
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
                      $downloadUrl = '/HIGH-Q/public/download_attachment.php?file=' . urlencode(basename($a));
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
                        echo '<a href="' . htmlspecialchars($downloadUrl) . '" target="_blank"><img src="' . htmlspecialchars($a) . '" style="max-width:120px;border-radius:6px"></a>';
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
    <form id="replyForm" enctype="multipart/form-data">
      <textarea name="message" style="width:100%;height:100px;padding:8px" placeholder="Reply..."></textarea>
      <div style="display:flex;gap:8px;margin-top:8px;align-items:center">
        <input type="file" name="attachments[]" multiple>
        <div style="margin-left:auto"><button class="btn" type="submit">Send</button></div>
      </div>
      <input type="hidden" name="_csrf" value="<?= htmlspecialchars(generateToken('chat_form')) ?>">
    </form>
    <div style="text-align:right;margin-top:8px">
      <button id="closeThreadBtn" class="btn" style="background:#f44336;color:#fff;border:none;padding:8px 12px;border-radius:6px;">Close Thread</button>
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
  xhr.open('POST', '../index.php?pages=chat',true);
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
  xhr.open('GET', '../index.php?pages=chat_view&thread_id=<?= $threadId ?>&ajax=1&_=' + Date.now(), true);
  xhr.onload = function(){ 
    if (xhr.status !== 200) return; 
    try{ var html = xhr.responseText; } catch(e){ return; }
    var parser = new DOMParser(); 
    var doc = parser.parseFromString(html, 'text/html');
    var messagesContainer = doc.querySelector('.card > div');
    if (messagesContainer) {
      var target = document.querySelector('.card > div');
      target.innerHTML = messagesContainer.innerHTML;
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
  xhr.open('POST', '../index.php?pages=chat', true); 
    xhr.setRequestHeader('X-Requested-With','XMLHttpRequest');
    xhr.onload = function(){ 
      try{ var r = JSON.parse(xhr.responseText); }
      catch(e){ 
        Swal.fire('Error', 'Invalid server response.', 'error'); 
        return; 
      } 
      if(r.status==='ok'){ 
        Swal.fire('Closed!', 'The thread has been closed.', 'success')
          .then(()=>{ window.location.href='?pages=chat'; });
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
      fd.append('_csrf', '" . generateToken('chat_form') . "');

      var promise = (typeof window.hqFetchCompat === 'function') ? window.hqFetchCompat('/HIGH-Q/admin/api/delete_attachment.php', { method: 'POST', body: fd }) : fetch('/HIGH-Q/admin/api/delete_attachment.php', { method: 'POST', body: fd });

      promise.then(function(r){
        if (r && r._parsed) return Promise.resolve(r._parsed);
        if (r && typeof r.json === 'function') return r.json();
        return Promise.resolve(r);
      })
      .then(function(j){
        if (j && j.status === 'ok') {
          Swal.fire('Deleted!', 'Attachment has been deleted.', 'success')
          .then(() => location.reload());
        } else {
          Swal.fire('Error', 'Delete failed: ' + (j && j.message || 'unknown'), 'error');
        }
      })
      .catch(function(e){
        Swal.fire('Error', 'Delete failed', 'error');
      });
    }
  });
}, false);</script>";

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
            
      (typeof window.hqFetchCompat === 'function' ? window.hqFetchCompat('/HIGH-Q/admin/api/delete_attachment.php', { method: 'POST', body: fd }) : fetch('/HIGH-Q/admin/api/delete_attachment.php', { method: 'POST', body: fd }))
+
+
+            .then(function(r){
+                // hqFetchCompat may return wrapped parsed response under _parsed or a Response-like object
        if (r && r._parsed) return Promise.resolve(r._parsed);
+                if (r && typeof r.json === 'function') return r.json();
+                return Promise.resolve(r);
+            })
+            .then(j => {
                 if (j.status === 'ok') {
                     Swal.fire('Deleted!', 'Attachment has been deleted.', 'success')
                     .then(() => location.reload());
                 } else {
                     Swal.fire('Error', 'Delete failed: ' + (j.message || 'unknown'), 'error');
                 }
             })
*** End Patch
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
