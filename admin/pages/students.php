<?php
// admin/pages/students.php
require_once '../includes/auth.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/csrf.php';

requirePermission('students'); // where 'students' matches the menu slug
// Generate CSRF token
$csrf = generateToken('students_form');

// Handle POST actions (activate/deactivate/delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action']) && isset($_GET['id'])) {
    $action = $_GET['action'];
    $id = (int)$_GET['id'];
    $token = $_POST['csrf_token'] ?? '';
    if (!verifyToken('students_form', $token)) {
        header('Location: index.php?pages=students'); exit;
    }

    $currentUserId = $_SESSION['user']['id'];

    // Protect main admin and yourself from destructive actions
    if ($id === 1 || $id === $currentUserId) {
        header('Location: index.php?pages=students'); exit;
    }

    if ($action === 'deactivate') {
        $stmt = $pdo->prepare('UPDATE users SET is_active = 2, updated_at = NOW() WHERE id = ?');
        $stmt->execute([$id]);
        logAction($pdo, $currentUserId, 'student_deactivate', ['student_id'=>$id]);
        header('Location: index.php?pages=students'); exit;
    }

    if ($action === 'activate') {
        $stmt = $pdo->prepare('UPDATE users SET is_active = 1, updated_at = NOW() WHERE id = ?');
        $stmt->execute([$id]);
        logAction($pdo, $currentUserId, 'student_activate', ['student_id'=>$id]);
        header('Location: index.php?pages=students'); exit;
    }

    if ($action === 'delete') {
        // Soft-delete: set is_active = 3 (deleted) if schema supports, else remove
        try {
            $stmt = $pdo->prepare('UPDATE users SET is_active = 3, updated_at = NOW() WHERE id = ?');
            $stmt->execute([$id]);
            logAction($pdo, $currentUserId, 'student_delete', ['student_id'=>$id]);
        } catch (Exception $e) {
            // Fallback to hard delete
            $stmt = $pdo->prepare('DELETE FROM users WHERE id = ?');
            $stmt->execute([$id]);
            logAction($pdo, $currentUserId, 'student_delete_hard', ['student_id'=>$id]);
        }
        header('Location: index.php?pages=students'); exit;
    }

  // Custom: send message/approve flow from modal
  if ($action === 'send_message') {
    $message = trim($_POST['message'] ?? '');
    $activate = isset($_POST['activate']) ? 1 : 0;

    // load student
    $sstmt = $pdo->prepare('SELECT * FROM users WHERE id = ? LIMIT 1');
    $sstmt->execute([$id]);
    $student = $sstmt->fetch(PDO::FETCH_ASSOC);
    if ($student) {
      if ($activate) {
        $ust = $pdo->prepare('UPDATE users SET is_active = 1, updated_at = NOW() WHERE id = ?');
        $ust->execute([$id]);
        logAction($pdo, $currentUserId, 'student_activate_via_modal', ['student_id'=>$id]);
      }

      // send email using existing helper if available
      if (function_exists('sendEmail') && filter_var($student['email'], FILTER_VALIDATE_EMAIL)) {
        $subject = 'Message from HIGH Q admin';
        $body = "Hello " . htmlspecialchars($student['name']) . ",\n\n" . $message . "\n\nRegards,\nHIGH Q Team";
        try { sendEmail($student['email'], $subject, $body); logAction($pdo, $currentUserId, 'student_message_sent', ['student_id'=>$id]); } catch (Exception $e) { /* ignore send errors */ }
      }
    }
    header('Location: index.php?pages=students'); exit;
  }
}

