<?php
// admin/pages/tutors.php
require_once '../includes/auth.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/csrf.php';

$pageTitle = 'Tutors';
$pageSubtitle = 'Manage tutor profiles and listings';

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
$query .= " ORDER BY created_at DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$tutors = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Add SweetAlert2 assets and CSS
$pageCss = '<link rel="stylesheet" href="../assets/css/tutors.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
<style>
.tutors-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
    gap: 1.5rem;
    padding: 1rem;
}
.tutor-card {
    background: white;
    border-radius: 10px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    padding: 1rem;
    display: flex;
    gap: 1rem;
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
    background: #FFD700;
    color: #000;
}
.tutor-card .status.normal {
    background: #E8E8E8;
    color: #333;
}
.tutor-photo {
    width: 80px;
    height: 80px;
    flex-shrink: 0;
}
.tutor-photo img {
    width: 100%;
    height: 100%;
    border-radius: 10px;
    object-fit: cover;
}
.tutor-info {
    flex: 1;
    min-width: 0;
}
.tutor-info h3 {
    margin: 0 0 0.5rem;
    font-size: 1.1rem;
    color: #000;
}
.tutor-info .role {
    color: #666;
    font-size: 0.9rem;
    margin: 0.25rem 0;
}
.tutor-info .subjects {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    margin: 0.5rem 0;
}
.tutor-info .subjects span {
    background: #F0F0F0;
    color: #333;
    padding: 2px 8px;
    border-radius: 12px;
    font-size: 0.85rem;
}
.tutor-experience {
    color: #666;
    font-size: 0.9rem;
    margin: 0.5rem 0;
}
.tutor-actions {
    display: flex;
    gap: 0.5rem;
    margin-top: 0.5rem;
}
.tutor-actions button {
    padding: 5px 15px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-size: 0.9rem;
    display: flex;
    align-items: center;
    gap: 5px;
}
.tutor-actions .edit-btn {
    background: #2196F3;
    color: white;
}
.tutor-actions .delete-btn {
    background: #dc3545;
    color: white;
}
.date {
    position: absolute;
    bottom: 10px;
    right: 10px;
    font-size: 0.8rem;
    color: #666;
}
</style>';
$pageJs = '<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
function deleteTutor(id, name) {
    Swal.fire({
        title: "Delete Tutor?",
        text: `Are you sure you want to delete ${name}?`,
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
    window.location.href = `index.php?pages=tutors&action=edit&id=${id}&csrf_token=<?= $csrf ?>`;
}
</script>';

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
$query .= " ORDER BY created_at DESC";

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
    } else {
        $action = $_GET['action'];
        $id     = (int)($_GET['id'] ?? 0);

        // Gather & sanitize inputs
        $name    = trim($_POST['name'] ?? '');
        $title   = trim($_POST['title'] ?? ''); // maps to qualifications
        $years   = trim($_POST['years_experience'] ?? ''); // maps to short_bio
        $long    = trim($_POST['bio'] ?? ''); // long_bio
        $subs    = array_filter(array_map('trim', explode(',', $_POST['subjects'] ?? '')));
        $subjects = json_encode($subs, JSON_UNESCAPED_UNICODE);
        $email   = trim($_POST['email'] ?? '');
        $phone   = trim($_POST['phone'] ?? '');
        $imageUrl = trim($_POST['image_url'] ?? '');

        // simple slugify helper (ASCII-safe)
        $slugify = function($text){
          $text = mb_strtolower(trim($text));
          // replace non letter or digits by -
          $text = preg_replace('/[^\p{L}\p{Nd}]+/u', '-', $text);
          $text = trim($text, '-');
          return $text ?: substr(sha1($text.time()),0,8);
        };

        if ($name && empty($slug)) {
          $slug = $slugify($name);
        }

        // Validation: require name only — allow partial saves so admin can complete later
        if (!$name) {
          $errors[] = "Name is required.";
        }

        // Check for duplicate slug
        if ($action === 'create' || ($action === 'edit' && $id)) {
            $checkSlug = $pdo->prepare("SELECT id FROM tutors WHERE slug = ? AND id != ?");
            $checkSlug->execute([$slug, $id ?? 0]);
            if ($checkSlug->fetch()) {
                $errors[] = "A tutor with the URL '{$slug}' already exists. Please use a different name.";
            }
        }

        if (empty($errors)) {
            if ($action === 'create') {
                $stmt = $pdo->prepare("
                  INSERT INTO tutors
                    (name, slug, photo, short_bio, long_bio, qualifications,
                     subjects, contact_email, phone, rating, is_featured)
                  VALUES (?,?,?,?,?,?,?,?,?,?,?)
                ");
                $stmt->execute([
                  $name, $slug,
                  $photoPath ?: null,
                  $short, $long, $quals,
                  $subjects, $email, $phone,
                  $rating, $feat
                ]);
                logAction($pdo, $_SESSION['user']['id'], 'tutor_created', ['slug'=>$slug]);
                $success[] = "Tutor '{$name}' created.";
            }

            if ($action === 'edit' && $id) {
                // keep existing photo if image_url not provided
                if (empty($imageUrl)) {
                    $old = $pdo->prepare("SELECT photo FROM tutors WHERE id=?");
                    $old->execute([$id]);
                    $photo = $old->fetchColumn();
                } else {
                    $photo = $imageUrl;
                }
                $stmt = $pdo->prepare(
                  "UPDATE tutors SET name=?, slug=?, photo=?, short_bio=?, long_bio=?, qualifications=?, subjects=?, contact_email=?, phone=?, rating=?, is_featured=?, updated_at=NOW() WHERE id=?"
                );
                $stmt->execute([
                  $name, $slug, $photo, $years ?: null, $long ?: null, $title ?: null,
                  $subjects, $email ?: null, $phone ?: null, null, 0, $id
                ]);
                logAction($pdo, $_SESSION['user']['id'], 'tutor_updated', ['tutor_id'=>$id]);
                $success[] = "Tutor '{$name}' updated.";
            }
                    // store image URL directly into photo
                    $photo = $imageUrl ?: null;
                    $stmt = $pdo->prepare(
                      "INSERT INTO tutors (name, slug, photo, short_bio, long_bio, qualifications, subjects, contact_email, phone, rating, is_featured) VALUES (?,?,?,?,?,?,?,?,?,?,?)"
                    );
                    $stmt->execute([
                      $name, $slug, $photo, $years ?: null, $long ?: null, $title ?: null,
                      $subjects, $email ?: null, $phone ?: null, null, 0
                    ]);
                    logAction($pdo, $_SESSION['user']['id'], 'tutor_created', ['slug'=>$slug]);
                    $success[] = "Tutor '{$name}' created.";
                }

        // Slugify the name
        $slugify = function($text) {
            $text = mb_strtolower(trim($text));
            $text = preg_replace('/[^\p{L}\p{Nd}]+/u', '-', $text);
            return trim($text, '-') ?: substr(sha1($text.time()), 0, 8);
        };
        $slug = $slugify($name);

        // Validation
        if (!$name) {
            $errors[] = "Name is required.";
        }

        // Check for duplicate slug
        if (empty($errors)) {
            $checkSlug = $pdo->prepare("SELECT id FROM tutors WHERE slug = ? AND id != ?");
            $checkSlug->execute([$slug, $id ?? 0]);
            if ($checkSlug->fetch()) {
                $errors[] = "A tutor with this name already exists. Please use a different name.";
            }
        }

        if (empty($errors)) {
            if ($action === 'create') {
                $photo = $imageUrl ?: null;
                $stmt = $pdo->prepare(
                    "INSERT INTO tutors (name, slug, photo, short_bio, long_bio, qualifications,
                                       subjects, contact_email, phone, rating, is_featured)
                     VALUES (?,?,?,?,?,?,?,?,?,?,?)");
                $stmt->execute([
                    $name, $slug, $photo, $years ?: null, $long ?: null, $title ?: null,
                    $subjects, $email ?: null, $phone ?: null, null, 0
                ]);
                logAction($pdo, $_SESSION['user']['id'], 'tutor_created', ['slug'=>$slug]);
                $success[] = "Tutor '{$name}' created.";
            }
            elseif ($action === 'edit' && $id) {
                $photo = empty($imageUrl) ? null : $imageUrl;
                if (!$photo) {
                    $old = $pdo->prepare("SELECT photo FROM tutors WHERE id=?");
                    $old->execute([$id]);
                    $photo = $old->fetchColumn();
                }
                
                $stmt = $pdo->prepare(
                    "UPDATE tutors 
                     SET name=?, slug=?, photo=?, short_bio=?, long_bio=?, qualifications=?,
                         subjects=?, contact_email=?, phone=?, rating=?, is_featured=?, updated_at=NOW()
                     WHERE id=?");
                $stmt->execute([
                    $name, $slug, $photo, $years ?: null, $long ?: null, $title ?: null,
                    $subjects, $email ?: null, $phone ?: null, null, 0, $id
                ]);
                logAction($pdo, $_SESSION['user']['id'], 'tutor_updated', ['tutor_id'=>$id]);
                $success[] = "Tutor '{$name}' updated.";
            }
            elseif ($action === 'delete' && $id) {
                $pdo->prepare("DELETE FROM tutors WHERE id=?")->execute([$id]);
                logAction($pdo, $_SESSION['user']['id'], 'tutor_deleted', ['tutor_id'=>$id]);
                $success[] = "Tutor deleted.";
            }
        }
    }
?>

// Process form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action'])) {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors[] = "Invalid CSRF token.";
    } else {
        $action = $_GET['action'];
        $id = (int)($_GET['id'] ?? 0);
        
        // Handle form submission
        // ... (your existing form processing code)
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Tutors Management — Admin</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <?= $pageCss ?>
    <?= $pageJs ?>
</head>
<body>
  <?php include '../includes/header.php'; ?>
  <?php include '../includes/sidebar.php'; ?>

  <?php include '../includes/header.php'; ?>
  <?php include '../includes/sidebar.php'; ?>

  <div class="container">
    <div class="tutors-header">
      <h1>Tutors Management</h1>
      <div class="tutors-actions">
        <form method="get" action="index.php" class="search-form">
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
        <?php foreach($tutors as $t): ?>
          <div class="tutor-card">
            <span class="status <?= $t['is_featured'] ? 'active' : 'normal' ?>">
              <?= $t['is_featured'] ? 'Active' : 'Normal' ?>
            </span>
            
            <div class="tutor-photo">
              <?php
                $photoPath = $t['photo'] ? (strpos($t['photo'], 'http') === 0 ? $t['photo'] : '../../public/' . $t['photo']) : '../../public/assets/images/avatar-placeholder.png';
              ?>
              <img src="<?= htmlspecialchars($photoPath) ?>" alt="<?= htmlspecialchars($t['name']) ?>">
            </div>
            
            <div class="tutor-info">
              <h3><?= htmlspecialchars($t['name']) ?></h3>
              <div class="role"><?= htmlspecialchars($t['qualifications'] ?: 'Not specified') ?></div>
              
              <div class="subjects">
                <?php 
                $subjects = json_decode($t['subjects'] ?? '[]', true);
                if (!empty($subjects)): 
                  foreach($subjects as $subject): 
                ?>
                  <span><?= htmlspecialchars($subject) ?></span>
                <?php 
                  endforeach;
                endif;
                ?>
              </div>
              
              <?php if (!empty($t['short_bio'])): ?>
              <div class="tutor-experience"><?= htmlspecialchars($t['short_bio']) ?></div>
              <?php endif; ?>
              
              <div class="tutor-actions">
                <button type="button" class="edit-btn" onclick="editTutor(<?= $t['id'] ?>)">
                  <i class="bx bx-edit"></i> Edit
                </button>
                <button type="button" class="delete-btn" onclick="deleteTutor(<?= $t['id'] ?>, '<?= htmlspecialchars(addslashes($t['name']), ENT_QUOTES) ?>')">
                  <i class="bx bx-trash"></i> Delete
                </button>
              </div>
            </div>
            
            <span class="date">Added <?= (new DateTime($t['created_at']))->format('d/m/Y') ?></span>
          </div>
        <?php endforeach; ?>
      <?php else: ?>
        <p>No tutors found.</p>
      <?php endif; ?>
    </div>
  </div> <!-- end container -->

  <!-- Tutor Modal (single instance) -->
  <div class="modal" id="tutorModal">
    <div class="modal-content">
      <span class="modal-close" id="tutorModalClose"><i class="bx bx-x"></i></span>
      <h3 id="tutorModalTitle">Add New Tutor</h3>
      <form id="tutorForm" method="post" action="">
        <input type="hidden" name="csrf_token" value="<?= $csrf ?>">

        <div class="form-row split-2">
          <div>
            <label>Full Name *</label>
            <input type="text" name="name" id="tName" required>
          </div>
          <div>
            <label>Title *</label>
            <input type="text" name="title" id="tTitle" placeholder="e.g., Senior Mathematics Teacher" required>
          </div>
        </div>

        <!-- email & phone removed to keep modal minimal; can be edited later -->

        <div class="form-row split-2">
          <div>
            <label>Subjects (comma-separated) *</label>
            <input type="text" name="subjects" id="tSubjects" placeholder="Mathematics, Physics, Chemistry" required>
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
          <button type="submit" class="btn-approve">Save Tutor</button>
        </div>
      </form>
    </div>
  </div>
  <div id="modalOverlay"></div>

  <?php include '../includes/footer.php'; ?>

  <script>
  // Modal logic
  document.addEventListener('DOMContentLoaded', function() {
    const tutorModal    = document.getElementById('tutorModal');
    const overlay       = document.getElementById('modalOverlay');
    const closeBtn      = document.getElementById('tutorModalClose');
    const newBtn        = document.getElementById('newTutorBtn');
    const tutorForm     = document.getElementById('tutorForm');
    const modalTitle    = document.getElementById('tutorModalTitle');

  const fields = {
    name:   document.getElementById('tName'),
    title:  document.getElementById('tTitle'),
    subs:   document.getElementById('tSubjects'),
    years:  document.getElementById('tYears'),
    bio:    document.getElementById('tBio'),
    image:  document.getElementById('tImageUrl')
  };

  function openModal(mode, data={}) {
    overlay.classList.add('open');
    tutorModal.classList.add('open');
    if (mode === 'edit') {
      modalTitle.textContent = 'Edit Tutor';
      tutorForm.action = `index.php?pages=tutors&action=edit&id=${data.id}`;
      fields.name.value  = data.name || '';
      fields.title.value = data.title || '';
  // email/phone removed from modal; keep them server-side untouched
      fields.subs.value  = data.subjects || '';
      fields.years.value = data.years || '';
      fields.bio.value   = data.bio || '';
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

  newBtn.addEventListener('click', () => openModal('create'));
  closeBtn.addEventListener('click', closeModal);
  overlay.addEventListener('click', closeModal);
  document.addEventListener('keydown', e => e.key==='Escape' && closeModal());

  document.querySelectorAll('.btn-editTutor').forEach(btn => {
    btn.addEventListener('click', () => {
      openModal('edit', {
        id:        btn.dataset.id,
        name:      btn.dataset.name,
        title:     btn.dataset.title,
        image:     btn.dataset.image,
        years:     btn.dataset.years,
        bio:       btn.dataset.bio,
        subjects:  btn.dataset.subjects
      });
    });
  });
  </script>
  <?php include '../includes/footer.php'; ?>
</body>
</html>