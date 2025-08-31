<?php
// admin/pages/tutors.php
require '../includes/auth.php';
require '../includes/db.php';
require '../includes/csrf.php';
require '../includes/functions.php';

// Only Admin & Sub-Admin
requireRole(['admin','sub-admin']);

$csrf    = generateToken();
$errors  = [];
$success = [];

// Handle Create / Edit / Delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action'])) {
    if (!verifyToken($_POST['csrf_token'] ?? '')) {
        $errors[] = "Invalid CSRF token.";
    } else {
        $act = $_GET['action'];
        $id  = isset($_GET['id']) ? (int)$_GET['id'] : null;

        // Gather and sanitize inputs
        $name     = trim($_POST['name'] ?? '');
        $slug     = trim($_POST['slug'] ?? '');
        $photo    = trim($_POST['photo'] ?? '');
        $short    = trim($_POST['short_bio'] ?? '');
        $long     = trim($_POST['long_bio'] ?? '');
        $quals    = trim($_POST['qualifications'] ?? '');
        $subs     = array_filter(array_map('trim', explode(',', $_POST['subjects'] ?? '')));
        $subjects = json_encode($subs, JSON_UNESCAPED_UNICODE);
        $email    = trim($_POST['contact_email'] ?? '');
        $phone    = trim($_POST['phone'] ?? '');
        $rating   = is_numeric($_POST['rating'] ?? null)
                    ? number_format((float)$_POST['rating'],2) : null;
        $feat     = isset($_POST['is_featured']) ? 1 : 0;

        // Basic validation
        if (!$name || !$slug) {
            $errors[] = "Name and slug are required.";
        }

        if (empty($errors)) {
            if ($act === 'create') {
                $stmt = $pdo->prepare("
                  INSERT INTO tutors
                    (name, slug, photo, short_bio, long_bio, qualifications, subjects,
                     contact_email, phone, rating, is_featured)
                  VALUES (?,?,?,?,?,?,?,?,?,?,?)
                ");
                $stmt->execute([
                  $name, $slug, $photo, $short, $long, $quals, $subjects,
                  $email, $phone, $rating, $feat
                ]);
                logAction($pdo, $_SESSION['user']['id'], 'tutor_created', ['slug'=>$slug]);
                $success[] = "Tutor '{$name}' created.";
            }

            if ($act === 'edit' && $id) {
                $stmt = $pdo->prepare("
                  UPDATE tutors SET
                    name=?, slug=?, photo=?, short_bio=?, long_bio=?, qualifications=?,
                    subjects=?, contact_email=?, phone=?, rating=?, is_featured=?, updated_at=NOW()
                  WHERE id=?
                ");
                $stmt->execute([
                  $name, $slug, $photo, $short, $long, $quals, $subjects,
                  $email, $phone, $rating, $feat, $id
                ]);
                logAction($pdo, $_SESSION['user']['id'], 'tutor_updated', ['tutor_id'=>$id]);
                $success[] = "Tutor '{$name}' updated.";
            }

            if ($act === 'delete' && $id) {
                $pdo->prepare("DELETE FROM tutors WHERE id=?")->execute([$id]);
                logAction($pdo, $_SESSION['user']['id'], 'tutor_deleted', ['tutor_id'=>$id]);
                $success[] = "Tutor deleted.";
            }
        }

        header("Location: index.php?page=tutors");
        exit;
    }
}

// Fetch all tutors
$tutors = $pdo->query("SELECT * FROM tutors ORDER BY created_at DESC")->fetchAll();
?>
