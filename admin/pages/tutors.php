<?php
// admin/pages/tutors.php
require_once '../includes/auth.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/csrf.php';
$pageTitle = 'Tutors';
$pageSubtitle = 'Manage tutor profiles and listings';

// Only Admin & Sub-Admin
requirePermission('tutors'); // where 'roles' matches the menu slug

$csrf     = generateToken();
$errors   = [];
$success  = [];

// Per-page CSS
$pageCss = '<link rel="stylesheet" href="../assets/css/tutors.css">';

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

        // Gather & sanitize (only the fields we keep in the modal)
        $name    = trim($_POST['name'] ?? '');
        // auto-generate slug from name when not provided
        $slug    = '';
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

        // Validation: require name, title and subjects
        if (!$name || !$title || empty($subs)) {
          $errors[] = "Name, Title and Subjects are required.";
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
                // If no new upload, keep existing
                if ($action === 'create') {
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

                if ($action === 'delete' && $id) {
                    $pdo->prepare("DELETE FROM tutors WHERE id=?")->execute([$id]);
                    logAction($pdo, $_SESSION['user']['id'], 'tutor_deleted', ['tutor_id'=>$id]);
                    $success[] = "Tutor deleted.";
                }
  <title>Tutors Management — Admin</title>
  <link rel="stylesheet" href="../public/assets/css/admin.css">
  <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
</head>
<body>
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

    <?php if($success): ?>
      <div class="alert success">
        <?php foreach($success as $msg) echo "<p>{$msg}</p>"; ?>
      </div>
    <?php endif; ?>
    <?php if($errors): ?>
      <div class="alert error">
        <?php foreach($errors as $err) echo "<p>{$err}</p>"; ?>
      </div>
    <?php endif; ?>

    <div class="tutors-grid">
      <?php foreach($tutors as $t): ?>
      <div class="tutor-card">
        <div class="tutor-photo">
          <img src="../<?= htmlspecialchars($t['photo'] ?: 'assets/images/avatar-placeholder.png') ?>"
               alt="<?= htmlspecialchars($t['name']) ?>">
        </div>
        <div class="tutor-info">
          <h3><?= htmlspecialchars($t['name']) ?></h3>
          <p class="role"><?= htmlspecialchars($t['qualifications']) ?></p>
          <p class="subjects">
            <?= implode(', ', json_decode($t['subjects'] ?? '[]', true)) ?>
          </p>
          <div class="tutor-meta">
            <span class="status-badge <?= $t['is_featured'] ? 'status-active' : 'status-banned' ?>">
              <?= $t['is_featured'] ? 'Featured' : 'Normal' ?>
            </span>
            <span class="date"><?= date('d/m/Y', strtotime($t['created_at'])) ?></span>
          </div>
          <button
            class="btn-editTutor"
            data-id="<?= $t['id'] ?>"
            data-name="<?= htmlspecialchars($t['name']) ?>"
            data-title="<?= htmlspecialchars($t['qualifications']) ?>"
            data-image="<?= htmlspecialchars($t['photo']) ?>"
            data-years="<?= htmlspecialchars($t['short_bio']) ?>"
            data-bio="<?= htmlspecialchars($t['long_bio']) ?>"
            data-subjects="<?= htmlspecialchars(implode(', ', json_decode($t['subjects'] ?? '[]', true))) ?>"
            data-email="<?= htmlspecialchars($t['contact_email']) ?>"
            data-phone="<?= htmlspecialchars($t['phone']) ?>"
          >
            <i class="bx bx-edit"></i> Edit
          </button>
          <form method="post" action="index.php?pages=tutors&action=delete&id=<?= $t['id'] ?>"
                style="display:inline">
            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
            <button type="submit" class="btn-banish">
              <i class="bx bx-trash"></i>
            </button>
          </form>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- Tutor Modal (single instance) -->
  <div class="modal" id="tutorModal">
    <div class="modal-content">
      <span class="modal-close" id="tutorModalClose"><i class="bx bx-x"></i></span>
      <h3 id="tutorModalTitle">Add Tutor</h3>
      <form id="tutorForm" method="post" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?= $csrf ?>">

        <div class="form-row">
          <label>Name</label>
          <input type="text" name="name" id="tName" required>
        </div>
        <div class="form-row">
          <label>Slug</label>
          <input type="text" name="slug" id="tSlug" required>
        </div>
        <div class="form-row">
          <label>Photo Upload</label>
          <input type="file" name="photo" id="tPhoto" accept="image/*">
        </div>
        <div class="form-row">
          <label>Short Bio</label>
          <textarea name="short_bio" id="tShort" rows="2"></textarea>
        </div>
        <div class="form-row">
          <label>Long Bio</label>
          <textarea name="long_bio" id="tLong" rows="4"></textarea>
        </div>
        <div class="form-row">
          <label>Qualifications</label>
          <textarea name="qualifications" id="tQuals" rows="2"></textarea>
        </div>
        <div class="form-row">
          <label>Subjects (comma-separated)</label>
          <input type="text" name="subjects" id="tSubjects">
        </div>
        <div class="form-row">
          <label>Contact Email</label>
          <input type="email" name="contact_email" id="tEmail">
        </div>
        <div class="form-row">
          <label>Phone</label>
          <input type="text" name="phone" id="tPhone">
        </div>
        <div class="form-row">
          <label>Rating (0–5)</label>
          <input type="number" name="rating" id="tRating" min="0" max="5" step="0.01">
        </div>
        <div class="form-row">
          <label><input type="checkbox" name="is_featured" id="tFeatured"> Featured</label>
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
  const tutorModal    = document.getElementById('tutorModal');
  const overlay       = document.getElementById('modalOverlay');
  const closeBtn      = document.getElementById('tutorModalClose');
  const newBtn        = document.getElementById('newTutorBtn');
  const tutorForm     = document.getElementById('tutorForm');
  const modalTitle    = document.getElementById('tutorModalTitle');

  const fields = {
    name:     document.getElementById('tName'),
    slug:     document.getElementById('tSlug'),
    photo:    document.getElementById('tPhoto'),
    short:    document.getElementById('tShort'),
    long:     document.getElementById('tLong'),
    quals:    document.getElementById('tQuals'),
    subs:     document.getElementById('tSubjects'),
    email:    document.getElementById('tEmail'),
    phone:    document.getElementById('tPhone'),
    rating:   document.getElementById('tRating'),
    featured: document.getElementById('tFeatured'),
  };

  function openModal(mode, data={}) {
    overlay.classList.add('open');
    tutorModal.classList.add('open');
    if (mode === 'edit') {
      modalTitle.textContent = 'Edit Tutor';
      tutorForm.action = `index.php?pages=tutors&action=edit&id=${data.id}`;
      Object.keys(fields).forEach(key => {
        if (key === 'featured') {
          fields[key].checked = data.featured == 1;
        } else if (key !== 'photo') {
          fields[key].value = data[key] || '';
        }
      });
    } else {
      modalTitle.textContent = 'Add Tutor';
  tutorForm.action = 'index.php?pages=tutors&action=create';
      Object.values(fields).forEach(f => {
        if (f.type === 'checkbox') f.checked = false;
        else f.value = '';
      });
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
        slug:      btn.dataset.slug,
        short:     btn.dataset.short,
        long:      btn.dataset.long,
        quals:     btn.dataset.quals,
        subjects:  btn.dataset.subjects,
        email:     btn.dataset.email,
        phone:     btn.dataset.phone,
        rating:    btn.dataset.rating,
        featured:  btn.dataset.featured
      });
    });
  });
  </script>
</body>
</html>
