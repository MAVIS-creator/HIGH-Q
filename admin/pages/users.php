<?php
// admin/pages/users.php
require_once '../includes/auth.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/csrf.php';

// Only Admins & Sub-Admins
requirePermission('users');

// Generate CSRF token
$csrf = generateToken('users_form');

// Fetch roles for dropdowns
$all_roles = $pdo->query("SELECT id, name, slug FROM roles ORDER BY name ASC")->fetchAll();

// Ensure users.css is loaded after admin.css by providing $pageCss for header
$pageCss = '';
// Page title and subtitle for header
$pageTitle = 'User Management';
$pageSubtitle = 'Manage user accounts, roles, and permissions';

// Server-side action handling (POST) and AJAX view (GET)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action'])) {
  $action = $_GET['action'];
  $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

  // CSRF
  $token = $_POST['csrf_token'] ?? '';
  if (!verifyToken('users_form', $token)) {
    header('Location: index.php?pages=users'); exit;
  }

  // Basic protections
  $currentUserId = $_SESSION['user']['id'];
  $isMainAdmin = ($id === 1);

  if ($action === 'approve') {
    // Approve a pending user and assign role (respect roles.max_count)
    $role_id = isset($_POST['role_id']) ? (int)$_POST['role_id'] : 0;
    if (!$role_id) { header('Location: index.php?pages=users'); exit; }

    // Check role max_count
    $stmt = $pdo->prepare('SELECT max_count FROM roles WHERE id = ?');
    $stmt->execute([$role_id]);
    $max = $stmt->fetchColumn();
    if ($max !== false && $max !== null && (int)$max > 0) {
      $stmt = $pdo->prepare('SELECT COUNT(*) FROM users WHERE role_id = ? AND is_active = 1');
      $stmt->execute([$role_id]);
      $count = (int)$stmt->fetchColumn();
      if ($count >= (int)$max) {
        // Role is full
        header('Location: index.php?pages=users'); exit;
      }

        if ($action === 'resend_verification') {
          // Resend verification email to user (admin action)
          $u = $pdo->prepare('SELECT id, email, name, is_active, email_verification_sent_at FROM users WHERE id = ? LIMIT 1');
          $u->execute([$id]); $usr = $u->fetch(PDO::FETCH_ASSOC);
          if (!$usr) { header('Location: index.php?pages=users'); exit; }

          // Only resend if user is not active
          if ((int)$usr['is_active'] === 0) {
            // Rate limit: allow resend every 10 minutes by default (env override)
            $wait = getenv('VERIFICATION_RESEND_WAIT_MIN') ? intval(getenv('VERIFICATION_RESEND_WAIT_MIN')) : 10;
            $canSend = true;
            if (!empty($usr['email_verification_sent_at'])) {
              $sentTs = strtotime($usr['email_verification_sent_at']);
              if ($sentTs !== false && (time() - $sentTs) < ($wait * 60)) $canSend = false;
            }

            if ($canSend) {
              // generate new token
              $token = bin2hex(random_bytes(32));
              $upd = $pdo->prepare('UPDATE users SET email_verification_token = ?, email_verification_sent_at = NOW() WHERE id = ?');
              $upd->execute([$token, $id]);

              // Use admin_url() so ADMIN_URL from .env (if set) is honoured and fallbacks apply consistently
              $verifyUrl = admin_url('verify_email.php?token=' . urlencode($token));

              $subject = 'Please verify your email';
              $html = '<p>Hi ' . htmlspecialchars($usr['name'] ?? '') . ',</p>' .
                      '<p>Please verify your email by clicking <a href="' . htmlspecialchars($verifyUrl) . '">this link</a>.</p>';
              @sendEmail($usr['email'], $subject, $html);
              logAction($pdo, $_SESSION['user']['id'], 'resend_verification', ['user_id'=>$id]);
            }
          }

          header('Location: index.php?pages=users'); exit;
        }
    }

    $stmt = $pdo->prepare('UPDATE users SET role_id = ?, is_active = 1, updated_at = NOW() WHERE id = ?');
    $stmt->execute([$role_id, $id]);
    logAction($pdo, $currentUserId, 'approve_user', ['user_id' => $id, 'role_id' => $role_id]);
    // Send approval email to the user if email present
    try {
      $u = $pdo->prepare('SELECT email, name FROM users WHERE id = ? LIMIT 1'); $u->execute([$id]); $usr = $u->fetch(PDO::FETCH_ASSOC);
      if ($usr && filter_var($usr['email'] ?? '', FILTER_VALIDATE_EMAIL) && function_exists('sendEmail')) {
        $sub = 'Your account has been approved';
        $body = '<p>Hi ' . htmlspecialchars($usr['name'] ?? '') . ',</p><p>Your account has been approved and activated. You can now login.</p>';
        @sendEmail($usr['email'], $sub, $body);
      }
    } catch(Throwable $e){}
    header('Location: index.php?pages=users'); exit;
  }

  if ($action === 'banish') {
    // Prevent banishing main admin or yourself
    if ($id === $currentUserId || $id === 1) { header('Location: index.php?pages=users'); exit; }
    $stmt = $pdo->prepare('UPDATE users SET is_active = 2, updated_at = NOW() WHERE id = ?');
    $stmt->execute([$id]);
    logAction($pdo, $currentUserId, 'banish_user', ['user_id' => $id]);
    header('Location: index.php?pages=users'); exit;
  }

  if ($action === 'reactivate') {
    // Reactivate a banned/pending user
    $stmt = $pdo->prepare('UPDATE users SET is_active = 1, updated_at = NOW() WHERE id = ?');
    $stmt->execute([$id]);
    logAction($pdo, $currentUserId, 'reactivate_user', ['user_id' => $id]);
    header('Location: index.php?pages=users'); exit;
  }

  if ($action === 'edit') {
    // Edit user details from modal
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $role_id = isset($_POST['role_id']) ? (int)$_POST['role_id'] : null;
    $is_active = isset($_POST['is_active']) ? (int)$_POST['is_active'] : null;

    // Don't allow editing main admin by non-main admin
    if ($id === 1 && $_SESSION['user']['id'] !== 1) { header('Location: index.php?pages=users'); exit; }

    $stmt = $pdo->prepare('UPDATE users SET name = ?, email = ?, role_id = ?, is_active = ?, updated_at = NOW() WHERE id = ?');
    $stmt->execute([$name, $email, $role_id, $is_active, $id]);
    logAction($pdo, $currentUserId, 'edit_user', ['user_id' => $id]);
    header('Location: index.php?pages=users'); exit;
  }
}

