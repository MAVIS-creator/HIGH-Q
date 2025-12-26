<?php
// admin/pages/testimonials.php
require_once '../includes/auth.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/csrf.php';

$pageTitle = 'Testimonials';
$pageSubtitle = 'Manage student success stories and testimonials';

requirePermission('settings'); // or create a 'testimonials' permission

$csrf = generateToken('testimonials_form');
$errors = [];
$success = [];

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action'])) {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors[] = "Invalid CSRF token.";
    } else {
        $action = $_GET['action'];
        $id = isset($_GET['id']) ? (int)$_GET['id'] : null;

        $name = trim($_POST['name'] ?? '');
        $role = trim($_POST['role_institution'] ?? '');
        $text = trim($_POST['testimonial_text'] ?? '');
        $badge = trim($_POST['outcome_badge'] ?? '');
        $order = isset($_POST['display_order']) ? (int)$_POST['display_order'] : 0;
        $active = isset($_POST['is_active']) ? 1 : 0;

        // Handle image upload
        $imagePath = null;
        if (!empty($_FILES['image']['name']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = __DIR__ . '/../../public/uploads/testimonials/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            
            if (in_array($ext, $allowed)) {
                $filename = 'testimonial_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
                $targetPath = $uploadDir . $filename;
                
                if (move_uploaded_file($_FILES['image']['tmp_name'], $targetPath)) {
                    $imagePath = 'uploads/testimonials/' . $filename;
                } else {
                    $errors[] = "Failed to upload image.";
                }
            } else {
                $errors[] = "Invalid image format. Allowed: JPG, PNG, GIF, WebP.";
            }
        }

        if ($action === 'create' && empty($errors)) {
            if (empty($name) || empty($text)) {
                $errors[] = "Name and testimonial text are required.";
            } else {
                try {
                    $stmt = $pdo->prepare("INSERT INTO testimonials (name, role_institution, testimonial_text, image_path, outcome_badge, display_order, is_active, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
                    $stmt->execute([$name, $role, $text, $imagePath, $badge, $order, $active]);
                    $success[] = "Testimonial created successfully.";
                    logAction($pdo, $_SESSION['user']['id'], 'testimonial_created', ['testimonial_id' => $pdo->lastInsertId()]);
                } catch (Exception $e) {
                    $errors[] = "Database error: " . $e->getMessage();
                }
            }
        } elseif ($action === 'edit' && $id && empty($errors)) {
            if (empty($name) || empty($text)) {
                $errors[] = "Name and testimonial text are required.";
            } else {
                try {
                    // Get existing image path if no new image uploaded
                    if (!$imagePath) {
                        $stmt = $pdo->prepare("SELECT image_path FROM testimonials WHERE id = ?");
                        $stmt->execute([$id]);
                        $existing = $stmt->fetch(PDO::FETCH_ASSOC);
                        $imagePath = $existing['image_path'] ?? null;
                    }

                    $stmt = $pdo->prepare("UPDATE testimonials SET name = ?, role_institution = ?, testimonial_text = ?, image_path = ?, outcome_badge = ?, display_order = ?, is_active = ?, updated_at = NOW() WHERE id = ?");
                    $stmt->execute([$name, $role, $text, $imagePath, $badge, $order, $active, $id]);
                    $success[] = "Testimonial updated successfully.";
                    logAction($pdo, $_SESSION['user']['id'], 'testimonial_updated', ['testimonial_id' => $id]);
                } catch (Exception $e) {
                    $errors[] = "Database error: " . $e->getMessage();
                }
            }
        } elseif ($action === 'delete' && $id) {
            try {
                // Delete image file if exists
                $stmt = $pdo->prepare("SELECT image_path FROM testimonials WHERE id = ?");
                $stmt->execute([$id]);
                $t = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($t && $t['image_path']) {
                    $filePath = __DIR__ . '/../../public/' . $t['image_path'];
                    if (file_exists($filePath)) {
                        unlink($filePath);
                    }
                }

                $stmt = $pdo->prepare("DELETE FROM testimonials WHERE id = ?");
                $stmt->execute([$id]);
                $success[] = "Testimonial deleted successfully.";
                logAction($pdo, $_SESSION['user']['id'], 'testimonial_deleted', ['testimonial_id' => $id]);
            } catch (Exception $e) {
                $errors[] = "Database error: " . $e->getMessage();
            }
        }
    }
}

// Fetch all testimonials
try {
    $testimonials = $pdo->query("SELECT * FROM testimonials ORDER BY display_order ASC, created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $testimonials = [];
    $errors[] = "Could not fetch testimonials: " . $e->getMessage();
}

$pageCss = '<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">';
?>

<?php
$__hqStandalone = (basename($_SERVER['SCRIPT_NAME'] ?? '') === 'testimonials.php');
if ($__hqStandalone) {
  require_once '../includes/header.php';
  require_once '../includes/sidebar.php';
}
?>

<div class="admin-page-content">
<div class="testimonials-page">
    <!-- Hero Header -->
    <div class="page-hero">
        <div class="page-hero-content">
            <div class="page-hero-text">
                <span class="page-hero-badge">Success Stories</span>
                <h1 class="page-hero-title">Testimonials Management</h1>
                <p class="page-hero-subtitle">Manage student testimonials and success stories for the Wall of Fame</p>
            </div>
            <div class="page-hero-actions">
                <button id="newTestimonialBtn" class="btn-primary-hero">
                    <i class="bx bx-plus"></i> Add Testimonial
                </button>
            </div>
        </div>
    </div>

    <?php if (!empty($success) || !empty($errors)): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            <?php if (!empty($success)): ?>
            Swal.fire({
                icon: 'success',
                title: 'Success',
                html: '<?php echo implode("<br>", array_map("htmlspecialchars", $success)); ?>',
                confirmButtonColor: '#ffd600'
            });
            <?php endif; ?>
            <?php if (!empty($errors)): ?>
            Swal.fire({
                icon: 'error',
                title: 'Error',
                html: '<?php echo implode("<br>", array_map("htmlspecialchars", $errors)); ?>',
                confirmButtonColor: '#d33'
            });
            <?php endif; ?>
        });
    </script>
    <?php endif; ?>

    <!-- Testimonials Grid -->
    <div class="content-card" style="margin-top: 24px;">
        <div class="testimonials-grid">
            <?php if (empty($testimonials)): ?>
                <p style="text-align: center; color: #666; padding: 40px;">No testimonials yet. Add your first success story!</p>
            <?php else: ?>
                <?php foreach ($testimonials as $t): ?>
                <div class="testimonial-card <?= $t['is_active'] ? '' : 'inactive' ?>">
                    <?php if ($t['image_path']): ?>
                        <div class="testimonial-image">
                            <img src="<?= htmlspecialchars(app_url($t['image_path'])) ?>" alt="<?= htmlspecialchars($t['name']) ?>">
                        </div>
                    <?php endif; ?>
                    
                    <div class="testimonial-content">
                        <?php if ($t['outcome_badge']): ?>
                            <span class="badge-outcome"><?= htmlspecialchars($t['outcome_badge']) ?></span>
                        <?php endif; ?>
                        
                        <h4><?= htmlspecialchars($t['name']) ?></h4>
                        
                        <?php if ($t['role_institution']): ?>
                            <p class="role"><?= htmlspecialchars($t['role_institution']) ?></p>
                        <?php endif; ?>
                        
                        <p class="text"><?= htmlspecialchars($t['testimonial_text']) ?></p>
                        
                        <div class="meta">
                            <span class="order">Order: <?= $t['display_order'] ?></span>
                            <span class="status <?= $t['is_active'] ? 'active' : 'inactive' ?>">
                                <?= $t['is_active'] ? 'Active' : 'Inactive' ?>
                            </span>
                        </div>
                    </div>
                    
                    <div class="testimonial-actions">
                        <button class="btn-edit" data-id="<?= $t['id'] ?>"
                                data-name="<?= htmlspecialchars($t['name']) ?>"
                                data-role="<?= htmlspecialchars($t['role_institution']) ?>"
                                data-text="<?= htmlspecialchars($t['testimonial_text']) ?>"
                                data-badge="<?= htmlspecialchars($t['outcome_badge']) ?>"
                                data-order="<?= $t['display_order'] ?>"
                                data-active="<?= $t['is_active'] ?>"
                                data-image="<?= htmlspecialchars($t['image_path']) ?>">
                            <i class="bx bx-edit"></i> Edit
                        </button>
                        <form method="post" action="index.php?pages=testimonials&action=delete&id=<?= $t['id'] ?>" style="display: inline;" class="delete-form">
                            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                            <button type="submit" class="btn-delete">
                                <i class="bx bx-trash"></i> Delete
                            </button>
                        </form>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal for Create/Edit -->
