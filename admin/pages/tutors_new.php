<?php
// admin/pages/tutors.php
require_once '../includes/auth.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/csrf.php';

// Compute BASE_URL from configured app_url() to avoid hardcoded '/HIGH-Q' path
$__app_path = parse_url(app_url(), PHP_URL_PATH) ?: '';
define('BASE_URL', rtrim($__app_path, '/'));
$pageTitle = 'Tutors';
$pageSubtitle = 'Manage tutor profiles and listings';

// Check if this is an AJAX request
$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
          strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

// Add SweetAlert2 assets and CSS
$pageCss = '<link rel="stylesheet" href="../assets/css/tutors.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">';

// Only Admin & Sub-Admin
requirePermission('tutors'); // where 'roles' matches the menu slug

// Initialize variables
$csrf = generateToken();
$errors = [];
$success = [];
$q = isset($_GET['q']) ? trim($_GET['q']) : '';

// Fetch tutors with search filter if provided
$query = "SELECT * FROM tutors";
$params = [];
if ($q) {
  $query .= " WHERE name LIKE ? OR subjects LIKE ? OR qualifications LIKE ?";
  $searchTerm = "%{$q}%";
  $params = [$searchTerm, $searchTerm, $searchTerm];
}
$query .= " ORDER BY id ASC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$tutors = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Directory for photo uploads
$uploadDir = __DIR__ . '/../../public/uploads/tutors/';
if (!is_dir($uploadDir)) {
  mkdir($uploadDir, 0755, true);
}

