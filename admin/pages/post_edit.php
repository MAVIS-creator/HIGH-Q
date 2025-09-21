<?php
require '../includes/auth.php';
require '../includes/db.php';
require '../includes/csrf.php';
requirePermission('posts');

$csrf = generateToken();
$id   = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare("SELECT * FROM posts WHERE id=?");
$stmt->execute([$id]);
$post = $stmt->fetch();

if (!$post) {
    echo "<p>Post not found.</p>";
    exit;
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
      <label>Featured Image</label>
      <input type="file" name="featured_image" accept="image/*">
      <?php if ($post['featured_image']): ?>
        <img src="../public/<?= htmlspecialchars($post['featured_image']) ?>" class="thumb" style="margin-top:0.5rem;">
      <?php endif; ?>
    </div>
    <div class="form-row">
      <label><input type="checkbox" name="publish" <?= $post['status']==='published'?'checked':''; ?>> Publish</label>
    </div>
    <div class="form-actions">
      <button type="submit" class="btn-approve">Update Post</button>
    </div>
  </form>
</div>
<script>
document.querySelectorAll('.edit-link').forEach(link => {
  link.addEventListener('click', function(e) {
    e.preventDefault();
    const id = this.dataset.id;
    fetch(`post_edit.php?id=${id}`)
      .then(res => res.text())
      .then(html => {
        document.getElementById('editPostModalContent').innerHTML = html;
        document.getElementById('modalOverlay').classList.add('open');
        document.getElementById('editPostModal').classList.add('open');
      });
  });
});

document.getElementById('modalOverlay').addEventListener('click', closeEditModal);
function closeEditModal() {
  document.getElementById('modalOverlay').classList.remove('open');
  document.getElementById('editPostModal').classList.remove('open');
}
</script>
