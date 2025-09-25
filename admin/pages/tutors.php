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

    // Handle uploaded image (prefer file upload over URL)
    $uploadedPath = null;
    if (!empty($_FILES['image_file']) && ($_FILES['image_file']['error'] ?? 1) === UPLOAD_ERR_OK) {
      $f = $_FILES['image_file'];
      $allowed = ['image/jpeg','image/png','image/gif','image/webp'];
      if (in_array($f['type'], $allowed, true)) {
        if (!is_dir($uploadDir)) @mkdir($uploadDir, 0755, true);
        $ext = pathinfo($f['name'], PATHINFO_EXTENSION);
        $nameSafe = time() . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
        $dest = $uploadDir . $nameSafe;
        if (move_uploaded_file($f['tmp_name'], $dest)) {
          // web path relative to project public
          $uploadedPath = 'uploads/tutors/' . $nameSafe;
        }
      }
    }
        if (empty($errors)) {
            // Use uploaded file if present, otherwise fallback to image URL
            $photo = $uploadedPath ?: ($imageUrl ?: null);

            if ($action === 'create') {
                $stmt = $pdo->prepare("INSERT INTO tutors (name, slug, photo, short_bio, long_bio, qualifications, subjects, contact_email, phone, rating, is_featured, created_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,NOW())");
                $stmt->execute([
                  $name, $slug, $photo, $years ?: null, $long ?: null, $title ?: null,
                  $subjects, $email ?: null, $phone ?: null, null, 0
                ]);
                logAction($pdo, $_SESSION['user']['id'], 'tutor_created', ['slug'=>$slug]);
                $success[] = "Tutor '{$name}' created.";
            } elseif ($action === 'edit' && $id) {
                // keep existing photo if none uploaded/provided
                if (empty($photo)) {
                    $old = $pdo->prepare("SELECT photo FROM tutors WHERE id=?");
                    $old->execute([$id]);
                    $photo = $old->fetchColumn();
                }
                $stmt = $pdo->prepare("UPDATE tutors SET name=?, slug=?, photo=?, short_bio=?, long_bio=?, qualifications=?, subjects=?, contact_email=?, phone=?, rating=?, is_featured=?, updated_at=NOW() WHERE id=?");
                $stmt->execute([
                  $name, $slug, $photo, $years ?: null, $long ?: null, $title ?: null,
                  $subjects, $email ?: null, $phone ?: null, null, 0, $id
                ]);
                logAction($pdo, $_SESSION['user']['id'], 'tutor_updated', ['tutor_id'=>$id]);
                $success[] = "Tutor '{$name}' updated.";
            } elseif ($action === 'delete' && $id) {
                $pdo->prepare("DELETE FROM tutors WHERE id=?")->execute([$id]);
                logAction($pdo, $_SESSION['user']['id'], 'tutor_deleted', ['tutor_id'=>$id]);
                $success[] = "Tutor deleted.";
            }
        }
    }
  }
}
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
      }
    }
  }
}
?>
<?php
// Load tutors for listing (safe when there are none)
$q = trim($_GET['q'] ?? '');
try {
  if ($q !== '') {
    $stmt = $pdo->prepare("SELECT * FROM tutors WHERE name LIKE ? OR qualifications LIKE ? ORDER BY created_at DESC");
    $like = "%{$q}%";
    $stmt->execute([$like, $like]);
  } else {
    $stmt = $pdo->query("SELECT * FROM tutors ORDER BY created_at DESC");
  }
  $tutors = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
} catch (Throwable $e) {
  $tutors = [];
  $errors[] = 'Could not load tutors: ' . $e->getMessage();
}
?>
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
      <h3 id="tutorModalTitle">Add New Tutor</h3>
  <form id="tutorForm" method="post" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?= $csrf ?>">

        <div class="form-row split-2">
          <div>
            <label>Full Name *</label>
            <input type="text" name="name" id="tName" required>
          </div>
          <div>
            <label>Title</label>
            <input type="text" name="title" id="tTitle" placeholder="e.g., Senior Mathematics Teacher">
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
          <label>Photo</label>
          <input type="file" name="image_file" id="tImageFile" accept="image/*">
          <div class="image-preview" id="tImagePreview" style="margin-top:8px"></div>
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
</body>
</html>
