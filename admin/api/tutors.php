<?php
session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
requirePermission('tutors');

header('Content-Type: application/json');

try {
    $conn = $pdo; // use PDO from includes/db.php
    $action = $_POST['action'] ?? $_GET['action'] ?? '';
    
    switch($action) {
        case 'upload_photo':
            handlePhotoUpload($conn);
            break;
            
        case 'create':
            createTutor($conn);
            break;
            
        case 'update':
            updateTutor($conn);
            break;
            
        case 'delete':
            deleteTutor($conn);
            break;
            
        case 'list':
            listTutors($conn);
            break;
            
        case 'get':
            getTutor($conn);
            break;
            
        default:
            throw new Exception('Invalid action');
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

function handlePhotoUpload($conn) {
    if (!isset($_FILES['photo'])) {
        throw new Exception('No file uploaded');
    }
    
    $file = $_FILES['photo'];
    
    // Validate file
    $allowed = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
    if (!in_array($file['type'], $allowed)) {
        throw new Exception('Invalid file type. Only JPG, PNG, GIF, and WebP allowed.');
    }
    
    if ($file['size'] > 2 * 1024 * 1024) {
        throw new Exception('File size must be less than 2MB');
    }
    
    // Create upload directory if not exists
    $uploadDir = __DIR__ . '/../../uploads/tutors/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    // Generate unique filename
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'tutor_' . uniqid() . '_' . time() . '.' . $ext;
    $filepath = $uploadDir . $filename;
    
    // Move uploaded file
    if (!move_uploaded_file($file['tmp_name'], $filepath)) {
        throw new Exception('Failed to save uploaded file');
    }
    
    // Return relative path
    $relativePath = '../uploads/tutors/' . $filename;
    
    echo json_encode([
        'success' => true,
        'message' => 'Photo uploaded successfully',
        'path' => $relativePath,
        'filename' => $filename
    ]);
}

function createTutor($conn) {
    $name = $_POST['name'] ?? '';
    $title = $_POST['title'] ?? '';
    $subjects = $_POST['subjects'] ?? '';
    $experience = $_POST['experience'] ?? 0;
    $email = $_POST['email'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $qualifications = $_POST['qualifications'] ?? '';
    $bio = $_POST['bio'] ?? '';
    $photo = $_POST['photo'] ?? '';
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    $is_featured = isset($_POST['is_featured']) ? 1 : 0;
    
    if (empty($name) || empty($title)) {
        throw new Exception('Name and title are required');
    }
    
    // Generate slug from name
    $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name), '-'));
    
    // Check if tutors table has the right columns
    try {
        $stmt = $conn->prepare("
            INSERT INTO tutors (name, slug, photo, short_bio, long_bio, qualifications, subjects, contact_email, phone, is_featured, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
        ");
        
        $stmt->execute([
            $name,
            $slug,
            $photo,
            $title, // Use title as short_bio
            $bio,
            $qualifications,
            $subjects,
            $email,
            $phone,
            $is_featured
        ]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Tutor created successfully',
            'id' => $conn->lastInsertId()
        ]);
    } catch (PDOException $e) {
        throw new Exception('Database error: ' . $e->getMessage());
    }
}

function updateTutor($conn) {
    $id = $_POST['id'] ?? 0;
    $name = $_POST['name'] ?? '';
    $title = $_POST['title'] ?? '';
    $subjects = $_POST['subjects'] ?? '';
    $experience = $_POST['experience'] ?? '';
    $email = $_POST['email'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $qualifications = $_POST['qualifications'] ?? '';
    $bio = $_POST['bio'] ?? '';
    $photo = $_POST['photo'] ?? '';
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    $is_featured = isset($_POST['is_featured']) ? 1 : 0;
    
    if (empty($id) || empty($name) || empty($title)) {
        throw new Exception('ID, name, and title are required');
    }
    
    $stmt = $conn->prepare("
        UPDATE tutors 
        SET name=?, title=?, subjects=?, experience=?, email=?, phone=?, qualifications=?, bio=?, photo=?, is_active=?, is_featured=?
        WHERE id=?
    ");
    
    $stmt->execute([$name, $title, $subjects, $experience, $email, $phone, $qualifications, $bio, $photo, $is_active, $is_featured, $id]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Tutor updated successfully'
    ]);
}

function deleteTutor($conn) {
    $id = $_POST['id'] ?? $_GET['id'] ?? 0;
    
    if (empty($id)) {
        throw new Exception('ID is required');
    }
    
    // Get photo path to delete file
    $stmt = $conn->prepare("SELECT photo FROM tutors WHERE id=?");
    $stmt->execute([$id]);
    $tutor = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Delete from database
    $stmt = $conn->prepare("DELETE FROM tutors WHERE id=?");
    $stmt->execute([$id]);
    
    // Delete photo file if exists
    if ($tutor && $tutor['photo']) {
        $photoPath = __DIR__ . '/../../' . $tutor['photo'];
        if (file_exists($photoPath)) {
            unlink($photoPath);
        }
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Tutor deleted successfully'
    ]);
}

function listTutors($conn) {
    $stmt = $conn->query("SELECT * FROM tutors ORDER BY id DESC");
    $tutors = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'tutors' => $tutors
    ]);
}

function getTutor($conn) {
    $id = $_GET['id'] ?? 0;
    
    if (empty($id)) {
        throw new Exception('ID is required');
    }
    
    $stmt = $conn->prepare("SELECT * FROM tutors WHERE id=?");
    $stmt->execute([$id]);
    $tutor = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$tutor) {
        throw new Exception('Tutor not found');
    }
    
    echo json_encode([
        'success' => true,
        'tutor' => $tutor
    ]);
}