// HANDLE CREATE / EDIT / DELETE
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (isset($_GET['action']) || isset($_POST['action']))) {
  // Get action from either GET or POST
  $action = $_GET['action'] ?? $_POST['action'] ?? '';
  $id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);

  // --- Debug logging: write incoming request details to a CLI-readable file ---
  try {
    $dbgPath = __DIR__ . '/../cli/tutor_post_debug.log';
    $headers = function_exists('getallheaders') ? getallheaders() : [];
    // Fallback: collect common HTTP_* server vars
    if (empty($headers)) {
      foreach ($_SERVER as $k => $v) {
        if (strpos($k, 'HTTP_') === 0) $headers[$k] = $v;
      }
    }
    $raw = @file_get_contents('php://input');
    $debugEntry = [
      'ts' => date('c'),
      'remote_addr' => $_SERVER['REMOTE_ADDR'] ?? null,
      'request_uri' => $_SERVER['REQUEST_URI'] ?? null,
      'is_ajax' => $isAjax,
      'method' => $_SERVER['REQUEST_METHOD'] ?? 'POST',
      'action_param' => $action,
      'id_param' => $id,
      'headers' => $headers,
      'cookies' => $_COOKIE ?? [],
      'post' => $_POST ?? [],
      'files' => $_FILES ?? [],
      'raw' => $raw,
    ];
    @file_put_contents($dbgPath, json_encode($debugEntry, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n\n", FILE_APPEND | LOCK_EX);
  } catch (Throwable $e) {
    // ignore logging failures
  }

  // Verify CSRF token but also log the provided token result to the same debug file
  $provided_csrf = $_POST['csrf_token'] ?? '';
  $csrfOk = false;
  try {
    $csrfOk = verifyCsrfToken($provided_csrf);
  } catch (Throwable $e) {
    $csrfOk = false;
  }
  try {
    $dbgPath = __DIR__ . '/../cli/tutor_post_debug.log';
    @file_put_contents($dbgPath, "CSRF_PROVIDED: " . ($provided_csrf ?: '[empty]') . "\nCSRF_OK: " . ($csrfOk ? '1' : '0') . "\n---\n", FILE_APPEND | LOCK_EX);
  } catch (Throwable $e) {}

  if (!$csrfOk) {
    $errors[] = "Invalid CSRF token.";
    if ($isAjax) {
      http_response_code(403);
      header('Content-Type: application/json');
      echo json_encode(['status' => 'error', 'message' => 'Invalid CSRF token']);
      exit;
    }
  } else {
    $action = $_GET['action'];
    $id     = (int)($_GET['id'] ?? 0);

    if ($action === 'delete' && $id) {
      // Delete logic (no name required)
      $pdo->prepare("DELETE FROM tutors WHERE id=?")->execute([$id]);
      logAction($pdo, $_SESSION['user']['id'], 'tutor_deleted', ['tutor_id' => $id]);
      $success[] = "Tutor deleted.";
      
      if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'success', 'message' => 'Tutor deleted successfully']);
        exit;
      }
    } else {
      // Create / Edit logic
      $name     = trim($_POST['name'] ?? '');
      $title    = trim($_POST['title'] ?? '');
      $years    = trim($_POST['years_experience'] ?? '');
      $long     = trim($_POST['bio'] ?? '');
      $subs     = array_filter(array_map('trim', explode(',', $_POST['subjects'] ?? '')));
      $subjects = json_encode($subs, JSON_UNESCAPED_UNICODE);
      $email    = trim($_POST['email'] ?? '');
      $phone    = trim($_POST['phone'] ?? '');
      $imageUrl = trim($_POST['image_url'] ?? '');

      if (!$name) {
        $errors[] = "Name is required.";
      }

      if (empty($errors)) {
        if ($action === 'create') {
          $photo = $imageUrl ?: null;
          $stmt = $pdo->prepare("INSERT INTO tutors (name, slug, photo, short_bio, long_bio, qualifications, subjects, contact_email, phone, rating, is_featured) VALUES (?,?,?,?,?,?,?,?,?,?,?)");
          $slug = strtolower(preg_replace('/[^a-z0-9]+/', '-', $name));
          $stmt->execute([
            $name,
            $slug,
            $photo,
            $years ?: null,
            $long ?: null,
            $title ?: null,
            $subjects,
            $email ?: null,
            $phone ?: null,
            null,
            0
          ]);
          logAction($pdo, $_SESSION['user']['id'], 'tutor_created', ['slug' => $slug]);
          $success[] = "Tutor '{$name}' created.";
          
          if ($isAjax) {
            header('Content-Type: application/json');
            echo json_encode(['status' => 'success', 'message' => "Tutor '{$name}' created successfully"]);
            exit;
          }
        } elseif ($action === 'edit' && $id) {
          $photo = $imageUrl ?: null;
          $stmt = $pdo->prepare("UPDATE tutors SET name=?, slug=?, photo=?, short_bio=?, long_bio=?, qualifications=?, subjects=?, contact_email=?, phone=?, rating=?, is_featured=?, updated_at=NOW() WHERE id=?");
          $slug = strtolower(preg_replace('/[^a-z0-9]+/', '-', $name));
          $stmt->execute([
            $name,
            $slug,
            $photo,
            $years ?: null,
            $long ?: null,
            $title ?: null,
            $subjects,
            $email ?: null,
            $phone ?: null,
            null,
            0,
            $id
          ]);
          logAction($pdo, $_SESSION['user']['id'], 'tutor_updated', ['tutor_id' => $id]);
          $success[] = "Tutor '{$name}' updated.";
          
          if ($isAjax) {
            header('Content-Type: application/json');
            echo json_encode(['status' => 'success', 'message' => "Tutor '{$name}' updated successfully"]);
            exit;
          }
        }
      }
    }
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tutors Management — Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="bg-slate-50">
<?php include '../includes/header.php'; ?>
<?php include '../includes/sidebar.php'; ?>

