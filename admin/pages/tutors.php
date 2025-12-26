<?php
// Admin Tutors Management Page - Modern UI
$pageTitle = 'Tutors Management';
$pageSubtitle = 'Manage teaching staff and their information';

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
requirePermission('tutors');
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';

// Pagination setup
$perPage = 9; // 3 cards x 3 rows per page
$page = isset($_GET['pg']) ? max(1, (int)$_GET['pg']) : 1;
$offset = ($page - 1) * $perPage;

// Fetch total count and tutors from database
$tutors = [];
$totalTutors = 0;
try {
    $totalTutors = (int)$pdo->query("SELECT COUNT(*) FROM tutors")->fetchColumn();
    $stmt = $pdo->prepare("SELECT * FROM tutors ORDER BY created_at DESC LIMIT :limit OFFSET :offset");
    $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $tutors = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $tutors = [];
}
$totalPages = ceil($totalTutors / $perPage);

// Get subjects for dropdown
$subjects = [];
try {
    $stmt = $pdo->query("SELECT DISTINCT subject FROM tutors WHERE subject IS NOT NULL AND subject != ''");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $subjects[] = $row['subject'];
    }
} catch (Exception $e) {}
?>
<div class="admin-page-content">
    <!-- Hero Header -->
    <div class="page-hero">
        <div class="page-hero-content">
            <div class="page-hero-text">
                <span class="page-hero-badge">Teaching Staff</span>
                <h1 class="page-hero-title"><?= htmlspecialchars($pageTitle) ?></h1>
                <p class="page-hero-subtitle"><?= htmlspecialchars($pageSubtitle) ?></p>
            </div>
            <button onclick="openAddModal()" class="btn-primary-hero">
                <i class='bx bx-plus'></i>
                Add Tutor
            </button>
        </div>
    </div>

    <!-- Search and Filter -->
    <div class="content-card">
        <div class="card-header">
            <h2 class="card-title">Search Tutors</h2>
        </div>
        <div class="card-body">
            <div class="search-bar">
                <i class='bx bx-search'></i>
                <input type="text" id="searchInput" placeholder="Search by name, title, or subject..." onkeyup="filterTutors()">
            </div>
        </div>
    </div>

    <!-- Tutors Grid -->
    <div class="tutors-grid" id="tutorsGrid">
        <?php if (empty($tutors)): ?>
            <div class="empty-state">
                <i class='bx bx-user-voice'></i>
                <h3>No tutors found</h3>
                <p>Add your first tutor to get started</p>
                <button onclick="openAddModal()" class="btn-primary">
                    <i class='bx bx-plus'></i> Add Tutor
                </button>
            </div>
        <?php else: ?>
            <?php foreach ($tutors as $tutor): ?>
                <div class="tutor-card" data-name="<?= strtolower(htmlspecialchars($tutor['name'] ?? '')) ?>" data-subject="<?= strtolower(htmlspecialchars($tutor['subject'] ?? '')) ?>">
                    <div class="tutor-avatar">
                        <?php if (!empty($tutor['photo'])): ?>
                            <?php 
                            $photoPath = $tutor['photo'];
                            // Construct proper public URL
                            if (preg_match('#^https?://#', $photoPath)) {
                                // Already a full URL
                            } elseif (strpos($photoPath, 'uploads/tutors/') !== false) {
                                // Already correct format
                                $photoPath = '../../public/' . $photoPath;
                            } else {
                                // Just filename
                                $photoPath = '../../public/uploads/tutors/' . basename($photoPath);
                            }
                            ?>
                            <img src="<?= htmlspecialchars($photoPath) ?>" alt="<?= htmlspecialchars($tutor['name']) ?>" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex'">
                            <span class="avatar-initial" style="display:none"><?= strtoupper(substr($tutor['name'] ?? 'T', 0, 1)) ?></span>
                        <?php else: ?>
                            <span class="avatar-initial"><?= strtoupper(substr($tutor['name'] ?? 'T', 0, 1)) ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="tutor-info">
                        <div class="tutor-header">
                            <h3 class="tutor-name"><?= htmlspecialchars($tutor['name'] ?? 'Unknown') ?></h3>
                            <span class="status-badge <?= ($tutor['is_active'] ?? 1) ? 'active' : 'inactive' ?>">
                                <?= ($tutor['is_active'] ?? 1) ? 'Active' : 'Inactive' ?>
                            </span>
                        </div>
                        <p class="tutor-title"><?= htmlspecialchars($tutor['title'] ?? '') ?></p>
                        
                        <?php if (!empty($tutor['email'])): ?>
                            <p class="tutor-contact"><i class='bx bx-envelope'></i> <?= htmlspecialchars($tutor['email']) ?></p>
                        <?php endif; ?>
                        
                        <?php if (!empty($tutor['phone'])): ?>
                            <p class="tutor-contact"><i class='bx bx-phone'></i> <?= htmlspecialchars($tutor['phone']) ?></p>
                        <?php endif; ?>
                        
                        <?php if (!empty($tutor['qualifications'])): ?>
                            <p class="tutor-qualification"><i class='bx bx-medal'></i> <?= htmlspecialchars($tutor['qualifications']) ?></p>
                        <?php endif; ?>
                        
                        <?php if (!empty($tutor['experience'])): ?>
                            <p class="tutor-experience"><i class='bx bx-briefcase'></i> <?= htmlspecialchars($tutor['experience']) ?> years experience</p>
                        <?php endif; ?>
                        
                        <?php if (!empty($tutor['subjects'])): ?>
                            <div class="tutor-subjects">
                                <span class="subjects-label">Subjects:</span>
                                <div class="subject-tags">
                                    <?php 
                                    $subjectList = is_string($tutor['subjects']) ? explode(',', $tutor['subjects']) : (array)$tutor['subjects'];
                                    foreach (array_slice($subjectList, 0, 3) as $subj): ?>
                                        <span class="subject-tag"><?= htmlspecialchars(trim($subj)) ?></span>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($tutor['experience'])): ?>
                            <p class="tutor-experience"><?= htmlspecialchars($tutor['experience']) ?> years of experience</p>
                        <?php endif; ?>
                        
                        <?php if (!empty($tutor['bio'])): ?>
                            <p class="tutor-bio"><?= htmlspecialchars(substr($tutor['bio'], 0, 120)) ?>...</p>
                        <?php endif; ?>
                    </div>
                    <div class="tutor-actions">
                        <button onclick="editTutor(<?= $tutor['id'] ?>)" class="btn-icon" title="Edit">
                            <i class='bx bx-edit-alt'></i>
                        </button>
                        <button onclick="toggleFeatured(<?= $tutor['id'] ?>, <?= $tutor['is_featured'] ?? 0 ?>)" class="btn-icon <?= ($tutor['is_featured'] ?? 0) ? 'featured' : '' ?>" title="Toggle Featured">
                            <i class='bx bx-star'></i>
                        </button>
                        <button onclick="deleteTutor(<?= $tutor['id'] ?>)" class="btn-icon danger" title="Delete">
                            <i class='bx bx-trash'></i>
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    
    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
    <div class="pagination">
        <?php if ($page > 1): ?>
            <a href="?pages=tutors&pg=<?= $page - 1 ?>" class="pagination-btn"><i class='bx bx-chevron-left'></i> Previous</a>
        <?php endif; ?>
        
        <div class="pagination-numbers">
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <a href="?pages=tutors&pg=<?= $i ?>" class="pagination-num <?= $i === $page ? 'active' : '' ?>"><?= $i ?></a>
            <?php endfor; ?>
        </div>
        
        <?php if ($page < $totalPages): ?>
            <a href="?pages=tutors&pg=<?= $page + 1 ?>" class="pagination-btn">Next <i class='bx bx-chevron-right'></i></a>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>

