<?php
// admin/pages/students.php
require_once '../includes/auth.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/csrf.php';

requirePermission('students'); // where 'students' matches the menu slug
// Generate CSRF token
$csrf = generateToken('students_form');

// Support POST-driven AJAX actions (confirm_registration, view_registration)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'confirm_registration') {
  $id = intval($_POST['id'] ?? 0);

  $pdo->beginTransaction();
  try {
    // Fetch registration (with email and user fallback)
    $stmt = $pdo->prepare("\n            SELECT sr.*, COALESCE(sr.email, u.email) AS email, u.id AS user_id\n            FROM student_registrations sr\n            LEFT JOIN users u ON u.id = sr.user_id\n            WHERE sr.id = ?\n            LIMIT 1\n        ");
    $stmt->execute([$id]);
    $reg = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$reg) {
      throw new Exception("Registration not found");
    }

    $email = $reg['email'];
    $studentId = $reg['user_id'] ?: null;

    // Generate reference + insert payment row
    $ref = uniqid("pay_");
    $amount = 20000; // adjust amount as needed
    $method = "paystack";

    $ins = $pdo->prepare("\n            INSERT INTO payments (student_id, registration_id, amount, payment_method, reference, status, created_at)\n            VALUES (?, ?, ?, ?, ?, 'pending', NOW())\n        ");
    $ins->execute([$studentId, $reg['id'], $amount, $method, $ref]);

    // Build payment link
    $paymentLink = ($_SERVER['HTTPS'] ?? '') === 'on' ? 'https' : 'http';
    $paymentLink .= '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . dirname($_SERVER['SCRIPT_NAME']) . '/../public/payments_wait.php?ref=' . urlencode($ref);

    // Send email
    $email_sent = false;
    if ($email) {
      $subject = "Payment Link for Your Registration";
      $message = "<p>Hi " . htmlspecialchars($reg['first_name']) . ",</p>\n                <p>Please complete your payment using the secure link below:</p>\n                <p><a href='" . htmlspecialchars($paymentLink) . "'>" . htmlspecialchars($paymentLink) . "</a></p>\n                <p>Best regards,<br><strong>HIGH Q SOLID ACADEMY</strong></p>\n            ";
      $email_sent = sendEmail($email, $subject, $message);
    }

    // Update status to indicate registration has been confirmed/awaiting payment
    $pdo->prepare("UPDATE student_registrations SET status = 'awaiting_payment' WHERE id = ?")->execute([$reg['id']]);

  $pdo->commit();

  header('Content-Type: application/json');
  echo json_encode([
    'success' => true,
    'message' => 'Payment link created.',
    'reference' => $ref,
    'email' => $email,
    'email_sent' => (bool)$email_sent,
  ]);
  } catch (Exception $e) {
    $pdo->rollBack();
  header('Content-Type: application/json');
  echo json_encode(['success' => false, 'error' => $e->getMessage()]);
  }
  exit;
}

