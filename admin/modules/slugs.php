<?php
/**
 * Slug Management Module
 * Manage all slugs for courses, posts, and other content
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
requirePermission('manage_settings');

$db = $pdo; // use shared PDO connection from db.php

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    try {
        switch ($_POST['action']) {
            case 'update_slug':
                $id = intval($_POST['id'] ?? 0);
                $newSlug = trim($_POST['slug'] ?? '');
                $type = $_POST['type'] ?? 'course';
                
                if (!$id || !$newSlug) {
                    throw new Exception('Invalid parameters');
                }
                
                // Validate slug format (lowercase, hyphens, no spaces)
                if (!preg_match('/^[a-z0-9-]+$/', $newSlug)) {
                    throw new Exception('Invalid slug format. Use lowercase letters, numbers, and hyphens only.');
                }
                
                // Check for duplicate slugs
                if ($type === 'course') {
                    $stmt = $db->prepare("SELECT id FROM courses WHERE slug = ? AND id != ?");
                    $stmt->execute([$newSlug, $id]);
                    if ($stmt->fetch()) {
                        throw new Exception('Slug already exists. Please choose a unique slug.');
                    }
                    
                    // Update slug
                    $stmt = $db->prepare("UPDATE courses SET slug = ? WHERE id = ?");
                    $stmt->execute([$newSlug, $id]);
                } elseif ($type === 'post') {
                    $stmt = $db->prepare("SELECT id FROM posts WHERE slug = ? AND id != ?");
                    $stmt->execute([$newSlug, $id]);
                    if ($stmt->fetch()) {
                        throw new Exception('Slug already exists. Please choose a unique slug.');
                    }
                    
                    $stmt = $db->prepare("UPDATE posts SET slug = ? WHERE id = ?");
                    $stmt->execute([$newSlug, $id]);
                }
                
                // Log audit
                $userId = intval($_SESSION['user']['id'] ?? 0);
                logAction($db, $userId, 'slug_updated', [
                    'type' => $type,
                    'id' => $id,
                    'new_slug' => $newSlug
                ]);
                
                echo json_encode(['success' => true, 'message' => 'Slug updated successfully']);
                exit;
                
            case 'delete_course':
                $id = intval($_POST['id'] ?? 0);
                if (!$id) {
                    throw new Exception('Invalid course ID');
                }
                
                // Soft delete by setting is_active to 0
                $stmt = $db->prepare("UPDATE courses SET is_active = 0 WHERE id = ?");
                $stmt->execute([$id]);
                
                $userId = intval($_SESSION['user']['id'] ?? 0);
                logAction($db, $userId, 'course_deleted', ['course_id' => $id]);
                
                echo json_encode(['success' => true, 'message' => 'Course deleted successfully']);
                exit;
                
            default:
                throw new Exception('Invalid action');
        }
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        exit;
    }
}

// Fetch all courses
$courses = [];
try {
    $stmt = $db->query("SELECT id, title, slug, is_active, created_at, updated_at FROM courses ORDER BY is_active DESC, title ASC");
    $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $courses = [];
}

// Fetch all posts
$posts = [];
try {
    $stmt = $db->query("SELECT id, title, slug, status, created_at, updated_at FROM posts ORDER BY status DESC, title ASC");
    $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $posts = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Slug Management - HIGH Q Admin</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <style>
        .slugs-container {
            padding: 24px;
            max-width: 1400px;
            margin: 0 auto;
        }
        
        .slugs-header {
            margin-bottom: 32px;
        }
        
        .slugs-header h1 {
            font-size: 28px;
            color: var(--hq-black);
            margin-bottom: 8px;
        }
        
        .slugs-header p {
            color: var(--hq-gray);
            font-size: 14px;
        }
        
        .tabs {
            display: flex;
            gap: 8px;
            border-bottom: 2px solid #e5e7eb;
            margin-bottom: 24px;
        }
        
        .tab {
            padding: 12px 24px;
            background: none;
            border: none;
            border-bottom: 3px solid transparent;
            color: var(--hq-gray);
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .tab.active {
            color: var(--hq-yellow);
            border-bottom-color: var(--hq-yellow);
        }
        
        .tab:hover {
            color: var(--hq-black);
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        .slugs-table {
            width: 100%;
            background: white;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .slugs-table table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .slugs-table th {
            background: #f9fafb;
            padding: 16px;
            text-align: left;
            font-weight: 600;
            color: var(--hq-black);
            border-bottom: 1px solid #e5e7eb;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .slugs-table td {
            padding: 16px;
            border-bottom: 1px solid #f3f4f6;
            color: var(--hq-gray);
        }
        
        .slugs-table tr:hover {
            background: #fafafa;
        }
        
        .slug-field {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .slug-input {
            flex: 1;
            padding: 8px 12px;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            font-family: 'Courier New', monospace;
            font-size: 13px;
            color: var(--hq-black);
        }
        
        .slug-input:focus {
            outline: none;
            border-color: var(--hq-yellow);
            box-shadow: 0 0 0 3px rgba(255, 214, 0, 0.1);
        }
        
        .slug-input:disabled {
            background: #f9fafb;
            cursor: not-allowed;
        }
        
        .btn-icon {
            padding: 8px;
            background: none;
            border: none;
            cursor: pointer;
            color: var(--hq-gray);
            border-radius: 4px;
            transition: all 0.2s;
        }
        
        .btn-icon:hover {
            background: #f3f4f6;
            color: var(--hq-black);
        }
        
        .btn-icon.edit:hover {
            color: var(--hq-blue-white);
        }
        
        .btn-icon.save {
            color: #10b981;
        }
        
        .btn-icon.save:hover {
            background: #d1fae5;
        }
        
        .btn-icon.cancel:hover {
            color: #ef4444;
            background: #fee2e2;
        }
        
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .status-badge.active {
            background: #d1fae5;
            color: #065f46;
        }
        
        .status-badge.inactive {
            background: #fee2e2;
            color: #991b1b;
        }
        
        .status-badge.published {
            background: #dbeafe;
            color: #1e40af;
        }
        
        .status-badge.draft {
            background: #fef3c7;
            color: #92400e;
        }
        
        .alert {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .alert.success {
            background: #d1fae5;
            color: #065f46;
        }
        
        .alert.error {
            background: #fee2e2;
            color: #991b1b;
        }
        
        .empty-state {
            text-align: center;
            padding: 64px 24px;
            color: var(--hq-gray);
        }
        
        .empty-state i {
            font-size: 48px;
            color: #d1d5db;
            margin-bottom: 16px;
        }
    </style>
</head>
<body>
    <div class="slugs-container">
        <div class="slugs-header">
            <h1><i class='bx bx-link'></i> Slug Management</h1>
            <p>Manage URL slugs for courses, posts, and other content. Slugs must be unique and URL-friendly.</p>
        </div>
        
        <div id="alert-container"></div>
        
        <div class="tabs">
            <button class="tab active" data-tab="courses">
                <i class='bx bx-book'></i> Programs/Courses (<?= count($courses) ?>)
            </button>
            <button class="tab" data-tab="posts">
                <i class='bx bx-news'></i> Blog Posts (<?= count($posts) ?>)
            </button>
        </div>
        
        <!-- Courses Tab -->
        <div class="tab-content active" id="courses">
            <div class="slugs-table">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Title</th>
                            <th>Slug</th>
                            <th>Status</th>
                            <th>Last Updated</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($courses)): ?>
                            <tr>
                                <td colspan="6">
                                    <div class="empty-state">
                                        <i class='bx bx-folder-open'></i>
                                        <p>No courses found</p>
                                    </div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($courses as $course): ?>
                                <tr>
                                    <td><?= htmlspecialchars($course['id']) ?></td>
                                    <td><strong><?= htmlspecialchars($course['title']) ?></strong></td>
                                    <td>
                                        <div class="slug-field">
                                            <input type="text" 
                                                   class="slug-input" 
                                                   value="<?= htmlspecialchars($course['slug']) ?>"
                                                   data-id="<?= $course['id'] ?>"
                                                   data-type="course"
                                                   data-original="<?= htmlspecialchars($course['slug']) ?>"
                                                   disabled>
                                            <button class="btn-icon edit" data-action="edit" title="Edit">
                                                <i class='bx bx-edit'></i>
                                            </button>
                                            <button class="btn-icon save" data-action="save" title="Save" style="display:none;">
                                                <i class='bx bx-check'></i>
                                            </button>
                                            <button class="btn-icon cancel" data-action="cancel" title="Cancel" style="display:none;">
                                                <i class='bx bx-x'></i>
                                            </button>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if ($course['is_active']): ?>
                                            <span class="status-badge active">
                                                <i class='bx bx-check-circle'></i> Active
                                            </span>
                                        <?php else: ?>
                                            <span class="status-badge inactive">
                                                <i class='bx bx-x-circle'></i> Inactive
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= date('M d, Y', strtotime($course['updated_at'])) ?></td>
                                    <td>
                                        <a href="../index.php?pages=courses&action=edit&id=<?= $course['id'] ?>" 
                                           class="btn-icon" title="Edit Course">
                                            <i class='bx bx-cog'></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Posts Tab -->
        <div class="tab-content" id="posts">
            <div class="slugs-table">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Title</th>
                            <th>Slug</th>
                            <th>Status</th>
                            <th>Last Updated</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($posts)): ?>
                            <tr>
                                <td colspan="6">
                                    <div class="empty-state">
                                        <i class='bx bx-folder-open'></i>
                                        <p>No blog posts found</p>
                                    </div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($posts as $post): ?>
                                <tr>
                                    <td><?= htmlspecialchars($post['id']) ?></td>
                                    <td><strong><?= htmlspecialchars($post['title']) ?></strong></td>
                                    <td>
                                        <div class="slug-field">
                                            <input type="text" 
                                                   class="slug-input" 
                                                   value="<?= htmlspecialchars($post['slug']) ?>"
                                                   data-id="<?= $post['id'] ?>"
                                                   data-type="post"
                                                   data-original="<?= htmlspecialchars($post['slug']) ?>"
                                                   disabled>
                                            <button class="btn-icon edit" data-action="edit" title="Edit">
                                                <i class='bx bx-edit'></i>
                                            </button>
                                            <button class="btn-icon save" data-action="save" title="Save" style="display:none;">
                                                <i class='bx bx-check'></i>
                                            </button>
                                            <button class="btn-icon cancel" data-action="cancel" title="Cancel" style="display:none;">
                                                <i class='bx bx-x'></i>
                                            </button>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if ($post['status'] === 'published'): ?>
                                            <span class="status-badge published">
                                                <i class='bx bx-globe'></i> Published
                                            </span>
                                        <?php else: ?>
                                            <span class="status-badge draft">
                                                <i class='bx bx-edit'></i> Draft
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= date('M d, Y', strtotime($post['updated_at'])) ?></td>
                                    <td>
                                        <a href="../index.php?pages=news&action=edit&id=<?= $post['id'] ?>" 
                                           class="btn-icon" title="Edit Post">
                                            <i class='bx bx-cog'></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <script>
        // Tab switching
        document.querySelectorAll('.tab').forEach(tab => {
            tab.addEventListener('click', () => {
                document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
                document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
                
                tab.classList.add('active');
                document.getElementById(tab.dataset.tab).classList.add('active');
            });
        });
        
        // Slug editing
        document.querySelectorAll('.slug-field').forEach(field => {
            const input = field.querySelector('.slug-input');
            const editBtn = field.querySelector('[data-action="edit"]');
            const saveBtn = field.querySelector('[data-action="save"]');
            const cancelBtn = field.querySelector('[data-action="cancel"]');
            
            editBtn.addEventListener('click', () => {
                input.disabled = false;
                input.focus();
                editBtn.style.display = 'none';
                saveBtn.style.display = 'block';
                cancelBtn.style.display = 'block';
            });
            
            cancelBtn.addEventListener('click', () => {
                input.value = input.dataset.original;
                input.disabled = true;
                editBtn.style.display = 'block';
                saveBtn.style.display = 'none';
                cancelBtn.style.display = 'none';
            });
            
            saveBtn.addEventListener('click', async () => {
                const newSlug = input.value.trim().toLowerCase();
                const id = input.dataset.id;
                const type = input.dataset.type;
                
                if (!newSlug) {
                    showAlert('Slug cannot be empty', 'error');
                    return;
                }
                
                if (!/^[a-z0-9-]+$/.test(newSlug)) {
                    showAlert('Invalid slug format. Use lowercase letters, numbers, and hyphens only.', 'error');
                    return;
                }
                
                try {
                    const response = await fetch('', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: `action=update_slug&id=${id}&slug=${newSlug}&type=${type}`
                    });
                    
                    const result = await response.json();
                    
                    if (result.success) {
                        input.dataset.original = newSlug;
                        input.disabled = true;
                        editBtn.style.display = 'block';
                        saveBtn.style.display = 'none';
                        cancelBtn.style.display = 'none';
                        showAlert(result.message, 'success');
                    } else {
                        showAlert(result.error, 'error');
                    }
                } catch (error) {
                    showAlert('Failed to update slug. Please try again.', 'error');
                }
            });
        });
        
        function showAlert(message, type) {
            const container = document.getElementById('alert-container');
            const alert = document.createElement('div');
            alert.className = `alert ${type}`;
            alert.innerHTML = `
                <i class='bx ${type === 'success' ? 'bx-check-circle' : 'bx-error-circle'}'></i>
                ${message}
            `;
            container.appendChild(alert);
            
            setTimeout(() => alert.remove(), 5000);
        }
    </script>
</body>
</html>
