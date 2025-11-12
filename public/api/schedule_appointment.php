<?php
// public/api/schedule_appointment.php - Handle appointment scheduling
header('Content-Type: application/json');
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/csrf.php';
require_once __DIR__ . '/../config/functions.php';

$response = ['success' => false, 'message' => ''];

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Validate CSRF token
    $token = $input['_csrf_token'] ?? '';
    if (!verifyToken('contact_form', $token)) {
        throw new Exception('Invalid security token. Please refresh the page and try again.');
    }

    // Validate required fields
    $name = trim($input['name'] ?? '');
    $email = trim($input['email'] ?? '');
    $phone = trim($input['phone'] ?? '');
    $visit_date = trim($input['visit_date'] ?? '');
    $visit_time = trim($input['visit_time'] ?? '');
    $message = trim($input['message'] ?? '');

    if (empty($name)) {
        throw new Exception('Please provide your name');
    }

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Please provide a valid email address');
    }

    if (empty($visit_date)) {
        throw new Exception('Please select a date for your visit');
    }

    if (empty($visit_time)) {
        throw new Exception('Please select a time for your visit');
    }

    // Validate date is not in the past
    $selectedDate = new DateTime($visit_date);
    $today = new DateTime();
    $today->setTime(0, 0, 0);
    
    if ($selectedDate < $today) {
        throw new Exception('Please select a future date');
    }

    // Convert 12-hour time to 24-hour format for database
    $timeObj = DateTime::createFromFormat('h:i A', $visit_time);
    if (!$timeObj) {
        throw new Exception('Invalid time format');
    }
    $dbTime = $timeObj->format('H:i:s');

    // Insert appointment into database
    $stmt = $pdo->prepare("
        INSERT INTO appointments (name, email, phone, visit_date, visit_time, message, status, created_at) 
        VALUES (?, ?, ?, ?, ?, ?, 'pending', NOW())
    ");
    
    $stmt->execute([$name, $email, $phone, $visit_date, $dbTime, $message]);
    $appointmentId = $pdo->lastInsertId();

    // Send email notification to admin
    try {
        $adminEmail = 'highqsolidacademy@gmail.com';
        $subject = 'New Appointment Request - High Q Academy';
        
        $emailBody = "<h2>New Appointment Request</h2>";
        $emailBody .= "<p><strong>Appointment ID:</strong> #" . $appointmentId . "</p>";
        $emailBody .= "<p><strong>Name:</strong> " . htmlspecialchars($name) . "</p>";
        $emailBody .= "<p><strong>Email:</strong> " . htmlspecialchars($email) . "</p>";
        $emailBody .= "<p><strong>Phone:</strong> " . htmlspecialchars($phone ?: 'Not provided') . "</p>";
        $emailBody .= "<p><strong>Requested Date:</strong> " . date('F j, Y', strtotime($visit_date)) . "</p>";
        $emailBody .= "<p><strong>Requested Time:</strong> " . $visit_time . "</p>";
        
        if ($message) {
            $emailBody .= "<p><strong>Message:</strong><br>" . nl2br(htmlspecialchars($message)) . "</p>";
        }
        
        $emailBody .= "<p><a href='" . (function_exists('app_url') ? app_url('admin/appointments.php') : 'http://localhost/HIGH-Q/admin/appointments.php') . "'>View in Admin Panel</a></p>";
        
        sendEmail($adminEmail, $subject, $emailBody);
    } catch (Exception $e) {
        // Log error but don't fail the appointment creation
        error_log('Failed to send appointment notification email: ' . $e->getMessage());
    }

    // Send confirmation email to user
    try {
        $userSubject = 'Appointment Request Received - High Q Academy';
        $userBody = "<h2>Thank you for scheduling a visit!</h2>";
        $userBody .= "<p>Dear " . htmlspecialchars($name) . ",</p>";
        $userBody .= "<p>We have received your request to visit High Q Solid Academy.</p>";
        $userBody .= "<p><strong>Requested Date:</strong> " . date('F j, Y', strtotime($visit_date)) . "</p>";
        $userBody .= "<p><strong>Requested Time:</strong> " . $visit_time . "</p>";
        $userBody .= "<p>Our team will review your request and contact you shortly to confirm the appointment.</p>";
        $userBody .= "<p>If you have any questions, please call us at <strong>0807 208 8794</strong> or email <strong>info@hqacademy.com</strong></p>";
        $userBody .= "<p>Best regards,<br>High Q Solid Academy Team</p>";
        
        sendEmail($email, $userSubject, $userBody);
    } catch (Exception $e) {
        error_log('Failed to send appointment confirmation email to user: ' . $e->getMessage());
    }

    $response['success'] = true;
    $response['message'] = 'Your visit has been scheduled! We will contact you shortly to confirm.';
    $response['appointment_id'] = $appointmentId;
    $response['calendar_data'] = [
        'title' => 'Visit to High Q Academy',
        'start_date' => $visit_date,
        'start_time' => $visit_time,
        'location' => '8 Pineapple Avenue, Aiyetoro, Ikorodu',
        'description' => 'Scheduled visit to High Q Solid Academy'
    ];

} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
