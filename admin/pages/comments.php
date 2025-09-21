<?php
// admin/pages/comments.php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
requirePermission('comments');
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../../public/config/functions.php';

$pageTitle = 'Comments';
require_once __DIR__ . '/../includes/header.php';

// AJAX actions: approve, reject, reply
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
    $token = $_POST['_csrf'] ?? '';
    if (!verifyToken('comments_form', $token)) { echo json_encode(['status'=>'error','message'=>'Invalid CSRF']); exit; }
    $action = $_POST['action'] ?? '';
    $id = intval($_POST['id'] ?? 0);
    if ($action === 'approve') {
        $upd = $pdo->prepare('UPDATE comments SET status = "approved" WHERE id = ?'); $ok = $upd->execute([$id]);
        if ($ok) logAction($pdo, $_SESSION['user']['id'], 'comment_approved', ['comment_id'=>$id]);
        echo json_encode(['status'=>$ok ? 'ok':'error']); exit;
    }
    if ($action === 'reject') {
        $upd = $pdo->prepare('UPDATE comments SET status = "deleted" WHERE id = ?'); $ok = $upd->execute([$id]);
        if ($ok) logAction($pdo, $_SESSION['user']['id'], 'comment_deleted', ['comment_id'=>$id]);
        echo json_encode(['status'=>$ok ? 'ok':'error']); exit;
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
        }
        echo json_encode(['status'=>$ok ? 'ok':'error']); exit;
    }

    echo json_encode(['status'=>'error']); exit;
}

$perPage = 30; $page = max(1,(int)($_GET['page']??1)); $offset = ($page-1)*$perPage;
$stmt = $pdo->prepare('SELECT * FROM comments ORDER BY created_at DESC LIMIT ? OFFSET ?'); $stmt->bindValue(1, $perPage, PDO::PARAM_INT); $stmt->bindValue(2, $offset, PDO::PARAM_INT); $stmt->execute();
$comments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// If requested via AJAX fragment (polling), return only the tbody rows
if (!empty($_GET['ajax'])) {
  foreach($comments as $c){
    echo "<tr>";
    echo "<td>".htmlspecialchars($c['id'])."</td>";
    echo "<td>".htmlspecialchars($c['post_id'])."</td>";
    echo "<td>".htmlspecialchars(($c['name']?:'').' / '.($c['email']?:''))."</td>";
    echo "<td>".htmlspecialchars(mb_strimwidth($c['content'],0,180,'...'))."</td>";
    echo "<td>".htmlspecialchars($c['status'])."</td>";
    echo "<td>".htmlspecialchars($c['created_at'])."</td>";
    echo "<td>".($c['status']==='pending'?"<button class='btn' onclick='doAction(\'approve\',{$c['id']})'>Approve</button> <button class='btn' onclick='doAction(\'reject\',{$c['id']})'>Delete</button> <button class='btn' onclick='openReply(\"{$c['id']}\", \"".htmlspecialchars(addslashes(mb_strimwidth($c['content'],0,120,'...')))."\")'>Reply</button>":"<button class='btn' onclick='openReply(\"{$c['id']}\", \"".htmlspecialchars(addslashes(mb_strimwidth($c['content'],0,120,'...')))."\")'>Reply</button>
    )."</td>";
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

?>
<div class="roles-page">
  <div class="page-header"><h1><i class="bx bxs-comment-detail"></i> Comments</h1></div>
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
              <button class="btn" onclick="doAction('approve',<?= $c['id'] ?>)">Approve</button>
              <button class="btn" onclick="doAction('reject',<?= $c['id'] ?>)">Delete</button>
              <button class="btn" onclick="openReply(<?= $c['id'] ?>, <?= json_encode(mb_strimwidth($c['content'],0,120,'...')) ?>)">Reply</button>
            <?php else: ?>
              <button class="btn" onclick="openReply(<?= $c['id'] ?>, <?= json_encode(mb_strimwidth($c['content'],0,120,'...')) ?>)">Reply</button>
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
</div>

<script>
function doAction(action,id){ if(!confirm('Are you sure?')) return; var fd=new FormData(); fd.append('action',action); fd.append('id',id); fd.append('_csrf','<?= generateToken('comments_form') ?>'); var xhr=new XMLHttpRequest(); xhr.open('POST',location.href,true); xhr.setRequestHeader('X-Requested-With','XMLHttpRequest'); xhr.onload=function(){ try{var r=JSON.parse(xhr.responseText);}catch(e){alert('Error');return;} if(r.status==='ok') location.reload(); else alert('Failed'); }; xhr.send(fd); }
// Poll the comments fragment every 5 seconds
setInterval(function(){
  var xhr = new XMLHttpRequest(); xhr.open('GET', location.pathname + '?pages=comments&ajax=1&_=' + Date.now(), true);
  xhr.onload = function(){ if (xhr.status !== 200) return; document.querySelector('table.roles-table tbody').innerHTML = xhr.responseText; };
  xhr.send();
}, 5000);
</script>

<?php require_once __DIR__ . '/../includes/footer.php';
