<?php
// admin/pages/posts.php
require_once '../includes/auth.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/csrf.php';

$pageTitle = 'News & Blog';
$pageSubtitle = 'Create and manage news articles and blog posts';

// Only Admin / Sub-Admin / Moderator
requirePermission('post'); // where 'roles' matches the menu slug

$csrf     = generateToken();
$errors   = [];
$success  = [];

// Ensure posts page CSS loads after admin.css
$pageCss = '<link rel="stylesheet" href="../assets/css/posts.css">';

// Make sure uploads folder exists
$uploadDir = __DIR__ . '/../../public/uploads/posts/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

// Determine application base URL (use APP_URL env if available, otherwise build from host)
$appUrl = getenv('APP_URL') ?: ($_ENV['APP_URL'] ?? null);
if (!$appUrl) {
    $appUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
    $appUrl .= ($_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME']);
}

// Detect whether the posts table has a category_id column on this install
$hasCategoryId = false;
try {
    $colStmt = $pdo->prepare("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'posts' AND COLUMN_NAME = 'category_id'");
    $colStmt->execute();
    $hasCategoryId = (bool)$colStmt->fetchColumn();
} catch (Throwable $e) {
    $hasCategoryId = false;
}
// Detect whether the posts table has a tags column (some installs don't)
$hasTags = false;
try {
    $colStmt2 = $pdo->prepare("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'posts' AND COLUMN_NAME = 'tags'");
    $colStmt2->execute();
    $hasTags = (bool)$colStmt2->fetchColumn();
} catch (Throwable $e) {
    $hasTags = false;
}

// Fetch list of available columns on posts table to build queries safely
$availableCols = [];
try {
    $colsStmt = $pdo->prepare("SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'posts'");
    $colsStmt->execute();
    $availableCols = $colsStmt->fetchAll(PDO::FETCH_COLUMN);
} catch (Throwable $e) {
    $availableCols = [];
}

$hasFeaturedImage = in_array('featured_image', $availableCols, true);
$hasStatus = in_array('status', $availableCols, true);
$hasAuthorId = in_array('author_id', $availableCols, true);
$hasUpdatedAt = in_array('updated_at', $availableCols, true);
$hasCategory = in_array('category', $availableCols, true);

// Handle Create / Edit / Delete / Toggle Publish
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action'])) {
    // Temporary debug log (remove when investigation complete)
    try {
        $dbgDir = __DIR__ . '/../../storage';
        if (!is_dir($dbgDir)) @mkdir($dbgDir, 0755, true);
        $dbgFile = $dbgDir . '/posts-debug.log';
        $log = "----\n" . date('c') . " POST to post.php action=" . ($_GET['action'] ?? '') . "\n";
        $log .= "POST keys: " . implode(', ', array_keys($_POST ?? [])) . "\n";
        $files = [];
        foreach ($_FILES as $k=>$f) { $files[] = $k . '(' . ($f['name'] ?? '') . ')'; }
        $log .= "FILES: " . implode(', ', $files) . "\n";
        @file_put_contents($dbgFile, $log, FILE_APPEND | LOCK_EX);
    } catch (\Throwable $e) { /* ignore debug failures */ }
    $dbgFile = __DIR__ . '/../../storage/posts-debug.log';
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors[] = "Invalid CSRF token.";
        @file_put_contents($dbgFile, date('c') . " CSRF: INVALID\n", FILE_APPEND | LOCK_EX);
    } else {
        @file_put_contents($dbgFile, date('c') . " CSRF: OK\n", FILE_APPEND | LOCK_EX);
        $act = $_GET['action'];
        $id  = (int)($_GET['id'] ?? 0);

        // Gather & sanitize
        $title       = trim($_POST['title'] ?? '');
        $slug        = trim($_POST['slug'] ?? '');
        $excerpt     = trim($_POST['excerpt'] ?? '');
        $content     = trim($_POST['content'] ?? '');
        $category_id = (int)($_POST['category_id'] ?? 0);
        $tags        = trim($_POST['tags'] ?? '');
        $status      = isset($_POST['publish']) ? 'published' : 'draft';

        // Slugify if empty
        if (!$slug && $title) {
            $slug = preg_replace('/[^a-z0-9]+/', '-', strtolower($title));
            $slug = trim($slug, '-');
        }

        // Handle featured image upload
        $imgPath = '';
        if (!empty($_FILES['featured_image']['name']) && $_FILES['featured_image']['error'] === UPLOAD_ERR_OK) {
            $ext      = pathinfo($_FILES['featured_image']['name'], PATHINFO_EXTENSION);
            $filename = uniqid('post_') . '.' . $ext;
            $target   = $uploadDir . $filename;
            if (move_uploaded_file($_FILES['featured_image']['tmp_name'], $target)) {
                // Save featured image as a full URL (APP_URL + uploads path) instead of filesystem path
                $imgRel = "uploads/posts/{$filename}";
                $imgPath = rtrim($appUrl, '/') . '/' . ltrim($imgRel, '/');
            }
        }

        // Validation
        if (!$title || !$content) {
            $errors[] = "Title and content are required.";
        }

        if (empty($errors)) {
                        // CREATE
            if ($act === 'create') {
                // Build columns and params dynamically based on DB schema
                $cols = ['title', 'slug', 'excerpt', 'content'];
                $params = [$title, $slug, $excerpt, $content];
                if ($hasCategoryId) { $cols[] = 'category_id'; $params[] = $category_id ?: null; }
                if ($hasTags) { $cols[] = 'tags'; $params[] = $tags; }
                if ($hasFeaturedImage) { $cols[] = 'featured_image'; $params[] = $imgPath ?: null; }
                if ($hasStatus) { $cols[] = 'status'; $params[] = $status; }
                if ($hasAuthorId) { $cols[] = 'author_id'; $params[] = $_SESSION['user']['id']; }

                $placeholders = implode(',', array_fill(0, count($cols), '?'));
                $sql = "INSERT INTO posts (" . implode(', ', $cols) . ") VALUES ({$placeholders})";
                try {
                    @file_put_contents($dbgFile, date('c') . " SQL: " . $sql . "\nPARAMS: " . json_encode($params) . "\n", FILE_APPEND | LOCK_EX);
                    $stmt = $pdo->prepare($sql);
                    $ok = $stmt->execute($params);
                    @file_put_contents($dbgFile, date('c') . " EXECUTE RESULT: " . ($ok ? 'true' : 'false') . "\n", FILE_APPEND | LOCK_EX);
                    if (!$ok) {
                        $ei = $stmt->errorInfo();
                        @file_put_contents($dbgFile, date('c') . " ERRORINFO: " . json_encode($ei) . "\n", FILE_APPEND | LOCK_EX);
                    }
                } catch (Throwable $e) {
                    @file_put_contents($dbgFile, date('c') . " EXCEPTION: " . $e->getMessage() . "\n", FILE_APPEND | LOCK_EX);
                    $ok = false;
                }
                                if ($ok) {
                                    $newId = $pdo->lastInsertId();
                                    logAction($pdo, $_SESSION['user']['id'], 'post_created', ['slug' => $slug]);
                                    @file_put_contents(__DIR__ . '/../../storage/posts-debug.log', date('c') . " DB CREATED ID: " . $newId . "\n", FILE_APPEND | LOCK_EX);
                                    // Set a session flash so admin UI can show a notification after redirect
                                    $_SESSION['flash_post'] = [
                                        'type' => 'success',
                                        'message' => "Article '{$title}' created.",
                                        'published' => ($status === 'published')
                                    ];
                                    // Redirect back to posts listing explicitly
                                    header("Location: index.php?pages=posts");
                                    exit;
                                } else {
                                    $ei = $stmt->errorInfo();
                                    $msg = "Failed to create article: " . ($ei[2] ?? 'Unknown DB error');
                                    $errors[] = $msg;
                                    @file_put_contents(__DIR__ . '/../../storage/posts-debug.log', date('c') . " DB CREATE ERROR: " . $msg . "\n", FILE_APPEND | LOCK_EX);
                                }
            }

            // EDIT
                        if ($act === 'edit' && $id) {
                // If no new image, keep existing
                if (!$imgPath) {
                    $old = $pdo->prepare("SELECT featured_image FROM posts WHERE id=?");
                    $old->execute([$id]);
                    $imgPath = $old->fetchColumn();
                }
                                                // Build SET clause and params dynamically
                                                $setParts = ['title=?', 'slug=?', 'excerpt=?', 'content=?'];
                                                $params = [$title, $slug, $excerpt, $content];
                                                if ($hasCategoryId) { $setParts[] = 'category_id=?'; $params[] = $category_id ?: null; }
                                                if ($hasTags) { $setParts[] = 'tags=?'; $params[] = $tags; }
                                                if ($hasFeaturedImage) { $setParts[] = 'featured_image=?'; $params[] = $imgPath ?: null; }
                                                if ($hasStatus) { $setParts[] = 'status=?'; $params[] = $status; }
                                                $sql = "UPDATE posts SET " . implode(', ', $setParts);
                                                if ($hasUpdatedAt) $sql .= ", updated_at=NOW()";
                                                $sql .= " WHERE id=?";
                                                $params[] = $id;
                                try {
                                    @file_put_contents($dbgFile, date('c') . " SQL: " . $sql . "\nPARAMS: " . json_encode($params) . "\n", FILE_APPEND | LOCK_EX);
                                    $stmt = $pdo->prepare($sql);
                                    $ok = $stmt->execute($params);
                                    @file_put_contents($dbgFile, date('c') . " EXECUTE RESULT: " . ($ok ? 'true' : 'false') . "\n", FILE_APPEND | LOCK_EX);
                                    if (!$ok) {
                                        $ei = $stmt->errorInfo();
                                        @file_put_contents($dbgFile, date('c') . " ERRORINFO: " . json_encode($ei) . "\n", FILE_APPEND | LOCK_EX);
                                    }
                                } catch (Throwable $e) {
                                    @file_put_contents($dbgFile, date('c') . " EXCEPTION: " . $e->getMessage() . "\n", FILE_APPEND | LOCK_EX);
                                    $ok = false;
                                }
                if ($ok) {
                    logAction($pdo, $_SESSION['user']['id'], 'post_updated', ['post_id' => $id]);
                    @file_put_contents(__DIR__ . '/../../storage/posts-debug.log', date('c') . " DB UPDATED ID: " . $id . "\n", FILE_APPEND | LOCK_EX);
                    // If this is an AJAX edit, return JSON including published state
                    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                        header('Content-Type: application/json');
                        echo json_encode(['success' => true, 'post' => [
                            'id' => $id,
                            'title' => $title,
                            'excerpt' => $excerpt,
                            'category' => '',
                            'author' => $_SESSION['user']['name'] ?? '' ,
                            'featured_image' => $imgPath,
                            'published' => ($status === 'published')
                        ]]);
                        exit;
                    }

                    // Non-AJAX update: set session flash and redirect to posts
                    $_SESSION['flash_post'] = [
                        'type' => 'success',
                        'message' => "Article '{$title}' updated.",
                        'published' => ($status === 'published')
                    ];
                    header("Location: index.php?pages=posts");
                    exit;
                } else {
                    $ei = $stmt->errorInfo();
                    $msg = "Failed to update article: " . ($ei[2] ?? 'Unknown DB error');
                    $errors[] = $msg;
                    @file_put_contents(__DIR__ . '/../../storage/posts-debug.log', date('c') . " DB UPDATE ERROR: " . $msg . "\n", FILE_APPEND | LOCK_EX);
                }
                // If this is an AJAX edit (X-Requested-With), return JSON with updated post data
                if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => true, 'post' => [
                        'id' => $id,
                        'title' => $title,
                        'excerpt' => $excerpt,
                        'category' => '', // category name not joined here
                        'author' => $_SESSION['user']['name'] ?? '' ,
                        'featured_image' => $imgPath
                    ]]);
                    exit;
                }
            }

            // DELETE
            if ($act === 'delete' && $id) {
                $pdo->prepare("DELETE FROM posts WHERE id=?")->execute([$id]);
                logAction($pdo, $_SESSION['user']['id'], 'post_deleted', ['post_id' => $id]);
                $success[] = "Article deleted.";
            }

            // TOGGLE PUBLISH
            if ($act === 'toggle' && $id) {
                $stmt = $pdo->prepare("UPDATE posts SET status = IF(status='draft','published','draft') WHERE id=?");
                $stmt->execute([$id]);
                logAction($pdo, $_SESSION['user']['id'], 'post_toggled', ['post_id' => $id]);
                $success[] = "Article status toggled.";
            }
        }

    // Only redirect to the posts list when we successfully created/updated (i.e. have success messages)
    if (empty($errors) && !empty($success)) {
        header("Location: index.php?pages=posts");
        exit;
    }
    }
}