// AJAX: return JSON for a single user view
if (isset($_GET['action']) && $_GET['action'] === 'view' && isset($_GET['id'])) {
  $id = (int)$_GET['id'];
  $stmt = $pdo->prepare('SELECT u.*, u.role_id AS role_id, r.name AS role_name, r.slug AS role_slug,
  (SELECT COUNT(*) FROM posts WHERE author_id = u.id) AS posts_count,
    (SELECT COUNT(*) FROM comments WHERE user_id = u.id) AS comments_count
    FROM users u LEFT JOIN roles r ON r.id = u.role_id WHERE u.id = ?');
  $stmt->execute([$id]);
  $u = $stmt->fetch(PDO::FETCH_ASSOC);
  if (!$u) {
    header('Content-Type: application/json'); echo json_encode(['error' => 'User not found']); exit;
  }
  $u['status'] = $u['is_active']==1 ? 'Active' : ($u['is_active']==0 ? 'Pending' : 'Banned');
  $u['status_value'] = (int)$u['is_active'];
  header('Content-Type: application/json'); echo json_encode($u); exit;
}

// Handle receipt upload by admin on user profile (AJAX file upload)
if (isset($_GET['action']) && $_GET['action'] === 'upload_receipt' && isset($_GET['id']) && $_SERVER['REQUEST_METHOD']==='POST') {
  $id = (int)$_GET['id'];
  $token = $_POST['csrf_token'] ?? '';
  if (!verifyToken('users_form', $token)) { header('Content-Type: application/json'); echo json_encode(['error'=>'Invalid CSRF']); exit; }
  if (empty($_FILES['receipt']) || $_FILES['receipt']['error'] !== UPLOAD_ERR_OK) { header('Content-Type: application/json'); echo json_encode(['error'=>'No file uploaded']); exit; }
  $f = $_FILES['receipt'];
  $ext = pathinfo($f['name'], PATHINFO_EXTENSION);
  $allowed = ['jpg','jpeg','png','pdf'];
  // Size limit 5MB
  if ($f['size'] > 5 * 1024 * 1024) { header('Content-Type: application/json'); echo json_encode(['error'=>'File too large (max 5MB)']); exit; }
  // MIME check
  $finfo = finfo_open(FILEINFO_MIME_TYPE);
  $mime = finfo_file($finfo, $f['tmp_name']);
  finfo_close($finfo);
  $mimeAllowed = ['image/jpeg','image/png','application/pdf'];
  if (!in_array($mime, $mimeAllowed) || !in_array(strtolower($ext), $allowed)) { header('Content-Type: application/json'); echo json_encode(['error'=>'Invalid file type']); exit; }
  $uploadDir = __DIR__ . '/../../public/uploads/receipts/'; if (!is_dir($uploadDir)) mkdir($uploadDir,0755,true);
  $fileName = 'receipt_' . $id . '_' . time() . '.' . $ext;
  $target = $uploadDir . $fileName;
  if (!move_uploaded_file($f['tmp_name'], $target)) { header('Content-Type: application/json'); echo json_encode(['error'=>'Failed to move file']); exit; }

  // Create a payments record or attach to existing pending payment for user
  $stmt = $pdo->prepare('SELECT id FROM payments WHERE student_id = ? AND status = "pending" ORDER BY created_at DESC LIMIT 1');
  $stmt->execute([$id]); $p = $stmt->fetch();
  if ($p) {
    $pid = $p['id'];
    $upd = $pdo->prepare('UPDATE payments SET receipt_path = ?, updated_at = NOW() WHERE id = ?');
    $upd->execute(["uploads/receipts/{$fileName}", $pid]);
  } else {
    $ref = 'RCPT-' . date('YmdHis') . '-' . substr(bin2hex(random_bytes(3)),0,6);
    $ins = $pdo->prepare('INSERT INTO payments (student_id, amount, payment_method, reference, status, receipt_path, created_at) VALUES (?, 0, "bank_transfer", ?, "uploaded", ?, NOW())');
    $ins->execute([$id, $ref, "uploads/receipts/{$fileName}"]); $pid = $pdo->lastInsertId();
  }

  // Notify admins via log
  logAction($pdo, $_SESSION['user']['id'], 'upload_receipt', ['user_id'=>$id, 'payment_id'=>$pid, 'path'=>"uploads/receipts/{$fileName}"]);
  header('Content-Type: application/json'); echo json_encode(['ok'=>true,'payment_id'=>$pid,'path'=>"uploads/receipts/{$fileName}"]); exit;
}

// Fetch counts for summary
$total_users   = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$active_users  = $pdo->query("SELECT COUNT(*) FROM users WHERE is_active=1")->fetchColumn();
$pending_users = $pdo->query("SELECT COUNT(*) FROM users WHERE is_active=0")->fetchColumn();
$banned_users  = $pdo->query("SELECT COUNT(*) FROM users WHERE is_active=2")->fetchColumn();

// Fetch users
$users = $pdo->query("
    SELECT u.*, r.name AS role_name, r.slug AS role_slug
    FROM users u
    LEFT JOIN roles r ON r.id = u.role_id
    ORDER BY u.created_at DESC
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - HIGH Q SOLID ACADEMY</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="bg-slate-50">
<?php include '../includes/header.php'; ?>
<?php include '../includes/sidebar.php'; ?>

<div class="min-h-screen w-full mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6 ml-[var(--sidebar-width)] transition-all duration-300">
    <!-- Header Section -->
    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-blue-600 via-blue-500 to-indigo-600 p-8 shadow-xl text-white">
        <div class="absolute inset-0 bg-[radial-gradient(circle_at_20%_20%,rgba(255,255,255,0.1),transparent_35%),radial-gradient(circle_at_80%_0%,rgba(255,255,255,0.1),transparent_25%)]"></div>
        <div class="relative flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <p class="text-xs uppercase tracking-[0.2em] text-blue-100/80">Administration</p>
                <h1 class="mt-2 text-3xl sm:text-4xl font-bold leading-tight">User Management</h1>
                <p class="mt-2 text-blue-100/90 max-w-2xl">Manage user accounts, roles, and permissions</p>
            </div>
            <div class="flex items-center gap-3">
                <div class="bg-white/10 backdrop-blur-md border border-white/20 rounded-xl p-3 text-center min-w-[100px]">
                    <div class="text-2xl font-bold"><?= $total_users ?></div>
                    <div class="text-xs text-blue-100">Total Users</div>
                </div>
                <div class="bg-white/10 backdrop-blur-md border border-white/20 rounded-xl p-3 text-center min-w-[100px]">
                    <div class="text-2xl font-bold"><?= $active_users ?></div>
                    <div class="text-xs text-blue-100">Active</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Controls & List -->
    <div class="bg-white rounded-2xl shadow-lg border border-slate-100 overflow-hidden">
        <!-- Filters -->
        <div class="p-5 border-b border-slate-100 bg-slate-50/50 flex flex-col sm:flex-row gap-4 justify-between items-center">
            <div class="relative w-full sm:w-96">
                <i class='bx bx-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400'></i>
                <input type="text" id="searchInput" placeholder="Search users..." 
                       class="w-full pl-10 pr-4 py-2 rounded-xl border border-slate-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 outline-none transition-all">
            </div>
            <div class="flex gap-3 w-full sm:w-auto">
                <select id="statusFilter" class="px-4 py-2 rounded-xl border border-slate-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 outline-none bg-white text-sm">
                    <option value="">All Status</option>
                    <option value="active">Active</option>
                    <option value="pending">Pending</option>
                    <option value="banned">Banned</option>
                </select>
                <select id="roleFilter" class="px-4 py-2 rounded-xl border border-slate-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 outline-none bg-white text-sm">
                    <option value="">All Roles</option>
                    <?php foreach ($all_roles as $r): ?>
                        <option value="<?= htmlspecialchars($r['slug']) ?>"><?= htmlspecialchars($r['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <!-- User Grid -->
        <div class="p-6 grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6" id="userTableBody">
            <?php foreach ($users as $u): 
                $status = $u['is_active']==1 ? 'Active' : ($u['is_active']==0 ? 'Pending' : 'Banned');
                $statusColor = $u['is_active']==1 ? 'bg-emerald-100 text-emerald-700' : ($u['is_active']==0 ? 'bg-amber-100 text-amber-700' : 'bg-rose-100 text-rose-700');
                $roleSlug = strtolower($u['role_slug'] ?? 'student');
            ?>
            <div class="user-card group relative bg-white rounded-xl border border-slate-200 p-5 hover:shadow-md transition-all duration-200 hover:border-blue-300"
                 data-status="<?= $u['is_active']==1?'active':($u['is_active']==0?'pending':'banned') ?>" 
                 data-role="<?= $roleSlug ?>">
                
                <div class="flex items-start justify-between mb-4">
                    <div class="flex items-center gap-4">
                        <div class="h-12 w-12 rounded-full overflow-hidden bg-slate-100 ring-2 ring-white shadow-sm">
                            <img src="<?= htmlspecialchars($u['avatar'] ? app_url($u['avatar']) : app_url('public/assets/images/hq-logo.jpeg')) ?>" 
                                 alt="Avatar" class="h-full w-full object-cover">
                        </div>
                        <div>
                            <h3 class="font-semibold text-slate-900 card-name"><?= htmlspecialchars($u['name']) ?></h3>
                            <p class="text-xs text-slate-500 card-email"><?= htmlspecialchars($u['email']) ?></p>
                        </div>
                    </div>
                    <div class="flex flex-col items-end gap-2">
                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-medium uppercase tracking-wider bg-slate-100 text-slate-600">
                            <?= htmlspecialchars($u['role_name'] ?? 'Student') ?>
                        </span>
                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-medium uppercase tracking-wider <?= $statusColor ?>">
                            <?= $status ?>
                        </span>
                    </div>
                </div>

                <div class="flex items-center justify-between pt-4 border-t border-slate-100">
                    <div class="text-xs text-slate-400">
                        Joined <?= date('M j, Y', strtotime($u['created_at'])) ?>
                    </div>
                    <div class="flex items-center gap-2">
                        <button class="btn-view p-2 text-slate-400 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-colors" 
                                data-user-id="<?= $u['id'] ?>" title="View Details">
                            <i class='bx bx-show text-lg'></i>
                        </button>
                        <button class="btn-edit p-2 text-slate-400 hover:text-amber-600 hover:bg-amber-50 rounded-lg transition-colors" 
                                data-user-id="<?= $u['id'] ?>" title="Edit User">
                            <i class='bx bx-edit text-lg'></i>
                        </button>
                        
                        <!-- Actions Dropdown Logic Simplified for UI -->
                        <?php if ($_SESSION['user']['role_slug']==='admin'): ?>
                            <?php if($u['is_active']===0): ?>
                                <form method="post" action="index.php?pages=users&action=approve&id=<?= $u['id'] ?>" class="inline-block">
                                    <input type="hidden" name="csrf_token" value="<?= $csrf; ?>">
                                    <input type="hidden" name="role_id" value="<?= $u['role_id'] ?: 3 ?>"> <!-- Default to student if not set -->
                                    <button type="submit" class="p-2 text-emerald-500 hover:bg-emerald-50 rounded-lg transition-colors" title="Approve">
                                        <i class='bx bx-check text-lg'></i>
                                    </button>
                                </form>
                            <?php endif; ?>
                            
                            <?php if ($u['id'] != 1 && $u['id'] != $_SESSION['user']['id']): ?>
                                <?php if($u['is_active']!==2): ?>
                                    <form method="post" action="index.php?pages=users&action=banish&id=<?= $u['id'] ?>" class="inline-block">
                                        <input type="hidden" name="csrf_token" value="<?= $csrf; ?>">
                                        <button type="submit" class="p-2 text-rose-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition-colors" title="Ban/Deactivate">
                                            <i class='bx bx-block text-lg'></i>
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <form method="post" action="index.php?pages=users&action=reactivate&id=<?= $u['id'] ?>" class="inline-block">
                                        <input type="hidden" name="csrf_token" value="<?= $csrf; ?>">
                                        <button type="submit" class="p-2 text-emerald-500 hover:bg-emerald-50 rounded-lg transition-colors" title="Reactivate">
                                            <i class='bx bx-refresh text-lg'></i>
                                        </button>
                                    </form>
                                <?php endif; ?>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- Tailwind Modal -->
<div id="userModal" class="fixed inset-0 z-[100] hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm transition-opacity" id="modalOverlay"></div>
    <div class="fixed inset-0 z-10 overflow-y-auto">
        <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
            <div class="relative transform overflow-hidden rounded-2xl bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg">
                
                <!-- Modal Header -->
                <div class="bg-slate-50 px-4 py-3 sm:px-6 flex justify-between items-center border-b border-slate-100">
                    <h3 class="text-base font-semibold leading-6 text-slate-900" id="modalTitle">User Details</h3>
                    <button type="button" id="userModalClose" class="text-slate-400 hover:text-slate-500">
                        <i class='bx bx-x text-2xl'></i>
                    </button>
                </div>

                <!-- Modal Body -->
                <div class="px-4 py-5 sm:p-6">
                    <!-- Tabs -->
                    <div class="flex space-x-1 rounded-xl bg-slate-100 p-1 mb-6">
                        <button class="tab-btn w-full rounded-lg py-2.5 text-sm font-medium leading-5 text-slate-700 ring-white ring-opacity-60 ring-offset-2 ring-offset-blue-400 focus:outline-none focus:ring-2 shadow bg-white" data-tab="viewTab">
                            Profile
                        </button>
                        <button class="tab-btn w-full rounded-lg py-2.5 text-sm font-medium leading-5 text-slate-500 hover:text-slate-700 focus:outline-none" data-tab="editTab">
                            Edit
                        </button>
                    </div>

                    <!-- View Tab -->
                    <div id="viewTab" class="tab-pane block space-y-4">
                        <div class="flex items-center gap-4 mb-6">
                            <img id="mAvatar" src="" alt="Avatar" class="h-16 w-16 rounded-full object-cover ring-2 ring-slate-100">
                            <div>
                                <h4 id="mName" class="text-lg font-bold text-slate-900"></h4>
                                <div class="flex gap-2 mt-1">
                                    <span id="mRole" class="px-2 py-0.5 rounded text-xs font-medium bg-slate-100 text-slate-600"></span>
                                    <span id="mStatus" class="px-2 py-0.5 rounded text-xs font-medium"></span>
                                </div>
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-4 text-sm">
                            <div>
                                <p class="text-slate-500 text-xs">Email</p>
                                <p id="mEmail" class="font-medium text-slate-900 break-all"></p>
                            </div>
                            <div>
                                <p class="text-slate-500 text-xs">Last Login</p>
                                <p id="mLastLogin" class="font-medium text-slate-900"></p>
                            </div>
                            <div>
                                <p class="text-slate-500 text-xs">Created</p>
                                <p id="mCreated" class="font-medium text-slate-900"></p>
                            </div>
                            <div>
                                <p class="text-slate-500 text-xs">Activity</p>
                                <p class="font-medium text-slate-900"><span id="mPosts">0</span> Posts, <span id="mComments">0</span> Comments</p>
                            </div>
                        </div>
                    </div>

                    <!-- Edit Tab -->
                    <div id="editTab" class="tab-pane hidden">
                        <form id="editForm" method="post" class="space-y-4">
                            <input type="hidden" name="csrf_token" value="<?= $csrf; ?>">
                            <div>
                                <label class="block text-sm font-medium text-slate-700">Name</label>
                                <input type="text" name="name" id="fName" required class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm border p-2">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700">Email</label>
                                <input type="email" name="email" id="fEmail" required class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm border p-2">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700">Role</label>
                                <select name="role_id" id="fRole" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm border p-2">
                                    <?php foreach ($all_roles as $r): ?>
                                    <option value="<?= $r['id'] ?>"><?= htmlspecialchars($r['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700">Status</label>
                                <select name="is_active" id="fStatus" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm border p-2">
                                    <option value="1">Active</option>
                                    <option value="0">Pending</option>
                                    <option value="2">Banned</option>
                                </select>
                            </div>
                            <div class="pt-4">
                                <button type="submit" class="w-full inline-flex justify-center rounded-lg border border-transparent bg-blue-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                                    Save Changes
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

<script>
// Modal Logic
const userModal = document.getElementById('userModal');
const overlay = document.getElementById('modalOverlay');
const closeBtn = document.getElementById('userModalClose');

function openModal() { userModal.classList.remove('hidden'); }
function closeModal() { userModal.classList.add('hidden'); }

overlay.addEventListener('click', closeModal);
closeBtn.addEventListener('click', closeModal);
document.addEventListener('keydown', e => { if(e.key === 'Escape') closeModal(); });

// Tabs Logic
const tabButtons = document.querySelectorAll('.tab-btn');
const tabPanes = document.querySelectorAll('.tab-pane');

function activateTab(id) {
    tabButtons.forEach(btn => {
        if(btn.dataset.tab === id) {
            btn.classList.add('bg-white', 'shadow', 'text-slate-700');
            btn.classList.remove('text-slate-500', 'hover:text-slate-700');
        } else {
            btn.classList.remove('bg-white', 'shadow', 'text-slate-700');
            btn.classList.add('text-slate-500', 'hover:text-slate-700');
        }
    });
    tabPanes.forEach(pane => {
        if(pane.id === id) pane.classList.remove('hidden');
        else pane.classList.add('hidden');
    });
}

tabButtons.forEach(btn => btn.addEventListener('click', () => activateTab(btn.dataset.tab)));

// Data Loading
async function loadUser(id, mode='view'){
  const res = await fetch((window.HQ_ADMIN_BASE || '') + '/index.php?pages=users&action=view&id=' + encodeURIComponent(id), { credentials: 'same-origin' });
  let data = null;
  try {
    data = await res.json();
  } catch (e) {
    alert('Session expired. Please login again.'); window.location = (window.HQ_ADMIN_BASE || '') + '/login.php';
    return;
  }
  if(data.error) {
    alert(data.error);
    return;
  }

  // Populate View
  document.getElementById('mName').textContent = data.name;
  document.getElementById('mEmail').textContent = data.email;
  document.getElementById('mRole').textContent = data.role_name;
  
  const statusEl = document.getElementById('mStatus');
  statusEl.textContent = data.status;
  statusEl.className = `px-2 py-0.5 rounded text-xs font-medium ${data.status_value===1 ? 'bg-emerald-100 text-emerald-700' : (data.status_value===0 ? 'bg-amber-100 text-amber-700' : 'bg-rose-100 text-rose-700')}`;

  document.getElementById('mLastLogin').textContent = data.last_login ?? '—';
  document.getElementById('mCreated').textContent = data.created_at ?? '—';
  document.getElementById('mPosts').textContent = data.posts_count;
  document.getElementById('mComments').textContent = data.comments_count;
  document.getElementById('mAvatar').src = data.avatar ? ((window.HQ_APP_BASE || '') + '/' + data.avatar.replace(/^\/+/, '')) : ((window.HQ_APP_BASE || '') + '/public/assets/images/hq-logo.jpeg');

  // Populate Edit
  const form = document.getElementById('editForm');
  form.action = `index.php?pages=users&action=edit&id=${data.id}`;
  document.getElementById('fName').value = data.name;
  document.getElementById('fEmail').value = data.email;
  document.getElementById('fRole').value = data.role_id ?? '';
  document.getElementById('fStatus').value = data.status_value;

  activateTab(mode==='edit'?'editTab':'viewTab');
  openModal();
}

document.querySelectorAll('.btn-view').forEach(btn=>btn.addEventListener('click', e=>{
  e.preventDefault(); loadUser(btn.dataset.userId,'view');
}));
document.querySelectorAll('.btn-edit').forEach(btn=>btn.addEventListener('click', e=>{
  e.preventDefault(); loadUser(btn.dataset.userId,'edit');
}));

// Search Filter
const searchInput = document.getElementById('searchInput');
const statusFilter = document.getElementById('statusFilter');
const roleFilter = document.getElementById('roleFilter');
const rows = document.querySelectorAll('#userTableBody .user-card');

function filterTable(){
  const search = searchInput.value.toLowerCase();
  const status = statusFilter.value;
  const role   = roleFilter.value;

  rows.forEach(row=>{
    const nameEl  = row.querySelector('.card-name');
    const emailEl = row.querySelector('.card-email');
    const name  = nameEl ? nameEl.textContent.toLowerCase() : '';
    const email = emailEl ? emailEl.textContent.toLowerCase() : '';
    const rowStatus = row.dataset.status;
    const rowRole   = row.dataset.role;

    let match = true;
    if(search && !(name.includes(search) || email.includes(search))) match = false;
    if(status && rowStatus!==status) match = false;
    if(role && rowRole!==role) match = false;

    row.style.display = match ? '' : 'none';
  });
}

searchInput.addEventListener('input', filterTable);
statusFilter.addEventListener('change', filterTable);
roleFilter.addEventListener('change', filterTable);
</script>
</body>
</html>
