<?php
// admin/pages/academic.php
// Remove any visible code output from the top of the page
// Ensure no script or debug code is rendered at the top of the page
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/csrf.php';
// --- Early AJAX handling: ensure JSON responses are not contaminated by HTML ---
// Determine requested action (AJAX clients typically send action via POST)
$earlyAction = $_POST['action'] ?? $_GET['action'] ?? '';
if (!empty($earlyAction)) {
  // Treat these as JSON/JSON-AJAX requests so we can set proper headers early
  header('Content-Type: application/json');

  // Use canonical helper so APP_URL from .env (if present) is honoured and fallbacks are consistent
  // app_url() is available because we included ../includes/functions.php earlier
  $baseUrl = rtrim(app_url(''), '/');
  // expose $baseUrl for later handlers in this file
  $GLOBALS['HQ_BASE_URL'] = $baseUrl;
}

  // Helper: insert payment row with fallback for databases where payments.id is not AUTO_INCREMENT
  function insertPaymentWithFallback(PDO $pdo, $studentId, $amount, $method, $reference) {
    try {
      $ins = $pdo->prepare('INSERT INTO payments (student_id, amount, payment_method, reference, status, created_at) VALUES (?, ?, ?, ?, "pending", NOW())');
      $ins->execute([ $studentId, $amount, $method, $reference ]);
      return (int)$pdo->lastInsertId();
    } catch (Throwable $e) {
      // fallback: lock table, compute next id, insert with explicit id
      try {
        $pdo->beginTransaction();
        // Lock the table for write to avoid races
        $pdo->exec('LOCK TABLES payments WRITE');
        $row = $pdo->query('SELECT MAX(id) AS m FROM payments')->fetch(PDO::FETCH_ASSOC);
        $next = (int)($row['m'] ?? 0) + 1;
        $ins2 = $pdo->prepare('INSERT INTO payments (id, student_id, amount, payment_method, reference, status, created_at) VALUES (?, ?, ?, ?, ?, "pending", NOW())');
        $ins2->execute([ $next, $studentId, $amount, $method, $reference ]);
        $pdo->exec('UNLOCK TABLES');
        $pdo->commit();
        return $next;
      } catch (Throwable $e2) {
        try { $pdo->exec('UNLOCK TABLES'); } catch (Throwable $_) {}
        if ($pdo->inTransaction()) $pdo->rollBack();
        throw $e; // rethrow original exception
      }
    }
  }

requirePermission('academic'); // where 'academic' matches the menu slug
// Generate CSRF token
$csrf = generateToken('academic_form');

// Support POST-driven AJAX actions (confirm_registration, view_registration)
// Handle create_payment_link (AJAX) - returns JSON link + reference
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_payment_link') {
  // Ensure admin permission
  requirePermission('academic');
  $id = intval($_POST['id'] ?? 0);
  if ($id <= 0) { echo json_encode(['success'=>false,'error'=>'Invalid ID']); exit; }

  try {
    $stmt = $pdo->prepare("SELECT sr.*, COALESCE(sr.email, u.email) AS email, u.id AS user_id FROM student_registrations sr LEFT JOIN users u ON u.id = sr.user_id WHERE sr.id = ? LIMIT 1");
    $stmt->execute([$id]);
    $reg = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$reg) { echo json_encode(['success'=>false,'error'=>'Registration not found']); exit; }

    // Generate unique payment reference
    $reference = 'PAY-' . strtoupper(bin2hex(random_bytes(5)));
    $amount = isset($_POST['amount']) ? floatval($_POST['amount']) : 0.0;
  $method = $_POST['method'] ?? 'bank_transfer';
  // normalize common values
  if (in_array(strtolower($method), ['bank', 'bank_transfer', 'transfer'])) $method = 'bank_transfer';
  if (strtolower($method) === 'paystack') $method = 'paystack';

  // Insert payment placeholder using fallback helper
  $paymentId = insertPaymentWithFallback($pdo, $reg['user_id'] ?: null, $amount, $method, $reference);

    // Prefer canonical helper so APP_URL from .env (if present) is honoured and fallbacks are consistent
    $base = rtrim(app_url(''), '/');
    // Build a public-facing URL to payments_wait.php (use app_url to preserve subfolder installs)
    $link = app_url('pay/' . urlencode($reference));

    // Try to send email to registrant with the payment link
    $emailSent = false;
    if (!empty($reg['email']) && filter_var($reg['email'], FILTER_VALIDATE_EMAIL) && function_exists('sendEmail')) {
      try {
        $subject = 'Payment link for your registration';
  $body = '<div style="font-family:Arial,Helvetica,sans-serif;color:#222;line-height:1.4">';
  $body .= '<h2 style="color:#1a73e8;margin-bottom:0.25rem">Registration Approved — Next Step: Payment</h2>';
  $body .= '<p style="margin-top:0.5rem">Hi ' . htmlspecialchars($reg['first_name'] ?? '') . ',</p>';
  $body .= '<p>Thank you — your registration has been reviewed and accepted. To complete your registration and secure your place, please complete the payment using the secure link below.</p>';
  $body .= '<p style="text-align:center;margin:20px 0"><a href="' . htmlspecialchars($link) . '" style="background-color:#1a73e8;color:#ffffff;padding:12px 18px;border-radius:6px;text-decoration:none;display:inline-block;font-weight:600">Complete Payment</a></p>';
  $body .= '<p style="font-size:0.95rem;color:#555">If the button above does not work, copy and paste this URL into your browser:</p>';
  $body .= '<p style="word-break:break-all;"><a href="' . htmlspecialchars($link) . '">' . htmlspecialchars($link) . '</a></p>';
  $body .= '<p style="margin-top:0.5rem">Reference: <strong>' . htmlspecialchars($reference) . '</strong></p>';
  $body .= '<hr style="border:none;border-top:1px solid #eee;margin:18px 0">';
  $body .= '<p style="font-size:0.9rem;color:#666">If you have questions, reply to this email or contact our support team. Best regards,<br><strong>HIGH Q SOLID ACADEMY</strong></p>';
  $body .= '</div>';
        $emailSent = (bool) sendEmail($reg['email'], $subject, $body);
        if (!$emailSent) {
          // log a note to the academic_confirm_errors log for admin debugging
          try {
            $logDir = __DIR__ . '/../../storage/logs'; if (!is_dir($logDir)) @mkdir($logDir, 0755, true);
            @file_put_contents($logDir . '/academic_confirm_errors.log', "[" . date('Y-m-d H:i:s') . "] create_payment_link email failed to send to: " . ($reg['email'] ?? 'unknown') . "\n", FILE_APPEND | LOCK_EX);
          } catch (Throwable $_) { }
        }
      } catch (Throwable $me) {
        try { $logDir = __DIR__ . '/../../storage/logs'; if (!is_dir($logDir)) @mkdir($logDir, 0755, true); @file_put_contents($logDir . '/academic_confirm_errors.log', "[" . date('Y-m-d H:i:s') . "] create_payment_link sendEmail exception: " . $me->getMessage() . "\n" . $me->getTraceAsString() . "\n", FILE_APPEND | LOCK_EX); } catch (Throwable $_) {}
        $emailSent = false;
      }
    }

    echo json_encode(['success'=>true,'link'=>$link,'reference'=>$reference,'email_sent'=>(bool)$emailSent,'email'=>$reg['email'] ?? null,'student'=>['first_name'=>$reg['first_name'] ?? null,'last_name'=>$reg['last_name'] ?? null,'email'=>$reg['email'] ?? null]]);
    exit;
  } catch (Throwable $e) {
    // Detailed logging to academic_confirm_errors.log to help debugging
    try {
      $logDir = __DIR__ . '/../../storage/logs';
      if (!is_dir($logDir)) @mkdir($logDir, 0755, true);
      $logFile = $logDir . '/academic_confirm_errors.log';
      $msg = "[" . date('Y-m-d H:i:s') . "] create_payment_link error: " . $e->getMessage() . " -- in " . $e->getFile() . ":" . $e->getLine() . "\n" . $e->getTraceAsString() . "\n\n";
      @file_put_contents($logFile, $msg, FILE_APPEND | LOCK_EX);
    } catch (Throwable $ex) { /* ignore logging errors */ }

    $resp = ['success'=>false,'error'=>'Server error'];
    $debugRequested = (!empty($_GET['debug']) && $_GET['debug'] === '1') || (!empty($_POST['debug']) && $_POST['debug'] === '1');
    if ($debugRequested) {
      $resp['error'] = $e->getMessage();
      $resp['trace'] = $e->getTraceAsString();
    }
    echo json_encode($resp);
    exit;
  }
}

