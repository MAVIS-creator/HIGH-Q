<?php
// admin/pages/courses.php
require_once '../includes/auth.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/csrf.php';

$pageTitle = 'Courses';
$pageSubtitle = 'Manage courses and programs offered on the site';

// Only Admin & Sub-Admin can manage courses
requirePermission('courses'); // where 'roles' matches the menu slug

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
  // $tutor removed
        $active = isset($_POST['is_active']) ? 1 : 0;
    $icon   = trim($_POST['icon'] ?? '');
    $features = trim($_POST['features'] ?? '');
    $highlight_badge = trim($_POST['highlight_badge'] ?? '');

    // Validation & sanitization
    if (mb_strlen($title) > 255) $title = mb_substr($title, 0, 255);
    if (mb_strlen($slug) > 255) $slug = mb_substr($slug, 0, 255);
    if (mb_strlen($dur) > 100) $dur = mb_substr($dur, 0, 100);
    if (mb_strlen($highlight_badge) > 100) $highlight_badge = mb_substr($highlight_badge, 0, 100);
    // features: limit each line to 500 chars and total concatenated length to 4000
    $features_lines = [];
    if ($features !== '') {
      foreach (preg_split('/\r?\n/', $features) as $line) {
        $line = trim($line);
        if ($line === '') continue;
        if (mb_strlen($line) > 500) $line = mb_substr($line, 0, 500);
        $features_lines[] = $line;
        if (strlen(implode('\n', $features_lines)) > 4000) break;
      }
    }

        if ($act === 'create') {
            if (!$title || !$slug) {
                $errors[] = "Title and slug are required.";
            } else {
                $stmt = $pdo->prepare(
                  "INSERT INTO courses
                    (title, slug, description, duration, price, tutor_id, created_by, is_active, icon, highlight_badge)
                  VALUES (?,?,?,?,?,?,?,?,?,?)"
                );
                $stmt->execute([
                  $title,
                  $slug,
                  $desc,
                  $dur,
                  $price,
                  null,
                  $_SESSION['user']['id'],
                  $active,
                  $icon ?: null,
                  $highlight_badge ?: null
                ]);
                $newCourseId = $pdo->lastInsertId();
                // Insert normalized features rows
                if (!empty($features_lines)) {
                    $pos = 0;
                    $ins = $pdo->prepare("INSERT INTO course_features (course_id, feature_text, position) VALUES (?, ?, ?)");
                    foreach ($features_lines as $line) {
                        $ins->execute([$newCourseId, $line, $pos++]);
                    }
                }
                logAction($pdo, $_SESSION['user']['id'], 'course_created', ['slug'=>$slug]);
                $success[] = "Course '{$title}' created.";
            }
        }

        if ($act === 'edit' && $id) {
            if (!$title || !$slug) {
                $errors[] = "Title and slug are required.";
            } else {
                $stmt = $pdo->prepare(
                  "UPDATE courses
                  SET title=?, slug=?, description=?, duration=?, price=?, tutor_id=?, is_active=?, icon=?, highlight_badge=?, updated_at=NOW()
                  WHERE id=?"
                );
                $stmt->execute([
                  $title,
                  $slug,
                  $desc,
                  $dur,
                  $price,
                  null,
                  $active,
                  $icon ?: null,
                  $highlight_badge ?: null,
                  $id
                ]);
                // Normalize features: delete old features rows and insert new ones
                $pdo->prepare("DELETE FROM course_features WHERE course_id = ?")->execute([$id]);
                if (!empty($features_lines)) {
                    $pos = 0;
                    $ins = $pdo->prepare("INSERT INTO course_features (course_id, feature_text, position) VALUES (?, ?, ?)");
                    foreach ($features_lines as $line) {
                        $ins->execute([$id, $line, $pos++]);
                    }
                }
                logAction($pdo, $_SESSION['user']['id'], 'course_updated', ['course_id'=>$id]);
                $success[] = "Course '{$title}' updated.";
            }
        }
    }

    // Load available icons (with class) from icons table (if exists)
    try {
      $icons = $pdo->query("SELECT id,name,filename,`class` FROM icons ORDER BY name")->fetchAll();
    } catch (\Exception $e) {
      $icons = [];
    }

    // Load courses with concatenated features
    try {
      $courses = $pdo->query("SELECT c.*, GROUP_CONCAT(cf.feature_text SEPARATOR '\\n') AS features_list FROM courses c LEFT JOIN course_features cf ON cf.course_id = c.id GROUP BY c.id ORDER BY c.title")->fetchAll(PDO::FETCH_ASSOC);
    } catch (\Exception $e) {
      $courses = [];
    }

