<?php
// admin/pages/comments.php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
requirePermission('comments');
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../../public/config/functions.php';

$pageTitle = 'Comments';
require_once __DIR__ . '/../includes/header.php';

// AJAX actions: approve, reject
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
    echo "<td>".($c['status']==='pending'?"<button class='btn' onclick='doAction(\'approve\',{$c['id']})'>Approve</button> <button class='btn' onclick='doAction(\'reject\',{$c['id']})'>Delete</button>":'&mdash;')."</td>";
    echo "</tr>";
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
          <?php else: ?> &mdash; <?php endif; ?></td>
      </tr>
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