// Handle confirm_registration when sent either as POST action or as POST to URL with ?action=confirm_registration
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ((isset($_POST['action']) && $_POST['action'] === 'confirm_registration') || (isset($_GET['action']) && $_GET['action'] === 'confirm_registration'))) {
  $id = intval($_POST['id'] ?? $_GET['id'] ?? 0);

  $pdo->beginTransaction();
  try {
    // Fetch registration (with email and user fallback)
    $stmt = $pdo->prepare("\n            SELECT sr.*, COALESCE(sr.email, u.email) AS email, u.id AS user_id\n            FROM student_registrations sr\n            LEFT JOIN users u ON u.id = sr.user_id\n            WHERE sr.id = ?\n            LIMIT 1\n        ");
    $stmt->execute([$id]);
    $reg = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$reg) {
      throw new Exception("Registration not found");
    }

    $email = $reg['email'];
    $studentId = $reg['user_id'] ?: null;

    // Generate reference + insert payment row
    $ref = uniqid("pay_");
    $amount = 20000; // adjust amount as needed
    // pick method from POST (admin UI) or fall back to bank_transfer
    $method = $_POST['method'] ?? 'bank_transfer';
    if (in_array(strtolower($method), ['bank', 'bank_transfer', 'transfer'])) $method = 'bank_transfer';
    if (strtolower($method) === 'paystack') $method = 'paystack';

  $paymentId = insertPaymentWithFallback($pdo, $studentId, $amount, $method, $ref);

    // Use canonical helper to construct the payment link so .env APP_URL and subfolder installs are respected
    $paymentLink = app_url('pay/' . urlencode($ref));

    // Send email
    $email_sent = false;
    if ($email) {
      $subject = "Payment Link for Your Registration";
  $message = '<div style="font-family:Arial,Helvetica,sans-serif;color:#222;line-height:1.45">';
  $message .= '<h2 style="color:#1a73e8;margin-bottom:0.25rem">Your Registration Has Been Accepted</h2>';
  $message .= '<p style="margin-top:0.5rem">Hi ' . htmlspecialchars($reg['first_name']) . ',</p>';
  $message .= '<p>Congratulations, nding pageyour registration has been reviewed and accepted. To finalize your enrollment, please complete the payment using the secure link below.</p>';
  $message .= '<p style="text-align:center;margin:20px 0"><a href="' . htmlspecialchars($paymentLink) . '" style="background-color:#1a73e8;color:#ffffff;padding:12px 18px;border-radius:6px;text-decoration:none;display:inline-block;font-weight:600">Complete Your Payment</a></p>';
  $message .= '<p style="font-size:0.95rem;color:#555">If the button does not work, use this URL:</p>';
  $message .= '<p style="word-break:break-all;"><a href="' . htmlspecialchars($paymentLink) . '">' . htmlspecialchars($paymentLink) . '</a></p>';
  $message .= '<p style="margin-top:0.5rem">Reference: <strong>' . htmlspecialchars($ref) . '</strong></p>';
  $message .= '<hr style="border:none;border-top:1px solid #eee;margin:18px 0">';
  $message .= '<p style="font-size:0.9rem;color:#666">If you need assistance, reply to this message or contact support.</p>';
  $message .= '<p style="margin-top:6px"><strong>Best regards,<br>HIGH Q SOLID ACADEMY</strong></p>';
  $message .= '</div>';
      $email_sent = sendEmail($email, $subject, $message);
    }

    // Update status to indicate registration has been confirmed/awaiting payment
    $pdo->prepare("UPDATE student_registrations SET status = 'awaiting_payment' WHERE id = ?")->execute([$reg['id']]);

  $pdo->commit();

  header('Content-Type: application/json');
  echo json_encode([
    'success' => true,
    'message' => 'Payment link created.',
    'reference' => $ref,
    'email' => $email,
    'email_sent' => (bool)$email_sent,
  ]);
  } catch (Exception $e) {
    $pdo->rollBack();
  header('Content-Type: application/json');
  // Log the exception to help debugging (safe append-only log)
  try {
    $logDir = __DIR__ . '/../../storage/logs';
    if (!is_dir($logDir)) @mkdir($logDir, 0755, true);
    $logFile = $logDir . '/academic_confirm_errors.log';
    $msg = "[" . date('Y-m-d H:i:s') . "] Confirm registration error: " . $e->getMessage() . " -- in " . $e->getFile() . ":" . $e->getLine() . "\n" . $e->getTraceAsString() . "\n\n";
    @file_put_contents($logFile, $msg, FILE_APPEND | LOCK_EX);
  } catch (Throwable $ex) { /* ignore logging errors */ }

  $resp = ['success' => false, 'error' => 'Server error'];
  // If explicit debug requested via GET or POST, include the exception message and trace
  $debugRequested = (!empty($_GET['debug']) && $_GET['debug'] === '1') || (!empty($_POST['debug']) && $_POST['debug'] === '1');
  if ($debugRequested) {
    $resp['error'] = $e->getMessage();
    $resp['trace'] = $e->getTraceAsString();
  }
  echo json_encode($resp);
  }
  exit;
}

// Support POST view_registration for AJAX clients
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'view_registration') {
  $id = intval($_POST['id'] ?? 0);

  $stmt = $pdo->prepare("
    SELECT sr.*, COALESCE(sr.email, u.email) AS email, u.name AS user_name
    FROM student_registrations sr
    LEFT JOIN users u ON u.id = sr.user_id
    WHERE sr.id = ?
    LIMIT 1
  ");
  $stmt->execute([$id]);
  $s = $stmt->fetch(PDO::FETCH_ASSOC);

  header('Content-Type: application/json');
  if (!$s) {
    // Fallback: try post_utme_registrations and map fields to the expected view shape
    $stmt2 = $pdo->prepare('SELECT * FROM post_utme_registrations WHERE id = ? LIMIT 1');
    $stmt2->execute([$id]);
    $p = $stmt2->fetch(PDO::FETCH_ASSOC);
    if (!$p) { echo json_encode(['success' => false, 'error' => 'Registration not found']); exit; }

    echo json_encode([
      'success' => true,
      'data' => [
        'id'                     => $p['id'] ?? null,
        'surname'                => $p['surname'] ?? null,
        'first_name'             => $p['first_name'] ?? null,
        'last_name'              => $p['surname'] ?? ($p['last_name'] ?? null),
        'other_names'            => $p['other_names'] ?? null,
        'email'                  => $p['email'] ?? null,
        'phone'                  => $p['phone'] ?? null,
        'date_of_birth'          => $p['date_of_birth'] ?? $p['date_of_birth_post'] ?? null,
        'gender'                 => $p['gender'] ?? null,
        'marital_status'         => $p['marital_status'] ?? null,
        'home_address'           => $p['address'] ?? $p['home_address'] ?? null,
        'state_of_origin'        => $p['state_of_origin'] ?? null,
        'local_government'       => $p['local_government'] ?? $p['lga'] ?? null,
        'nin'                    => $p['nin'] ?? null,
        'profile_code'           => $p['profile_code'] ?? $p['jamb_profile_code'] ?? null,
        'registration_type'      => $p['registration_type'] ?? 'post_utme',
        'previous_education'     => ($p['secondary_school'] ?? null),
        'academic_goals'         => ($p['course_first_choice'] ?? null),
        'sponsor_name'           => $p['sponsor_name'] ?? null,
        'sponsor_phone'          => $p['sponsor_phone'] ?? null,
        'sponsor_address'        => $p['sponsor_address'] ?? null,
        'emergency_contact_name' => $p['next_of_kin_name'] ?? null,
        'emergency_contact_phone'=> $p['next_of_kin_phone'] ?? null,
        'emergency_relationship' => $p['next_of_kin_relationship'] ?? null,
        'passport_photo'         => $p['passport_photo'] ?? $p['passport_path'] ?? null,
        'status'                 => $p['status'] ?? null,
        'created_at'             => $p['created_at'] ?? null,
      ]
    ]);
    exit;
  }

  // Full data response with all important fields
  echo json_encode([
    'success' => true,
    'data' => [
      'id'                     => $s['id'] ?? null,
      'surname'                => $s['surname'] ?? $s['last_name'] ?? null,
      'first_name'             => $s['first_name'] ?? null,
      'last_name'              => $s['last_name'] ?? null,
      'other_names'            => $s['other_names'] ?? $s['middle_name'] ?? null,
      'email'                  => $s['email'] ?? null,
      'phone'                  => $s['phone'] ?? $s['phone_number'] ?? null,
      'date_of_birth'          => $s['date_of_birth'] ?? null,
      'gender'                 => $s['gender'] ?? null,
      'marital_status'         => $s['marital_status'] ?? null,
      'home_address'           => $s['home_address'] ?? $s['address'] ?? null,
      'state_of_origin'        => $s['state_of_origin'] ?? null,
      'local_government'       => $s['local_government'] ?? $s['lga'] ?? null,
      'nin'                    => $s['nin'] ?? null,
      'profile_code'           => $s['profile_code'] ?? $s['jamb_profile_code'] ?? null,
      'registration_type'      => $s['registration_type'] ?? $s['program_type'] ?? null,
      'exam_type'              => $s['exam_type'] ?? null,
      'exam_year'              => $s['exam_year'] ?? null,
      'previous_education'     => $s['previous_education'] ?? null,
      'academic_goals'         => $s['academic_goals'] ?? null,
      'sponsor_name'           => $s['sponsor_name'] ?? null,
      'sponsor_phone'          => $s['sponsor_phone'] ?? null,
      'sponsor_address'        => $s['sponsor_address'] ?? null,
      'emergency_contact_name' => $s['emergency_contact_name'] ?? $s['next_of_kin_name'] ?? null,
      'emergency_contact_phone'=> $s['emergency_contact_phone'] ?? $s['next_of_kin_phone'] ?? null,
      'emergency_relationship' => $s['emergency_relationship'] ?? $s['next_of_kin_relationship'] ?? null,
      'passport_photo'         => $s['passport_photo'] ?? $s['passport_path'] ?? null,
      'status'                 => $s['status'] ?? null,
      'created_at'             => $s['created_at'] ?? null,
    ]
  ]);
  exit;
}

// Handle POST actions (activate/deactivate/delete)
// Handle AJAX GET view of a registration
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'view' && isset($_GET['id'])) {
  $id = (int)$_GET['id'];
  $stmt = $pdo->prepare("SELECT sr.*, u.email, u.name AS user_name FROM student_registrations sr LEFT JOIN users u ON u.id = sr.user_id WHERE sr.id = ? LIMIT 1");
  $stmt->execute([$id]); $s = $stmt->fetch(PDO::FETCH_ASSOC);
  header('Content-Type: application/json');
  if (!$s) {
    // Try post_utme_registrations as a fallback
    $stmt2 = $pdo->prepare('SELECT * FROM post_utme_registrations WHERE id = ? LIMIT 1');
    $stmt2->execute([$id]);
    $s2 = $stmt2->fetch(PDO::FETCH_ASSOC);
    if (!$s2) { echo json_encode(['error'=>'Not found']); exit; }
    // return post-UTME record
    echo json_encode($s2); exit;
  }
  echo json_encode($s); exit;
}