// Support POST view_registration for AJAX clients
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'view_registration') {
  $id = intval($_POST['id'] ?? 0);

  $stmt = $pdo->prepare("
    SELECT sr.*, COALESCE(sr.email, u.email) AS email, u.name AS user_name
    FROM student_registrations sr
    LEFT JOIN users u ON u.id = sr.user_id
    WHERE sr.id = ?
    LIMIT 1
  ");
  $stmt->execute([$id]);
  $s = $stmt->fetch(PDO::FETCH_ASSOC);

  header('Content-Type: application/json');
  if (!$s) {
    echo json_encode(['success' => false, 'error' => 'Registration not found']);
    exit;
  }

  echo json_encode([
    'success' => true,
    'data' => [
      'first_name'             => $s['first_name'] ?? null,
      'last_name'              => $s['last_name'] ?? null,
      'email'                  => $s['email'] ?? null,
      'date_of_birth'          => $s['date_of_birth'] ?? null,
      'home_address'           => $s['home_address'] ?? null,
      'previous_education'     => $s['previous_education'] ?? null,
      'academic_goals'         => $s['academic_goals'] ?? null,
      'emergency_contact_name' => $s['emergency_contact_name'] ?? null,
      'emergency_contact_phone'=> $s['emergency_contact_phone'] ?? null,
      'emergency_relationship' => $s['emergency_relationship'] ?? null,
      'status'                 => $s['status'] ?? null,
      'created_at'             => $s['created_at'] ?? null,
    ]
  ]);
  exit;
}

// Handle POST actions (activate/deactivate/delete)
// Handle AJAX GET view of a registration
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'view' && isset($_GET['id'])) {
  $id = (int)$_GET['id'];
  $stmt = $pdo->prepare("SELECT sr.*, u.email, u.name AS user_name FROM student_registrations sr LEFT JOIN users u ON u.id = sr.user_id WHERE sr.id = ? LIMIT 1");
  $stmt->execute([$id]); $s = $stmt->fetch(PDO::FETCH_ASSOC);
  header('Content-Type: application/json');
  if (!$s) { echo json_encode(['error'=>'Not found']); exit; }
  echo json_encode($s); exit;
}

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
    // If this id exists in student_registrations, delete that registration. Otherwise treat as users delete.
    $checkReg = $pdo->prepare('SELECT id FROM student_registrations WHERE id = ? LIMIT 1');
    $checkReg->execute([$id]);
    $regFound = $checkReg->fetch(PDO::FETCH_ASSOC);
    if ($regFound) {
      $del = $pdo->prepare('DELETE FROM student_registrations WHERE id = ?');
      $del->execute([$id]);
      logAction($pdo, $currentUserId, 'registration_delete', ['registration_id'=>$id]);
      header('Location: index.php?pages=students'); exit;
    }

    // Fallback: Soft-delete user record (legacy path)
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

  // Confirm registration (admin) - send notification
  if ($action === 'confirm_registration') {
    $stmt = $pdo->prepare('SELECT * FROM student_registrations WHERE id = ? LIMIT 1'); $stmt->execute([$id]); $reg = $stmt->fetch(PDO::FETCH_ASSOC);
    $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']);
    if (!$reg) {
      if ($isAjax) { echo json_encode(['status'=>'error','message'=>'Not found']); exit; }
      header('Location: index.php?pages=students'); exit;
    }

    // If already confirmed, return meaningful JSON error for AJAX or redirect with flash
    if (isset($reg['status']) && strtolower($reg['status']) === 'confirmed') {
      if ($isAjax) { echo json_encode(['status'=>'error','message'=>'Registration already confirmed']); exit; }
      setFlash('error','Registration already confirmed'); header('Location: index.php?pages=students'); exit;
    }

    // Transaction: mark confirmed and optionally create payment and send reference
    try {
      $pdo->beginTransaction();
      $upd = $pdo->prepare('UPDATE student_registrations SET status = ?, updated_at = NOW() WHERE id = ?'); $upd->execute(['confirmed', $id]);
      logAction($pdo, $currentUserId, 'confirm_registration', ['registration_id'=>$id]);

      // Optional payment creation: admin may include create_payment=1 and amount in POST (AJAX)
      if (!empty($_POST['create_payment']) && !empty($_POST['amount'])) {
        $amount = floatval($_POST['amount']);
        $method = $_POST['method'] ?? 'bank';
        // create payment placeholder and send reference to registrant email
        $ref = 'REG-' . date('YmdHis') . '-' . substr(bin2hex(random_bytes(3)),0,6);
        $ins = $pdo->prepare('INSERT INTO payments (student_id, amount, payment_method, reference, status, created_at) VALUES (NULL, ?, ?, ?, "pending", NOW())');
        $ins->execute([$amount, $method, $ref]);
        $paymentId = $pdo->lastInsertId();
        logAction($pdo, $currentUserId, 'create_payment_for_registration', ['registration_id'=>$id,'payment_id'=>$paymentId,'reference'=>$ref,'amount'=>$amount]);

          // send email to registrant with link to payments_wait (if email present)
          $emailSent = false;
          if (!empty($reg['email']) && filter_var($reg['email'], FILTER_VALIDATE_EMAIL) && function_exists('sendEmail')) {
            // Try to fetch site settings for branding (non-essential)
            $siteName = 'HIGH Q'; $logoUrl = '';$contactEmail = '';$contactPhone = '';
            try {
              $s = $pdo->query('SELECT site_name, logo_url, contact_email, contact_phone FROM site_settings LIMIT 1')->fetch(PDO::FETCH_ASSOC);
              if (!empty($s['site_name'])) $siteName = $s['site_name'];
              if (!empty($s['logo_url'])) $logoUrl = $s['logo_url'];
              if (!empty($s['contact_email'])) $contactEmail = $s['contact_email'];
              if (!empty($s['contact_phone'])) $contactPhone = $s['contact_phone'];
            } catch (Throwable $e) { /* ignore */ }

            $subject = $siteName . ' — Payment instructions for your registration';
            $proto = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
            // build link relative to public folder (best-effort)
            $base = $proto . '://' . $host;
            $link = $base . dirname($_SERVER['SCRIPT_NAME']) . '/../public/payments_wait.php?ref=' . urlencode($ref);

            // Branded HTML message
            $body = '<!doctype html><html><head><meta charset="utf-8"><title>' . htmlspecialchars($subject) . '</title>';
            $body .= '<style>body{font-family:Arial,Helvetica,sans-serif;color:#333} .container{max-width:640px;margin:0 auto;padding:20px} .btn{display:inline-block;padding:10px 16px;background:#d62828;color:#fff;border-radius:6px;text-decoration:none}</style>';
            $body .= '</head><body><div class="container">';
            if ($logoUrl) $body .= '<div style="margin-bottom:12px;"><img src="' . htmlspecialchars($logoUrl) . '" alt="' . htmlspecialchars($siteName) . '" style="max-height:60px"></div>';
            $body .= '<h2 style="color:#111">Hello ' . htmlspecialchars(trim($reg['first_name'] . ' ' . ($reg['last_name'] ?? ''))) . ',</h2>';
            $body .= '<p>Your registration has been approved by ' . htmlspecialchars($siteName) . ' and requires payment to complete enrollment.</p>';
            $body .= '<p><strong>Amount:</strong> ₦' . number_format($amount,2) . '</p>';
            $body .= '<p><strong>Payment reference:</strong> <code>' . htmlspecialchars($ref) . '</code></p>';
            $body .= '<p><a class="btn" href="' . htmlspecialchars($link) . '">Complete payment now</a></p>';
            $body .= '<p style="color:#666;font-size:13px;margin-top:18px">If the button above does not work, copy and paste this link into your browser: <br>' . htmlspecialchars($link) . '</p>';
            if ($contactEmail || $contactPhone) {
              $body .= '<hr><p style="color:#666;font-size:13px">Need help? Contact us at ' . ($contactEmail ? htmlspecialchars($contactEmail) : '') . ($contactPhone ? ' / ' . htmlspecialchars($contactPhone) : '') . '</p>';
            }
            $body .= '<p style="margin-top:20px">Thanks,<br>' . htmlspecialchars($siteName) . ' team</p>';
            $body .= '</div></body></html>';

            try {
              $emailSent = (bool) sendEmail($reg['email'], $subject, $body);
            } catch (Throwable $e) { $emailSent = false; }
          }
      }

      $pdo->commit();
      if ($isAjax) {
        $resp = ['status'=>'ok','message'=>'Confirmed', 'email_sent'=>!empty($emailSent), 'reference'=> $ref ?? null, 'amount'=> isset($amount) ? $amount : null, 'email'=> $reg['email'] ?? null];
        echo json_encode($resp); exit;
      }
      header('Location: index.php?pages=students'); exit;
    } catch (Exception $e) {
      if ($pdo->inTransaction()) $pdo->rollBack();
      if ($isAjax) { echo json_encode(['status'=>'error','message'=>'Server error']); exit; }
      setFlash('error','Failed to confirm registration'); header('Location: index.php?pages=students'); exit;
    }
  }

  // Reject registration with optional reason
  if ($action === 'reject_registration') {
    $reason = trim($_POST['reason'] ?? '');
    $stmt = $pdo->prepare('SELECT * FROM student_registrations WHERE id = ? LIMIT 1'); $stmt->execute([$id]); $reg = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($reg) {
      $upd = $pdo->prepare('UPDATE student_registrations SET status = ? WHERE id = ?'); $upd->execute(['rejected', $id]);
      logAction($pdo, $currentUserId, 'reject_registration', ['registration_id'=>$id, 'reason'=>$reason]);
      $emailSent = false;
      if (!empty($reg['email']) && filter_var($reg['email'], FILTER_VALIDATE_EMAIL) && function_exists('sendEmail')) {
        $subject = 'Registration Update — HIGH Q SOLID ACADEMY';
        $body = '<p>Hi ' . htmlspecialchars($reg['first_name'] . ' ' . ($reg['last_name'] ?? '')) . ',</p><p>Your registration has been rejected.' . ($reason ? '<br><strong>Reason:</strong> ' . htmlspecialchars($reason) : '') . '</p>';
        try { $emailSent = (bool) sendEmail($reg['email'], $subject, $body); } catch (Throwable $e) { $emailSent = false; }
      }
      $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']);
      if ($isAjax) { echo json_encode(['status'=>'ok','message'=>'Registration rejected','email_sent'=>!empty($emailSent)]); exit; }
    }
    header('Location: index.php?pages=students'); exit;
  }
}

