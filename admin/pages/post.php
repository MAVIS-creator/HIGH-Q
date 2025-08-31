<?php
// admin/pages/posts.php
require '../includes/auth.php';
require '../includes/db.php';
require '../includes/csrf.php';
require '../includes/functions.php';

// Only Admin / Sub-Admin / Moderator
requireRole(['admin','sub-admin','moderator']);

$csrf     = generateToken();
$errors   = [];
$success  = [];

// Make sure uploads folder exists
$uploadDir = __DIR__ . '/../../public/uploads/posts/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

// Handle Create / Edit / Delete / Toggle Publish
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action'])) {
    if (!verifyToken($_POST['csrf_token'] ?? '')) {
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
            $slug = preg_replace('/[^a-z0-9]+/','-',strtolower($title));
            $slug = trim($slug,'-');
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
                $stmt = $pdo->prepare("
                  INSERT INTO posts
                    (title, slug, excerpt, content, category_id, tags, featured_image,
                     status, created_by)
                  VALUES (?,?,?,?,?,?,?,?,?)
                ");
                $stmt->execute([
                  $title, $slug, $excerpt, $content,
                  $category_id ?: null,
                  $tags, $imgPath ?: null,
                  $status,
                  $_SESSION['user']['id']
                ]);
                logAction($pdo, $_SESSION['user']['id'], 'post_created', ['slug'=>$slug]);
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
                $stmt = $pdo->prepare("
                  UPDATE posts SET
                    title=?, slug=?, excerpt=?, content=?, category_id=?, tags=?,
                    featured_image=?, status=?, updated_at=NOW()
                  WHERE id=?
                ");
                $stmt->execute([
                  $title, $slug, $excerpt, $content,
                  $category_id ?: null,
                  $tags,
                  $imgPath ?: null,
                  $status,
                  $id
                ]);
                logAction($pdo, $_SESSION['user']['id'], 'post_updated', ['post_id'=>$id]);
                $success[] = "Article '{$title}' updated.";
            }

            // DELETE
            if ($act === 'delete' && $id) {
                $pdo->prepare("DELETE FROM posts WHERE id=?")->execute([$id]);
                logAction($pdo, $_SESSION['user']['id'], 'post_deleted', ['post_id'=>$id]);
                $success[] = "Article deleted.";
            }

            // TOGGLE PUBLISH
            if ($act === 'toggle' && $id) {
                $stmt = $pdo->prepare("UPDATE posts SET status = IF(status='draft','published','draft') WHERE id=?");
                $stmt->execute([$id]);
                logAction($pdo, $_SESSION['user']['id'], 'post_toggled', ['post_id'=>$id]);
                $success[] = "Article status toggled.";
            }
        }

        header("Location: index.php?page=posts");
        exit;
    }
}

// Search filter
$q = trim($_GET['q'] ?? '');

// Fetch categories
$categories = $pdo->query("SELECT id,name FROM categories ORDER BY name")->fetchAll();

// Fetch posts with optional search
$sql  = "SELECT p.*, u.name AS author, c.name AS category
         FROM posts p
         LEFT JOIN users u ON u.id = p.created_by
         LEFT JOIN categories c ON c.id = p.category_id
         WHERE p.title LIKE :q
         ORDER BY p.created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute([':q'=>"%{$q}%"]);
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
          <input type="hidden" name="page" value="posts">
          <input type="text" name="q" placeholder="Search by title..." value="<?=htmlspecialchars($q)?>">
          <td>
  <?= htmlspecialchars($p['title']) ?><br>
  <a href="post_edit.php?id=<?= $p['id'] ?>" 
     class="edit-link" 
     data-id="<?= $p['id'] ?>">Edit Post</a>
</td>

        </form>
        <button id="newPostBtn" class="btn-approve">
          <i class="bx bx-plus"></i> Add Article
        </button>
      </div>
    </div>

    <?php if($posts): ?>
      <table class="posts-table">
        <thead>
          <tr>
            <th>Image</th>
            <th>Title</th>
            <th>Category</th>
            <th>Tags</th>
            <th>Status</th>
            <th>Date</th>
            <th>Actions</th>
          </tr>
        </thead>
       <tbody>
