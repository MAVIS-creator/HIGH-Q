<?php
// Moved to top with other includes
require_once '../includes/header.php';
require_once '../includes/sidebar.php';


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

// Determine application base URL using canonical helper so APP_URL from .env is honoured
$appUrl = rtrim(app_url(''), '/');

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
                // Save featured image as a relative path (uploads/posts/...) so it works across envs
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
                                                    // If published, trigger newsletter send (simple immediate loop in background)
                                                    if ($status === 'published') {
                                                        try {
                                                            // Fetch subscribers in small batches
                                                            $batchSize = 50;
                                                            $offset = 0;
                                                            while (true) {
                                                                $sstmt = $pdo->prepare('SELECT id,email,unsubscribe_token FROM newsletter_subscribers ORDER BY id LIMIT ? OFFSET ?');
                                                                $sstmt->bindValue(1, (int)$batchSize, PDO::PARAM_INT);
                                                                $sstmt->bindValue(2, (int)$offset, PDO::PARAM_INT);
                                                                $sstmt->execute();
                                                                $subs = $sstmt->fetchAll(PDO::FETCH_ASSOC);
                                                                if (!$subs) break;
                                                                foreach ($subs as $sub) {
                                                                    $uToken = $sub['unsubscribe_token'] ?: bin2hex(random_bytes(20));
                                                                    // If token was missing, store it
                                                                    if (empty($sub['unsubscribe_token'])) {
                                                                        try {
                                                                            $upd = $pdo->prepare('UPDATE newsletter_subscribers SET unsubscribe_token=?, token_created_at=NOW() WHERE id=?');
                                                                            $upd->execute([$uToken, $sub['id']]);
                                                                        } catch (Throwable $_) {}
                                                                    }
                                                                    $postUrl = app_url('post.php?id=' . urlencode($newId));
                                                                    $unsubscribeUrl = app_url('public/unsubscribe_newsletter.php?token=' . urlencode($uToken));
                                                                    $html = "<p>Hi,</p><p>A new article was published: <strong>" . htmlspecialchars($title) . "</strong></p>";
                                                                    $html .= "<p>" . nl2br(htmlspecialchars($excerpt ?: substr($content,0,200))) . "</p>";
                                                                    $html .= "<p><a href=\"{$postUrl}\">Read the full article</a></p>";
                                                                    $html .= "<hr><p style=\"font-size:0.9rem;color:#666\">If you no longer wish to receive these emails, <a href=\"{$unsubscribeUrl}\">unsubscribe</a>.</p>";
                                                                    // Use admin sendEmail (autoloaded earlier)
                                                                    try { sendEmail($sub['email'], 'New article: ' . $title, $html); } catch (Throwable $_) {}
                                                                }
                                                                $offset += $batchSize;
                                                                // small sleep to reduce SMTP load
                                                                usleep(200000);
                                                            }
                                                        } catch (Throwable $e) {
                                                            @file_put_contents(__DIR__ . '/../../storage/logs/newsletter_errors.log', date('c') . " Newsletter send error: " . $e->getMessage() . "\n", FILE_APPEND | LOCK_EX);
                                                        }
                                                    }
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
                                        // If this update sets status to published, send newsletter to subscribers
                                        if ($status === 'published') {
                                            try {
                                                // Fetch subscribers and send emails in batches
                                                $batchSize = 50;
                                                $offset = 0;
                                                while (true) {
                                                    $sstmt = $pdo->prepare('SELECT id,email,unsubscribe_token FROM newsletter_subscribers ORDER BY id LIMIT ? OFFSET ?');
                                                    $sstmt->bindValue(1, (int)$batchSize, PDO::PARAM_INT);
                                                    $sstmt->bindValue(2, (int)$offset, PDO::PARAM_INT);
                                                    $sstmt->execute();
                                                    $subs = $sstmt->fetchAll(PDO::FETCH_ASSOC);
                                                    if (!$subs) break;
                                                    foreach ($subs as $sub) {
                                                        $uToken = $sub['unsubscribe_token'] ?: bin2hex(random_bytes(20));
                                                        if (empty($sub['unsubscribe_token'])) {
                                                            try { $upd = $pdo->prepare('UPDATE newsletter_subscribers SET unsubscribe_token=?, token_created_at=NOW() WHERE id=?'); $upd->execute([$uToken, $sub['id']]); } catch (Throwable $_) {}
                                                        }
                                                        $postUrl = app_url('post.php?id=' . urlencode($id));
                                                        $unsubscribeUrl = app_url('public/unsubscribe_newsletter.php?token=' . urlencode($uToken));
                                                        $html = "<p>Hi,</p><p>An article was published: <strong>" . htmlspecialchars($title) . "</strong></p>";
                                                        $html .= "<p>" . nl2br(htmlspecialchars($excerpt ?: substr($content,0,200))) . "</p>";
                                                        $html .= "<p><a href=\"{$postUrl}\">Read the full article</a></p>";
                                                        $html .= "<hr><p style=\"font-size:0.9rem;color:#666\">If you no longer wish to receive these emails, <a href=\"{$unsubscribeUrl}\">unsubscribe</a>.</p>";
                                                        try { sendEmail($sub['email'], 'New article: ' . $title, $html); } catch (Throwable $_) {}
                                                    }
                                                    $offset += $batchSize;
                                                    usleep(200000);
                                                }
                                            } catch (Throwable $e) {
                                                @file_put_contents(__DIR__ . '/../../storage/logs/newsletter_errors.log', date('c') . " Newsletter send error: " . $e->getMessage() . "\n", FILE_APPEND | LOCK_EX);
                                            }
                                        }
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
                        // Attempt to get category name
                        $catName = '';
                        if ($hasCategoryId && $category_id) {
                            try {
                                $cst = $pdo->prepare('SELECT name FROM categories WHERE id=?');
                                $cst->execute([$category_id]);
                                $catName = $cst->fetchColumn() ?: '';
                            } catch (Throwable $e) { $catName = ''; }
                        } elseif (!empty($post['category'])) {
                            $catName = $post['category'];
                        }
                        echo json_encode(['success' => true, 'post' => [
                            'id' => $id,
                            'title' => $title,
                            'excerpt' => $excerpt,
                            'category' => $catName,
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
    <link rel="stylesheet" href="../assets/css/modern-tables.css">
</head>

<body>
<main class="main-content" style="padding: 2rem; max-width: 1600px; margin: 0 auto;">
<div class="posts-page">
    <div class="page-header" style="background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%); padding: 2.5rem; border-radius: 1rem; margin-bottom: 2.5rem; color: #1e293b; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 8px 24px rgba(251, 191, 36, 0.25);">
        <div>
            <h1 style="font-size: 2.5rem; font-weight: 800; margin: 0 0 0.5rem 0; display: flex; align-items: center; gap: 12px;"><i class='bx bxs-news' style="font-size: 2.5rem;"></i> News & Blog</h1>
            <p style="font-size: 1.1rem; opacity: 0.85; margin: 0;">Create and manage articles and blog posts</p>
        </div>
        <button onclick="location.href='post_edit.php'" style="padding: 1rem 1.75rem; background: #1e293b; color: white; border: none; border-radius: 0.75rem; font-weight: 700; cursor: pointer; transition: all 0.2s; display: flex; align-items: center; gap: 0.75rem;" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 16px rgba(30,41,59,0.3)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='none'">
            <i class='bx bx-plus' style="font-size: 1.5rem;"></i> New Post
        </button>
    </div>

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
                <form method="get" action="index.php?pages=posts" class="search-form">
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
                            <?php else: ?>
                                <div class="image-placeholder"><i class='bx bxs-image'></i></div>
                            <?php endif; ?>
                        <div class="meta">
                            <strong><?= htmlspecialchars($p['title']) ?></strong>
                            <div class="info"><?= htmlspecialchars($p['category_name'] ?? 'Uncategorized') ?> • <?= htmlspecialchars($p['author'] ?? '') ?></div>
                            <?php if ($p['status'] === 'published'): ?>
                                <span class="status-badge status-published" style="margin-left:8px">Published</span>
                            <?php else: ?>
                                <span class="status-badge status-draft" style="margin-left:8px">Draft</span>
                            <?php endif; ?>
                            <?php if ($p['status'] === 'published' && (empty($p['excerpt']) || empty($p['featured_image']))): ?>
                                <span title="Missing excerpt or image" style="color:var(--hq-yellow);margin-left:8px"><i class="bx bx-error"></i></span>
                            <?php endif; ?>
                        </div>
                        <div class="excerpt"><?= nl2br(htmlspecialchars($p['excerpt'] ?: '')) ?></div>
                        <div class="actions">
                            <a href="index.php?pages=post_edit&id=<?= $p['id'] ?>" class="btn edit-link" data-id="<?= $p['id'] ?>">Edit</a>
                            <form method="post" action="index.php?pages=posts&action=delete&amp;id=<?= $p['id'] ?>" style="display:inline">
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
                <!-- Slug and excerpt are auto-generated; removed from modal to simplify authoring -->
                <div class="form-row">
                    <label>Content *</label>
                    <textarea name="content" id="pContent" rows="6" required></textarea>
                    <div class="muted" style="margin-top:8px;font-size:13px;padding:10px;border-left:3px solid var(--hq-yellow);background:#fffdf6;border-radius:4px;">
                        <strong>Heading quick guide</strong>
                        <div style="margin-top:6px">You can use simple Markdown-style headings when writing content. They will be converted automatically on public view:</div>
                        <ul style="margin:8px 0 0 18px;padding:0;">
                          <li><code># Section title</code> &rarr; <em>&lt;h2&gt;Section title&lt;/h2&gt;</em></li>
                          <li><code>## Subsection</code> &rarr; <em>&lt;h3&gt;Subsection&lt;/h3&gt;</em></li>
                          <li><code>### Sub-sub</code> &rarr; <em>&lt;h4&gt;Sub-sub&lt;/h4&gt;</em></li>
                        </ul>
                        <div style="margin-top:8px;color:var(--hq-gray);">Leave a blank line between headings and paragraphs for best results.</div>
                    </div>
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
// clicking on overlay already closes; also support clicking the modal backdrop area
overlay.addEventListener('click', () => { closePostModal(); closeEditModal(); });
postModal.addEventListener('click', function(e){
    if (e.target === postModal) closePostModal();
});

// Open modal and load form
document.querySelectorAll('.edit-link').forEach(link => {
  link.addEventListener('click', e => {
    e.preventDefault();
    const id = link.dataset.id;
    fetch(`index.php?pages=post_edit&id=${id}`)
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
                    const cat = data.post.category || 'Uncategorized';
                    if (info) info.textContent = `${cat} • ${data.post.author || ''}`;
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
                var m = data.error || 'Update failed';
                if (typeof Swal !== 'undefined') Swal.fire('Error', m, 'error'); else alert(m);
            }
    });
  });
}
</script>