<!-- Add/Edit Tutor Modal -->
<div id="tutorModal" class="modal-overlay" style="display: none;">
    <div class="modal-container">
        <div class="modal-header">
            <h2 id="modalTitle">Add Tutor</h2>
            <button onclick="closeModal()" class="modal-close"><i class='bx bx-x'></i></button>
        </div>
        <form id="tutorForm" class="modal-body">
            <input type="hidden" id="tutorId" name="id">
            
            <div class="form-grid">
                <div class="form-group">
                    <label for="name">Full Name *</label>
                    <input type="text" id="name" name="name" required>
                </div>
                <div class="form-group">
                    <label for="title">Title/Position</label>
                    <input type="text" id="title" name="title" placeholder="e.g., Senior Mathematics Instructor">
                </div>
            </div>
            
            <div class="form-group">
                <label for="subjects">Subjects (comma-separated)</label>
                <input type="text" id="subjects" name="subjects" placeholder="Mathematics, Physics, Chemistry">
            </div>
            
            <div class="form-grid">
                <div class="form-group">
                    <label for="experience">Years of Experience</label>
                    <input type="number" id="experience" name="experience" min="0">
                </div>
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" placeholder="tutor@example.com">
                </div>
            </div>
            
            <div class="form-grid">
                <div class="form-group">
                    <label for="phone">Phone Number</label>
                    <input type="tel" id="phone" name="phone" placeholder="+234 xxx xxx xxxx">
                </div>
                <div class="form-group">
                    <label for="qualifications">Qualifications</label>
                    <input type="text" id="qualifications" name="qualifications" placeholder="B.Sc Mathematics, M.Ed">
                </div>
            </div>
            
            <div class="form-group">
                <label>Photo</label>
                <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
                    <button type="button" onclick="document.getElementById('photoFile').click()" class="btn-secondary" style="flex: 1; min-width: 200px;">
                        <i class='bx bx-upload'></i> Upload Photo
                    </button>
                    <input type="file" id="photoFile" accept="image/*" style="display: none;">
                    <input type="url" id="photo" name="photo" placeholder="Or enter URL..." style="flex: 2; min-width: 300px;">
                </div>
            </div>
            
            <div class="form-group">
                <label for="bio">Bio/Description</label>
                <textarea id="bio" name="bio" rows="4" placeholder="Brief description about the tutor..."></textarea>
            </div>
            
            <div class="form-group checkbox-group">
                <label class="checkbox-label">
                    <input type="checkbox" id="is_active" name="is_active" checked>
                    <span>Active</span>
                </label>
                <label class="checkbox-label">
                    <input type="checkbox" id="is_featured" name="is_featured">
                    <span>Featured</span>
                </label>
            </div>
        </form>
        <div class="modal-footer">
            <button type="button" onclick="closeModal()" class="btn-secondary">Cancel</button>
            <button type="button" onclick="saveTutor()" class="btn-primary">Save Tutor</button>
        </div>
    </div>