<?php foreach($posts as $p): ?>
<tr id="post-row-<?= $p['id'] ?>">
  <td>
    <?php if($p['featured_image']): ?>
      <img src="../public/<?= htmlspecialchars($p['featured_image']) ?>" class="thumb">
    <?php else: ?>
      —
    <?php endif; ?>
  </td>
  <td>
    <?= htmlspecialchars($p['title']) ?><br>
    <a href="post_edit.php?id=<?= $p['id'] ?>" 
       class="edit-link" 
       data-id="<?= $p['id'] ?>">Edit Post</a>
  </td>
  <td><?= htmlspecialchars($p['category'] ?? 'Uncategorized') ?></td>
  <td><?= htmlspecialchars($p['tags']) ?></td>
  <td>
    <span class="status-badge <?= $p['status']=='published'?'status-active':'status-pending' ?>">
      <?= ucfirst($p['status']) ?>
    </span>
  </td>
  <td><?= date('d/m/Y', strtotime($p['created_at'])) ?></td>
  <td>
    <!-- your toggle/delete buttons here -->
  </td>
</tr>
<?php endforeach; ?>
</tbody>

      </table>

    <?php else: ?>
      <p class="no-data">No articles found matching your search.</p>
    <?php endif; ?>
  </div>

  <!-- Post Modal -->
  <div class="modal" id="postModal">
    <div class="modal-content">
      <span class="modal-close" id="postModalClose"><i class="bx bx-x"></i></span>
      <h3 id="postModalTitle">New Article</h3>
      <form id="postForm" method="post" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?=$csrf?>">

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
            <?php foreach($categories as $c): ?>
              <option value="<?=$c['id']?>"><?=htmlspecialchars($c['name'])?></option>
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
<div id="modalOverlay"></div>

  <?php include '../includes/footer.php'; ?>

  <script>
  // Modal logic
  const postModal    = document.getElementById('postModal');
  const overlay      = document.getElementById('modalOverlay');
  const closePostBtn = document.getElementById('postModalClose');
  const newPostBtn   = document.getElementById('newPostBtn');
  const postForm     = document.getElementById('postForm');
  const modalTitle   = document.getElementById('postModalTitle');
  const fields       = {
    title:    document.getElementById('pTitle'),
    slug:     document.getElementById('pSlug'),
    excerpt:  document.getElementById('pExcerpt'),
    content:  document.getElementById('pContent'),
    category: document.getElementById('pCategory'),
    tags:     document.getElementById('pTags'),
    image:    document.getElementById('pImage'),
    publish:  document.getElementById('pPublish')
  };

  function openPostModal(mode, data={}) {
    overlay.classList.add('open');
    postModal.classList.add('open');
    if (mode === 'edit') {
      modalTitle.textContent = 'Edit Article';
      postForm.action = `index.php?page=posts&action=edit&id=${data.id}`;
      fields.title.value    = data.title;
      fields.slug.value     = data.slug;
      fields.excerpt.value  = data.excerpt;
      fields.content.value  = data.content;
      fields.category.value = data.category_id || '';
      fields.tags.value     = data.tags;
      fields.publish.checked= data.status==='published';
    } else {
      modalTitle.textContent = 'New Article';
      postForm.action = 'index.php?page=posts&action=create';
      Object.values(fields).forEach(f => {
        if (f.type === 'checkbox') f.checked = false;
        else if (f.tagName === 'INPUT' || f.tagName === 'TEXTAREA' || f.tagName === 'SELECT') f.value = '';
      });
    }
  }

  function closePostModal() {
    overlay.classList.remove('open');
    postModal.classList.remove('open');
  }

  newPostBtn.addEventListener('click', () => openPostModal('create'));
  closePostBtn.addEventListener('click', closePostModal);
  overlay.addEventListener('click', closePostModal);
  document.addEventListener('keydown', e => e.key==='Escape' && closePostModal());

  document.querySelectorAll('.btn-editPost').forEach(btn => {
    btn.addEventListener('click', () => {
      openPostModal('edit', {
        id:           btn.dataset.id,
        title:        btn.dataset.title,
        slug:         btn.dataset.slug,
        excerpt:      btn.dataset.excerpt,
        content:      btn.dataset.content,
        category_id:  btn.dataset.category,
        tags:         btn.dataset.tags,
        status:       btn.dataset.status
      });
    });
  });
const overlay      = document.getElementById('modalOverlay');
const editModal    = document.getElementById('editPostModal');
const editContent  = document.getElementById('editPostModalContent');

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
        bindAjaxForm(); // bind submit after content loads
      });
  });
});

// Close modal
overlay.addEventListener('click', closeEditModal);
function closeEditModal() {
  overlay.classList.remove('open');
  editModal.classList.remove('open');
}

// Bind AJAX submit
function bindAjaxForm() {
  const form = document.getElementById('ajaxEditPostForm');
  form.addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(form);
    fetch(`index.php?page=posts&action=edit&id=${formData.get('id')}`, {
      method: 'POST',
      body: formData
    })
    .then(res => res.text())
    .then(() => {
      closeEditModal();
      // Optionally refresh just the row or reload page
      location.reload();
    });
  });
}
</script>

