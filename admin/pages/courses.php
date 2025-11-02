<?php
// admin/pages/courses.php
require_once __DIR__ . '/../bootstrap.php';
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
    // Allow empty price which indicates "Varies". Store as NULL in DB when left blank.
    $priceRaw = trim($_POST['price'] ?? '');
    if ($priceRaw === '') {
      $price = null;
    } else {
      // strip common formatting (commas, currency symbols) and keep numbers/dot
      $priceClean = preg_replace('/[^0-9.]/', '', $priceRaw);
      $price = number_format((float)$priceClean, 2, '.', '');
    }
  // $tutor removed
        $active = isset($_POST['is_active']) ? 1 : 0;
  // Support custom Boxicons class via icon_class (preferred) or fallback to selected filename/class from the dropdown
  $icon_class = trim($_POST['icon_class'] ?? '');
  $icon   = $icon_class !== '' ? $icon_class : trim($_POST['icon'] ?? '');
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

        // Auto-generate slug from title when missing
        if (!$slug && $title) {
            $slug = preg_replace('/[^a-z0-9]+/', '-', strtolower($title));
            $slug = trim($slug, '-');
        }

        if ($act === 'create') {
            if (!$title) {
                $errors[] = "Title is required.";
            } elseif (!$slug) {
                $errors[] = "Could not generate a slug from the title. Please enter a slug.";
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
                // Load available icons (with class) from icons table (if exists)
                try {
                  $icons = $pdo->query("SELECT id,name,filename,`class` FROM icons ORDER BY name")->fetchAll();
                } catch (\Exception $e) {
                  $icons = [];
                }
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
      // Auto-generate slug if missing when editing
      if (!$slug && $title) {
        $slug = preg_replace('/[^a-z0-9]+/', '-', strtolower($title));
        $slug = trim($slug, '-');
      }
      if (!$title) {
        $errors[] = "Title is required.";
      } elseif (!$slug) {
        $errors[] = "Could not generate a slug from the title. Please enter a slug.";
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
    // Delete action
    if ($act === 'delete' && $id) {
      // remove course features and course row
      try {
        $pdo->prepare('DELETE FROM course_features WHERE course_id = ?')->execute([$id]);
        $pdo->prepare('DELETE FROM courses WHERE id = ?')->execute([$id]);
        logAction($pdo, $_SESSION['user']['id'], 'course_deleted', ['course_id'=>$id]);
        $success[] = "Course deleted.";
      } catch (\Exception $e) {
        $errors[] = "Failed to delete course: " . $e->getMessage();
      }
    }
    // Bulk convert icons: map filename -> boxicons class using icons table
    if ($act === 'bulk_convert_icons') {
      try {
        // fetch mappings where a class exists
        $mappings = $pdo->query("SELECT filename, `class` FROM icons WHERE `class` IS NOT NULL AND `class` <> ''")->fetchAll(PDO::FETCH_ASSOC);
        $updated = 0;
        $updStmt = $pdo->prepare("UPDATE courses SET icon = ? WHERE icon = ?");
        foreach ($mappings as $m) {
          $filename = $m['filename'];
          $cls = $m['class'];
          if (!$filename || !$cls) continue;
          $updStmt->execute([$cls, $filename]);
          $updated += $updStmt->rowCount();
        }
        if ($updated > 0) {
          $success[] = "Bulk converted {$updated} icon(s) to Boxicons classes.";
          logAction($pdo, $_SESSION['user']['id'], 'bulk_convert_icons', ['changed'=>$updated]);
        } else {
          $success[] = "No icons required conversion.";
        }
      } catch (\Exception $e) {
        $errors[] = "Bulk convert failed: " . $e->getMessage();
      }
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

    // Load available icons (with class) from icons table (if exists)
    try {
      $icons = $pdo->query("SELECT id,name,filename,`class` FROM icons ORDER BY name")->fetchAll();
    } catch (\Exception $e) {
      $icons = [];
    }
?>

<?php include '../includes/header.php'; ?>
<?php include '../includes/sidebar.php'; ?>

<div class="courses-page">

  <?php if ($errors): ?>
   <div class="alert error">
        <?php foreach ($errors as $err): ?><p><?= htmlspecialchars($err) ?></p><?php endforeach; ?>
      </div>
  <?php endif; ?>
  <?php if ($success): ?>
   <div class="alert success server-success">
        <?php foreach ($success as $s): ?><p><?= htmlspecialchars($s) ?></p><?php endforeach; ?>
      </div>
  <?php endif; ?>


  <div class="page-actions" style="display:flex;justify-content:flex-end;gap:8px;align-items:center;margin-bottom:12px;">
    <button id="newCourseBtn" class="header-cta">New Course</button>
  <form method="post" action="?action=bulk_convert_icons" style="display:inline">
      <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
      <button type="submit" class="header-cta" style="background:#f7c948;color:#222">Bulk Convert Icons</button>
    </form>
  </div>

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
              <?php
                // If icon is an absolute URL or starts with / -> use directly, else assume filename in public assets
                $iconVal = $c['icon'];
                if (preg_match('#^https?://#i', $iconVal) || strpos($iconVal, '/') === 0 || strpos($iconVal, '//') === 0) {
                    $iconSrc = $iconVal;
                } else {
                    $iconSrc = '../public/assets/images/icons/' . ltrim($iconVal, '/');
                }
              ?>
              <img src="<?= htmlspecialchars($iconSrc) ?>" alt="<?= htmlspecialchars($c['title']) ?>">
            <?php endif; ?>
          <?php else: ?>
            <i class='bx bxs-graduation'></i>
          <?php endif; ?>
        </div>
        <div class="course-title">
          <h3><?= htmlspecialchars($c['title']) ?></h3>
          <small><?= htmlspecialchars($c['duration'] ?: 'Flexible') ?></small>
        </div>
  <form method="post" action="?action=delete&id=<?= $c['id'] ?>" class="delete-form">
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
        <span class="price"><?php if ($c['price'] === null || $c['price'] === '' ) { echo 'Varies'; } else { echo '₦' . number_format($c['price'],0); } ?></span>
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
          data-price="<?= $c['price'] === null ? '' : $c['price'] ?>"
          data-tutor="<?= $c['tutor_id'] ?>"
          data-active="<?= $c['is_active'] ?>"
          data-icon="<?= htmlspecialchars($c['icon'] ?? '') ?>"
          data-features="<?= htmlspecialchars($c['features_list'] ?? $c['features'] ?? '') ?>"
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

  <form id="courseForm" method="post" action="<?= htmlspecialchars(admin_url('pages/courses.php?action=create')) ?>">
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
            <label for="fPrice">Price (₦) <small style="color:#666;font-weight:400">leave blank for "Varies"</small></label>
            <input type="text" name="price" id="fPrice" placeholder="Varies">
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
              <div style="display:flex;flex-direction:column;gap:6px">
                <input type="text" name="icon_class" id="fIconClass" placeholder="Or enter boxicons class (e.g. 'bx bxs-book')" style="padding:6px;border:1px solid #ccc;border-radius:4px;font-size:0.95rem">
                <div id="iconPreview" aria-hidden="true" style="min-width:40px;min-height:24px"></div>
              </div>
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

  <!-- Icon Picker Modal -->
  <div class="modal" id="iconPickerModal" style="display:none;z-index:9999">
    <div class="modal-content" style="max-width:520px;">
      <span class="modal-close" id="iconPickerClose"><i class='bx bx-x'></i></span>
      <h3 style="margin-bottom:10px">Select Icon</h3>
      <div id="iconPickerList" style="display:grid;grid-template-columns:repeat(3,1fr);gap:16px;max-height:340px;overflow-y:auto;margin-bottom:10px;"></div>
      <div style="text-align:right"><button type="button" id="iconPickerCancel" style="padding:6px 16px;border-radius:4px;border:1px solid #bbb;background:#eee;">Cancel</button></div>
    </div>
  </div>

  <script>
  // Icon Picker Modal logic
  const iconPickerBtn = document.getElementById('iconPickerBtn');
  const iconPickerModal = document.getElementById('iconPickerModal');
  const iconPickerList = document.getElementById('iconPickerList');
  const iconPickerClose = document.getElementById('iconPickerClose');
  const iconPickerCancel = document.getElementById('iconPickerCancel');
  // icons data from PHP
  const iconsData = <?php echo json_encode($icons); ?>;
  function openIconPicker() {
    if (!iconPickerModal || !iconPickerList) return;
    iconPickerList.innerHTML = '';
    iconsData.forEach(ic => {
      let el = document.createElement('div');
      el.className = 'icon-picker-item';
      el.style = 'display:flex;flex-direction:column;align-items:center;gap:4px;padding:8px;cursor:pointer;border:1px solid #eee;border-radius:6px;background:#fafafa;';
      let preview;
      if (ic.class && ic.class.indexOf('bx') !== -1) {
        preview = `<i class='${ic.class}' style='font-size:28px;color:#222'></i>`;
      } else {
        preview = `<img src='../public/assets/images/icons/${ic.filename}' style='width:28px;height:28px;object-fit:contain'>`;
      }
      el.innerHTML = `${preview}<span style='font-size:0.95em;color:#444'>${ic.name}</span>`;
      el.title = ic.name;
      el.onclick = function() {
        if (fIconClass) fIconClass.value = ic.class && ic.class.indexOf('bx') !== -1 ? ic.class : ic.filename;
        updateIconPreview();
        iconPickerModal.style.display = 'none';
      };
      iconPickerList.appendChild(el);
    });
    iconPickerModal.style.display = 'block';
    iconPickerModal.classList.add('open');
    overlay.classList.add('open');
  }
  if (iconPickerBtn) iconPickerBtn.addEventListener('click', openIconPicker);
  if (iconPickerClose) iconPickerClose.addEventListener('click', function(){ iconPickerModal.style.display = 'none'; overlay.classList.remove('open'); iconPickerModal.classList.remove('open'); });
  if (iconPickerCancel) iconPickerCancel.addEventListener('click', function(){ iconPickerModal.style.display = 'none'; overlay.classList.remove('open'); iconPickerModal.classList.remove('open'); });
  </script>
  

  
  <!-- SweetAlert2 for toast notifications -->
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script>
    (function(){
      try {
        var successMsgs = <?php echo json_encode(array_values($success)); ?> || [];
        if (successMsgs.length > 0 && typeof Swal !== 'undefined') {
          // hide server banner if present
          var banner = document.querySelector('.server-success'); if (banner) banner.style.display = 'none';
          successMsgs.forEach(function(msg, idx){
            setTimeout(function(){
              Swal.fire({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 2200,
                timerProgressBar: true,
                icon: 'success',
                title: msg
              });
            }, idx * 250);
          });
        }
      } catch(e){ console.error(e); }
    })();
  </script>

  <?php include '../includes/footer.php'; ?>

</div>

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
  const newCourseBtn   = document.getElementById('newCourseBtn'); // may be absent in some layouts
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
  const fIconClass = document.getElementById('fIconClass');
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
  if (fIconClass) fIconClass.value = '';

  if (mode === 'edit' && data && data.id) {
      // populate fields
      fTitle.value = data.title || '';
      fSlug.value = data.slug || '';
      fDesc.value = data.desc || '';
      fDuration.value = data.duration || '';
  // populate price (may be empty meaning 'Varies')
  fPrice.value = (data.price === null || typeof data.price === 'undefined' || data.price === '') ? '' : data.price;
      fActive.checked = data.is_active == '1' || data.is_active == 1 || data.is_active === true;
      // if the stored icon looks like a boxicons class (contains 'bx'), populate the custom class input
      if (typeof data.icon === 'string' && data.icon.indexOf('bx') !== -1) {
        if (fIconClass) fIconClass.value = data.icon || '';
        if (fIcon) fIcon.value = '';
      } else {
        if (fIcon) fIcon.value = data.icon || '';
        if (fIconClass) fIconClass.value = '';
      }
      if (fFeatures) fFeatures.value = data.features || '';
      if (fBadge) fBadge.value = data.badge || '';
      updateIconPreview();
      // Use client-side admin base so JS respects subfolder; header.php exposes window.HQ_ADMIN_BASE
      courseForm.action = (window.HQ_ADMIN_BASE || '') + '/pages/courses.php?action=edit&id=' + encodeURIComponent(data.id);
    } else {
      courseForm.action = (window.HQ_ADMIN_BASE || '') + '/pages/courses.php?action=create';
    }
  }
  // Auto-generate slug client-side as user types title when slug is empty
  fTitle && fTitle.addEventListener('input', function(){
    try {
      if (!fSlug) return;
      if (fSlug.value && fSlug.value.trim() !== '') return; // don't overwrite if user provided
      var v = String(fTitle.value || '').toLowerCase();
      v = v.replace(/[^a-z0-9]+/g, '-').replace(/(^-|-$)/g, '');
      fSlug.value = v;
    } catch(e){console.error(e)}
  });
// Update icon preview live
function updateIconPreview() {
  if (!iconPreview || !fIcon) return;
  const v = (fIconClass && fIconClass.value) ? fIconClass.value : (fIcon.value || '');
  if (!v) { iconPreview.innerHTML = ''; return; }
  if (v.indexOf('bx') !== -1) {
    iconPreview.innerHTML = `<i class="${escapeHtml(v)}" style="font-size:20px"></i>`;
  } else {
    // assume filename
    iconPreview.innerHTML = `<img src="../public/assets/images/icons/${escapeHtml(v)}" style="width:28px;height:28px;object-fit:contain">`;
  }
}

fIcon && fIcon.addEventListener('change', updateIconPreview);
fIconClass && fIconClass.addEventListener('input', updateIconPreview);

// helper to escape (very small helper for insertion into HTML)
function escapeHtml(s){
  return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

  function closeCourseModal() {
    overlay.classList.remove('open');
    courseModal.classList.remove('open');
  }

  if (newCourseBtn) newCourseBtn.addEventListener('click', () => openCourseModal('create'));
  if (closeCourseBtn) closeCourseBtn.addEventListener('click', closeCourseModal);
  if (overlay) overlay.addEventListener('click', closeCourseModal);
  document.addEventListener('keydown', e => { if (e.key === 'Escape') closeCourseModal(); });

  document.querySelectorAll('.btn-editCourse').forEach(btn => {
    btn.addEventListener('click', () => {
      // use getAttribute for fields that may contain newlines or special chars (features, desc)
      openCourseModal('edit', {
        id: btn.dataset.id,
        title: btn.dataset.title,
        slug: btn.dataset.slug,
        desc: btn.getAttribute('data-desc'),
        duration: btn.dataset.duration,
        price: btn.dataset.price,
        tutor_id: btn.dataset.tutor,
        is_active: btn.dataset.active,
        icon: btn.dataset.icon || '',
        features: btn.getAttribute('data-features') || '',
        badge: btn.dataset.badge || ''
      });
    });
  });

  // Attach confirmation to delete forms using SweetAlert2
  document.querySelectorAll('form.delete-form').forEach(f => {
    f.addEventListener('submit', function(e){
      e.preventDefault();
      const form = this;
      Swal.fire({ title: 'Delete course?', text: 'Delete this course? This action cannot be undone.', icon: 'warning', showCancelButton: true, confirmButtonText: 'Yes, delete', confirmButtonColor: '#d33' }).then(function(res){ if (res.isConfirmed) form.submit(); });
    });
  });
  </script>
</body>
</html>