</div>

<style>
/* Tutors Page Specific Styles */
.admin-page-content {
    padding: 24px 32px 48px;
}

.page-hero {
    background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%);
    padding: 2rem 2.5rem;
    margin: -24px -32px 24px -32px;
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
    border-radius: 0.75rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
}

.btn-primary-hero:hover {
    background: #0f172a;
    transform: translateY(-1px);
}

.content-card {
    background: #fff;
    border-radius: 1rem;
    border: 1px solid #e2e8f0;
    margin-bottom: 1.5rem;
    overflow: hidden;
}

.card-header {
    padding: 1rem 1.5rem;
    border-bottom: 1px solid #e2e8f0;
    background: #f8fafc;
}

.card-title {
    font-size: 1rem;
    font-weight: 600;
    color: #1e293b;
    margin: 0;
}

.card-body {
    padding: 1.5rem;
}

.search-bar {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    background: #f1f5f9;
    border-radius: 0.75rem;
    padding: 0.75rem 1rem;
}

.search-bar i {
    color: #64748b;
    font-size: 1.25rem;
}

.search-bar input {
    flex: 1;
    border: none;
    background: transparent;
    font-size: 0.95rem;
    outline: none;
}

.tutors-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 1.5rem;
    max-width: 1400px;
    margin: 0 auto;
}

@media (max-width: 1200px) {
    .tutors-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 768px) {
    .tutors-grid {
        grid-template-columns: 1fr;
    }
}

.tutor-card {
    background: #fff;
    border-radius: 1rem;
    border: 1px solid #e2e8f0;
    padding: 1.5rem;
    display: flex;
    gap: 1rem;
    transition: all 0.2s;
}

.tutor-card:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
    border-color: #fbbf24;
}

.tutor-avatar {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    background: #fbbf24;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    overflow: hidden;
}