<div id="testimonialModal" class="modal">
    <div class="modal-content">
        <span class="modal-close" id="closeModal">&times;</span>
        <h3 id="modalTitle">Add Testimonial</h3>
        
        <form id="testimonialForm" method="post" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
            
            <div class="form-group">
                <label>Student Name *</label>
                <input type="text" name="name" id="testimonialName" required>
            </div>
            
            <div class="form-group">
                <label>Role / Institution</label>
                <input type="text" name="role_institution" id="testimonialRole" placeholder="e.g., LAUTECH Engineering Student">
            </div>
            
            <div class="form-group">
                <label>Testimonial Text *</label>
                <textarea name="testimonial_text" id="testimonialText" rows="5" required></textarea>
            </div>
            
            <div class="form-group">
                <label>Outcome Badge</label>
                <input type="text" name="outcome_badge" id="testimonialBadge" placeholder="e.g., 305 JAMB Score, Tech Job Placement">
            </div>
            
            <div class="form-group">
                <label>Student Photo (Optional)</label>
                <input type="file" name="image" id="testimonialImage" accept="image/*">
                <small style="color: #666; display: block; margin-top: 4px;">Recommended: Square image, max 2MB</small>
                <div id="currentImage" style="margin-top: 8px;"></div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label>Display Order</label>
                    <input type="number" name="display_order" id="testimonialOrder" value="0" min="0">
                </div>
                
                <div class="form-group">
                    <label style="display: flex; align-items: center; gap: 8px;">
                        <input type="checkbox" name="is_active" id="testimonialActive" checked>
                        Active
                    </label>
                </div>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn-primary">Save Testimonial</button>
                <button type="button" class="btn-secondary" onclick="closeTestimonialModal()">Cancel</button>
            </div>
        </form>
    </div>
