<?php
// admin/pages/tutors.php
require_once '../includes/auth.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/csrf.php';

define('BASE_URL', '/HIGH-Q');
$pageTitle = 'Tutors';
$pageSubtitle = 'Manage tutor profiles and listings';

// Check if this is an AJAX request
$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
          strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

// Add SweetAlert2 assets and CSS
$pageCss = '<link rel="stylesheet" href="../assets/css/tutors.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
<style>
.tutors-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 1.5rem;
    padding: 1rem;
}
.tutor-card {
    background: white;
    border-radius: 10px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    overflow: hidden;
    position: relative;
}
.tutor-card .status {
    position: absolute;
    top: 10px;
    right: 10px;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 12px;
}
.tutor-card .status.active {
    background: #ffd700;
    color: #000;
}
.tutor-card .status.normal {
    background: #e0e0e0;
    color: #333;
}
.tutor-card .date {
    position: absolute;
    bottom: 10px;
    right: 10px;
    font-size: 12px;
    color: #666;
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
    padding: 1rem;
}
.tutor-info h3 {
    margin: 0 0 0.5rem;
    font-size: 1.2rem;
}
.tutor-info .role {
    color: #666;
    margin-bottom: 0.5rem;
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
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action'])) {
  if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
    $errors[] = "Invalid CSRF token.";
    if ($isAjax) {
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
    <div class="tutors-header">
      <h1>Tutors Management</h1>
      <div class="tutors-actions">
  <form method="get" action="../index.php" class="search-form">
          <input type="hidden" name="pages" value="tutors">
          <input type="text" name="q" placeholder="Search Tutors" value="<?= htmlspecialchars($q) ?>">
        </form>
        <button id="newTutorBtn" class="btn-approve">
          <i class="bx bx-plus"></i> Add Tutor
        </button>
      </div>
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

    <div class="tutors-grid">
      <?php if (!empty($tutors)): ?>
        <?php foreach ($tutors as $t): ?>
          <div class="tutor-card" data-id="<?= $t['id'] ?>" data-name="<?= htmlspecialchars($t['name'], ENT_QUOTES) ?>" data-title="<?= htmlspecialchars($t['qualifications'] ?? '', ENT_QUOTES) ?>" data-subjects="<?= htmlspecialchars(implode(', ', json_decode($t['subjects'] ?? '[]', true)), ENT_QUOTES) ?>" data-years="<?= htmlspecialchars($t['short_bio'] ?? '', ENT_QUOTES) ?>" data-bio="<?= htmlspecialchars($t['long_bio'] ?? '', ENT_QUOTES) ?>" data-image="<?= htmlspecialchars($t['photo'] ?? '', ENT_QUOTES) ?>" data-email="<?= htmlspecialchars($t['contact_email'] ?? '', ENT_QUOTES) ?>" data-phone="<?= htmlspecialchars($t['phone'] ?? '', ENT_QUOTES) ?>">
            <span class="status <?= $t['is_featured'] ? 'active' : 'normal' ?>">
              <?= $t['is_featured'] ? 'Active' : 'Normal' ?>
            </span>

            <div class="tutor-photo">
              <?php
              $photoPath = $t['photo'] ? (strpos($t['photo'], 'http') === 0 ? $t['photo'] : '../../public/' . $t['photo']) : '/HIGH-Q/public/assets/images/hq-logo.jpeg';
              ?>
              <img src="<?= htmlspecialchars($photoPath) ?>" alt="<?= htmlspecialchars($t['name']) ?>">
            </div>

            <div class="tutor-info">
              <h3><?= htmlspecialchars($t['name']) ?></h3>
              <p class="role"><?= htmlspecialchars($t['qualifications'] ?: 'Not specified') ?></p>
              <div class="subjects">
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

              <div class="tutor-actions">
                <button type="button" class="edit-btn" onclick="editTutor(<?= $t['id'] ?>)">
                  <i class="bx bx-edit"></i> Edit
                </button>
                <button type="button" class="delete-btn" onclick="deleteTutor(<?= $t['id'] ?>, '<?= htmlspecialchars(addslashes($t['name']), ENT_QUOTES) ?>')">
                  <i class="bx bx-trash"></i> Delete
                </button>
              </div>
            </div>

            <span class="date">
              Added <?= (new DateTime($t['created_at']))->format('d/m/Y') ?>
            </span>
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
    <form id="tutorForm" method="post" action="<?= BASE_URL ?>/admin/pages/tutors.php?action=create">
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
    const BASE_URL = "<?= rtrim(dirname($_SERVER['SCRIPT_NAME']), '/') ?>";

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
        tutorForm.action = `tutors.php?action=edit&id=${data.id}`;
        fields.name.value = data.name || '';
        fields.title.value = data.title || '';
        fields.subs.value = data.subjects || '';
        fields.years.value = data.years || '';
        fields.bio.value = data.bio || '';
        fields.image.value = data.image || '';
      } else {
        modalTitle.textContent = 'Add Tutor';
        tutorForm.action = 'tutors.php?action=create';
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
      const submitBtn = tutorForm.querySelector('button[type="submit"]');
      const originalText = submitBtn.textContent;
      submitBtn.disabled = true;
      submitBtn.textContent = 'Processing...';

      try {
        const formData = new FormData(this);
        const response = await fetch(this.action, {
          method: 'POST',
          body: formData,
          headers: {
            'X-Requested-With': 'XMLHttpRequest'
          }
        });

        let data;
        const contentType = response.headers.get('content-type');
        if (contentType && contentType.includes('application/json')) {
          data = await response.json();
        } else {
          const text = await response.text();
          if (text.includes('success')) {
            data = { status: 'success' };
          } else {
            throw new Error('Invalid response format');
          }
        }

        if (data.status === 'error') {
          throw new Error(data.message || 'Failed to save tutor');
        }

        await Swal.fire({
          icon: 'success',
          title: 'Success',
          text: 'Tutor saved successfully!'
        });

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
        form.action = `${BASE_URL}/admin/pages/tutors.php?action=delete&id=${id}`;

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
    form.action = `${BASE_URL}/admin/pages/tutors.php?action=edit&id=${id}`;
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