<div class="min-h-screen w-full mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6 ml-[var(--sidebar-width)] transition-all duration-300">
    <!-- Header Section -->
    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-emerald-600 via-emerald-500 to-teal-600 p-8 shadow-xl text-white">
        <div class="absolute inset-0 bg-[radial-gradient(circle_at_20%_20%,rgba(255,255,255,0.1),transparent_35%),radial-gradient(circle_at_80%_0%,rgba(255,255,255,0.1),transparent_25%)]"></div>
        <div class="relative flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <p class="text-xs uppercase tracking-[0.2em] text-emerald-100/80">Academic Staff</p>
                <h1 class="mt-2 text-3xl sm:text-4xl font-bold leading-tight">Tutors Management</h1>
                <p class="mt-2 text-emerald-100/90 max-w-2xl">Manage teaching staff and their information</p>
            </div>
            <button id="newTutorBtn" class="flex items-center gap-2 bg-white text-emerald-600 px-5 py-2.5 rounded-xl font-semibold shadow-lg hover:bg-emerald-50 transition-all transform hover:scale-105 active:scale-95">
                <i class='bx bx-plus text-xl'></i>
                <span>Add Tutor</span>
            </button>
        </div>
    </div>

    <!-- Controls & List -->
    <div class="bg-white rounded-2xl shadow-lg border border-slate-100 overflow-hidden">
        <!-- Search -->
        <div class="p-5 border-b border-slate-100 bg-slate-50/50">
            <form method="get" action="index.php" class="relative max-w-md">
                <input type="hidden" name="pages" value="tutors">
                <i class='bx bx-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400'></i>
                <input type="text" name="q" placeholder="Search by name, title, or subject..." value="<?= htmlspecialchars($q) ?>"
                       class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-slate-200 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-200 outline-none transition-all">
            </form>
        </div>

        <!-- Tutors Grid -->
        <div class="p-6 grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
            <?php if (!empty($tutors)): ?>
                <?php foreach ($tutors as $t): ?>
                <div class="tutor-card group relative bg-white rounded-xl border border-slate-200 p-5 hover:shadow-md transition-all duration-200 hover:border-emerald-300 flex flex-col h-full"
                     data-id="<?= $t['id'] ?>" 
                     data-name="<?= htmlspecialchars($t['name'], ENT_QUOTES) ?>" 
                     data-title="<?= htmlspecialchars($t['qualifications'] ?? '', ENT_QUOTES) ?>" 
                     data-subjects="<?= htmlspecialchars(implode(', ', json_decode($t['subjects'] ?? '[]', true)), ENT_QUOTES) ?>" 
                     data-years="<?= htmlspecialchars($t['short_bio'] ?? '', ENT_QUOTES) ?>" 
                     data-bio="<?= htmlspecialchars($t['long_bio'] ?? '', ENT_QUOTES) ?>" 
                     data-image="<?= htmlspecialchars($t['photo'] ?? '', ENT_QUOTES) ?>" 
                     data-email="<?= htmlspecialchars($t['contact_email'] ?? '', ENT_QUOTES) ?>" 
                     data-phone="<?= htmlspecialchars($t['phone'] ?? '', ENT_QUOTES) ?>">
                    
                    <div class="flex items-start gap-4 mb-4">
                        <div class="h-16 w-16 rounded-xl overflow-hidden bg-slate-100 ring-1 ring-slate-200 flex-shrink-0">
                            <?php $photoPath = $t['photo'] ? (strpos($t['photo'], 'http') === 0 ? $t['photo'] : '../../public/' . $t['photo']) : '../../public/assets/images/hq-logo.jpeg'; ?>
                            <img src="<?= htmlspecialchars($photoPath) ?>" alt="<?= htmlspecialchars($t['name']) ?>" class="h-full w-full object-cover">
                        </div>
                        <div>
                            <h3 class="font-bold text-lg text-slate-900 leading-tight"><?= htmlspecialchars($t['name']) ?></h3>
                            <p class="text-sm text-emerald-600 font-medium mt-0.5"><?= htmlspecialchars($t['qualifications'] ?: 'Tutor') ?></p>
                            <p class="text-xs text-slate-500 mt-1"><?= htmlspecialchars($t['short_bio'] ?? '0') ?> years exp.</p>
                        </div>
                    </div>

                    <div class="mb-4 flex-grow">
                        <div class="flex flex-wrap gap-1.5 mb-3">
                            <?php $subjects = json_decode($t['subjects'] ?? '[]', true); ?>
                            <?php if (!empty($subjects)): foreach ($subjects as $subject): ?>
                                <span class="px-2 py-0.5 rounded-md bg-slate-100 text-slate-600 text-xs font-medium border border-slate-200">
                                    <?= htmlspecialchars($subject) ?>
                                </span>
                            <?php endforeach; endif; ?>
                        </div>
                        <p class="text-sm text-slate-600 line-clamp-3 leading-relaxed">
                            <?= htmlspecialchars($t['long_bio'] ?? 'No bio available.') ?>
                        </p>
                    </div>

                    <div class="flex items-center justify-between pt-4 border-t border-slate-100 mt-auto">
                        <span class="text-xs text-slate-400">Added <?= (new DateTime($t['created_at']))->format('M j, Y') ?></span>
                        <div class="flex gap-2">
                            <button type="button" class="edit-btn p-2 text-slate-400 hover:text-emerald-600 hover:bg-emerald-50 rounded-lg transition-colors" onclick="editTutor(<?= $t['id'] ?>)" title="Edit">
                                <i class='bx bx-edit text-lg'></i>
                            </button>
                            <button type="button" class="delete-btn p-2 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition-colors" onclick="deleteTutor(<?= $t['id'] ?>, '<?= htmlspecialchars(addslashes($t['name']), ENT_QUOTES) ?>')" title="Delete">
                                <i class='bx bx-trash text-lg'></i>
                            </button>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-span-full text-center py-12 text-slate-500">
                    <i class='bx bx-user-x text-4xl mb-2'></i>
                    <p>No tutors found matching your criteria.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Tailwind Modal -->