</div>

<div id="modalOverlay"></div>
</div>

<style>
.testimonials-page {
    width: 100%;
    max-width: 100%;
}

.page-hero {
    background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%);
    padding: 2rem 2.5rem;
    margin: -24px -32px 24px -32px;
    border-radius: 0;
}

.page-hero-content {
    max-width: 1400px;
    margin: 0 auto;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 1rem;
}

.page-hero-badge {
    display: inline-block;
    font-size: 0.7rem;
    text-transform: uppercase;
    letter-spacing: 0.15em;
    color: rgba(0,0,0,0.6);
    margin-bottom: 0.5rem;
}

.page-hero-title {
    font-size: 2rem;
    font-weight: 700;
    color: #1e293b;
    margin: 0;
}

.page-hero-subtitle {
    font-size: 1rem;
    color: rgba(0,0,0,0.7);
    margin: 0.5rem 0 0 0;
}

.btn-primary-hero {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1.5rem;
    background: #1e293b;
    color: #fff;
    border: none;
    border-radius: 0.5rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
}

.btn-primary-hero:hover {
    background: #0f172a;
    transform: translateY(-2px);
}

.content-card {
    background: white;
    border-radius: 12px;
    padding: 24px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
}

.testimonials-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: 20px;
}

.testimonial-card {
    background: #fff;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    padding: 20px;
    transition: all 0.2s;
}

.testimonial-card:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    border-color: #ffd600;
}

.testimonial-card.inactive {
    opacity: 0.6;
    background: #f9fafb;
}

.testimonial-image {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    overflow: hidden;
    margin-bottom: 12px;
}

.testimonial-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.badge-outcome {
    display: inline-block;
    background: #ffd600;
    color: #0b1a2c;
    font-weight: 700;
    padding: 4px 10px;
    border-radius: 999px;
    font-size: 0.75rem;
    margin-bottom: 8px;
}

.testimonial-content h4 {
    margin: 0 0 4px;
    font-size: 1.1rem;
    color: #111;
}

.testimonial-content .role {
    font-size: 0.85rem;
    color: #666;
    margin: 0 0 12px;
}

.testimonial-content .text {
    font-size: 0.95rem;
    line-height: 1.6;
    color: #333;
    margin: 0 0 12px;
}

.testimonial-content .meta {
    display: flex;
    gap: 12px;
    font-size: 0.85rem;
}

.testimonial-content .order {
    color: #666;
}

.testimonial-content .status.active {
    color: #059669;
    font-weight: 600;
}

.testimonial-content .status.inactive {
    color: #dc2626;
    font-weight: 600;
}

.testimonial-actions {
    display: flex;
    gap: 8px;
    margin-top: 12px;
    padding-top: 12px;
    border-top: 1px solid #e5e7eb;
}

.btn-edit, .btn-delete {
    flex: 1;
    padding: 8px 12px;
    border: none;
    border-radius: 6px;
    font-size: 0.9rem;
    cursor: pointer;
    transition: all 0.2s;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 4px;
}