// Prefer to show structured student registrations if the table exists
$hasRegistrations = false;
try {
  $check = $pdo->query("SHOW TABLES LIKE 'student_registrations'")->fetch();
  $hasRegistrations = !empty($check);
} catch (Throwable $e) { $hasRegistrations = false; }

// ensure counters exist regardless of which data path is used
$active = 0; $pending = 0; $banned = 0; $total = 0;

if ($hasRegistrations) {
  // simple pagination
  $perPage = 12;
  $page = max(1, (int)($_GET['page'] ?? 1));
  $offset = ($page - 1) * $perPage;

  $countStmt = $pdo->prepare("SELECT COUNT(*) FROM student_registrations");
  $countStmt->execute();
  $total = (int)$countStmt->fetchColumn();

  $stmt = $pdo->prepare("SELECT sr.*, u.email, u.name AS user_name FROM student_registrations sr LEFT JOIN users u ON u.id = sr.user_id ORDER BY sr.created_at DESC LIMIT ? OFFSET ?");
  $stmt->bindValue(1, $perPage, PDO::PARAM_INT);
  $stmt->bindValue(2, $offset, PDO::PARAM_INT);
  $stmt->execute();
  $students = $stmt->fetchAll(PDO::FETCH_ASSOC);

} else {
  // Fetch students (users with role slug 'student' or where role is null)
  $stmt = $pdo->prepare("SELECT u.*, r.name AS role_name, r.slug AS role_slug FROM users u LEFT JOIN roles r ON r.id = u.role_id WHERE r.slug = 'student' OR u.role_id IS NULL ORDER BY u.created_at DESC");
  $stmt->execute();
  $students = $stmt->fetchAll(PDO::FETCH_ASSOC);

  // Counts for legacy users list
  $total = count($students);
  $active = 0; $pending = 0; $banned = 0;
  foreach ($students as $s) {
    if ($s['is_active']==1) $active++;
    elseif ($s['is_active']==0) $pending++;
    elseif ($s['is_active']==2) $banned++;
  }
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
    <?php if (!empty($hasRegistrations)): ?>
      <?php foreach ($students as $s): ?>
        <div class="user-card" data-status="<?= htmlspecialchars($s['status'] ?? 'pending') ?>">
          <div class="card-left">
            <img src="<?= '../public/assets/images/avatar-placeholder.png' ?>" class="avatar-sm card-avatar">
            <div class="card-meta">
              <div class="card-name"><?= htmlspecialchars($s['first_name'] . ' ' . ($s['last_name'] ?: '')) ?></div>
              <div class="card-email"><?= htmlspecialchars($s['email'] ?? $s['user_name'] ?? '') ?></div>
              <div class="card-badges">
                <span class="role-badge role-student">Student</span>
                <span class="status-badge <?= ($s['status']==='paid' || $s['status']==='confirmed') ? 'status-active' : 'status-pending' ?>"><?= htmlspecialchars(ucfirst($s['status'])) ?></span>
              </div>
            </div>
          </div>
          <div class="card-right">
            <div class="card-actions">
              <!-- view icon removed as requested -->
              <?php if (!empty($s['status'])): ?>
                <button class="btn btn-approve inline-confirm" data-id="<?= $s['id'] ?>">Confirm</button>
                <button class="btn btn-banish inline-reject" data-id="<?= $s['id'] ?>">Reject</button>
              <?php endif; ?>
              <form method="post" action="index.php?pages=students&action=delete&id=<?= $s['id'] ?>" class="inline-form" onsubmit="return confirm('Delete this registration?');">
                <input type="hidden" name="csrf_token" value="<?= $csrf; ?>">
                <button type="submit" class="btn-banish">Delete</button>
              </form>
            </div>
            <div class="card-details" style="margin-top:10px;padding:12px;border-radius:6px;background:#fff;">
              <div><strong>DOB:</strong> <?= htmlspecialchars($s['date_of_birth'] ?? '-') ?></div>
              <div><strong>Address:</strong> <?= htmlspecialchars(strlen($s['home_address']??'')>80 ? substr($s['home_address'],0,80).'...' : ($s['home_address']??'-')) ?></div>
              <div><strong>Emergency:</strong> <?= htmlspecialchars(($s['emergency_contact_name'] ?? '-') . ' / ' . ($s['emergency_contact_phone'] ?? '-')) ?></div>
              <div style="margin-top:8px;"><a href="#" class="view-registration" data-id="<?= $s['id'] ?>">View full registration</a></div>
            </div>
          </div>
        </div>
      <?php endforeach; ?>

      <!-- Pagination -->
      <?php if (!empty($total) && isset($perPage)): $pages = ceil($total / $perPage); ?>
        <div class="pagination" style="margin-top:16px;display:flex;gap:8px;align-items:center;">
          <?php for ($p=1;$p<=$pages;$p++): ?>
            <a href="index.php?pages=students&page=<?= $p ?>" class="btn <?= $p==($page??1)?'btn-active':'' ?>"><?= $p ?></a>
          <?php endfor; ?>
        </div>
      <?php endif; ?>

    <?php else: ?>
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
            <!-- view icon removed as requested -->
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
    <?php endif; ?>
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

// View registration link behavior: fetch registration JSON and show modal with Confirm/Reject
const __students_csrf = '<?= $csrf ?>';
document.querySelectorAll('.view-registration').forEach(btn => {
  btn.addEventListener('click', function(e) {
    e.preventDefault();
    const id = this.dataset.id;
    const body = new URLSearchParams();
    body.append('action', 'view_registration');
    body.append('id', id);
    // include CSRF token if desired by server-side protections
    body.append('csrf_token', __students_csrf);

    fetch('index.php?pages=students', {
      method: 'POST',
      credentials: 'same-origin',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With':'XMLHttpRequest' },
      body: body.toString()
    })
    .then(res => res.json())
    .then(resp => {
      if (resp.success) {
        const d = resp.data;
        document.getElementById('registrationContent').innerHTML = `
          <div style="max-height:480px;overflow:auto;">
            <h4>${d.first_name || ''} ${d.last_name || ''}</h4>
            <p><strong>Email:</strong> ${d.email || ''}</p>
            <p><strong>Date of Birth:</strong> ${d.date_of_birth || ''}</p>
            <p><strong>Home Address:</strong> ${d.home_address || ''}</p>
            <p><strong>Previous Education:</strong> ${d.previous_education || ''}</p>
            <p><strong>Academic Goals:</strong> ${d.academic_goals || ''}</p>
            <p><strong>Emergency Contact:</strong> ${d.emergency_contact_name || ''} (${d.emergency_relationship || ''}) - ${d.emergency_contact_phone || ''}</p>
            <p><strong>Status:</strong> ${d.status || ''}</p>
            <p><strong>Registered At:</strong> ${d.created_at || ''}</p>
          </div>
        `;
        const modal = document.getElementById('registrationViewModal');
        modal.dataset.regId = id;
        modal.style.display = 'flex';
      } else {
        Swal.fire('Error', resp.error || 'Registration not found', 'error');
      }
    }).catch(err => { console.error(err); Swal.fire('Error','Failed to load registration','error'); });
  });
});
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

<!-- Registration View & Approve/Reject Modal -->
<div id="registrationViewModal" class="modal" style="display:none;position:fixed;z-index:9999;left:0;top:0;width:100%;height:100%;background:rgba(0,0,0,0.45);align-items:center;justify-content:center;">
  <div class="modal-content" style="background:#fff;padding:18px;border-radius:8px;max-width:780px;width:96%;box-shadow:0 8px 24px rgba(0,0,0,0.2);">
    <h3 id="regModalTitle">Registration</h3>
    <div id="registrationContent" style="margin-bottom:12px;"></div>
    <div style="display:flex;gap:8px;justify-content:flex-end;">
      <button id="regClose" type="button" class="btn">Close</button>
      <form id="regConfirmForm" method="post" style="display:inline;">
        <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
        <button type="submit" class="btn btn-approve">Confirm</button>
      </form>
      <button id="regRejectBtn" class="btn btn-banish">Reject</button>
    </div>
  </div>
</div>

<script>
// Registration modal handlers
const regModal = document.getElementById('registrationViewModal');
const regClose = document.getElementById('regClose');
const regConfirmForm = document.getElementById('regConfirmForm');
const regRejectBtn = document.getElementById('regRejectBtn');

regClose.addEventListener('click', ()=> regModal.style.display='none');
regModal.addEventListener('click', (e)=>{ if (e.target === regModal) regModal.style.display='none'; });

// When confirm form is submitted, POST to confirm_registration
// Helper to POST action and reload
async function postAction(url, formData){
  const res = await fetch(url, { method: 'POST', body: formData, credentials: 'same-origin', headers: {'X-Requested-With':'XMLHttpRequest'} });
  let payload = null;
  try { payload = await res.json(); } catch (e) { payload = null; }
  if (!res.ok || !payload) {
    const msg = payload && (payload.message || payload.error) ? (payload.message || payload.error) : 'Server error';
    await Swal.fire('Error', msg, 'error');
    throw new Error(msg);
  }

  const ok = (payload.status && payload.status === 'ok') || (payload.success === true);
  if (!ok) {
    const msg = payload.message || payload.error || 'Operation failed';
    await Swal.fire('Error', msg, 'error');
    throw new Error(msg);
  }

  // Show success with optional details (reference / email_sent / email)
  let details = '';
  if (payload.reference) details += `<div style="margin-top:8px"><strong>Reference:</strong> <code>${payload.reference}</code></div>`;
  if (typeof payload.email_sent !== 'undefined') details += `<div><strong>Email sent:</strong> ${payload.email_sent ? 'Yes' : 'No'}</div>`;
  if (payload.email) details += `<div><strong>Email:</strong> ${payload.email}</div>`;
  const message = payload.message || 'Operation completed';
  const html = message + (details ? `<hr/>${details}` : '');
  await Swal.fire({ title: 'Success', html: html, icon: 'success' });
  return payload;
}

// Confirm (modal) handler
regConfirmForm.addEventListener('submit', async function(e){
  e.preventDefault();
  const id = regModal.dataset.regId;
  if (!id) return Swal.fire('Error','No registration selected','error');
  const choice = await Swal.fire({ title: 'Confirm registration?', text: 'Create payment reference to send to registrant?', icon:'question', showDenyButton:true, showCancelButton:true, confirmButtonText:'Confirm only', denyButtonText:'Create payment & confirm' });
  try {
    if (choice.isConfirmed) {
      const fd = new FormData(); fd.append('csrf_token','<?= $csrf ?>');
      const payload = await postAction(`index.php?pages=students&action=confirm_registration&id=${id}`, fd);
      // success shown by postAction; reload to reflect changes
      window.location = 'index.php?pages=students';
    } else if (choice.isDenied) {
      const { value: formValues } = await Swal.fire({
        title: 'Create payment',
        html: '<input id="swal-amount" class="swal2-input" placeholder="Amount">' +
              '<select id="swal-method" class="swal2-select"><option value="bank">Bank Transfer</option><option value="online">Online</option></select>',
        focusConfirm: false,
        preConfirm: () => ({ amount: document.getElementById('swal-amount').value, method: document.getElementById('swal-method').value })
      });
      if (!formValues) return;
      const amt = parseFloat(formValues.amount || 0);
      if (!amt || amt <= 0) return Swal.fire('Error','Provide a valid amount','error');
      const fd = new FormData(); fd.append('csrf_token','<?= $csrf ?>'); fd.append('create_payment','1'); fd.append('amount', amt); fd.append('method', formValues.method || 'bank');
      const payload = await postAction(`index.php?pages=students&action=confirm_registration&id=${id}`, fd);
      window.location = 'index.php?pages=students';
    }
  } catch (err) {
    Swal.fire('Error','Failed to confirm','error');
  }
});

// Reject (modal) via SweetAlert2 textarea
regRejectBtn.addEventListener('click', ()=>{
  const id = regModal.dataset.regId;
  if (!id) return Swal.fire('Error','No registration selected','error');
  Swal.fire({
    title: 'Reject registration',
    input: 'textarea',
    inputLabel: 'Reason (optional)',
    inputPlaceholder: 'Enter a reason for rejection',
    showCancelButton: true,
    confirmButtonText: 'Reject',
    cancelButtonText: 'Cancel',
    inputAttributes: { 'aria-label': 'Rejection reason' }
  }).then(result=>{
    if (result.isConfirmed) {
      (async ()=>{
        try {
          const fd = new FormData(); fd.append('csrf_token', '<?= $csrf ?>'); fd.append('reason', result.value || '');
          const payload = await postAction(`index.php?pages=students&action=reject_registration&id=${id}`, fd);
          // update UI: remove buttons and mark status
          const card = document.querySelector(`.user-card[data-status][data-id='${id}']`) || document.querySelector(`.user-card [data-id='${id}']`)?.closest('.user-card');
          if (card) {
            card.querySelectorAll('.inline-confirm, .inline-reject').forEach(b=>b.remove());
            const badge = card.querySelector('.status-badge'); if (badge) { badge.textContent = 'Rejected'; badge.classList.remove('status-pending'); badge.classList.remove('status-active'); badge.classList.add('status-banned'); }
          }
        } catch (e) { Swal.fire('Error','Failed to reject','error'); }
      })();
    }
  });
});

// Delegated inline Confirm/Reject handlers
document.addEventListener('click', function(e){
  const t = e.target.closest('.inline-confirm');
    if (t) {
    const id = t.dataset.id;
    if (!id) return Swal.fire('Error','No registration id','error');
    // Ask if admin wants to create a payment right away
    Swal.fire({
      title: 'Confirm registration?',
      text: 'Do you want to create a payment reference to send to the registrant now?',
      icon: 'question',
      showDenyButton: true,
      showCancelButton: true,
      confirmButtonText: 'Confirm only',
      denyButtonText: 'Create payment & confirm'
    }).then(async res=>{
        if (res.isConfirmed) {
              const fd=new FormData(); fd.append('csrf_token','<?= $csrf ?>');
              try {
                const payload = await postAction(`index.php?pages=students&action=confirm_registration&id=${id}`, fd);
                // postAction already displayed success and details. update UI: hide confirm/reject buttons and set status badge
                const card = document.querySelector(`.user-card[data-status][data-id='${id}']`) || document.querySelector(`.user-card [data-id='${id}']`)?.closest('.user-card');
                if (card) {
                  card.querySelectorAll('.inline-confirm, .inline-reject').forEach(b=>b.remove());
                  const badge = card.querySelector('.status-badge'); if (badge) { badge.textContent = 'Confirmed'; badge.classList.remove('status-pending'); badge.classList.add('status-active'); }
                }
              } catch(e){ Swal.fire('Error','Failed to confirm','error'); }
      } else if (res.isDenied) {
        // Prompt for amount and method
        const { value: formValues } = await Swal.fire({
          title: 'Create payment',
          html:
            '<input id="swal-amount" class="swal2-input" placeholder="Amount">' +
            '<select id="swal-method" class="swal2-select"><option value="bank">Bank Transfer</option><option value="online">Online</option></select>',
          focusConfirm: false,
          preConfirm: () => {
            return {
              amount: document.getElementById('swal-amount').value,
              method: document.getElementById('swal-method').value
            }
          }
        });
        if (!formValues) return;
        const amt = parseFloat(formValues.amount || 0);
        if (!amt || amt <= 0) return Swal.fire('Error','Provide a valid amount','error');
        const fd=new FormData(); fd.append('csrf_token','<?= $csrf ?>'); fd.append('create_payment','1'); fd.append('amount', amt); fd.append('method', formValues.method || 'bank');
        try {
          const payload = await postAction(`index.php?pages=students&action=confirm_registration&id=${id}`, fd);
          // update UI similar to above
          const card = document.querySelector(`.user-card[data-status][data-id='${id}']`) || document.querySelector(`.user-card [data-id='${id}']`)?.closest('.user-card');
          if (card) {
            card.querySelectorAll('.inline-confirm, .inline-reject').forEach(b=>b.remove());
            const badge = card.querySelector('.status-badge'); if (badge) { badge.textContent = 'Confirmed'; badge.classList.remove('status-pending'); badge.classList.add('status-active'); }
          }
        } catch(e){ Swal.fire('Error','Failed to create payment','error'); }
      }
    });
    return;
  }
  const r = e.target.closest('.inline-reject');
    if (r) {
    const id = r.dataset.id;
    if (!id) return Swal.fire('Error','No registration id','error');
    Swal.fire({
      title: 'Reject registration',
      input: 'textarea',
      inputLabel: 'Reason (optional)',
      inputPlaceholder: 'Reason for rejection',
      showCancelButton: true,
      confirmButtonText: 'Reject'
    }).then(result=>{ if (result.isConfirmed) { const fd=new FormData(); fd.append('csrf_token','<?= $csrf ?>'); fd.append('reason', result.value || ''); postAction(`index.php?pages=students&action=reject_registration&id=${id}`, fd).catch(err=>Swal.fire('Error','Failed to reject','error')); } });
    return;
  }
});
</script>

</body>
</html>
