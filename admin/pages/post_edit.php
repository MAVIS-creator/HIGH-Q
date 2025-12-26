<?php
require __DIR__ . '/../includes/auth.php';
require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/csrf.php';
requirePermission('post');

$csrf = generateToken();
$id   = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare("SELECT * FROM posts WHERE id=?");
$stmt->execute([$id]);
$post = $stmt->fetch();

if (!$post) {
    echo "<p>Post not found.</p>";
    exit;
}
// Fetch categories for the select (some installs may not have the table)
try {
  $catsStmt = $pdo->query("SELECT id,name FROM categories ORDER BY name");
  $categories = $catsStmt->fetchAll();
} catch (Throwable $e) {
  $categories = [];
}
?>
<div class="modal-content-inner">
  <h3>Edit Post: <?= htmlspecialchars($post['title']) ?></h3>
  <form id="ajaxEditPostForm" enctype="multipart/form-data">
    <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
    <input type="hidden" name="id" value="<?= $post['id'] ?>">
    <div class="form-row">
      <label>Title</label>
      <input type="text" name="title" value="<?= htmlspecialchars($post['title']) ?>" required>
    </div>
    <div class="form-row">
      <label>Slug</label>
      <input type="text" name="slug" value="<?= htmlspecialchars($post['slug']) ?>">
    </div>
    <div class="form-row">
      <label>Excerpt</label>
      <input type="text" name="excerpt" value="<?= htmlspecialchars($post['excerpt']) ?>">
    </div>
    <div class="form-row">
      <label>Content</label>
      <textarea name="content" rows="6"><?= htmlspecialchars($post['content']) ?></textarea>
    </div>
    <div class="form-row">
      <label>Tags</label>
      <input type="text" name="tags" value="<?= htmlspecialchars($post['tags']) ?>">
    </div>
    <div class="form-row">
      <label>Category</label>
      <select name="category_id">
        <option value="">Uncategorized</option>
        <?php foreach ($categories as $c): ?>
          <option value="<?= $c['id'] ?>" <?= (isset($post['category_id']) && $post['category_id'] == $c['id']) ? 'selected' : '' ?>><?= htmlspecialchars($c['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="form-row">
      <label>Featured Image</label>
      <div class="file-input-wrap">
        <input type="file" name="featured_image" id="editImage" accept="image/*">
        <button type="button" class="btn" onclick="document.getElementById('editImage').click()">Choose File</button>
        <span id="editImageName" class="file-name">No file chosen</span>
      </div>
      <?php if (!empty($post['featured_image'])): ?>
        <?php
          $fi = $post['featured_image'];
          if (preg_match('#^https?://#i', $fi) || strpos($fi, '//') === 0 || strpos($fi, '/') === 0) {
            $imgSrc = $fi;
          } else {
            $imgSrc = '../public/' . ltrim($fi, '/');
          }
        ?>
        <img src="<?= htmlspecialchars($imgSrc) ?>" class="thumb" id="existingThumb" style="margin-top:0.5rem;max-width:180px;border-radius:6px;">
      <?php endif; ?>
    </div>
    <div class="form-row">
      <label><input type="checkbox" name="publish" <?= $post['status']==='published'?'checked':''; ?>> Publish</label>
    </div>
    <div class="form-actions">
      <button type="button" class="btn" onclick="closeEditModal()">Cancel</button>
      <button type="submit" class="btn-approve">Update Post</button>
    </div>
  </form>
</div>
<script>
// edit-link clicks are handled centrally from the posts listing page. This file only contains the modal content.
</script>
<script>
  // Preview chosen featured image file and update filename display
  (function(){
    var fileInput = document.getElementById('editImage');
    var fileNameDisplay = document.getElementById('editImageName');
    if (fileInput) {
      fileInput.addEventListener('change', function(){
        var f = this.files && this.files[0];
        if (!f) {
          fileNameDisplay.textContent = 'No file chosen';
          return;
        }
        fileNameDisplay.textContent = f.name;
        var reader = new FileReader();
        reader.onload = function(e){
          var img = document.getElementById('existingThumb');
          if (!img) {
            img = document.createElement('img');
            img.className = 'thumb';
            img.id = 'existingThumb';
            img.style.cssText = 'margin-top:0.5rem;max-width:180px;border-radius:6px;';
            fileInput.closest('.form-row').appendChild(img);
          }
          img.src = e.target.result;
        };
        reader.readAsDataURL(f);
      });
    }
  })();
</script>