// Export all registrations to CSV
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'export_csv') {
  requirePermission('academic');
  
  // Determine source table
  $source = $_GET['source'] ?? '';
  $table = 'student_registrations';
  
  if ($source === 'postutme') {
    $table = 'post_utme_registrations';
  } elseif ($source === 'universal') {
    $table = 'universal_registrations';
  }
  
  try {
    // Check if table exists
    $check = $pdo->query("SHOW TABLES LIKE '$table'")->fetch();
    if (!$check) {
      header('Content-Type: application/json');
      echo json_encode(['error' => 'Table not found']); 
      exit;
    }
    
    // Fetch all registrations
    $stmt = $pdo->query("SELECT * FROM $table ORDER BY created_at DESC");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($rows)) {
      header('Content-Type: application/json');
      echo json_encode(['error' => 'No registrations found']); 
      exit;
    }
    
    // Set CSV headers
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="registrations_' . $source . '_' . date('Y-m-d_His') . '.csv"');
    
    $output = fopen('php://output', 'w');
    
    // Define comprehensive columns
    $columns = [
      'ID', 'Surname', 'Other Names', 'Email', 'Phone', 'Gender', 'Date of Birth',
      'Marital Status', 'NIN', 'State of Origin', 'Local Government', 'Home Address',
      'Profile Code', 'Exam Type', 'Exam Year', 'Course/Academic Goals', 'Previous Education',
      'Sponsor Name', 'Sponsor Phone', 'Sponsor Address',
      'Next of Kin Name', 'Next of Kin Phone', 'Next of Kin Address',
      'Passport Photo Path', 'Status', 'Created At'
    ];
    
    // Write CSV header
    fputcsv($output, $columns);
    
    // Write data rows
    foreach ($rows as $row) {
      $csvRow = [
        $row['id'] ?? '',
        $row['surname'] ?? $row['last_name'] ?? '',
        $row['other_names'] ?? $row['first_name'] ?? '',
        $row['email'] ?? '',
        $row['phone'] ?? $row['phone_number'] ?? '',
        $row['gender'] ?? '',
        $row['date_of_birth'] ?? '',
        $row['marital_status'] ?? '',
        $row['nin'] ?? '',
        $row['state_of_origin'] ?? '',
        $row['local_government'] ?? $row['lga'] ?? '',
        $row['home_address'] ?? $row['address'] ?? '',
        $row['profile_code'] ?? $row['jamb_profile_code'] ?? '',
        $row['exam_type'] ?? '',
        $row['exam_year'] ?? '',
        $row['academic_goals'] ?? $row['course_of_study'] ?? $row['course_first_choice'] ?? '',
        $row['previous_education'] ?? $row['secondary_school'] ?? '',
        $row['sponsor_name'] ?? '',
        $row['sponsor_phone'] ?? '',
        $row['sponsor_address'] ?? '',
        $row['next_of_kin_name'] ?? $row['emergency_contact_name'] ?? '',
        $row['next_of_kin_phone'] ?? $row['emergency_contact_phone'] ?? '',
        $row['next_of_kin_address'] ?? '',
        $row['passport_photo'] ?? $row['passport_path'] ?? '',
        $row['status'] ?? '',
        $row['created_at'] ?? ''
      ];
      fputcsv($output, $csvRow);
    }
    
    fclose($output);
    exit;
    
  } catch (Throwable $e) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Export failed: ' . $e->getMessage()]);
    exit;
  }
}