?>

  <?php if ($errors): ?>
   <div class="alert error">
        <?php foreach ($errors as $err): ?><p><?= htmlspecialchars($err) ?></p><?php endforeach; ?>
      </div>
  <?php endif; ?>
  <?php if ($success): ?>
   <div class="alert success">
        <?php foreach ($success as $s): ?><p><?= htmlspecialchars($s) ?></p><?php endforeach; ?>
      </div>
  <?php endif; ?>


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
        <div class="course-icon">
          <?php if (!empty($c['icon'])): ?>
            <?php if (strpos($c['icon'], 'bx') !== false): // icon stored as boxicons class ?>
              <i class="<?= htmlspecialchars($c['icon']) ?>" style="font-size:28px"></i>
            <?php else: ?>
              <img src="../public/assets/images/icons/<?= htmlspecialchars($c['icon']) ?>" alt="<?= htmlspecialchars($c['title']) ?>">
            <?php endif; ?>
          <?php else: ?>
            <i class='bx bxs-graduation'></i>
          <?php endif; ?>
        </div>
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

      <?php if (!empty($c['features_list'])): ?>
        <ul class="course-features">
          <?php foreach (explode("\n", $c['features_list']) as $f): if (trim($f) === '') continue; ?>
            <li><?= htmlspecialchars(trim($f)) ?></li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>

      <?php if (!empty($c['highlight_badge'])): ?>
        <div class="course-highlight"><?= htmlspecialchars($c['highlight_badge']) ?></div>
      <?php endif; ?>

      <div class="course-meta">
        <span class="price">₦<?= number_format($c['price'],0) ?></span>
  <!-- Tutor removed from display -->
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
          data-icon="<?= htmlspecialchars($c['icon'] ?? '') ?>"
          data-features="<?= htmlspecialchars($c['features'] ?? '') ?>"
          data-badge="<?= htmlspecialchars($c['highlight_badge'] ?? '') ?>"
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
        <div class="form-row compact-row">
          <div class="form-group">
            <label for="fTitle">Title</label>
            <input type="text" name="title" id="fTitle" required>
          </div>
          <div class="form-group">
            <label for="fSlug">Slug</label>
            <input type="text" name="slug" id="fSlug" required>
          </div>
        </div>
        <div class="form-row compact-row">
          <div class="form-group">
            <label for="fDuration">Duration</label>
            <input type="text" name="duration" id="fDuration">
          </div>
          <div class="form-group">
            <label for="fPrice">Price (₦)</label>
            <input type="number" name="price" id="fPrice" step="0.01" min="0">
          </div>
        </div>
        <div class="form-row compact-row">
          <div class="form-group">
            <label for="fIcon">Icon</label>
            <div style="display:flex;gap:8px;align-items:center">
              <select name="icon" id="fIcon" class="styled-select">
                <option value="">— Default —</option>
                <?php foreach($icons as $ic): ?>
                  <?php $val = $ic['class'] ?: $ic['filename']; ?>
                  <option value="<?= htmlspecialchars($val) ?>"><?= htmlspecialchars($ic['name']) ?></option>
                <?php endforeach; ?>
              </select>
              <div id="iconPreview" aria-hidden="true" style="min-width:40px;min-height:24px"></div>
            </div>
          </div>
          <div class="form-group">
            <label for="fBadge">Highlight badge (short text)</label>
            <input type="text" name="highlight_badge" id="fBadge">
          </div>
        </div>
        <div class="form-row compact-row">
          <div class="form-group">
            <label for="fFeatures">Features (one per line)</label>
            <textarea name="features" id="fFeatures" rows="4" placeholder="Benefit 1\nBenefit 2\nBenefit 3"></textarea>
          </div>
          <div class="form-group">
            <label for="fDesc">Description</label>
            <textarea name="description" id="fDesc" rows="3"></textarea>
          </div>
        </div>
        <div class="form-row compact-row">
          <div class="form-group">
            <label><input type="checkbox" name="is_active" id="fActive" checked> Active</label>
          </div>
        </div>
        <div class="form-actions" style="justify-content:flex-end">
          <button type="submit" class="btn-approve">Save Course</button>
        </div>
      </form>
    </div>
  </div>
  <div id="modalOverlay"></div>

  <?php include '../includes/footer.php'; ?>

  <style>
    .compact-row {
      display: flex;
      flex-direction: row;
      gap: 1.5em;
    }
    .form-group {
      flex: 1 1 0;
      display: flex;
      flex-direction: column;
    }
    .styled-select {
      background: #f8f8f8;
      border: 1px solid #bbb;
      border-radius: 4px;
      padding: 0.5em 2em 0.5em 0.5em;
      font-size: 1em;
      appearance: none;
      -webkit-appearance: none;
      -moz-appearance: none;
      background-image: url('data:image/svg+xml;utf8,<svg fill="gray" height="16" viewBox="0 0 24 24" width="16" xmlns="http://www.w3.org/2000/svg"><path d="M7 10l5 5 5-5z"/></svg>');
      background-repeat: no-repeat;
      background-position: right 0.5em center;
      background-size: 1.2em;
    }
    .styled-select:focus {
      border-color: #888;
      outline: none;
    }
  </style>
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
  // Tutor removed
  const fActive   = document.getElementById('fActive');
  const fIcon     = document.getElementById('fIcon');
  const fFeatures = document.getElementById('fFeatures');
  const fBadge    = document.getElementById('fBadge');
  const iconPreview = document.getElementById('iconPreview');

  function openCourseModal(mode, data = {}) {
    overlay.classList.add('open');
    courseModal.classList.add('open');

    modalTitle.textContent = mode === 'edit' ? 'Edit Course' : 'New Course';

    // reset form
    courseForm.reset();
    if (fFeatures) fFeatures.value = '';
    if (fBadge) fBadge.value = '';
    if (iconPreview) iconPreview.innerHTML = '';

    if (mode === 'edit' && data && data.id) {
      // populate fields
      fTitle.value = data.title || '';
      fSlug.value = data.slug || '';
      fDesc.value = data.desc || '';
      fDuration.value = data.duration || '';
      fPrice.value = data.price || '';
      fActive.checked = data.is_active == '1' || data.is_active == 1 || data.is_active === true;
      fIcon.value = data.icon || '';
      if (fFeatures) fFeatures.value = data.features || '';
      if (fBadge) fBadge.value = data.badge || '';
      updateIconPreview();
      courseForm.action = `index.php?pages=courses&action=edit&id=${data.id}`;
    } else {
      courseForm.action = 'index.php?pages=courses&action=create';
    }

// Update icon preview live
function updateIconPreview() {
  if (!iconPreview || !fIcon) return;
  const v = fIcon.value || '';
  if (!v) { iconPreview.innerHTML = ''; return; }
  if (v.indexOf('bx') !== -1) {
    iconPreview.innerHTML = `<i class="${escapeHtml(v)}" style="font-size:20px"></i>`;
  } else {
    // assume filename
    iconPreview.innerHTML = `<img src="../public/assets/images/icons/${escapeHtml(v)}" style="width:28px;height:28px;object-fit:contain">`;
  }
}

fIcon && fIcon.addEventListener('change', updateIconPreview);

// helper to escape (very small helper for insertion into HTML)
function escapeHtml(s){
  return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
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
