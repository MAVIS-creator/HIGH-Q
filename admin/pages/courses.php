<?php
// admin/pages/courses.php
require_once '../includes/auth.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/csrf.php';

$pageTitle = 'Courses';
$pageSubtitle = 'Manage courses and programs offered on the site';

// Only Admin & Sub-Admin can manage courses
requirePermission('roles'); // where 'roles' matches the menu slug

$csrf    = generateToken();
$errors  = [];
$success = [];

// Ensure per-page CSS for courses modal if not already set
$pageCss = '<link rel="stylesheet" href="../assets/css/courses.css">';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action'])) {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors[] = "Invalid CSRF token.";
    } else {
        $act    = $_GET['action'];
        $id     = isset($_GET['id']) ? (int)$_GET['id'] : null;
        $title  = trim($_POST['title'] ?? '');
        $slug   = trim($_POST['slug'] ?? '');
        $desc   = trim($_POST['description'] ?? '');
        $dur    = trim($_POST['duration'] ?? '');
        $price  = number_format((float)($_POST['price'] ?? 0), 2, '.', '');
        $tutor  = (int)($_POST['tutor_id'] ?? 0);
        $active = isset($_POST['is_active']) ? 1 : 0;

        if ($act === 'create') {
            if (!$title || !$slug) {
                $errors[] = "Title and slug are required.";
            } else {
                $stmt = $pdo->prepare("
                  INSERT INTO courses
                    (title, slug, description, duration, price, tutor_id, created_by, is_active)
                  VALUES (?,?,?,?,?,?,?,?)
                ");
                $stmt->execute([
                  $title, $slug, $desc, $dur, $price,
                  $tutor ?: null,
                  $_SESSION['user']['id'],
                  $active
                ]);
                logAction($pdo, $_SESSION['user']['id'], 'course_created', ['slug'=>$slug]);
                $success[] = "Course '{$title}' created.";
            }
        }

        if ($act === 'edit' && $id) {
            if (!$title || !$slug) {
                $errors[] = "Title and slug are required.";
            } else {
                $stmt = $pdo->prepare("
                  UPDATE courses
                  SET title=?, slug=?, description=?, duration=?, price=?, tutor_id=?, is_active=?, updated_at=NOW()
                  WHERE id=?
                ");
                $stmt->execute([
                  $title, $slug, $desc, $dur, $price,
                  $tutor ?: null,
                  $active,
                  $id
                ]);
                logAction($pdo, $_SESSION['user']['id'], 'course_updated', ['course_id'=>$id]);
                $success[] = "Course '{$title}' updated.";
            }
        }

        if ($act === 'delete' && $id) {
            $pdo->prepare("DELETE FROM courses WHERE id=?")->execute([$id]);
            logAction($pdo, $_SESSION['user']['id'], 'course_deleted', ['course_id'=>$id]);
            $success[] = "Course deleted.";
        }
    }

    // avoid form resubmission
    header("Location: index.php?pages=courses");
    exit;
}

// Fetch all courses for listing and tutor dropdown
$courses = $pdo->query("
    SELECT c.*, u.name AS tutor_name
    FROM courses c
    LEFT JOIN users u ON u.id = c.tutor_id
    ORDER BY created_at DESC
")->fetchAll();

$tutors = $pdo->query("
    SELECT id,name FROM users
    WHERE role_id = (SELECT id FROM roles WHERE slug='tutor')
      AND is_active=1
    ORDER BY name
")->fetchAll();

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Courses Management - HIGH Q SOLID ACADEMY</title>
  <link rel="stylesheet" href="../assets/css/admin.css">
  <link rel="stylesheet" href="../assets/css/courses.css">
  <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
</head>
<body>
  <?php include '../includes/header.php'; ?>
  <?php include '../includes/sidebar.php'; ?>

  <div class="container" style="margin-left:240px;">
    <h1>Courses / Programs</h1>

    <?php if ($success): ?>
      <div class="alert success">
        <?php foreach ($success as $msg): ?><p><?= htmlspecialchars($msg) ?></p><?php endforeach; ?>
      </div>
    <?php endif; ?>
    <?php if ($errors): ?>
      <div class="alert error">
        <?php foreach ($errors as $err): ?><p><?= htmlspecialchars($err) ?></p><?php endforeach; ?>
      </div>
    <?php endif; ?>

    <button id="newCourseBtn" class="btn-approve">
      <i class='bx bx-plus'></i> New Course
    </button>

 <div class="courses-grid">
  <?php if (empty($courses)): ?>
    <div class="course-card empty-state">
      <h3>No programs yet</h3>
      <p>Add a program using the <strong>New Course</strong> button. When empty, each program will show its summary here.</p>
    </div>
  <?php endif; ?>
  <?php foreach ($courses as $c): ?>
    <div class="course-card">
      <div class="course-header">
        <div class="course-icon"><i class='bx bxs-graduation'></i></div>
        <div class="course-title">
          <h3><?= htmlspecialchars($c['title']) ?></h3>
          <small><?= htmlspecialchars($c['duration'] ?: 'Flexible') ?></small>
        </div>
        <form method="post" action="index.php?pages=courses&action=delete&id=<?= $c['id'] ?>" class="delete-form">
          <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
          <button type="submit" class="btn-delete">Delete</button>
        </form>
      </div>

      <p class="course-desc"><?= nl2br(htmlspecialchars($c['description'])) ?></p>

      <div class="course-meta">
        <span class="price">₦<?= number_format($c['price'],0) ?></span>
        <span class="tutor"><?= htmlspecialchars($c['tutor_name'] ?? 'No Tutor') ?></span>
        <span class="status-badge <?= $c['is_active']?'status-active':'status-banned' ?>">
          <?= $c['is_active'] ? 'Active' : 'Inactive' ?>
        </span>
      </div>

      <div class="course-actions">
        <button class="btn-editCourse"
          data-id="<?= $c['id'] ?>"
          data-title="<?= htmlspecialchars($c['title']) ?>"
          data-slug="<?= htmlspecialchars($c['slug']) ?>"
          data-desc="<?= htmlspecialchars($c['description']) ?>"
          data-duration="<?= htmlspecialchars($c['duration']) ?>"
          data-price="<?= $c['price'] ?>"
          data-tutor="<?= $c['tutor_id'] ?>"
          data-active="<?= $c['is_active'] ?>"
        ><i class='bx bx-edit'></i> Edit</button>
      </div>
    </div>
  <?php endforeach; ?>
</div>

  <!-- Course Modal (Create/Edit) -->
  <div class="modal" id="courseModal">
    <div class="modal-content">
      <span class="modal-close" id="courseModalClose"><i class='bx bx-x'></i></span>
      <h3 id="courseModalTitle">New Course</h3>

      <form id="courseForm" method="post">
        <input type="hidden" name="csrf_token" value="<?= $csrf ?>">

        <div class="form-row">
          <label>Title</label>
          <input type="text" name="title" id="fTitle" required>
        </div>

        <div class="form-row">
          <label>Slug</label>
          <input type="text" name="slug" id="fSlug" required>
        </div>

        <div class="form-row">
          <label>Description</label>
          <textarea name="description" id="fDesc" rows="3"></textarea>
        </div>

        <div class="form-row">
          <label>Duration</label>
          <input type="text" name="duration" id="fDuration">
        </div>

        <div class="form-row">
          <label>Price (₦)</label>
          <input type="number" name="price" id="fPrice" step="0.01" min="0">
        </div>

        <div class="form-row">
          <label>Tutor</label>
          <select name="tutor_id" id="fTutor">
            <option value="">— None —</option>
            <?php foreach($tutors as $t): ?>
              <option value="<?= $t['id'] ?>"><?= htmlspecialchars($t['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="form-row">
          <label><input type="checkbox" name="is_active" id="fActive" checked> Active</label>
        </div>

        <div class="form-actions">
          <button type="submit" class="btn-approve">Save Course</button>
        </div>
      </form>
    </div>
  </div>
  <div id="modalOverlay"></div>

  <?php include '../includes/footer.php'; ?>

  <script>
  // Modal logic
  const courseModal    = document.getElementById('courseModal');
  const overlay        = document.getElementById('modalOverlay');
  const closeCourseBtn = document.getElementById('courseModalClose');
  const newCourseBtn   = document.getElementById('newCourseBtn');
  const courseForm     = document.getElementById('courseForm');
  const modalTitle     = document.getElementById('courseModalTitle');

  // Form fields
  const fTitle    = document.getElementById('fTitle');
  const fSlug     = document.getElementById('fSlug');
  const fDesc     = document.getElementById('fDesc');
  const fDuration = document.getElementById('fDuration');
  const fPrice    = document.getElementById('fPrice');
  const fTutor    = document.getElementById('fTutor');
  const fActive   = document.getElementById('fActive');

  function openCourseModal(mode, data = {}) {
    overlay.classList.add('open');
    courseModal.classList.add('open');

    if (mode === 'edit') {
      modalTitle.textContent = 'Edit Course';
      courseForm.action = `index.php?pages=courses&action=edit&id=${data.id}`;
      fTitle.value    = data.title;
      fSlug.value     = data.slug;
      fDesc.value     = data.desc;
      fDuration.value = data.duration;
      fPrice.value    = data.price;
      fTutor.value    = data.tutor_id;
      fActive.checked = data.is_active == 1;
    } else {
      modalTitle.textContent = 'New Course';
      courseForm.action = 'index.php?pages=courses&action=create';
      fTitle.value = fSlug.value = fDesc.value = fDuration.value = fPrice.value = '';
      fTutor.value = '';
      fActive.checked = true;
    }
  }

  function closeCourseModal() {
    overlay.classList.remove('open');
    courseModal.classList.remove('open');
  }

  newCourseBtn.addEventListener('click', () => openCourseModal('create'));
  closeCourseBtn.addEventListener('click', closeCourseModal);
  overlay.addEventListener('click', closeCourseModal);
  document.addEventListener('keydown', e => { if (e.key === 'Escape') closeCourseModal(); });

  document.querySelectorAll('.btn-editCourse').forEach(btn => {
    btn.addEventListener('click', () => {
      openCourseModal('edit', {
        id: btn.dataset.id,
        title: btn.dataset.title,
        slug: btn.dataset.slug,
        desc: btn.dataset.desc,
        duration: btn.dataset.duration,
        price: btn.dataset.price,
        tutor_id: btn.dataset.tutor,
        is_active: btn.dataset.active
      });
    });
  });
  </script>
</body>
</html>