// Search filter
$q = trim($_GET['q'] ?? '');

// Fetch categories (table may not exist on some installs)
try {
    $categories = $pdo->query("SELECT id,name FROM categories ORDER BY name")->fetchAll();
} catch (Exception $e) {
    // Provide an empty array and set a warning so UI can show a helpful message
    $categories = [];
    $catWarning = 'Categories table not found. Create the table or add categories to enable categorization.';
}

$sql  = "SELECT p.*, u.name AS author, COALESCE(c.name, p.category) AS category_name
         FROM posts p
         LEFT JOIN users u ON u.id = p.author_id
         LEFT JOIN categories c ON c.id = p.category_id
         WHERE p.title LIKE :q
         ORDER BY p.created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute([':q' => "%{$q}%"]);
$posts = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>News & Blog Management — Admin</title>
    <link rel="stylesheet" href="../public/assets/css/admin.css">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
</head>

<body>
    <?php include '../includes/header.php'; ?>
    <?php include '../includes/sidebar.php'; ?>

    <div class="container">
        <?php if (!empty($_SESSION['flash_post'])): ?>
            <?php $f = $_SESSION['flash_post']; unset($_SESSION['flash_post']); ?>
            <div class="admin-notice" style="background:#e7ffef;border-left:4px solid #66cc88;padding:12px;margin-bottom:12px;">
                <div><?= htmlspecialchars($f['message']) ?></div>
                <?php if (isset($f['published'])): ?>
                    <div style="font-size:0.9rem;color:#666;margin-top:6px;">Status: <strong><?= $f['published'] ? 'Published' : 'Draft' ?></strong></div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
            <div class="admin-notice" style="background:#ffecec;border-left:4px solid #f28b82;padding:12px;margin-bottom:12px;">
                <?php foreach ($errors as $err) echo '<div>' . htmlspecialchars($err) . '</div>'; ?>
            </div>
        <?php endif; ?>
        <?php if (!empty($success)): ?>
            <div class="admin-notice" style="background:#e7ffef;border-left:4px solid #66cc88;padding:12px;margin-bottom:12px;">
                <?php foreach ($success as $s) echo '<div>' . htmlspecialchars($s) . '</div>'; ?>
            </div>
        <?php endif; ?>
        <div class="module-header">
            <div>
                <h1>News & Blog Management</h1>
                <p class="subtitle">Create and manage news articles and blog posts.</p>
            </div>
            <div class="module-actions">
                <form method="get" action="index.php" class="search-form">
                    <input type="hidden" name="pages" value="posts">
                    <input type="text" name="q" placeholder="Search by title..." value="<?= htmlspecialchars($q) ?>">
                    

                </form>
                <button id="newPostBtn" class="btn-approve">
                    <i class="bx bx-plus"></i> Add Article
                </button>
            </div>
        </div>

        <?php if ($posts): ?>
            <?php
                $missing = [];
                foreach ($posts as $pp) {
                    if ($pp['status'] === 'published' && (empty($pp['excerpt']) || empty($pp['featured_image']))) {
                        $missing[] = $pp;
                    }
                }
            ?>
            <?php if (!empty($missing)): ?>
                <div class="admin-notice" style="background:#fff7e6;border-left:4px solid var(--hq-yellow);padding:12px;margin-bottom:12px;">
                    <strong>Notice:</strong> <?= count($missing) ?> published post(s) are missing an excerpt or featured image. It's recommended to add an excerpt and/or featured image for better listings and sharing.
                </div>
            <?php endif; ?>
            <div class="posts-grid">
                <?php foreach ($posts as $p): ?>
                    <div class="post-card" id="post-row-<?= $p['id'] ?>">
                        <?php if ($p['featured_image']): ?>
                                <?php
                                    // Support both full URLs and legacy relative paths stored in DB.
                                    $fi = $p['featured_image'];
                                    if (preg_match('#^https?://#i', $fi) || strpos($fi, '//') === 0 || strpos($fi, '/') === 0) {
                                        $imgSrc = $fi;
                                    } else {
                                        // legacy relative path (e.g. uploads/posts/xxx.jpg) -> prefix admin->public
                                        $imgSrc = '../public/' . ltrim($fi, '/');
                                    }
                                ?>
                                <img src="<?= htmlspecialchars($imgSrc) ?>" class="thumb">
                            <?php endif; ?>
                        <div class="meta">
                            <strong><?= htmlspecialchars($p['title']) ?></strong>
                            <div class="info"><?= htmlspecialchars($p['category'] ?? 'Uncategorized') ?> • <?= htmlspecialchars($p['author'] ?? '') ?></div>
                            <?php if ($p['status'] === 'published' && (empty($p['excerpt']) || empty($p['featured_image']))): ?>
                                <span title="Missing excerpt or image" style="color:var(--hq-yellow);margin-left:8px">⚠</span>
                            <?php endif; ?>
                        </div>
                        <div class="excerpt"><?= nl2br(htmlspecialchars($p['excerpt'] ?: '')) ?></div>
                        <div class="actions">
                            <a href="post_edit.php?id=<?= $p['id'] ?>" class="btn edit-link" data-id="<?= $p['id'] ?>">Edit</a>
                            <form method="post" action="index.php?action=delete&amp;id=<?= $p['id'] ?>&amp;pages=posts" style="display:inline">
                                <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                                <button class="btn btn-danger" type="submit">Delete</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="posts-empty">
                <p>No articles yet. Click "Add Article" to create the first post.</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Post Modal -->
    <div class="modal" id="postModal">
        <div class="modal-content">
            <span class="modal-close" id="postModalClose"><i class="bx bx-x"></i></span>
            <h3 id="postModalTitle">New Article</h3>
            <form id="postForm" method="post" action="index.php?pages=posts&action=create" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?= $csrf ?>">

                <div class="form-row">
                    <label>Title *</label>
                    <input type="text" name="title" id="pTitle" required>
                </div>
                <div class="form-row">
                    <label>Slug</label>
                    <input type="text" name="slug" id="pSlug">
                </div>
                <div class="form-row">
                    <label>Excerpt</label>
                    <input type="text" name="excerpt" id="pExcerpt">
                </div>
                <div class="form-row">
                    <label>Content *</label>
                    <textarea name="content" id="pContent" rows="6" required></textarea>
                </div>
                <div class="form-row">
                    <label>Category</label>
                    <select name="category_id" id="pCategory">
                        <option value="">Uncategorized</option>
                        <?php foreach ($categories as $c): ?>
                            <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-row">
                    <label>Tags (comma-separated)</label>
                    <input type="text" name="tags" id="pTags">
                </div>
                <div class="form-row">
                    <label>Featured Image</label>
                    <div class="file-input-wrap">
                        <input type="file" name="featured_image" id="pImage" accept="image/*">
                        <button type="button" id="pImageBtn" class="btn">Choose File</button>
                        <span id="pImageName" class="file-name">No file chosen</span>
                    </div>
                </div>
                <div class="form-row">
                    <label><input type="checkbox" name="publish" id="pPublish"> Publish immediately</label>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn-approve">Save Article</button>
                </div>
            </form>
        </div>
    </div>
    <div id="modalOverlay"></div>
    <div class="modal" id="editPostModal">
        <div class="modal-content" id="editPostModalContent">
            <!-- AJAX-loaded content will go here -->
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>

   <script>