// Fetch students (users with role slug 'student' or where role is null)
$stmt = $pdo->prepare("SELECT u.*, r.name AS role_name, r.slug AS role_slug FROM users u LEFT JOIN roles r ON r.id = u.role_id WHERE r.slug = 'student' OR u.role_id IS NULL ORDER BY u.created_at DESC");
$stmt->execute();
$students = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Counts
$total = count($students);
$active = 0; $pending = 0; $banned = 0;
foreach ($students as $s) {
    if ($s['is_active']==1) $active++;
    elseif ($s['is_active']==0) $pending++;
    elseif ($s['is_active']==2) $banned++;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Students - HIGH Q SOLID ACADEMY</title>
  <link rel="stylesheet" href="../assets/css/users.css">
  <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
</head>
<body>
<?php include '../includes/header.php'; ?>
<?php include '../includes/sidebar.php'; ?>

<div class="users-page">

  <div class="summary-cards">
    <div class="card"><span class="icon"><i class='bx bx-user'></i></span><div><h3><?= $total ?></h3><p>Total Students</p></div></div>
    <div class="card"><span class="icon"><i class='bx bx-user-check'></i></span><div><h3><?= $active ?></h3><p>Active</p></div></div>
    <div class="card"><span class="icon"><i class='bx bx-time-five'></i></span><div><h3><?= $pending ?></h3><p>Pending</p></div></div>
    <div class="card"><span class="icon"><i class='bx bx-user-x'></i></span><div><h3><?= $banned ?></h3><p>Banned</p></div></div>
  </div>

  <div class="user-filters">
    <input type="text" id="searchInput" placeholder="Search students by name or email">
    <select id="statusFilter">
      <option value="">All Status</option>
      <option value="active">Active</option>
      <option value="pending">Pending</option>
      <option value="banned">Banned</option>
    </select>
  </div>

  <div class="users-list" id="studentsList">
    <?php foreach ($students as $s):
      $status = $s['is_active']==1 ? 'Active' : ($s['is_active']==0 ? 'Pending' : 'Banned');
      $roleClass = 'role-student';
    ?>
    <div class="user-card" data-status="<?= $s['is_active']==1?'active':($s['is_active']==0?'pending':'banned') ?>">
      <div class="card-left">
        <img src="<?= $s['avatar'] ?: '../public/assets/images/avatar-placeholder.png' ?>" class="avatar-sm card-avatar">
        <div class="card-meta">
          <div class="card-name"><?= htmlspecialchars($s['name']) ?></div>
          <div class="card-email"><?= htmlspecialchars($s['email']) ?></div>
          <div class="card-badges">
            <span class="role-badge <?= $roleClass ?>">Student</span>
            <span class="status-badge <?= $status==='Active' ? 'status-active' : ($status==='Pending' ? 'status-pending' : 'status-banned') ?>"><?= $status ?></span>
          </div>
        </div>
      </div>
      <div class="card-right">
        <div class="card-actions">
          <button class="btn-view" data-user-id="<?= $s['id'] ?>" title="View"><i class='bx bx-show'></i></button>
          <?php if ($s['id'] != 1 && $s['id'] != $_SESSION['user']['id']): ?>
            <?php if ($s['is_active'] == 1): ?>
              <form method="post" action="index.php?pages=students&action=deactivate&id=<?= $s['id'] ?>" class="inline-form">
                <input type="hidden" name="csrf_token" value="<?= $csrf; ?>">
                <button type="submit" class="btn-banish">Deactivate</button>
              </form>
            <?php elseif ($s['is_active'] == 0): ?>
              <form method="post" action="index.php?pages=students&action=activate&id=<?= $s['id'] ?>" class="inline-form">
                <input type="hidden" name="csrf_token" value="<?= $csrf; ?>">
                <button type="submit" class="btn-approve">Activate</button>
              </form>
            <?php else: ?>
              <form method="post" action="index.php?pages=students&action=activate&id=<?= $s['id'] ?>" class="inline-form">
                <input type="hidden" name="csrf_token" value="<?= $csrf; ?>">
                <button type="submit" class="btn-approve">Reactivate</button>
              </form>
            <?php endif; ?>
            <form method="post" action="index.php?pages=students&action=delete&id=<?= $s['id'] ?>" class="inline-form" onsubmit="return confirm('Delete this student? This cannot be undone.');">
              <input type="hidden" name="csrf_token" value="<?= $csrf; ?>">
              <button type="submit" class="btn-banish">Delete</button>
            </form>
          <?php endif; ?>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>

</div>

<?php include '../includes/footer.php'; ?>

<script>
// Client-side search/filter
const searchInput = document.getElementById('searchInput');
const statusFilter = document.getElementById('statusFilter');
const studentsList = document.getElementById('studentsList');

function filterStudents(){
  const q = searchInput.value.toLowerCase();
  const status = statusFilter.value;
  document.querySelectorAll('#studentsList .user-card').forEach(card=>{
    const name = card.querySelector('.card-name').textContent.toLowerCase();
    const email = card.querySelector('.card-email').textContent.toLowerCase();
    const cardStatus = card.dataset.status;
    const matchesQ = q==='' || name.includes(q) || email.includes(q);
    const matchesStatus = status==='' || cardStatus===status;
    card.style.display = (matchesQ && matchesStatus) ? '' : 'none';
  });
}

searchInput.addEventListener('input', filterStudents);
statusFilter.addEventListener('change', filterStudents);

// View button behavior (reuse users.php modal if present)
document.querySelectorAll('.btn-view').forEach(btn=>btn.addEventListener('click', e=>{
  e.preventDefault();
  const id = btn.dataset.userId;
  // If users modal exists in DOM (users.php), call its loader; otherwise open a simple window
  if (typeof loadUser === 'function') {
    loadUser(id,'view');
  } else {
    window.location = `index.php?pages=users&action=view&id=${id}`;
  }
}));
</script>

<!-- Approve & Message Modal -->
<div id="studentModal" class="modal" style="display:none;position:fixed;z-index:9999;left:0;top:0;width:100%;height:100%;background:rgba(0,0,0,0.4);align-items:center;justify-content:center;">
  <div class="modal-content" style="background:#fff;padding:18px;border-radius:8px;max-width:560px;width:94%;box-shadow:0 8px 24px rgba(0,0,0,0.2);">
    <h3 id="modalTitle">Message Student</h3>
    <form id="modalForm" method="post">
      <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
      <div style="margin-bottom:8px;"><label>To: <span id="modalStudentName"></span> (<span id="modalStudentEmail"></span>)</label></div>
      <div style="margin-bottom:8px;"><label>Message</label><textarea name="message" rows="6" required style="width:100%;padding:8px;border:1px solid #ccc;border-radius:6px;"></textarea></div>
      <div style="display:flex;align-items:center;gap:12px;margin-bottom:8px;"><label style="display:flex;align-items:center;gap:8px;"><input type="checkbox" name="activate" id="modalActivate"> Activate student</label></div>
      <div style="display:flex;justify-content:flex-end;gap:8px;"><button type="button" id="modalCancel" class="btn">Cancel</button><button type="submit" class="btn btn-approve">Send & Close</button></div>
    </form>
  </div>
</div>

<script>
// Modal wiring
const modal = document.getElementById('studentModal');
const modalTitle = document.getElementById('modalTitle');
const modalStudentName = document.getElementById('modalStudentName');
const modalStudentEmail = document.getElementById('modalStudentEmail');
const modalForm = document.getElementById('modalForm');
const modalCancel = document.getElementById('modalCancel');

function openStudentModal(id, name, email){
  modal.style.display = 'flex';
  modalStudentName.textContent = name;
  modalStudentEmail.textContent = email;
  modalForm.action = `index.php?pages=students&action=send_message&id=${id}`;
}

modalCancel.addEventListener('click', ()=> modal.style.display='none');

// Attach openers to each student card view button (augment existing)
document.querySelectorAll('.user-card .btn-view').forEach(btn=>{
  btn.addEventListener('contextmenu', e=>e.preventDefault());
  btn.addEventListener('dblclick', e=>{
    // double-click to open approve/message modal
    const card = btn.closest('.user-card');
    const id = btn.dataset.userId;
    const name = card.querySelector('.card-name').textContent.trim();
    const email = card.querySelector('.card-email').textContent.trim();
    openStudentModal(id, name, email);
  });
});

// Close modal when clicking outside content
modal.addEventListener('click', (e)=>{ if(e.target===modal) modal.style.display='none'; });
</script>

</body>
</html>