.tutor-avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.avatar-initial {
    font-size: 1.5rem;
    font-weight: 700;
    color: #1e293b;
}

.tutor-info {
    flex: 1;
    min-width: 0;
}

.tutor-header {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    margin-bottom: 0.25rem;
}

.tutor-name {
    font-size: 1.1rem;
    font-weight: 600;
    color: #1e293b;
    margin: 0;
}

.status-badge {
    font-size: 0.7rem;
    padding: 0.2rem 0.6rem;
    border-radius: 1rem;
    font-weight: 600;
}

.status-badge.active {
    background: #dcfce7;
    color: #166534;
}

.status-badge.inactive {
    background: #fee2e2;
    color: #991b1b;
}

.tutor-title {
    font-size: 0.85rem;
    color: #64748b;
    margin: 0 0 0.75rem 0;
}

.tutor-subjects {
    margin-bottom: 0.5rem;
}

.subjects-label {
    font-size: 0.75rem;
    color: #64748b;
    margin-right: 0.5rem;
}

.subject-tags {
    display: inline-flex;
    flex-wrap: wrap;
    gap: 0.35rem;
}

.subject-tag {
    font-size: 0.75rem;
    padding: 0.2rem 0.6rem;
    background: #f1f5f9;
    border: 1px solid #e2e8f0;
    border-radius: 1rem;
    color: #475569;
}

.tutor-experience {
    font-size: 0.85rem;
    color: #f59e0b;
    font-weight: 500;
    margin: 0.5rem 0;
    display: flex;
    align-items: center;
    gap: 0.4rem;
}

.tutor-contact {
    font-size: 0.85rem;
    color: #64748b;
    margin: 0.4rem 0;
    display: flex;
    align-items: center;
    gap: 0.4rem;
}

.tutor-contact i {
    color: #94a3b8;
    font-size: 0.95rem;
}

.tutor-qualification {
    font-size: 0.85rem;
    color: #8b5cf6;
    font-weight: 500;
    margin: 0.4rem 0;
    display: flex;
    align-items: center;
    gap: 0.4rem;
}

.tutor-qualification i {
    color: #a78bfa;
}

.tutor-bio {
    font-size: 0.85rem;
    color: #64748b;
    margin: 0.5rem 0 0 0;
    line-height: 1.5;
}

.tutor-actions {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.btn-icon {
    width: 36px;
    height: 36px;
    border-radius: 0.5rem;
    border: 1px solid #e2e8f0;
    background: #fff;
    color: #64748b;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;
}

.btn-icon:hover {
    background: #f1f5f9;
    color: #1e293b;
}

.btn-icon.danger:hover {
    background: #fee2e2;
    color: #dc2626;
    border-color: #fecaca;
}

.btn-icon.featured {
    background: #fef3c7;
    color: #f59e0b;
    border-color: #fcd34d;
}

.empty-state {
    grid-column: 1 / -1;
    text-align: center;
    padding: 4rem 2rem;
    background: #f8fafc;
    border-radius: 1rem;
    border: 2px dashed #e2e8f0;
}

.empty-state i {
    font-size: 4rem;
    color: #cbd5e1;
    margin-bottom: 1rem;
}

.empty-state h3 {
    font-size: 1.25rem;
    color: #475569;
    margin: 0 0 0.5rem 0;
}

.empty-state p {
    color: #94a3b8;
    margin: 0 0 1.5rem 0;
}

/* Modal Styles */
.modal-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1000;
    padding: 1rem;
}

.modal-container {
    background: #fff;
    border-radius: 1rem;
    width: 100%;
    max-width: 600px;
    max-height: 90vh;
    overflow: hidden;
    display: flex;
    flex-direction: column;
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1.25rem 1.5rem;
    border-bottom: 1px solid #e2e8f0;
}

.modal-header h2 {
    font-size: 1.25rem;
    font-weight: 600;
    margin: 0;
}

.modal-close {
    background: none;
    border: none;
    font-size: 1.5rem;
    cursor: pointer;
    color: #64748b;
}

.modal-body {
    padding: 1.5rem;
    overflow-y: auto;
}

.modal-footer {
    display: flex;
    justify-content: flex-end;
    gap: 0.75rem;
    padding: 1rem 1.5rem;
    border-top: 1px solid #e2e8f0;
    background: #f8fafc;
}