// Auto-generate slug from title and excerpt from content; display chosen file name
const pTitle = document.getElementById('pTitle');
const pSlug = document.getElementById('pSlug');
const pContent = document.getElementById('pContent');
const pExcerpt = document.getElementById('pExcerpt');
const pImage = document.getElementById('pImage');
const pImageName = document.getElementById('pImageName');
const pImageBtn = document.getElementById('pImageBtn');

function slugify(v){ return String(v || '').toLowerCase().replace(/[^a-z0-9]+/g,'-').replace(/(^-|-$)/g,''); }
if (pTitle && pSlug) {
    pTitle.addEventListener('input', function(){
        try { if (!pSlug.value || pSlug.value.trim()==='') pSlug.value = slugify(pTitle.value); } catch(e){}
    });
}
if (pContent && pExcerpt) {
    pContent.addEventListener('input', function(){
        try {
            if (!pExcerpt.value || pExcerpt.value.trim()==='') {
                var txt = pContent.value.replace(/\s+/g,' ').trim();
                pExcerpt.value = txt.length > 160 ? txt.substr(0,160).trim() + '...' : txt;
            }
        } catch(e){}
    });
}
if (pImage) {
    pImage.addEventListener('change', function(){
        if (pImage.files && pImage.files.length>0) {
            pImageName.textContent = pImage.files[0].name;
            // optional small preview: if file is image, show preview inside modal
            try {
                var f = pImage.files[0];
                if (f.type.indexOf('image/') === 0) {
                    var reader = new FileReader();
                    reader.onload = function(e){
                        // create or update preview img
                        var ex = document.getElementById('pImagePreview');
                        if (!ex) {
                            ex = document.createElement('img'); ex.id = 'pImagePreview'; ex.style.maxWidth='120px'; ex.style.maxHeight='80px'; ex.style.marginLeft='8px'; ex.style.borderRadius='6px';
                            pImageName.parentNode.appendChild(ex);
                        }
                        ex.src = e.target.result;
                    };
                    reader.readAsDataURL(f);
                }
            } catch(e){}
        } else pImageName.textContent = 'No file chosen';
    });
    if (pImageBtn) pImageBtn.addEventListener('click', function(){ pImage.click(); });
}

