<?php
// admin/pages/posts.php
require_once '../includes/auth.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/csrf.php';

$pageTitle = 'News & Blog';
$pageSubtitle = 'Create and manage news articles and blog posts';

// Only Admin / Sub-Admin / Moderator
requirePermission('roles'); // where 'roles' matches the menu slug

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

// Handle Create / Edit / Delete / Toggle Publish
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action'])) {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors[] = "Invalid CSRF token.";
    } else {
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
                $imgPath = "uploads/posts/{$filename}";
            }
        }

        // Validation
        if (!$title || !$content) {
            $errors[] = "Title and content are required.";
        }

        if (empty($errors)) {
                        // CREATE
                        if ($act === 'create') {
                                $stmt = $pdo->prepare("\n                  INSERT INTO posts\n                    (title, slug, excerpt, content, category_id, tags, featured_image,\n                     status, author_id)\n                  VALUES (?,?,?,?,?,?,?,?,?)\n                ");
                                $stmt->execute([
                                        $title,
                                        $slug,
                                        $excerpt,
                                        $content,
                                        $category_id ?: null,
                                        $tags,
                                        $imgPath ?: null,
                                        $status,
                                        $_SESSION['user']['id']
                                ]);
                                logAction($pdo, $_SESSION['user']['id'], 'post_created', ['slug' => $slug]);
                                $success[] = "Article '{$title}' created.";
                        }

            // EDIT
                        if ($act === 'edit' && $id) {
                // If no new image, keep existing
                if (!$imgPath) {
                    $old = $pdo->prepare("SELECT featured_image FROM posts WHERE id=?");
                    $old->execute([$id]);
                    $imgPath = $old->fetchColumn();
                }
                                $stmt = $pdo->prepare("\n                  UPDATE posts SET\n                    title=?, slug=?, excerpt=?, content=?, category_id=?, tags=?,\n                    featured_image=?, status=?, updated_at=NOW()\n                  WHERE id=?\n                ");
                $stmt->execute([
                    $title,
                    $slug,
                    $excerpt,
                    $content,
                    $category_id ?: null,
                    $tags,
                    $imgPath ?: null,
                    $status,
                    $id
                ]);
                logAction($pdo, $_SESSION['user']['id'], 'post_updated', ['post_id' => $id]);
                $success[] = "Article '{$title}' updated.";
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

    header("Location: index.php?pages=posts");
        exit;
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

$sql  = "SELECT p.*, u.name AS author, p.category AS category
         FROM posts p
         LEFT JOIN users u ON u.id = p.author_id
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
            <div class="posts-grid">
                <?php foreach ($posts as $p): ?>
                    <div class="post-card" id="post-row-<?= $p['id'] ?>">
                        <?php if ($p['featured_image']): ?>
                            <img src="../public/<?= htmlspecialchars($p['featured_image']) ?>" class="thumb">
                        <?php endif; ?>
                        <div class="meta">
                            <strong><?= htmlspecialchars($p['title']) ?></strong>
                            <div class="info"><?= htmlspecialchars($p['category'] ?? 'Uncategorized') ?> • <?= htmlspecialchars($p['author'] ?? '') ?></div>
                        </div>
                        <div class="excerpt"><?= nl2br(htmlspecialchars($p['excerpt'] ?: '')) ?></div>
                        <div class="actions">
                            <a href="post_edit.php?id=<?= $p['id'] ?>" class="btn">Edit</a>
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
            <form id="postForm" method="post" enctype="multipart/form-data">
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
                    <input type="file" name="featured_image" id="pImage" accept="image/*">
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
const overlay     = document.getElementById('modalOverlay');
const editModal   = document.getElementById('editPostModal');
const editContent = document.getElementById('editPostModalContent');

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
                        img.src = `../public/${data.post.featured_image}`;
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