.btn-edit {
    background: #3b82f6;
    color: white;
}

.btn-edit:hover {
    background: #2563eb;
}

.btn-delete {
    background: #ef4444;
    color: white;
}

.btn-delete:hover {
    background: #dc2626;
}

/* Modal Styles */
.modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.4);
    overflow-y: auto;
}

.modal.open {
    display: flex;
    align-items: center;
    justify-content: center;
}

.modal-content {
    background-color: #fff;
    margin: 20px;
    padding: 30px;
    border-radius: 12px;
    width: 90%;
    max-width: 600px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.2);
}

.modal-close {
    color: #aaa;
    float: right;
    font-size: 28px;
    font-weight: bold;
    cursor: pointer;
    line-height: 20px;
}

.modal-close:hover {
    color: #000;
}

.modal-content h3 {
    margin-top: 0;
    color: #111;
}

.form-group {
    margin-bottom: 16px;
}

.form-group label {
    display: block;
    margin-bottom: 6px;
    font-weight: 600;
    color: #333;
}

.form-group input[type="text"],
.form-group input[type="number"],
.form-group textarea {
    width: 100%;
    padding: 10px;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    font-size: 0.95rem;
}

.form-group input[type="file"] {
    width: 100%;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
}

.form-actions {
    display: flex;
    gap: 12px;
    margin-top: 24px;
}

.btn-primary, .btn-secondary {
    padding: 10px 20px;
    border: none;
    border-radius: 6px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
}

.btn-primary {
    background: #ffd600;
    color: #111;
}

.btn-primary:hover {
    background: #ffed4e;
}

.btn-secondary {
    background: #6b7280;
    color: white;
}

.btn-secondary:hover {
    background: #4b5563;
}

@media (max-width: 768px) {
    .testimonials-grid {
        grid-template-columns: 1fr;
    }
    
    .form-row {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
const modal = document.getElementById('testimonialModal');
const overlay = document.getElementById('modalOverlay');
const newBtn = document.getElementById('newTestimonialBtn');
const closeBtn = document.getElementById('closeModal');
const form = document.getElementById('testimonialForm');
const modalTitle = document.getElementById('modalTitle');

function openTestimonialModal(mode = 'create', data = {}) {
    modalTitle.textContent = mode === 'create' ? 'Add Testimonial' : 'Edit Testimonial';
    
    if (mode === 'create') {
        form.action = 'index.php?pages=testimonials&action=create';
        form.reset();
        document.getElementById('currentImage').innerHTML = '';
    } else {
        form.action = `index.php?pages=testimonials&action=edit&id=${data.id}`;
        document.getElementById('testimonialName').value = data.name || '';
        document.getElementById('testimonialRole').value = data.role || '';
        document.getElementById('testimonialText').value = data.text || '';
        document.getElementById('testimonialBadge').value = data.badge || '';
        document.getElementById('testimonialOrder').value = data.order || 0;
        document.getElementById('testimonialActive').checked = data.active == 1;
        
        if (data.image) {
            document.getElementById('currentImage').innerHTML = `<img src="<?= app_url('') ?>${data.image}" style="width: 80px; height: 80px; border-radius: 50%; object-fit: cover;"><br><small>Current image (leave empty to keep)</small>`;
        } else {
            document.getElementById('currentImage').innerHTML = '';
        }
    }
    
    modal.classList.add('open');
    overlay.classList.add('open');
}

function closeTestimonialModal() {
    modal.classList.remove('open');
    overlay.classList.remove('open');
}

newBtn.addEventListener('click', () => openTestimonialModal('create'));
closeBtn.addEventListener('click', closeTestimonialModal);
overlay.addEventListener('click', closeTestimonialModal);

document.querySelectorAll('.btn-edit').forEach(btn => {
    btn.addEventListener('click', () => {
        openTestimonialModal('edit', {
            id: btn.dataset.id,
            name: btn.dataset.name,
            role: btn.dataset.role,
            text: btn.dataset.text,
            badge: btn.dataset.badge,
            order: btn.dataset.order,
            active: btn.dataset.active,
            image: btn.dataset.image
        });
    });
});

document.querySelectorAll('.delete-form').forEach(form => {
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        Swal.fire({
            title: 'Delete Testimonial?',
            text: 'This action cannot be undone.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6b7280',
            confirmButtonText: 'Yes, delete it'
        }).then((result) => {
            if (result.isConfirmed) {
                form.submit();
            }
        });
    });
});
</script>

<?php
if ($__hqStandalone) {
  require_once '../includes/footer.php';
}
?>