<div id="tutorModal" class="fixed inset-0 z-[100] hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm transition-opacity" id="modalOverlay"></div>
    <div class="fixed inset-0 z-10 overflow-y-auto">
        <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
            <div class="relative transform overflow-hidden rounded-2xl bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg">
                <div class="bg-slate-50 px-4 py-3 sm:px-6 flex justify-between items-center border-b border-slate-100">
                    <h3 class="text-base font-semibold leading-6 text-slate-900" id="tutorModalTitle">Add New Tutor</h3>
                    <button type="button" id="tutorModalClose" class="text-slate-400 hover:text-slate-500">
                        <i class='bx bx-x text-2xl'></i>
                    </button>
                </div>
                
                <div class="px-4 py-5 sm:p-6">
                    <form id="tutorForm" method="post" action="index.php?pages=tutors&action=create" class="space-y-4">
                        <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                        
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-slate-700">Full Name *</label>
                                <input type="text" name="name" id="tName" required class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 sm:text-sm border p-2">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700">Qualifications</label>
                                <input type="text" name="title" id="tTitle" placeholder="e.g. B.Sc" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 sm:text-sm border p-2">
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-slate-700">Subjects</label>
                                <input type="text" name="subjects" id="tSubjects" placeholder="Math, Physics" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 sm:text-sm border p-2">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700">Experience (Yrs)</label>
                                <input type="text" name="years_experience" id="tYears" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 sm:text-sm border p-2">
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-700">Bio</label>
                            <textarea name="bio" id="tBio" rows="3" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 sm:text-sm border p-2"></textarea>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-700">Image URL</label>
                            <input type="text" name="image_url" id="tImageUrl" placeholder="https://..." class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 sm:text-sm border p-2">
                        </div>

                        <div class="pt-2 flex gap-3">
                            <button type="submit" class="flex-1 justify-center rounded-lg border border-transparent bg-emerald-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2">
                                Save Tutor
                            </button>
                            <button type="reset" class="flex-none justify-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 shadow-sm hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2">
                                Clear
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const tutorModal = document.getElementById('tutorModal');
    const overlay = document.getElementById('modalOverlay');
    const closeBtn = document.getElementById('tutorModalClose');
    const newBtn = document.getElementById('newTutorBtn');
    const tutorForm = document.getElementById('tutorForm');
    const modalTitle = document.getElementById('tutorModalTitle');

    const fields = {
      name: document.getElementById('tName'),
      title: document.getElementById('tTitle'),
      subs: document.getElementById('tSubjects'),
      years: document.getElementById('tYears'),
      bio: document.getElementById('tBio'),
      image: document.getElementById('tImageUrl')
    };

    function openModal(mode, data = {}) {
      tutorModal.classList.remove('hidden');
      if (mode === 'edit') {
        modalTitle.textContent = 'Edit Tutor';
        tutorForm.action = `index.php?pages=tutors&action=edit&id=${data.id}`;
        fields.name.value = data.name || '';
        fields.title.value = data.title || '';
        fields.subs.value = data.subjects || '';
        fields.years.value = data.years || '';
        fields.bio.value = data.bio || '';
        fields.image.value = data.image || '';
      } else {
        modalTitle.textContent = 'Add Tutor';
        tutorForm.action = 'index.php?pages=tutors&action=create';
        Object.values(fields).forEach(f => f.value = '');
      }
    }

    function closeModal() {
      tutorModal.classList.add('hidden');
    }

    // Handle form submission via AJAX
    tutorForm.addEventListener('submit', async function(e) {
      e.preventDefault();
      const submitBtn = tutorForm.querySelector('button[type="submit"]');
      const originalText = submitBtn.textContent;
      submitBtn.disabled = true;
      submitBtn.textContent = 'Processing...';
      
      try {
        const formData = new FormData(this);
        const response = await fetch(this.action, {
          method: 'POST',
          body: formData,
          headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
        });
        
        if (!response.ok) throw new Error(`Server error: ${response.status}`);
        
        const data = await response.json();
        if (data.status === 'error') throw new Error(data.message || 'Failed to save tutor');
        
        await Swal.fire({ icon: 'success', title: 'Success', text: data.message || 'Tutor saved successfully!' });
        closeModal();
        window.location.reload();
        
      } catch (error) {
        console.error('Error:', error);
        Swal.fire({ icon: 'error', title: 'Error', text: error.message || 'Failed to save tutor.' });
      } finally {
        submitBtn.disabled = false;
        submitBtn.textContent = originalText;
      }
    });

    closeBtn.addEventListener('click', closeModal);
    overlay.addEventListener('click', closeModal);
    document.addEventListener('keydown', e => e.key === 'Escape' && closeModal());
    newBtn.addEventListener('click', () => openModal('create'));

    // Expose edit function globally for onclick handlers
    window.editTutor = function(id) {
        const card = document.querySelector(`.tutor-card[data-id='${id}']`);
        if (!card) return;
        openModal('edit', {
          id: card.dataset.id,
          name: card.dataset.name,
          title: card.dataset.title,
          image: card.dataset.image,
          years: card.dataset.years,
          bio: card.dataset.bio,
          subjects: card.dataset.subjects
        });
    };

    window.deleteTutor = function(id, name) {
        Swal.fire({
          title: "Delete Tutor?",
          text: "Are you sure you want to delete " + name + "?",
          icon: "warning",
          showCancelButton: true,
          confirmButtonColor: "#e11d48",
          cancelButtonColor: "#64748b",
          confirmButtonText: "Yes, delete it!"
        }).then((result) => {
          if (result.isConfirmed) {
            const form = document.createElement("form");
            form.method = "POST";
            form.action = `index.php?pages=tutors&action=delete&id=${id}`;
            const csrfInput = document.createElement("input");
            csrfInput.type = "hidden";
            csrfInput.name = "csrf_token";
            csrfInput.value = "<?= $csrf ?>";
            form.appendChild(csrfInput);
            document.body.appendChild(form);
            form.submit();
          }
        });
    };
});
</script>
</body>
</html>