.form-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
}

.form-group {
    margin-bottom: 1rem;
}

.form-group label {
    display: block;
    font-size: 0.875rem;
    font-weight: 500;
    color: #374151;
    margin-bottom: 0.5rem;
}

.form-group input,
.form-group textarea {
    width: 100%;
    padding: 0.75rem;
    border: 1px solid #d1d5db;
    border-radius: 0.5rem;
    font-size: 0.95rem;
    transition: border-color 0.2s;
}

.form-group input:focus,
.form-group textarea:focus {
    outline: none;
    border-color: #fbbf24;
    box-shadow: 0 0 0 3px rgba(251, 191, 36, 0.1);
}

.checkbox-group {
    display: flex;
    gap: 1.5rem;
}

.checkbox-label {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    cursor: pointer;
}

.checkbox-label input {
    width: auto;
}

.btn-primary {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1.25rem;
    background: #fbbf24;
    color: #1e293b;
    border: none;
    border-radius: 0.5rem;
    font-weight: 600;
    cursor: pointer;
    transition: background 0.2s;
}

.btn-primary:hover {
    background: #f59e0b;
}

.btn-secondary {
    padding: 0.75rem 1.25rem;
    background: #f1f5f9;
    color: #475569;
    border: 1px solid #e2e8f0;
    border-radius: 0.5rem;
    font-weight: 500;
    cursor: pointer;
}

.btn-secondary:hover {
    background: #e2e8f0;
}

@media (max-width: 768px) {
    .page-hero {
        margin: -24px -16px 16px -16px;
        padding: 1.5rem;
    }
    
    .page-hero-content {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .tutors-grid {
        grid-template-columns: 1fr;
    }
    
    .tutor-card {
        flex-direction: column;
    }
    
    .tutor-actions {
        flex-direction: row;
        margin-top: 1rem;
    }
    
    .form-grid {
        grid-template-columns: 1fr;
    }
}

/* Pagination */
.pagination {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 1rem;
    margin-top: 2rem;
    padding: 1rem 0;
}

.pagination-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1.25rem;
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 0.75rem;
    color: #475569;
    font-weight: 600;
    font-size: 0.9rem;
    text-decoration: none;
    transition: all 0.2s;
}

.pagination-btn:hover {
    background: #f8fafc;
    border-color: #fbbf24;
    color: #1e293b;
}

.pagination-numbers {
    display: flex;
    gap: 0.5rem;
}

.pagination-num {
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 0.5rem;
    background: #fff;
    border: 1px solid #e2e8f0;
    color: #475569;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.2s;
}

.pagination-num:hover {
    background: #f8fafc;
    border-color: #fbbf24;
}

.pagination-num.active {
    background: #fbbf24;
    border-color: #fbbf24;
    color: #1e293b;
}
</style>

<script>
function filterTutors() {
    const query = document.getElementById('searchInput').value.toLowerCase();
    const cards = document.querySelectorAll('.tutor-card');
    
    cards.forEach(card => {
        const name = card.dataset.name || '';
        const subject = card.dataset.subject || '';
        const visible = name.includes(query) || subject.includes(query);
        card.style.display = visible ? '' : 'none';
    });
}

function openAddModal() {
    document.getElementById('modalTitle').textContent = 'Add Tutor';
    document.getElementById('tutorForm').reset();
    document.getElementById('tutorId').value = '';
    document.getElementById('tutorModal').style.display = 'flex';
}

function closeModal() {
    document.getElementById('tutorModal').style.display = 'none';
}

async function editTutor(id) {
    try {
        const res = await fetch(`../api/tutors.php?action=get&id=${id}`);
        const data = await res.json();
        if (data.success) {
            const t = data.tutor;
            document.getElementById('modalTitle').textContent = 'Edit Tutor';
            document.getElementById('tutorId').value = t.id;
            document.getElementById('name').value = t.name || '';
            document.getElementById('title').value = t.title || '';
            document.getElementById('subjects').value = t.subjects || '';
            document.getElementById('experience').value = t.experience || '';
            document.getElementById('photo').value = t.photo || '';
            document.getElementById('bio').value = t.bio || '';
            document.getElementById('is_active').checked = t.is_active == 1;
            document.getElementById('is_featured').checked = t.is_featured == 1;
            document.getElementById('tutorModal').style.display = 'flex';
        }
    } catch (e) {
        Swal.fire('Error', 'Failed to load tutor data', 'error');
    }
}

