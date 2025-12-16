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
.tutor-card .status {
    position: absolute;
    top: 12px;
    right: 12px;
    padding: 6px 12px;
    border-radius: 8px;
    font-size: 13px;
    font-weight:600;
    box-shadow:0 2px 8px rgba(0,0,0,0.1);
}
.tutor-card .status.active {
    background: #ffd700;
    color: #000;
}
.tutor-card .status.normal {
    background: #e8e8e8;
    color: #444;
}
.tutor-card .date {
    position: absolute;
    bottom: 12px;
    right: 12px;
    font-size: 12px;
    color: #888;
    background:rgba(255,255,255,0.9);
    padding:4px 8px;
    border-radius:6px;
}
.tutor-photo {
    width: 100%;
    height: 200px;
    overflow: hidden;
}
.tutor-photo img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}
.tutor-info {
    padding: 1.25rem;
}
.tutor-info h3 {
    margin: 0 0 0.5rem;
    font-size: 1.3rem;
    color:#111;
    font-weight:700;
}
.tutor-info .role {
    color: #777;
    margin-bottom: 0.5rem;
    font-size:0.95rem;
}
.tutor-info .subjects {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    margin-bottom: 1rem;
}
.tutor-info .subjects span {
    background: #f0f0f0;
    padding: 2px 8px;
    border-radius: 12px;
    font-size: 0.9rem;
}
.tutor-actions {
    display: flex;
    gap: 0.5rem;
    margin-top: 1rem;
}
.tutor-actions button {
    padding: 5px 10px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
}
.tutor-actions .edit-btn {
    background: #3085d6;
    color: white;
}
.tutor-actions .delete-btn {
    background: #d33;
    color: white;
}

.btn-cancel {
  background: #e0e0e0;
  color: #333;
  margin-left: 0.5rem;
  border: none;
  border-radius: 4px;
  padding: 5px 12px;
  cursor: pointer;
}
.btn-cancel:hover {
  background: #ccc;
}

/* Modal form label styling */
.modal-content label {
  color: #1a73e8; /* blue-ish label color */
  font-weight: 600;
}