// Export single registration to PDF-like format (HTML for print)
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'export_single' && isset($_GET['id'])) {
  requirePermission('academic');
  $id = (int)$_GET['id'];
  
  // Try student_registrations first
  $stmt = $pdo->prepare("SELECT * FROM student_registrations WHERE id = ? LIMIT 1");
  $stmt->execute([$id]);
  $s = $stmt->fetch(PDO::FETCH_ASSOC);
  
  if (!$s) {
    // Try post_utme_registrations
    $stmt2 = $pdo->prepare('SELECT * FROM post_utme_registrations WHERE id = ? LIMIT 1');
    $stmt2->execute([$id]);
    $s = $stmt2->fetch(PDO::FETCH_ASSOC);
  }
  
  if (!$s) {
    // Try universal_registrations
    $stmt3 = $pdo->prepare('SELECT * FROM universal_registrations WHERE id = ? LIMIT 1');
    $stmt3->execute([$id]);
    $s = $stmt3->fetch(PDO::FETCH_ASSOC);
  }
  
  if (!$s) {
    echo '<h1>Registration not found</h1>';
    exit;
  }
  
  // Get passport path
  $passportPath = $s['passport_photo'] ?? $s['passport_path'] ?? '';
  $fullName = trim(($s['surname'] ?? $s['first_name'] ?? '') . ' ' . ($s['other_names'] ?? $s['last_name'] ?? ''));
  
  ?>
  <!DOCTYPE html>
  <html lang="en">
  <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registration Details - <?= htmlspecialchars($fullName) ?></title>
    <style>
      * { margin: 0; padding: 0; box-sizing: border-box; }
      body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f8fafc; padding: 20px; }
      .container { max-width: 800px; margin: 0 auto; background: white; border-radius: 16px; box-shadow: 0 4px 20px rgba(0,0,0,0.1); overflow: hidden; }
      .header { background: linear-gradient(135deg, #0b1a2c 0%, #1e3a5f 100%); color: white; padding: 30px; text-align: center; }
      .header h1 { font-size: 1.75rem; margin-bottom: 5px; }
      .header p { opacity: 0.8; }
      .passport-section { text-align: center; padding: 20px; border-bottom: 1px solid #e2e8f0; }
      .passport-section img { width: 150px; height: 150px; object-fit: cover; border-radius: 12px; border: 4px solid #ffd600; box-shadow: 0 4px 12px rgba(0,0,0,0.15); }
      .section { padding: 20px 30px; border-bottom: 1px solid #e2e8f0; }
      .section:last-child { border-bottom: none; }
      .section-title { font-size: 0.85rem; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 15px; display: flex; align-items: center; gap: 8px; }
      .section-title::before { content: ''; width: 4px; height: 20px; background: #ffd600; border-radius: 2px; }
      .field-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px; }
      .field { }
      .field-label { font-size: 0.75rem; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 4px; }
      .field-value { font-size: 0.95rem; color: #1e293b; font-weight: 500; }
      .field-full { grid-column: 1 / -1; }
      .status-badge { display: inline-block; padding: 6px 12px; border-radius: 9999px; font-size: 0.75rem; font-weight: 700; text-transform: uppercase; }
      .status-confirmed { background: #d1fae5; color: #065f46; }
      .status-pending { background: #fef3c7; color: #92400e; }
      .status-rejected { background: #fee2e2; color: #991b1b; }
      .print-btn { position: fixed; bottom: 20px; right: 20px; background: #0b1a2c; color: white; border: none; padding: 12px 24px; border-radius: 8px; cursor: pointer; font-weight: 600; box-shadow: 0 4px 12px rgba(0,0,0,0.2); }
      .print-btn:hover { background: #1e3a5f; }
      @media print {
        body { background: white; padding: 0; }
        .container { box-shadow: none; }
        .print-btn { display: none; }
      }
    </style>
  </head>
  <body>
    <div class="container">
      <div class="header">
        <h1>HIGH Q SOLID ACADEMY</h1>
        <p>Student Registration Details</p>
      </div>
      
      <?php if ($passportPath): ?>
      <div class="passport-section">
        <img src="<?= htmlspecialchars($passportPath) ?>" alt="Passport Photo" onerror="this.style.display='none'">
      </div>
      <?php endif; ?>
      
      <div class="section">
        <div class="section-title">Personal Information</div>
        <div class="field-grid">
          <div class="field">
            <div class="field-label">Surname</div>
            <div class="field-value"><?= htmlspecialchars($s['surname'] ?? $s['last_name'] ?? '-') ?></div>
          </div>
          <div class="field">
            <div class="field-label">Other Names</div>
            <div class="field-value"><?= htmlspecialchars($s['other_names'] ?? $s['first_name'] ?? '-') ?></div>
          </div>
          <div class="field">
            <div class="field-label">Email</div>
            <div class="field-value"><?= htmlspecialchars($s['email'] ?? '-') ?></div>
          </div>
          <div class="field">
            <div class="field-label">Phone</div>
            <div class="field-value"><?= htmlspecialchars($s['phone'] ?? $s['phone_number'] ?? '-') ?></div>
          </div>
          <div class="field">
            <div class="field-label">Gender</div>
            <div class="field-value"><?= htmlspecialchars($s['gender'] ?? '-') ?></div>
          </div>
          <div class="field">
            <div class="field-label">Date of Birth</div>
            <div class="field-value"><?= htmlspecialchars($s['date_of_birth'] ?? '-') ?></div>
          </div>
          <div class="field">
            <div class="field-label">Marital Status</div>
            <div class="field-value"><?= htmlspecialchars($s['marital_status'] ?? '-') ?></div>
          </div>
          <div class="field">
            <div class="field-label">NIN</div>
            <div class="field-value"><?= htmlspecialchars($s['nin'] ?? '-') ?></div>
          </div>
        </div>
      </div>
      
      <div class="section">
        <div class="section-title">Location Details</div>
        <div class="field-grid">
          <div class="field">
            <div class="field-label">State of Origin</div>
            <div class="field-value"><?= htmlspecialchars($s['state_of_origin'] ?? '-') ?></div>
          </div>
          <div class="field">
            <div class="field-label">Local Government</div>
            <div class="field-value"><?= htmlspecialchars($s['local_government'] ?? $s['lga'] ?? '-') ?></div>
          </div>
          <div class="field field-full">
            <div class="field-label">Home Address</div>
            <div class="field-value"><?= htmlspecialchars($s['home_address'] ?? $s['address'] ?? '-') ?></div>
          </div>
        </div>
      </div>
      
      <div class="section">
        <div class="section-title">Academic Information</div>
        <div class="field-grid">
          <div class="field">
            <div class="field-label">Profile Code</div>
            <div class="field-value"><?= htmlspecialchars($s['profile_code'] ?? $s['jamb_profile_code'] ?? '-') ?></div>
          </div>
          <div class="field">
            <div class="field-label">Exam Type</div>
            <div class="field-value"><?= htmlspecialchars($s['exam_type'] ?? '-') ?></div>
          </div>
          <div class="field">
            <div class="field-label">Exam Year</div>
            <div class="field-value"><?= htmlspecialchars($s['exam_year'] ?? '-') ?></div>
          </div>
          <div class="field">
            <div class="field-label">Course / Academic Goals</div>
            <div class="field-value"><?= htmlspecialchars($s['academic_goals'] ?? $s['course_of_study'] ?? $s['course_first_choice'] ?? '-') ?></div>
          </div>
          <div class="field field-full">
            <div class="field-label">Previous Education</div>
            <div class="field-value"><?= htmlspecialchars($s['previous_education'] ?? $s['secondary_school'] ?? '-') ?></div>
          </div>
        </div>
      </div>
      
      <div class="section">
        <div class="section-title">Sponsor / Guardian</div>
        <div class="field-grid">
          <div class="field">
            <div class="field-label">Name</div>
            <div class="field-value"><?= htmlspecialchars($s['sponsor_name'] ?? $s['guardian_name'] ?? '-') ?></div>
          </div>
          <div class="field">
            <div class="field-label">Phone</div>
            <div class="field-value"><?= htmlspecialchars($s['sponsor_phone'] ?? $s['guardian_phone'] ?? '-') ?></div>
          </div>
          <div class="field field-full">
            <div class="field-label">Address</div>
            <div class="field-value"><?= htmlspecialchars($s['sponsor_address'] ?? $s['guardian_address'] ?? '-') ?></div>
          </div>
        </div>
      </div>
      
      <div class="section">
        <div class="section-title">Next of Kin</div>
        <div class="field-grid">
          <div class="field">
            <div class="field-label">Name</div>
            <div class="field-value"><?= htmlspecialchars($s['next_of_kin_name'] ?? $s['emergency_contact_name'] ?? '-') ?></div>
          </div>
          <div class="field">
            <div class="field-label">Phone</div>
            <div class="field-value"><?= htmlspecialchars($s['next_of_kin_phone'] ?? $s['emergency_contact_phone'] ?? '-') ?></div>
          </div>
          <div class="field field-full">
            <div class="field-label">Address</div>
            <div class="field-value"><?= htmlspecialchars($s['next_of_kin_address'] ?? '-') ?></div>
          </div>
        </div>
      </div>
      
      <div class="section">
        <div class="section-title">Registration Status</div>
        <div class="field-grid">
          <div class="field">
            <div class="field-label">Status</div>
            <div class="field-value">
              <?php 
                $status = strtolower($s['status'] ?? 'pending');
                $statusClass = $status === 'confirmed' ? 'status-confirmed' : ($status === 'rejected' ? 'status-rejected' : 'status-pending');
              ?>
              <span class="status-badge <?= $statusClass ?>"><?= htmlspecialchars(ucfirst($status)) ?></span>
            </div>
          </div>
          <div class="field">
            <div class="field-label">Registration Date</div>
            <div class="field-value"><?= htmlspecialchars($s['created_at'] ?? '-') ?></div>
          </div>
        </div>
      </div>
    </div>
    
    <button class="print-btn" onclick="window.print()">
      <span>🖨️ Print</span>
    </button>
  </body>
  </html>
  <?php
  exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action']) && isset($_GET['id'])) {
    $action = $_GET['action'];
    $id = (int)$_GET['id'];
    $token = $_POST['csrf_token'] ?? '';
  if (!verifyToken('academic_form', $token)) {
    header('Location: ' . admin_url('index.php?pages=academic')); exit;
  }

    $currentUserId = $_SESSION['user']['id'];

    // Protect main admin and yourself from destructive actions
    if ($id === 1 || $id === $currentUserId) {
  header('Location: ' . admin_url('index.php?pages=academic')); exit;
  }

    if ($action === 'deactivate') {
        $stmt = $pdo->prepare('UPDATE users SET is_active = 2, updated_at = NOW() WHERE id = ?');
        $stmt->execute([$id]);
  logAction($pdo, $currentUserId, 'student_deactivate', ['student_id'=>$id]);
  header('Location: ' . admin_url('index.php?pages=academic')); exit;
    }

    if ($action === 'activate') {
        $stmt = $pdo->prepare('UPDATE users SET is_active = 1, updated_at = NOW() WHERE id = ?');
        $stmt->execute([$id]);
  logAction($pdo, $currentUserId, 'student_activate', ['student_id'=>$id]);
  header('Location: ' . admin_url('index.php?pages=academic')); exit;
    }

      // Delete a post-UTME registration row (separate action) - handle before generic delete
      if ($action === 'delete_postutme') {
        try {
          $del = $pdo->prepare('DELETE FROM post_utme_registrations WHERE id = ?');
          $del->execute([$id]);
          logAction($pdo, $currentUserId, 'postutme_delete', ['postutme_id'=>$id]);
        } catch (Throwable $e) {
          // ignore errors
        }
        header('Location: ' . admin_url('pages/academic.php')); exit;
      }

  if ($action === 'delete') {
    // If this id exists in student_registrations, delete that registration. Otherwise treat as users delete.
    $checkReg = $pdo->prepare('SELECT id FROM student_registrations WHERE id = ? LIMIT 1');
    $checkReg->execute([$id]);
    $regFound = $checkReg->fetch(PDO::FETCH_ASSOC);
      if ($regFound) {
      $del = $pdo->prepare('DELETE FROM student_registrations WHERE id = ?');
      $del->execute([$id]);
      logAction($pdo, $currentUserId, 'registration_delete', ['registration_id'=>$id]);
  header('Location: ' . admin_url('index.php?pages=academic')); exit;
    }
  
    // Delete a post-UTME registration row
    if ($action === 'delete_postutme') {
      try {
        $del = $pdo->prepare('DELETE FROM post_utme_registrations WHERE id = ?');
        $del->execute([$id]);
        logAction($pdo, $currentUserId, 'postutme_delete', ['postutme_id'=>$id]);
      } catch (Throwable $e) {
        // swallow and continue to redirect
      }
      header('Location: ' . admin_url('pages/academic.php')); exit;
    }

    // Fallback: Soft-delete user record (legacy path)
    try {
      $stmt = $pdo->prepare('UPDATE users SET is_active = 3, updated_at = NOW() WHERE id = ?');
      $stmt->execute([$id]);
      logAction($pdo, $currentUserId, 'student_delete', ['student_id'=>$id]);
    } catch (Exception $e) {
      // Fallback to hard delete
      $stmt = $pdo->prepare('DELETE FROM users WHERE id = ?');
      $stmt->execute([$id]);
      logAction($pdo, $currentUserId, 'student_delete_hard', ['student_id'=>$id]);
    }
  header('Location: ' . admin_url('pages/academic.php')); exit;
  }

  // Custom: send message/approve flow from modal
  if ($action === 'send_message') {
    $message = trim($_POST['message'] ?? '');
    $activate = isset($_POST['activate']) ? 1 : 0;

    // load student
    $sstmt = $pdo->prepare('SELECT * FROM users WHERE id = ? LIMIT 1');
    $sstmt->execute([$id]);
    $student = $sstmt->fetch(PDO::FETCH_ASSOC);
    if ($student) {
      if ($activate) {
        $ust = $pdo->prepare('UPDATE users SET is_active = 1, updated_at = NOW() WHERE id = ?');
        $ust->execute([$id]);
        logAction($pdo, $currentUserId, 'student_activate_via_modal', ['student_id'=>$id]);
      }

      // send email using existing helper if available
      if (function_exists('sendEmail') && filter_var($student['email'], FILTER_VALIDATE_EMAIL)) {
        $subject = 'Message from HIGH Q admin';
        $body = "Hello " . htmlspecialchars($student['name']) . ",\n\n" . $message . "\n\nRegards,\nHIGH Q Team";
        try { sendEmail($student['email'], $subject, $body); logAction($pdo, $currentUserId, 'student_message_sent', ['student_id'=>$id]); } catch (Exception $e) { /* ignore send errors */ }
      }
    }
  header('Location: ' . admin_url('pages/academic.php')); exit;
  }

  // Confirm registration (admin) - send notification
  if ($action === 'confirm_registration') {
    $stmt = $pdo->prepare('SELECT * FROM student_registrations WHERE id = ? LIMIT 1'); $stmt->execute([$id]); $reg = $stmt->fetch(PDO::FETCH_ASSOC);
    $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']);
    if (!$reg) {
  if ($isAjax) { echo json_encode(['status'=>'error','message'=>'Not found']); exit; }
  header('Location: ' . admin_url('pages/academic.php')); exit;
    }

    // If already confirmed, return meaningful JSON error for AJAX or redirect with flash
    if (isset($reg['status']) && strtolower($reg['status']) === 'confirmed') {
  if ($isAjax) { echo json_encode(['status'=>'error','message'=>'Registration already confirmed']); exit; }
  setFlash('error','Registration already confirmed'); header('Location: ' . admin_url('pages/academic.php')); exit;
    }

    // Transaction: mark confirmed and optionally create payment and send reference
    try {
      $pdo->beginTransaction();
      $upd = $pdo->prepare('UPDATE student_registrations SET status = ?, updated_at = NOW() WHERE id = ?'); $upd->execute(['confirmed', $id]);
      logAction($pdo, $currentUserId, 'confirm_registration', ['registration_id'=>$id]);

      // Optional payment creation: admin may include create_payment=1 and amount in POST (AJAX)
      if (!empty($_POST['create_payment']) && !empty($_POST['amount'])) {
        $amount = floatval($_POST['amount']);
        $method = $_POST['method'] ?? 'bank';
        // create payment placeholder and send reference to registrant email
        $ref = 'REG-' . date('YmdHis') . '-' . substr(bin2hex(random_bytes(3)),0,6);
        $ins = $pdo->prepare('INSERT INTO payments (student_id, amount, payment_method, reference, status, created_at) VALUES (NULL, ?, ?, ?, "pending", NOW())');
        $ins->execute([$amount, $method, $ref]);
        $paymentId = $pdo->lastInsertId();
        logAction($pdo, $currentUserId, 'create_payment_for_registration', ['registration_id'=>$id,'payment_id'=>$paymentId,'reference'=>$ref,'amount'=>$amount]);

          // send email to registrant with link to payments_wait (if email present)
          $emailSent = false;
          if (!empty($reg['email']) && filter_var($reg['email'], FILTER_VALIDATE_EMAIL) && function_exists('sendEmail')) {
            // Try to fetch site settings for branding (non-essential)
            $siteName = 'HIGH Q'; $logoUrl = '';$contactEmail = '';$contactPhone = '';
            try {
              $s = $pdo->query('SELECT site_name, logo_url, contact_email, contact_phone FROM site_settings LIMIT 1')->fetch(PDO::FETCH_ASSOC);
              if (!empty($s['site_name'])) $siteName = $s['site_name'];
              if (!empty($s['logo_url'])) $logoUrl = $s['logo_url'];
              if (!empty($s['contact_email'])) $contactEmail = $s['contact_email'];
              if (!empty($s['contact_phone'])) $contactPhone = $s['contact_phone'];
            } catch (Throwable $e) { /* ignore */ }

            $subject = $siteName . ' — Payment instructions for your registration';
            $proto = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
            // build link relative to public folder (best-effort)
            $base = $proto . '://' . $host;
            // Prefer app_url() so APP_URL or computed base includes any subdirectory
            // Prefer the canonical friendly pay route so APP_URL and subfolder installs are honoured
            $link = app_url('pay/' . urlencode($ref));

            // Branded HTML message
            $body = '<!doctype html><html><head><meta charset="utf-8"><title>' . htmlspecialchars($subject) . '</title>';
            $body .= '<style>body{font-family:Arial,Helvetica,sans-serif;color:#333} .container{max-width:640px;margin:0 auto;padding:20px} .btn{display:inline-block;padding:10px 16px;background:#d62828;color:#fff;border-radius:6px;text-decoration:none}</style>';
            $body .= '</head><body><div class="container">';
            if ($logoUrl) $body .= '<div style="margin-bottom:12px;"><img src="' . htmlspecialchars($logoUrl) . '" alt="' . htmlspecialchars($siteName) . '" style="max-height:60px"></div>';
            $body .= '<h2 style="color:#111">Hello ' . htmlspecialchars(trim($reg['first_name'] . ' ' . ($reg['last_name'] ?? ''))) . ',</h2>';
            $body .= '<p>Your registration has been approved by ' . htmlspecialchars($siteName) . ' and requires payment to complete enrollment.</p>';
            $body .= '<p><strong>Amount:</strong> ₦' . number_format($amount,2) . '</p>';
            $body .= '<p><strong>Payment reference:</strong> <code>' . htmlspecialchars($ref) . '</code></p>';
            $body .= '<p><a class="btn" href="' . htmlspecialchars($link) . '">Complete payment now</a></p>';
            $body .= '<p style="color:#666;font-size:13px;margin-top:18px">If the button above does not work, copy and paste this link into your browser: <br>' . htmlspecialchars($link) . '</p>';
            if ($contactEmail || $contactPhone) {
              $body .= '<hr><p style="color:#666;font-size:13px">Need help? Contact us at ' . ($contactEmail ? htmlspecialchars($contactEmail) : '') . ($contactPhone ? ' / ' . htmlspecialchars($contactPhone) : '') . '</p>';
            }
            $body .= '<p style="margin-top:20px">Thanks,<br>' . htmlspecialchars($siteName) . ' team</p>';
            $body .= '</div></body></html>';

            try {
              $emailSent = (bool) sendEmail($reg['email'], $subject, $body);
            } catch (Throwable $e) { $emailSent = false; }
          }
      }

      $pdo->commit();
      if ($isAjax) {
        $resp = ['status'=>'ok','message'=>'Confirmed', 'email_sent'=>!empty($emailSent), 'reference'=> $ref ?? null, 'amount'=> isset($amount) ? $amount : null, 'email'=> $reg['email'] ?? null];
        echo json_encode($resp); exit;
      }
  header('Location: ' . admin_url('pages/academic.php')); exit;
    } catch (Exception $e) {
      if ($pdo->inTransaction()) $pdo->rollBack();
      if ($isAjax) { echo json_encode(['status'=>'error','message'=>'Server error']); exit; }
  setFlash('error','Failed to confirm registration'); header('Location: ' . admin_url('pages/academic.php')); exit;
    }
  }

  // Reject registration with optional reason
  if ($action === 'reject_registration') {
    $reason = trim($_POST['reason'] ?? '');
    $stmt = $pdo->prepare('SELECT * FROM student_registrations WHERE id = ? LIMIT 1'); $stmt->execute([$id]); $reg = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($reg) {
      $upd = $pdo->prepare('UPDATE student_registrations SET status = ? WHERE id = ?'); $upd->execute(['rejected', $id]);
      logAction($pdo, $currentUserId, 'reject_registration', ['registration_id'=>$id, 'reason'=>$reason]);
      $emailSent = false;
      if (!empty($reg['email']) && filter_var($reg['email'], FILTER_VALIDATE_EMAIL) && function_exists('sendEmail')) {
        $subject = 'Registration Update — HIGH Q SOLID ACADEMY';
        $body = '<p>Hi ' . htmlspecialchars($reg['first_name'] . ' ' . ($reg['last_name'] ?? '')) . ',</p><p>Your registration has been rejected.' . ($reason ? '<br><strong>Reason:</strong> ' . htmlspecialchars($reason) : '') . '</p>';
        try { $emailSent = (bool) sendEmail($reg['email'], $subject, $body); } catch (Throwable $e) { $emailSent = false; }
      }
      $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']);
      if ($isAjax) { echo json_encode(['status'=>'ok','message'=>'Registration rejected','email_sent'=>!empty($emailSent)]); exit; }
    }
  header('Location: ' . admin_url('pages/academic.php')); exit;
  }
}

// Prefer to show structured student registrations if the table exists
$hasRegistrations = false;
try {
  $check = $pdo->query("SHOW TABLES LIKE 'student_registrations'")->fetch();
  $hasRegistrations = !empty($check);
} catch (Throwable $e) { $hasRegistrations = false; }

// Also detect post_utme_registrations table so we can list/export those entries
$hasPostUtme = false;
try {
  $check2 = $pdo->query("SHOW TABLES LIKE 'post_utme_registrations'")->fetch();
  $hasPostUtme = !empty($check2);
} catch (Throwable $e) { $hasPostUtme = false; }

// Also detect universal_registrations table (new wizard)
$hasUniversal = false;
try {
  $check3 = $pdo->query("SHOW TABLES LIKE 'universal_registrations'")->fetch();
  $hasUniversal = !empty($check3);
} catch (Throwable $e) { $hasUniversal = false; }

// Allow admin to request a source via GET ?source=postutme|regular|universal
$requestedSource = strtolower(trim($_GET['source'] ?? ''));
$registrations_source = 'student_registrations';
if ($requestedSource === 'postutme' && $hasPostUtme) {
  $registrations_source = 'post_utme_registrations';
  $hasRegistrations = true;
} elseif ($requestedSource === 'universal' && $hasUniversal) {
  $registrations_source = 'universal_registrations';
  $hasRegistrations = true;
} elseif ($requestedSource === 'regular') {
  // prefer student_registrations if present; otherwise fall back to post_utme
  if ($hasRegistrations) {
    $registrations_source = 'student_registrations';
  } elseif ($hasPostUtme) {
    $registrations_source = 'post_utme_registrations';
    $hasRegistrations = true;
  }
} else {
  // default behavior: if universal_registrations exists, use that; else student_registrations; else post_utme
  if ($hasUniversal) {
    $hasRegistrations = true;
    $registrations_source = 'universal_registrations';
    $requestedSource = 'universal';
  } elseif (!$hasRegistrations && $hasPostUtme) {
    $hasRegistrations = true;
    $registrations_source = 'post_utme_registrations';
    $requestedSource = 'postutme';
  }
}

// expose for template UI
$current_source = $requestedSource ?: ($registrations_source === 'universal_registrations' ? 'universal' : ($registrations_source === 'post_utme_registrations' ? 'postutme' : 'regular'));

// ensure counters exist regardless of which data path is used
$active = 0; $pending = 0; $banned = 0; $total = 0;
// KPI counters (awaiting/confirmed/rejected) for summary cards
$countAwaiting = 0; $countConfirmed = 0; $countRejected = 0;

if ($hasRegistrations) {
  // simple pagination
  $perPage = 12;
  $page = max(1, (int)($_GET['page'] ?? 1));
  $offset = ($page - 1) * $perPage;

  if ($registrations_source === 'student_registrations') {
    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM student_registrations");
    $countStmt->execute();
    $total = (int)$countStmt->fetchColumn();

    // KPI counts
    try {
      $countAwaiting = (int)$pdo->query("SELECT COUNT(*) FROM student_registrations WHERE status = 'awaiting_payment'")->fetchColumn();
      $countConfirmed = (int)$pdo->query("SELECT COUNT(*) FROM student_registrations WHERE status = 'confirmed'")->fetchColumn();
      $countRejected = (int)$pdo->query("SELECT COUNT(*) FROM student_registrations WHERE status = 'rejected'")->fetchColumn();
    } catch (Throwable $_) { /* ignore if columns differ */ }

    $stmt = $pdo->prepare("SELECT sr.*, u.email, u.name AS user_name FROM student_registrations sr LEFT JOIN users u ON u.id = sr.user_id ORDER BY sr.created_at DESC LIMIT ? OFFSET ?");
    $stmt->bindValue(1, $perPage, PDO::PARAM_INT);
    $stmt->bindValue(2, $offset, PDO::PARAM_INT);
    $stmt->execute();
    $academic = $stmt->fetchAll(PDO::FETCH_ASSOC);
  } elseif ($registrations_source === 'universal_registrations') {
    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM universal_registrations");
    $countStmt->execute();
    $total = (int)$countStmt->fetchColumn();

    // KPI counts (best-effort)
    try {
      $countAwaiting = (int)$pdo->query("SELECT COUNT(*) FROM universal_registrations WHERE status = 'awaiting_payment'")->fetchColumn();
      $countConfirmed = (int)$pdo->query("SELECT COUNT(*) FROM universal_registrations WHERE status = 'confirmed'")->fetchColumn();
      $countRejected = (int)$pdo->query("SELECT COUNT(*) FROM universal_registrations WHERE status = 'rejected'")->fetchColumn();
    } catch (Throwable $_) { }

    $stmt = $pdo->prepare("SELECT * FROM universal_registrations ORDER BY created_at DESC LIMIT ? OFFSET ?");
    $stmt->bindValue(1, $perPage, PDO::PARAM_INT);
    $stmt->bindValue(2, $offset, PDO::PARAM_INT);
    $stmt->execute();
    $academic = $stmt->fetchAll(PDO::FETCH_ASSOC);
    // mark entries so template can render appropriate actions
    foreach ($academic as &$ss) { $ss['__universal'] = 1; }
    unset($ss);
  } else {
    // post_utme_registrations listing
    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM post_utme_registrations");
    $countStmt->execute();
    $total = (int)$countStmt->fetchColumn();

    // KPI counts (best-effort)
    try {
      $countAwaiting = (int)$pdo->query("SELECT COUNT(*) FROM post_utme_registrations WHERE status = 'awaiting_payment'")->fetchColumn();
      $countConfirmed = (int)$pdo->query("SELECT COUNT(*) FROM post_utme_registrations WHERE status = 'confirmed'")->fetchColumn();
      $countRejected = (int)$pdo->query("SELECT COUNT(*) FROM post_utme_registrations WHERE status = 'rejected'")->fetchColumn();
    } catch (Throwable $_) { }

    $stmt = $pdo->prepare("SELECT pur.* FROM post_utme_registrations pur ORDER BY pur.created_at DESC LIMIT ? OFFSET ?");
    $stmt->bindValue(1, $perPage, PDO::PARAM_INT);
    $stmt->bindValue(2, $offset, PDO::PARAM_INT);
    $stmt->execute();
    $academic = $stmt->fetchAll(PDO::FETCH_ASSOC);
    // mark entries so template can render appropriate actions
    foreach ($academic as &$ss) { $ss['__postutme'] = 1; }
    unset($ss);
  }

} else {
  // Fetch academic (users with role slug 'student' or where role is null)
  $stmt = $pdo->prepare("SELECT u.*, r.name AS role_name, r.slug AS role_slug FROM users u LEFT JOIN roles r ON r.id = u.role_id WHERE r.slug = 'student' OR u.role_id IS NULL ORDER BY u.created_at DESC");
  $stmt->execute();
  $academic = $stmt->fetchAll(PDO::FETCH_ASSOC);

  // Counts for legacy users list
  $total = count($academic);
  $active = 0; $pending = 0; $banned = 0;
  foreach ($academic as $s) {
    if ($s['is_active']==1) $active++;
    elseif ($s['is_active']==0) $pending++;
    elseif ($s['is_active']==2) $banned++;
  }
}
?>

<?php
$__hqStandalone = (basename($_SERVER['SCRIPT_NAME'] ?? '') === 'academic.php');
if ($__hqStandalone) {
  require_once __DIR__ . '/../includes/header.php';
  require_once __DIR__ . '/../includes/sidebar.php';
}
?>

<style>
        :root {
            --hq-yellow: #ffd600;
            --hq-yellow-light: #ffe566;
            --hq-black: #0a0a0a;
            --hq-gray: #f3f4f6;
        }
        .academic-page {
            max-width: 100%;
            width: 100%;
            padding: 0;
        }
        .page-hero-academic {
            background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%);
            padding: 2rem 2.5rem;
            margin: -24px -32px 24px -32px;
        }
        .page-hero-academic .hero-content {
            max-width: 1400px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }
        .page-hero-academic .badge {
            display: inline-block;
            font-size: 0.7rem;
            text-transform: uppercase;
            letter-spacing: 0.15em;
            color: rgba(0,0,0,0.6);
            margin-bottom: 0.5rem;
        }
        .page-hero-academic h1 {
            font-size: 2rem;
            font-weight: 700;
            color: #1e293b;
            margin: 0;
            line-height: 1.2;
        }
        .page-hero-academic p {
            font-size: 1rem;
            color: rgba(0,0,0,0.7);
            margin: 0.5rem 0 0 0;
        }
        .page-hero-academic .stat-badge {
            background: #1e293b;
            color: #fff;
            padding: 0.75rem 1.5rem;
            border-radius: 0.75rem;
            font-weight: 600;
        }
        .filter-card {
            background: white;
            border-radius: 1rem;
            padding: 1.75rem;
            box-shadow: 0 2px 12px rgba(0,0,0,0.06);
            margin-bottom: 2rem;
            border: 1px solid #e2e8f0;
        }
        .filter-card input, .filter-card select {
            border: 1px solid #e2e8f0;
            border-radius: 0.75rem;
            padding: 0.75rem 1rem;
            font-size: 0.95rem;
        }
        .filter-card input:focus, .filter-card select:focus {
            outline: none;
            border-color: var(--hq-yellow);
            box-shadow: 0 0 0 3px rgba(255, 214, 0, 0.2);
        }
        .academic-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1.5rem;
        }
        .student-card {
            background: white;
            border-radius: 1rem;
            border: 1px solid #e2e8f0;
            padding: 1.5rem;
            transition: all 0.2s;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }
        .student-card:hover {
            box-shadow: 0 8px 20px rgba(0,0,0,0.1);
            border-color: var(--hq-yellow);
            transform: translateY(-2px);
        }
        .student-card h3 {
            font-size: 1.1rem;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 0.25rem;
        }
        .student-card p {
            font-size: 0.9rem;
            color: #64748b;
        }
        .status-badge-pending {
            background: #fef3c7;
            color: #92400e;
            padding: 0.35rem 0.85rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.025em;
        }
        .status-badge-confirmed {
            background: #d1fae5;
            color: #065f46;
            padding: 0.35rem 0.85rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.025em;
        }
        .tab-button {
            padding: 0.75rem 1.5rem;
            border-radius: 0.75rem;
            font-weight: 600;
            transition: all 0.2s;
        }
        .tab-button.active {
            background: var(--hq-yellow);
            color: var(--hq-black);
            box-shadow: 0 2px 8px rgba(255, 214, 0, 0.3);
        }
        @media (max-width: 1200px) {
            .academic-grid { grid-template-columns: repeat(2, 1fr); }
        }
        @media (max-width: 768px) {
            .academic-grid { grid-template-columns: 1fr; }
            .page-hero-academic { padding: 1.5rem; margin: -24px -16px 24px -16px; }
            .filter-card { padding: 1rem; }
        }
      </style>

<div class="admin-page-content">
<div class="academic-page">
    <!-- Header Section -->
    <div class="page-hero-academic">
        <div class="hero-content">
            <div>
                <span class="badge">Student Affairs</span>
                <h1>Admissions Management</h1>
                <p>Manage student registrations and applications</p>
            </div>
            <div class="stat-badge">
                <span><?= number_format($total) ?> Total Registrations</span>
            </div>
        </div>
    </div>

    <!-- Controls & Filters -->
    <div class="filter-card">
      <div class="d-flex flex-column flex-md-row gap-3 justify-content-between align-items-md-center">
        <div class="d-flex flex-column flex-md-row gap-3 align-items-md-center" style="flex:1;">
          <div class="position-relative" style="flex:1; max-width: 420px;">
            <i class='bx bx-search' style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:#94a3b8;"></i>
            <input type="text" id="searchInput" placeholder="Search by name, email..." class="form-control" style="padding-left:40px; border-radius: 0.75rem;">
          </div>

          <select id="statusFilter" class="form-select" style="border-radius: 0.75rem; max-width: 220px;">
            <option value="">All Statuses</option>
            <option value="active">Approved</option>
            <option value="pending">Pending</option>
            <option value="banned">Rejected</option>
          </select>
        </div>

        <div class="btn-group" role="group" aria-label="Registration source">
          <a href="index.php?pages=academic" class="btn btn-sm <?= ($current_source==='regular' || $current_source==='') ? 'btn-dark' : 'btn-outline-secondary' ?>">Regular</a>
          <?php if ($hasPostUtme): ?>
          <a href="index.php?pages=academic&source=postutme" class="btn btn-sm <?= ($current_source==='postutme') ? 'btn-dark' : 'btn-outline-secondary' ?>">Post-UTME</a>
          <?php endif; ?>
          <?php if ($hasUniversal): ?>
          <a href="index.php?pages=academic&source=universal" class="btn btn-sm <?= ($current_source==='universal') ? 'btn-dark' : 'btn-outline-secondary' ?>">New Wizard</a>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- KPI Summary -->
    <div class="kpi-cards" style="display:grid;grid-template-columns:repeat(4,1fr);gap:1rem;margin-bottom:1.5rem;">
      <div class="kpi-card" style="background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:1rem;box-shadow:0 1px 4px rgba(0,0,0,0.04)">
        <div style="font-size:.8rem;color:#64748b">Total</div>
        <div style="font-size:1.4rem;font-weight:800;color:#1f2937;"><?= number_format($total) ?></div>
      </div>
      <div class="kpi-card" style="background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:1rem;box-shadow:0 1px 4px rgba(0,0,0,0.04)">
        <div style="font-size:.8rem;color:#64748b">Awaiting Payment</div>
        <div style="font-size:1.4rem;font-weight:800;color:#92400e;"><?= number_format($countAwaiting) ?></div>
      </div>
      <div class="kpi-card" style="background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:1rem;box-shadow:0 1px 4px rgba(0,0,0,0.04)">
        <div style="font-size:.8rem;color:#64748b">Confirmed</div>
        <div style="font-size:1.4rem;font-weight:800;color:#065f46;"><?= number_format($countConfirmed) ?></div>
      </div>
      <div class="kpi-card" style="background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:1rem;box-shadow:0 1px 4px rgba(0,0,0,0.04)">
        <div style="font-size:.8rem;color:#64748b">Rejected</div>
        <div style="font-size:1.4rem;font-weight:800;color:#991b1b;"><?= number_format($countRejected) ?></div>
      </div>
    </div>

    <!-- academic Grid -->
    <div class="academic-grid" id="academicList">
        <?php if (!empty($academic)): ?>
            <?php foreach ($academic as $s): 
                $status = $s['status'] ?? ($s['is_active']==1 ? 'active' : ($s['is_active']==0 ? 'pending' : 'banned'));
                $displayName = $s['first_name'] ?? $s['name'] ?? 'Unknown';
                if (isset($s['last_name'])) $displayName .= ' ' . $s['last_name'];
                $displayEmail = $s['email'] ?? $s['user_name'] ?? '';
                $passportThumb = $s['passport_path'] ?? ($s['avatar'] ?? null);
                $isPostUtme = !empty($s['__postutme']);
                $isUniversal = !empty($s['__universal']);
                // Universal registrations display program type badge
                $programTypeBadge = $isUniversal && !empty($s['program_type']) ? ucfirst($s['program_type']) : null;
            ?>
            <div class="student-card academic-card"
                 data-status="<?= strtolower($status) ?>" data-id="<?= $s['id'] ?>">
              <div class="d-flex align-items-start gap-3 mb-3">
                <div class="academic-avatar">
                  <img src="<?= htmlspecialchars($passportThumb ?: 'assets/img/hq-logo.jpeg') ?>"
                     alt="Avatar"
                     onerror="this.src='assets/img/hq-logo.jpeg'">
                </div>

                <div class="flex-grow-1" style="min-width:0;">
                  <h3 class="card-name" style="margin:0; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;"><?= htmlspecialchars($displayName) ?></h3>
                  <div class="card-email" style="white-space:nowrap; overflow:hidden; text-overflow:ellipsis;"><?= htmlspecialchars($displayEmail) ?></div>
                  <div class="d-flex flex-wrap gap-2 mt-2">
                    <span class="badge bg-primary-subtle text-primary"><?= $programTypeBadge ?: 'Student' ?></span>
                    <?php if ($status==='paid' || $status==='confirmed' || $status==='active'): ?>
                      <span class="badge bg-success-subtle text-success"><?= htmlspecialchars(ucfirst($status)) ?></span>
                    <?php elseif ($status==='rejected' || $status==='banned'): ?>
                      <span class="badge bg-danger-subtle text-danger"><?= htmlspecialchars(ucfirst($status)) ?></span>
                    <?php else: ?>
                      <span class="badge bg-warning-subtle text-warning"><?= htmlspecialchars(ucfirst($status)) ?></span>
                    <?php endif; ?>
                  </div>
                </div>
              </div>

                <div class="pt-3 mt-auto" style="border-top:1px solid #eef2f7; display:flex; flex-wrap:wrap; gap:0.5rem; justify-content:flex-end;">
                    <?php if (!$isPostUtme && !$isUniversal): ?>
                        <?php if (!empty($s['status'])): ?>
                      <button type="button" onclick="confirmRegistration(<?= $s['id'] ?>)" class="btn btn-sm btn-outline-success">
                                Confirm
                            </button>
                      <button type="button" onclick="rejectRegistration(<?= $s['id'] ?>)" class="btn btn-sm btn-outline-danger">
                                Reject
                            </button>
                        <?php endif; ?>
                    <?php endif; ?>
                    
                  <button type="button" onclick="viewRegistration(<?= $s['id'] ?>)" class="btn btn-sm btn-outline-primary">
                        View
                    </button>
                    
                    <form method="post" action="index.php?pages=academic&action=<?= $isPostUtme ? 'delete_postutme' : ($isUniversal ? 'delete_universal' : 'delete') ?>&id=<?= $s['id'] ?>" class="inline-block" onsubmit="return confirm('Are you sure?');">
                        <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                    <button type="submit" class="btn btn-sm btn-outline-secondary" title="Delete">
                      <i class='bx bx-trash'></i>
                        </button>
                    </form>
                </div>
            </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="text-center" style="padding:3rem 1rem;color:#64748b;grid-column:1/-1;">
              <i class='bx bx-folder-open' style="font-size:2rem;display:block;margin-bottom:0.5rem;"></i>
                <p>No registrations found.</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Pagination -->
    <?php if (!empty($total) && isset($perPage)): $pages = (int) ceil($total / $perPage); $baseLink = 'index.php?pages=academic' . ($current_source==='postutme' ? '&source=postutme' : ''); $currentPage = (int)($page ?? 1); ?>
    <?php if ($pages > 1): ?>
    <nav aria-label="Academic pagination" class="mt-4">
      <ul class="pagination justify-content-center">
        <?php for ($p=1;$p<=$pages;$p++): ?>
          <li class="page-item <?= $p===$currentPage ? 'active' : '' ?>">
            <a class="page-link" href="<?= $baseLink . '&page=' . $p ?>"><?= $p ?></a>
          </li>
        <?php endfor; ?>
      </ul>
    </nav>
    <?php endif; ?>
    <?php endif; ?>
</div>

  <style>
  .academic-avatar{width:56px;height:56px;border-radius:999px;overflow:hidden;background:#f1f5f9;flex:0 0 56px;border:2px solid #fff;box-shadow:0 1px 3px rgba(0,0,0,0.08)}
  .academic-avatar img{width:100%;height:100%;object-fit:cover;display:block}
  .academic-card .card-email{font-size:0.9rem;color:#64748b}
  .academic-card .badge{font-weight:600}
  </style>

<script>
// Client-side search
document.getElementById('searchInput').addEventListener('keyup', function(e) {
    const q = e.target.value.toLowerCase();
  document.querySelectorAll('.academic-card').forEach(card => {
        const name = card.querySelector('.card-name').textContent.toLowerCase();
        const email = card.querySelector('.card-email').textContent.toLowerCase();
    card.style.display = (name.includes(q) || email.includes(q)) ? '' : 'none';
    });
});

document.getElementById('statusFilter').addEventListener('change', function(e) {
    const status = e.target.value.toLowerCase();
  document.querySelectorAll('.academic-card').forEach(card => {
        const cardStatus = card.dataset.status;
    if (!status) card.style.display = '';
    else card.style.display = (cardStatus.includes(status)) ? '' : 'none';
    });
});

function viewRegistration(id) {
  fetch('index.php?pages=academic&action=view&id=' + id, {
    headers: {
      'X-Requested-With': 'XMLHttpRequest',
      'Accept': 'application/json'
    }
  })
  .then(r => r.json())
  .then(data => {
    if(data.error) { Swal.fire('Error', data.error, 'error'); return; }
    const d = data.data || data;
    
    // Build passport preview
    let passportHtml = '';
    if (d.passport_photo) {
      passportHtml = `<div class="text-center mb-3">
        <img src="${d.passport_photo}" alt="Passport Photo" style="width:120px;height:120px;object-fit:cover;border-radius:12px;border:3px solid #ffd600;box-shadow:0 4px 12px rgba(0,0,0,0.1);">
      </div>`;
    }
    
    let html = `
    <div style="max-height:65vh;overflow-y:auto;padding:0 1rem;">
      ${passportHtml}
      
      <!-- Personal Information -->
      <div style="background:#f8fafc;border-radius:12px;padding:1rem;margin-bottom:1rem;">
        <h5 style="font-size:0.85rem;font-weight:700;color:#64748b;margin-bottom:0.75rem;text-transform:uppercase;letter-spacing:0.05em;">
          <i class='bx bx-user'></i> Personal Information
        </h5>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:0.5rem;">
          <p style="margin:0;font-size:0.9rem;"><strong>Surname:</strong> ${d.surname || d.last_name || '-'}</p>
          <p style="margin:0;font-size:0.9rem;"><strong>Other Names:</strong> ${d.other_names || d.first_name || '-'}</p>
          <p style="margin:0;font-size:0.9rem;"><strong>Email:</strong> ${d.email || '-'}</p>
          <p style="margin:0;font-size:0.9rem;"><strong>Phone:</strong> ${d.phone || d.emergency_contact_phone || '-'}</p>
          <p style="margin:0;font-size:0.9rem;"><strong>Gender:</strong> ${d.gender || '-'}</p>
          <p style="margin:0;font-size:0.9rem;"><strong>DOB:</strong> ${d.date_of_birth || '-'}</p>
          <p style="margin:0;font-size:0.9rem;"><strong>Marital Status:</strong> ${d.marital_status || '-'}</p>
          <p style="margin:0;font-size:0.9rem;"><strong>NIN:</strong> ${d.nin || '-'}</p>
        </div>
      </div>
      
      <!-- Location Information -->
      <div style="background:#f8fafc;border-radius:12px;padding:1rem;margin-bottom:1rem;">
        <h5 style="font-size:0.85rem;font-weight:700;color:#64748b;margin-bottom:0.75rem;text-transform:uppercase;letter-spacing:0.05em;">
          <i class='bx bx-map'></i> Location Details
        </h5>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:0.5rem;">
          <p style="margin:0;font-size:0.9rem;"><strong>State of Origin:</strong> ${d.state_of_origin || '-'}</p>
          <p style="margin:0;font-size:0.9rem;"><strong>Local Govt:</strong> ${d.local_government || '-'}</p>
          <p style="margin:0;font-size:0.9rem;grid-column:1/-1;"><strong>Home Address:</strong> ${d.home_address || '-'}</p>
        </div>
      </div>
      
      <!-- Academic Information -->
      <div style="background:#f8fafc;border-radius:12px;padding:1rem;margin-bottom:1rem;">
        <h5 style="font-size:0.85rem;font-weight:700;color:#64748b;margin-bottom:0.75rem;text-transform:uppercase;letter-spacing:0.05em;">
          <i class='bx bx-book'></i> Academic Information
        </h5>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:0.5rem;">
          <p style="margin:0;font-size:0.9rem;"><strong>Profile Code:</strong> ${d.profile_code || d.jamb_profile_code || '-'}</p>
          <p style="margin:0;font-size:0.9rem;"><strong>Exam Type:</strong> ${d.exam_type || '-'}</p>
          <p style="margin:0;font-size:0.9rem;"><strong>Exam Year:</strong> ${d.exam_year || '-'}</p>
          <p style="margin:0;font-size:0.9rem;"><strong>Course:</strong> ${d.academic_goals || d.course_of_study || '-'}</p>
          <p style="margin:0;font-size:0.9rem;grid-column:1/-1;"><strong>Previous Education:</strong> ${d.previous_education || '-'}</p>
        </div>
      </div>
      
      <!-- Sponsor/Guardian Information -->
      <div style="background:#f8fafc;border-radius:12px;padding:1rem;margin-bottom:1rem;">
        <h5 style="font-size:0.85rem;font-weight:700;color:#64748b;margin-bottom:0.75rem;text-transform:uppercase;letter-spacing:0.05em;">
          <i class='bx bx-group'></i> Sponsor/Guardian Information
        </h5>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:0.5rem;">
          <p style="margin:0;font-size:0.9rem;"><strong>Sponsor Name:</strong> ${d.sponsor_name || d.guardian_name || '-'}</p>
          <p style="margin:0;font-size:0.9rem;"><strong>Sponsor Phone:</strong> ${d.sponsor_phone || d.guardian_phone || '-'}</p>
          <p style="margin:0;font-size:0.9rem;grid-column:1/-1;"><strong>Sponsor Address:</strong> ${d.sponsor_address || d.guardian_address || '-'}</p>
        </div>
      </div>
      
      <!-- Next of Kin Information -->
      <div style="background:#f8fafc;border-radius:12px;padding:1rem;margin-bottom:1rem;">
        <h5 style="font-size:0.85rem;font-weight:700;color:#64748b;margin-bottom:0.75rem;text-transform:uppercase;letter-spacing:0.05em;">
          <i class='bx bx-user-plus'></i> Next of Kin
        </h5>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:0.5rem;">
          <p style="margin:0;font-size:0.9rem;"><strong>Name:</strong> ${d.next_of_kin_name || '-'}</p>
          <p style="margin:0;font-size:0.9rem;"><strong>Phone:</strong> ${d.next_of_kin_phone || '-'}</p>
          <p style="margin:0;font-size:0.9rem;grid-column:1/-1;"><strong>Address:</strong> ${d.next_of_kin_address || '-'}</p>
        </div>
      </div>
      
      <!-- Registration Status -->
      <div style="background:#f8fafc;border-radius:12px;padding:1rem;">
        <h5 style="font-size:0.85rem;font-weight:700;color:#64748b;margin-bottom:0.75rem;text-transform:uppercase;letter-spacing:0.05em;">
          <i class='bx bx-check-circle'></i> Registration Status
        </h5>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:0.5rem;">
          <p style="margin:0;font-size:0.9rem;"><strong>Status:</strong> <span style="padding:0.25rem 0.5rem;border-radius:999px;font-size:0.75rem;font-weight:600;background:${d.status === 'confirmed' ? '#d1fae5' : d.status === 'rejected' ? '#fee2e2' : '#fef3c7'};color:${d.status === 'confirmed' ? '#065f46' : d.status === 'rejected' ? '#991b1b' : '#92400e'}">${(d.status || 'Pending').toUpperCase()}</span></p>
          <p style="margin:0;font-size:0.9rem;"><strong>Created:</strong> ${d.created_at || '-'}</p>
        </div>
      </div>
    </div>`;
    
    Swal.fire({
      title: (d.surname || d.first_name || '') + ' ' + (d.other_names || d.last_name || ''),
      html: html,
      width: '700px',
      showCloseButton: true,
      showConfirmButton: true,
      confirmButtonText: '<i class="bx bx-download"></i> Export PDF',
      confirmButtonColor: '#ffd600',
      showDenyButton: true,
      denyButtonText: 'Close',
      denyButtonColor: '#64748b'
    }).then((result) => {
      if (result.isConfirmed) {
        // Trigger export for this student
        window.open('index.php?pages=academic&action=export_single&id=' + id, '_blank');
      }
    });
  });
}

function confirmRegistration(id) {
    Swal.fire({
        title: 'Confirm Registration?',
        text: "This will approve the student.",
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Yes, confirm'
    }).then((result) => {
        if (result.isConfirmed) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = 'index.php?pages=academic&action=confirm_registration';
            const idInput = document.createElement('input');
            idInput.type = 'hidden'; idInput.name = 'id'; idInput.value = id;
            const csrf = document.createElement('input');
            csrf.type = 'hidden'; csrf.name = 'csrf_token'; csrf.value = '<?= $csrf ?>';
            form.appendChild(idInput); form.appendChild(csrf);
            document.body.appendChild(form);
            form.submit();
        }
    });
}

function rejectRegistration(id) {
    Swal.fire({
        title: 'Reject Registration',
        input: 'text',
        inputLabel: 'Reason for rejection',
        showCancelButton: true,
        confirmButtonText: 'Reject',
        confirmButtonColor: '#e11d48'
    }).then((result) => {
        if (result.isConfirmed) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = 'index.php?pages=academic&action=reject_registration';
            const idInput = document.createElement('input');
            idInput.type = 'hidden'; idInput.name = 'id'; idInput.value = id;
            const reasonInput = document.createElement('input');
            reasonInput.type = 'hidden'; reasonInput.name = 'reason'; reasonInput.value = result.value;
            const csrf = document.createElement('input');
            csrf.type = 'hidden'; csrf.name = 'csrf_token'; csrf.value = '<?= $csrf ?>';
            form.appendChild(idInput); form.appendChild(reasonInput); form.appendChild(csrf);
            document.body.appendChild(form);
            form.submit();
        }
    });
}
</script>
</div> <!-- .academic-page -->
</div> <!-- .admin-page-content -->

<?php
if ($__hqStandalone) {
  require_once __DIR__ . '/../includes/footer.php';
}
?>