const overlay     = document.getElementById('modalOverlay');
const editModal   = document.getElementById('editPostModal');
const editContent = document.getElementById('editPostModalContent');

// Add Article modal wiring
const postModal = document.getElementById('postModal');
const newPostBtn = document.getElementById('newPostBtn');
const postForm = document.getElementById('postForm');
const postModalClose = document.getElementById('postModalClose');

function openPostModal() {
    overlay.classList.add('open');
    postModal.classList.add('open');
    // Ensure form posts to create action
    postForm.action = 'index.php?pages=posts&action=create';
}

function closePostModal() {
    overlay.classList.remove('open');
    postModal.classList.remove('open');
}

if (newPostBtn) newPostBtn.addEventListener('click', openPostModal);
if (postModalClose) postModalClose.addEventListener('click', closePostModal);
overlay.addEventListener('click', () => { closePostModal(); closeEditModal(); });

// Open modal and load form
document.querySelectorAll('.edit-link').forEach(link => {
  link.addEventListener('click', e => {
    e.preventDefault();
    const id = link.dataset.id;
    fetch(`post_edit.php?id=${id}`)
      .then(res => res.text())
      .then(html => {
        editContent.innerHTML = html;
        overlay.classList.add('open');
        editModal.classList.add('open');
        bindAjaxForm(id);
      });
  });
});