// Only Admin & Sub-Admin
</style>';

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
      // ✅ Delete logic (no name required)
      $pdo->prepare("DELETE FROM tutors WHERE id=?")->execute([$id]);
      logAction($pdo, $_SESSION['user']['id'], 'tutor_deleted', ['tutor_id' => $id]);
      $success[] = "Tutor deleted.";
      
      if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'success', 'message' => 'Tutor deleted successfully']);
        exit;
      }
    } else {
      // ✅ Create / Edit logic
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
<title>Tutors Management — Admin</title>
<link rel="stylesheet" href="../assets/css/admin.css">
<link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
</head>

<body>
  <?php include '../includes/header.php'; ?>
  <?php include '../includes/sidebar.php'; ?>

  <div class="container">
    <div class="tutors-page">
      <div class="tutors-header">
        <div class="tutors-header-info">
          <h1>Tutors Management</h1>
          <p>Manage teaching staff and their information</p>
        </div>
        <button id="newTutorBtn" class="btn-add-tutor">
          <i class="bx bx-plus"></i> Add Tutor
        </button>
      </div>

      <?php if (!empty($success) || !empty($errors)): ?>
        <script>
          document.addEventListener('DOMContentLoaded', function() {
            <?php if (!empty($success)): ?>
              Swal.fire({
                icon: 'success',
                title: 'Success',
                html: '<?php echo implode("<br>", array_map("htmlspecialchars", $success)); ?>',
                confirmButtonColor: '#3085d6'
              });
            <?php endif; ?>
            <?php if (!empty($errors)): ?>
              Swal.fire({
                icon: 'error',
                title: 'Error',
                html: '<?php echo implode("<br>", array_map("htmlspecialchars", $errors)); ?>',
                confirmButtonColor: '#d33'
              });
            <?php endif; ?>
          });
        </script>
      <?php endif; ?>

      <div class="tutors-search-section">
        <h3>Search Tutors</h3>
        <form method="get" action="index.php?pages=tutors" class="search-form">
          <input type="text" name="q" placeholder="Search by name, title, or subject…" value="<?= htmlspecialchars($q) ?>">
        </form>
      </div>

      <div class="tutors-grid">
        <?php if (!empty($tutors)): ?>
          <?php foreach ($tutors as $t): ?>
            <div class="tutor-card" data-id="<?= $t['id'] ?>" data-name="<?= htmlspecialchars($t['name'], ENT_QUOTES) ?>" data-title="<?= htmlspecialchars($t['qualifications'] ?? '', ENT_QUOTES) ?>" data-subjects="<?= htmlspecialchars(implode(', ', json_decode($t['subjects'] ?? '[]', true)), ENT_QUOTES) ?>" data-years="<?= htmlspecialchars($t['short_bio'] ?? '', ENT_QUOTES) ?>" data-bio="<?= htmlspecialchars($t['long_bio'] ?? '', ENT_QUOTES) ?>" data-image="<?= htmlspecialchars($t['photo'] ?? '', ENT_QUOTES) ?>" data-email="<?= htmlspecialchars($t['contact_email'] ?? '', ENT_QUOTES) ?>" data-phone="<?= htmlspecialchars($t['phone'] ?? '', ENT_QUOTES) ?>">
              <div class="tutor-card-header">
                <div class="tutor-photo">
                  <?php
                  $photoPath = $t['photo'] ? (strpos($t['photo'], 'http') === 0 ? $t['photo'] : '../../public/' . $t['photo']) : '../../public/assets/images/hq-logo.jpeg';
                  ?>
                  <img src="<?= htmlspecialchars($photoPath) ?>" alt="<?= htmlspecialchars($t['name']) ?>">
                </div>
                <div class="tutor-card-meta">
                  <h3><?= htmlspecialchars($t['name']) ?></h3>
                  <p class="role"><?= htmlspecialchars($t['qualifications'] ?: 'Not specified') ?></p>
                </div>
              </div>

              <div class="tutor-card-subjects">
                <strong>Subjects:</strong>
              </div>
              <div class="tutor-card-subjects">
                <?php
                $subjects = json_decode($t['subjects'] ?? '[]', true);
                if (!empty($subjects)):
                  foreach ($subjects as $subject):

                ?>
                    <span><?= htmlspecialchars($subject) ?></span>
                <?php
                  endforeach;
                endif;
                ?>
              </div>

              <p class="tutor-experience"><?= htmlspecialchars($t['short_bio'] ?? '') ?> years of experience</p>
              <p class="tutor-description"><?= htmlspecialchars($t['long_bio'] ?? '') ?></p>

              <div class="tutor-actions">
                <button type="button" class="edit-btn" onclick="editTutor(<?= $t['id'] ?>)">
                  <i class="bx bx-edit"></i> Edit
                </button>
                <button type="button" class="delete-btn" onclick="deleteTutor(<?= $t['id'] ?>, '<?= htmlspecialchars(addslashes($t['name']), ENT_QUOTES) ?>')">
                  <i class="bx bx-trash"></i> Delete
                </button>
              </div>

              <p class="tutor-date">
                Added <?= (new DateTime($t['created_at']))->format('d/m/Y') ?>
              </p>
            </div>
          <?php endforeach; ?>
        <?php else: ?>
          <p>No tutors found.</p>
        <?php endif; ?>
      </div>
    </div>

  <!-- Tutor Modal (single instance) -->
  <div class="modal" id="tutorModal">
    <div class="modal-content">
      <span class="modal-close" id="tutorModalClose"><i class="bx bx-x"></i></span>
      <h3 id="tutorModalTitle">Add New Tutor</h3>
    <form id="tutorForm" method="post" action="index.php?pages=tutors&action=create">
        <input type="hidden" name="csrf_token" value="<?= $csrf ?>">

        <div class="form-row split-2">
          <div>
            <label>Full Name *</label>
            <input type="text" name="name" id="tName" required>
          </div>
          <div>
            <label>Qualifications</label>
            <input type="text" name="title" id="tTitle" placeholder="e.g., B.Sc Biology, PGCE">
          </div>
        </div>

        <!-- email & phone removed to keep modal minimal; can be edited later -->

        <div class="form-row split-2">
          <div>
            <label>Subjects (comma-separated)</label>
            <input type="text" name="subjects" id="tSubjects" placeholder="Mathematics, Physics, Chemistry">
          </div>
          <div>
            <label>Years of Experience</label>
            <input type="text" name="years_experience" id="tYears">
          </div>
        </div>

        <div class="form-row">
          <label>Bio</label>
          <textarea name="bio" id="tBio" rows="4"></textarea>
        </div>

        <div class="form-row">
          <label>Image URL</label>
          <input type="text" name="image_url" id="tImageUrl" placeholder="https://... or /uploads/tutors/abc.jpg">
        </div>

        <div class="form-actions">
          <div class="sticky-actions">
              <button type="submit" class="btn-approve">Save Tutor</button>
          </div>
          <button type="reset" class="btn-cancel">Clear</button>
        </div>
      </form>
    </div>
  </div>
  <div id="modalOverlay"></div>

  <?php include '../includes/footer.php'; ?>

  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script>
  document.addEventListener('DOMContentLoaded', function() {
    // Prefer admin_url() when available (respects .env ADMIN_URL). Fall back to script dirname.
    const BASE_URL = <?= json_encode( (function(){
      try {
        if (function_exists('admin_url')) return rtrim(admin_url(''), '/');
      } catch (Throwable $_) {}
      return rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
    })() ) ?>;

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
      overlay.classList.add('open');
      tutorModal.classList.add('open');
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
      overlay.classList.remove('open');
      tutorModal.classList.remove('open');
    }

    // Handle form submission via AJAX
    tutorForm.addEventListener('submit', async function(e) {
      e.preventDefault();
      
      // Show loading state
      const submitBtn = tutorForm.querySelector('button[type="submit"]');
      const originalText = submitBtn.textContent;
      submitBtn.disabled = true;
      submitBtn.textContent = 'Processing...';
      
      try {
        const formData = new FormData(this);
        const response = await fetch(this.action, {
          method: 'POST',
          body: formData,
          credentials: 'include', // include cookies / basic auth
          headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
          }
        });
        
        // First check if the response is OK
        if (!response.ok) {
          const txt = await response.text().catch(() => '[no body]');
          console.error('Non-OK response', response.status, txt);
          throw new Error(`Server error: ${response.status}`);
        }
        
        let data;
        const contentType = response.headers.get('content-type');
        if (contentType && contentType.includes('application/json')) {
          data = await response.json();
          if (data.status === 'error') {
            throw new Error(data.message || 'Failed to save tutor');
          }
        } else {
          const text = await response.text();
          console.error('Non-JSON response from server:', text);
          throw new Error('Server did not return JSON response');
        }
        
        // Show success message
        await Swal.fire({
          icon: 'success',
          title: 'Success',
          text: data.message || 'Tutor saved successfully!'
        });
        
        // Close modal and refresh page
        closeModal();
        window.location.reload();
        
      } catch (error) {
        console.error('Error:', error);
        Swal.fire({
          icon: 'error',
          title: 'Error',
          text: error.message || 'Failed to save tutor. Please try again.'
        });
      } finally {
        // Reset button state
        submitBtn.disabled = false;
        submitBtn.textContent = originalText;
      }
    });

    closeBtn.addEventListener('click', closeModal);
    overlay.addEventListener('click', closeModal);
    document.addEventListener('keydown', e => e.key === 'Escape' && closeModal());
    newBtn.addEventListener('click', () => openModal('create'));

    document.querySelectorAll('.edit-btn').forEach(btn => {
      btn.addEventListener('click', () => {
        const card = btn.closest('.tutor-card');
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
      });
    });
  });