// Handle photo file upload
function handlePhotoUpload(input) {
    const file = input.files[0];
    if (!file) return;
    
    // Validate file type
    if (!file.type.startsWith('image/')) {
        Swal.fire('Error', 'Please select an image file', 'error');
        input.value = '';
        return;
    }
    
    // Validate file size (max 2MB)
    if (file.size > 2 * 1024 * 1024) {
        Swal.fire('Error', 'Image must be less than 2MB', 'error');
        input.value = '';
        return;
    }
    
    // Create preview
    const reader = new FileReader();
    reader.onload = function(e) {
        const preview = document.getElementById('photoPreviewImg');
        const previewContainer = document.getElementById('photoPreview');
        if (preview && previewContainer) {
            preview.src = e.target.result;
            previewContainer.style.display = 'block';
        }
    };
    reader.readAsDataURL(file);
    
    // Upload file
    uploadPhotoFile(file);
}

async function uploadPhotoFile(file) {
    const formData = new FormData();
    formData.append('photo', file);
    formData.append('action', 'upload_photo');
    
    try {
        Swal.fire({
            title: 'Uploading...',
            allowOutsideClick: false,
            didOpen: () => Swal.showLoading()
        });
        
        const res = await fetch('../api/tutors.php', {
            method: 'POST',
            body: formData
        });
        const data = await res.json();
        
        if (data.success && data.path) {
            document.getElementById('photo').value = data.path;
            Swal.fire('Success', 'Photo uploaded successfully', 'success');
        } else {
            throw new Error(data.message || 'Upload failed');
        }
    } catch (e) {
        Swal.fire('Error', e.message || 'Failed to upload photo', 'error');
    }
}

async function saveTutor() {
    const form = document.getElementById('tutorForm');
    const formData = new FormData(form);
    formData.append('action', document.getElementById('tutorId').value ? 'update' : 'create');
    formData.append('is_active', document.getElementById('is_active').checked ? 1 : 0);
    formData.append('is_featured', document.getElementById('is_featured').checked ? 1 : 0);
    
    try {
        const res = await fetch('../api/tutors.php', {
            method: 'POST',
            body: formData
        });
        const data = await res.json();
        if (data.success) {
            Swal.fire('Success', data.message, 'success').then(() => location.reload());
        } else {
            Swal.fire('Error', data.message || 'Failed to save', 'error');
        }
    } catch (e) {
        Swal.fire('Error', 'Failed to save tutor', 'error');
    }
}

async function deleteTutor(id) {
    const result = await Swal.fire({
        title: 'Delete Tutor?',
        text: 'This action cannot be undone.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc2626',
        confirmButtonText: 'Delete'
    });
    
    if (result.isConfirmed) {
        try {
            const formData = new FormData();
            formData.append('action', 'delete');
            formData.append('id', id);
            
            const res = await fetch('../api/tutors.php', {
                method: 'POST',
                body: formData
            });
            const data = await res.json();
            if (data.success) {
                Swal.fire('Deleted', 'Tutor has been removed', 'success').then(() => location.reload());
            } else {
                Swal.fire('Error', data.message || 'Failed to delete', 'error');
            }
        } catch (e) {
            Swal.fire('Error', 'Failed to delete tutor', 'error');
        }
    }
}

async function toggleFeatured(id, current) {
    try {
        const formData = new FormData();
        formData.append('action', 'toggle_featured');
        formData.append('id', id);
        formData.append('is_featured', current ? 0 : 1);
        
        const res = await fetch('../api/tutors.php', {
            method: 'POST',
            body: formData
        });
        const data = await res.json();
        if (data.success) {
            location.reload();
        }
    } catch (e) {
        console.error(e);
    }
}

// Close modal on escape key
document.addEventListener('keydown', e => {
    if (e.key === 'Escape') closeModal();
});

// Close modal on overlay click
document.getElementById('tutorModal')?.addEventListener('click', e => {
    if (e.target.classList.contains('modal-overlay')) closeModal();
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