function closeEditModal() {
  overlay.classList.remove('open');
  editModal.classList.remove('open');
}
overlay.addEventListener('click', closeEditModal);

// Bind AJAX submit
function bindAjaxForm(id) {
  const form = document.getElementById('ajaxEditPostForm');
  form.addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(form);
    fetch(`index.php?pages=posts&action=edit&id=${id}`, {
      method: 'POST',
      body: formData,
      headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(res => res.json())
    .then(data => {
            if (data.success) {
                // Update card in place
                const card = document.getElementById(`post-row-${id}`);
                if (card) {
                    card.querySelector('.meta strong').textContent = data.post.title;
                    const info = card.querySelector('.meta .info');
                    if (info) info.textContent = `${data.post.category || 'Uncategorized'} • ${data.post.author || ''}`;
                    const excerpt = card.querySelector('.excerpt');
                    if (excerpt) excerpt.innerHTML = (data.post.excerpt || '').replace(/\n/g, '<br>');
                    if (data.post.featured_image) {
                        let img = card.querySelector('img.thumb');
                        if (!img) {
                            img = document.createElement('img');
                            img.className = 'thumb';
                            card.insertBefore(img, card.firstChild);
                        }
                        // Determine if returned path is absolute URL or relative
                        let fi = data.post.featured_image;
                        let src = fi;
                        try {
                            if (!/^https?:\/\//i.test(fi) && !fi.startsWith('//') && !fi.startsWith('/')) {
                                // legacy relative path -> prefix with ../public/
                                src = '../public/' + fi.replace(/^\/+/, '');
                            }
                        } catch (e) { src = fi; }
                        img.src = src;
                    }
                }
                // Show temporary notification about publish state if provided
                if (data.post && typeof data.post.published !== 'undefined') {
                    const container = document.querySelector('.container');
                    if (container) {
                        const note = document.createElement('div');
                        note.className = 'admin-notice';
                        note.style = "background:#e7ffef;border-left:4px solid #66cc88;padding:12px;margin-bottom:12px;";
                        note.textContent = data.post.published ? 'Post published.' : 'Post saved as draft.';
                        container.insertBefore(note, container.firstChild);
                        setTimeout(() => note.remove(), 4500);
                    }
                }
                closeEditModal();
            } else {
                alert(data.error || 'Update failed');
            }
    });
  });
}
</script>