</script>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
  function deleteTutor(id, name) {
    const BASE_URL = "<?= BASE_URL ?>";
    Swal.fire({
      title: "Delete Tutor?",
      text: "Are you sure you want to delete " + name + "?",
      icon: "warning",
      showCancelButton: true,
      confirmButtonColor: "#d33",
      cancelButtonColor: "#3085d6",
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
  }

  function editTutor(id) {
    const BASE_URL = "<?= BASE_URL ?>";
    const card = document.querySelector(`.tutor-card[data-id='${id}']`);
    if (!card) return;

    document.getElementById('tutorModalTitle').textContent = 'Edit Tutor';
    const form = document.getElementById('tutorForm');
    form.action = `index.php?pages=tutors&action=edit&id=${id}`;
    document.getElementById('tName').value = card.dataset.name || '';
    document.getElementById('tTitle').value = card.dataset.title || '';
    document.getElementById('tSubjects').value = card.dataset.subjects || '';
    document.getElementById('tYears').value = card.dataset.years || '';
    document.getElementById('tBio').value = card.dataset.bio || '';
    document.getElementById('tImageUrl').value = card.dataset.image || '';

    document.getElementById('modalOverlay').classList.add('open');
    document.getElementById('tutorModal').classList.add('open');
  }
</script>

</body>

</html>