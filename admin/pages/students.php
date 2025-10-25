<?php
// admin./pages/students.php
// Remove any visible code output from the top of the page
// Ensure no script or debug code is rendered at the top of the page
require_once '../includes/auth.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/csrf.php';
// --- Early AJAX handling: ensure JSON responses are not contaminated by HTML ---
// Determine requested action (AJAX clients typically send action via POST)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$earlyAction = $_POST['action'] ?? $_GET['action'] ?? '';
if (!empty($earlyAction)) {
  // Treat these as JSON/JSON-AJAX requests so we can set proper headers early
  header('Content-Type: application/json');

  // Try to load APP_URL from .env if Dotenv is available, otherwise fall back to request host
  $baseUrl = null;
  if (class_exists('\Dotenv\\Dotenv')) {
    try {
      $dotenv = \Dotenv\Dotenv::createImmutable(__DIR__ . '/../../');
      $dotenv->load();
      $baseUrl = rtrim($_ENV['APP_URL'] ?? ($_ENV['APP_URL'] ?? ''), '/');
    } catch (Throwable $e) {
      // ignore dotenv load errors
      $baseUrl = null;
    }
  }
  if (empty($baseUrl)) {
    $proto = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $baseUrl = $proto . '://' . $host;
  }
  // expose $baseUrl for later handlers in this file
  $GLOBALS['HQ_BASE_URL'] = $baseUrl;
}

  // Helper: insert payment row with fallback for databases where payments.id is not AUTO_INCREMENT
  function insertPaymentWithFallback(PDO $pdo, $studentId, $amount, $method, $reference) {
    try {
      // Always add a small random surcharge (between ₦0.01 and ₦167.54) and persist it where possible
      $surcharge = round(mt_rand(1, 16754) / 100, 2);
      $amount_with_surcharge = round($amount + $surcharge, 2);

      $ins = $pdo->prepare('INSERT INTO payments (student_id, amount, payment_method, reference, status, created_at) VALUES (?, ?, ?, ?, "pending", NOW())');
      $ins->execute([ $studentId, $amount_with_surcharge, $method, $reference ]);
      $pid = (int)$pdo->lastInsertId();

      // Try to persist surcharge into metadata column if it exists (best-effort, ignore failures)
      try {
        $upd = $pdo->prepare('UPDATE payments SET metadata = JSON_OBJECT("surcharge", ?) WHERE id = ?');
        $upd->execute([$surcharge, $pid]);
      } catch (Throwable $_) { /* ignore if metadata column or JSON functions unavailable */ }

      return $pid;
    } catch (Throwable $e) {
      // fallback: lock table, compute next id, insert with explicit id
      try {
        $pdo->beginTransaction();
        // Lock the table for write to avoid races
        $pdo->exec('LOCK TABLES payments WRITE');
        $row = $pdo->query('SELECT MAX(id) AS m FROM payments')->fetch(PDO::FETCH_ASSOC);
        $next = (int)($row['m'] ?? 0) + 1;
        // Add surcharge here too for fallback insertion
        $surcharge = round(mt_rand(1, 16754) / 100, 2);
        $amount_with_surcharge = round($amount + $surcharge, 2);
        $ins2 = $pdo->prepare('INSERT INTO payments (id, student_id, amount, payment_method, reference, status, created_at) VALUES (?, ?, ?, ?, ?, "pending", NOW())');
        $ins2->execute([ $next, $studentId, $amount_with_surcharge, $method, $reference ]);
        $pdo->exec('UNLOCK TABLES');
        $pdo->commit();
        // Attempt to set metadata.surcharge if possible
        try { $upd2 = $pdo->prepare('UPDATE payments SET metadata = JSON_OBJECT("surcharge", ?) WHERE id = ?'); $upd2->execute([$surcharge, $next]); } catch (Throwable $_) {}
        return $next;
      } catch (Throwable $e2) {
        try { $pdo->exec('UNLOCK TABLES'); } catch (Throwable $_) {}
        if ($pdo->inTransaction()) $pdo->rollBack();
        throw $e; // rethrow original exception
      }
    }
  }

requirePermission('students'); // where 'students' matches the menu slug
// Generate CSRF token
$csrf = generateToken('students_form');

// Support POST-driven AJAX actions (confirm_registration, view_registration)
// Handle create_payment_link (AJAX) - returns JSON link + reference
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_payment_link') {
  // Ensure admin permission
  requirePermission('students');
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

    // Build link using loaded base URL if available
    // Prefer APP_URL from environment, otherwise build from request host
    $base = $_ENV['APP_URL'] ?? getenv('APP_URL') ?: ($GLOBALS['HQ_BASE_URL'] ?? null);
    if (empty($base)) {
      $base = ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost');
    }
    $base = rtrim($base, '/');
    // Build a public-facing URL to payments_wait.php (avoid filesystem paths/dirname)
  // Use a prettier, public-facing URL (e.g. /pay/{ref})
  $link = $base . '/pay/' . urlencode($reference);

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
          // log a note to the students_confirm_errors log for admin debugging
          try {
            $logDir = __DIR__ . '/../../storage/logs'; if (!is_dir($logDir)) @mkdir($logDir, 0755, true);
            @file_put_contents($logDir . '/students_confirm_errors.log', "[" . date('Y-m-d H:i:s') . "] create_payment_link email failed to send to: " . ($reg['email'] ?? 'unknown') . "\n", FILE_APPEND | LOCK_EX);
          } catch (Throwable $_) { }
        }
      } catch (Throwable $me) {
        try { $logDir = __DIR__ . '/../../storage/logs'; if (!is_dir($logDir)) @mkdir($logDir, 0755, true); @file_put_contents($logDir . '/students_confirm_errors.log', "[" . date('Y-m-d H:i:s') . "] create_payment_link sendEmail exception: " . $me->getMessage() . "\n" . $me->getTraceAsString() . "\n", FILE_APPEND | LOCK_EX); } catch (Throwable $_) {}
        $emailSent = false;
      }
    }

    echo json_encode(['success'=>true,'link'=>$link,'reference'=>$reference,'email_sent'=>(bool)$emailSent,'email'=>$reg['email'] ?? null,'student'=>['first_name'=>$reg['first_name'] ?? null,'last_name'=>$reg['last_name'] ?? null,'email'=>$reg['email'] ?? null]]);
    exit;
  } catch (Throwable $e) {
    // Detailed logging to students_confirm_errors.log to help debugging
    try {
      $logDir = __DIR__ . '/../../storage/logs';
      if (!is_dir($logDir)) @mkdir($logDir, 0755, true);
      $logFile = $logDir . '/students_confirm_errors.log';
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

    // Build payment link (prefer APP_URL from environment if loaded)
    $base = $GLOBALS['HQ_BASE_URL'] ?? null;
    if (empty($base)) {
      $base = ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost');
    }
    // Prefer APP_URL from environment, otherwise build from request host
    $base = $_ENV['APP_URL'] ?? getenv('APP_URL') ?: ($GLOBALS['HQ_BASE_URL'] ?? null);
    if (empty($base)) {
      $base = ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost');
    }
    $base = rtrim($base, '/');
    // Build a public-facing URL to payments_wait.php (avoid filesystem paths/dirname)
  $paymentLink = $base . '/pay/' . urlencode($ref);

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
    $logFile = $logDir . '/students_confirm_errors.log';
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
  // Support POST view_registration for AJAX clients (supports regular and post-utme types)
  $type = trim($_POST['type'] ?? 'registration');
  
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
  if ($type === 'post') {
    $stmt = $pdo->prepare('SELECT * FROM post_utme_registrations WHERE id = ? LIMIT 1');
    $stmt->execute([$id]);
    $s = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$s) { echo json_encode(['success' => false, 'error' => 'Post-UTME registration not found']); exit; }
    // decode olevel_results if present
    $olevel = null;
    if (!empty($s['olevel_results'])) {
      $decoded = @json_decode($s['olevel_results'], true);
      if (is_array($decoded)) $olevel = $decoded;
    }

    // return a complete set of fields for the admin modal to render
    $data = $s;
    $data['olevel_results_parsed'] = $olevel;
    echo json_encode(['success'=>true,'data'=>$data]);
    exit;
  }

  header('Content-Type: application/json');
  if (!$s) {
    echo json_encode(['success' => false, 'error' => 'Registration not found']);
    exit;
  }

  echo json_encode([
    'success' => true,
    'data' => [
      'first_name'             => $s['first_name'] ?? null,
      'last_name'              => $s['last_name'] ?? null,
      'email'                  => $s['email'] ?? null,
      'date_of_birth'          => $s['date_of_birth'] ?? null,
      'home_address'           => $s['home_address'] ?? null,
      'previous_education'     => $s['previous_education'] ?? null,
      'academic_goals'         => $s['academic_goals'] ?? null,
      'emergency_contact_name' => $s['emergency_contact_name'] ?? null,
      'emergency_contact_phone'=> $s['emergency_contact_phone'] ?? null,
      'emergency_relationship' => $s['emergency_relationship'] ?? null,
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
  if (!$s) { echo json_encode(['error'=>'Not found']); exit; }
  echo json_encode($s); exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action']) && isset($_GET['id'])) {
    $action = $_GET['action'];
    $id = (int)$_GET['id'];
    $token = $_POST['csrf_token'] ?? '';
  if (!verifyToken('students_form', $token)) {
  header('Location: index.php?pages=students'); exit;
  }

    $currentUserId = $_SESSION['user']['id'];

    // Protect main admin and yourself from destructive actions
  if ($id === 1 || $id === $currentUserId) {
  header('Location: index.php?pages=students'); exit;
  }

    if ($action === 'deactivate') {
        $stmt = $pdo->prepare('UPDATE users SET is_active = 2, updated_at = NOW() WHERE id = ?');
        $stmt->execute([$id]);
  logAction($pdo, $currentUserId, 'student_deactivate', ['student_id'=>$id]);
  header('Location: index.php?pages=students'); exit;
    }

    if ($action === 'activate') {
        $stmt = $pdo->prepare('UPDATE users SET is_active = 1, updated_at = NOW() WHERE id = ?');
        $stmt->execute([$id]);
  logAction($pdo, $currentUserId, 'student_activate', ['student_id'=>$id]);
  header('Location: index.php?pages=students'); exit;
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
  header('Location: index.php?pages=students'); exit;
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
  header('Location: index.php?pages=students'); exit;
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
  header('Location: index.php?pages=students'); exit;
  }

  // Confirm registration (admin) - send notification
  if ($action === 'confirm_registration') {
    $stmt = $pdo->prepare('SELECT * FROM student_registrations WHERE id = ? LIMIT 1'); $stmt->execute([$id]); $reg = $stmt->fetch(PDO::FETCH_ASSOC);
    $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']);
    if (!$reg) {
  if ($isAjax) { echo json_encode(['status'=>'error','message'=>'Not found']); exit; }
  header('Location: index.php?pages=students'); exit;
    }

    // If already confirmed, return meaningful JSON error for AJAX or redirect with flash
    if (isset($reg['status']) && strtolower($reg['status']) === 'confirmed') {
  if ($isAjax) { echo json_encode(['status'=>'error','message'=>'Registration already confirmed']); exit; }
  setFlash('error','Registration already confirmed'); header('Location: index.php?pages=students'); exit;
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
  // apply random surcharge to the payment amount
  $surcharge = round(mt_rand(1, 16754) / 100, 2);
  $amount_with_surcharge = round($amount + $surcharge, 2);
  $ins = $pdo->prepare('INSERT INTO payments (student_id, amount, payment_method, reference, status, created_at) VALUES (NULL, ?, ?, ?, "pending", NOW())');
  $ins->execute([$amount_with_surcharge, $method, $ref]);
  $paymentId = $pdo->lastInsertId();
  // try to persist surcharge into metadata (best-effort)
  try { $pdo->prepare('UPDATE payments SET metadata = JSON_OBJECT("surcharge", ?) WHERE id = ?')->execute([$surcharge, $paymentId]); } catch (Throwable $_) {}
  // update amount variable used in emails
  $amount = $amount_with_surcharge;
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
            $link = $base . dirname($_SERVER['SCRIPT_NAME']) . '/../public/payments_wait.php?ref=' . urlencode($ref);

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
  header('Location: index.php?pages=students'); exit;
    } catch (Exception $e) {
      if ($pdo->inTransaction()) $pdo->rollBack();
      if ($isAjax) { echo json_encode(['status'=>'error','message'=>'Server error']); exit; }
  setFlash('error','Failed to confirm registration'); header('Location: index.php?pages=students'); exit;
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
  header('Location: index.php?pages=students'); exit;
  }
}

// Prefer to show structured student registrations if the table exists
$hasRegistrations = false;
try {
  $check = $pdo->query("SHOW TABLES LIKE 'student_registrations'")->fetch();
  $hasRegistrations = !empty($check);
} catch (Throwable $e) { $hasRegistrations = false; }

// ensure counters exist regardless of which data path is used
$active = 0; $pending = 0; $banned = 0; $total = 0;

if ($hasRegistrations) {
  // simple pagination
  $perPage = 12;
  $page = max(1, (int)($_GET['page'] ?? 1));
  $offset = ($page - 1) * $perPage;
  // If a post_utme_registrations table also exists, merge both tables into a single paginated list
  try {
    $hasPost = (bool) $pdo->query("SHOW TABLES LIKE 'post_utme_registrations'")->fetch();
  } catch (Throwable $e) { $hasPost = false; }

  if ($hasPost) {
    // count total across both
    $countStmt = $pdo->prepare("SELECT (SELECT COUNT(*) FROM student_registrations) + (SELECT COUNT(*) FROM post_utme_registrations) AS c");
    $countStmt->execute();
    $total = (int)$countStmt->fetchColumn();

    // union both tables selecting common columns
    // Force a common collation for string columns to avoid 'Illegal mix of collations for operation UNION'
    $sql = "SELECT * FROM (
      SELECT sr.id, 'regular' AS registration_type,
        sr.first_name COLLATE utf8mb4_general_ci AS first_name,
        sr.last_name COLLATE utf8mb4_general_ci AS last_name,
        COALESCE(sr.email, u.email) COLLATE utf8mb4_general_ci AS email,
        sr.status COLLATE utf8mb4_general_ci AS status,
        sr.passport_path COLLATE utf8mb4_general_ci AS passport_path,
        sr.created_at AS created_at
        FROM student_registrations sr LEFT JOIN users u ON u.id = sr.user_id
      UNION ALL
      SELECT pr.id, 'post' AS registration_type,
        pr.first_name COLLATE utf8mb4_general_ci AS first_name,
        pr.surname COLLATE utf8mb4_general_ci AS last_name,
        pr.email COLLATE utf8mb4_general_ci AS email,
        pr.status COLLATE utf8mb4_general_ci AS status,
        pr.passport_photo COLLATE utf8mb4_general_ci AS passport_path,
        pr.created_at AS created_at
        FROM post_utme_registrations pr
    ) t ORDER BY t.created_at DESC LIMIT ? OFFSET ?";

    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(1, $perPage, PDO::PARAM_INT);
    $stmt->bindValue(2, $offset, PDO::PARAM_INT);
    $stmt->execute();
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
  } else {
    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM student_registrations");
    $countStmt->execute();
    $total = (int)$countStmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT sr.*, u.email, u.name AS user_name FROM student_registrations sr LEFT JOIN users u ON u.id = sr.user_id ORDER BY sr.created_at DESC LIMIT ? OFFSET ?");
    $stmt->bindValue(1, $perPage, PDO::PARAM_INT);
    $stmt->bindValue(2, $offset, PDO::PARAM_INT);
    $stmt->execute();
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

} else {
  // Fetch students (users with role slug 'student' or where role is null)
  $stmt = $pdo->prepare("SELECT u.*, r.name AS role_name, r.slug AS role_slug FROM users u LEFT JOIN roles r ON r.id = u.role_id WHERE r.slug = 'student' OR u.role_id IS NULL ORDER BY u.created_at DESC");
  $stmt->execute();
  $students = $stmt->fetchAll(PDO::FETCH_ASSOC);

  // Counts for legacy users list
  $total = count($students);
  $active = 0; $pending = 0; $banned = 0;
  foreach ($students as $s) {
    if ($s['is_active']==1) $active++;
    elseif ($s['is_active']==0) $pending++;
    elseif ($s['is_active']==2) $banned++;
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Students - HIGH Q SOLID ACADEMY</title>
  <link rel="stylesheet" href="../assets/css/users.css">
  <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
</head>
<body>
<?php include '../includes/header.php'; ?>
<?php include '../includes/sidebar.php'; ?>

<div class="users-page">

<?php
// Temporary debug banner: enable by visiting /HIGH-Q/admin./pages/students.php?dbg=1
if (!empty($_GET['dbg']) && $_GET['dbg'] === '1') {
  $hasPostVar = isset($hasPost) ? ($hasPost ? 'yes' : 'no') : 'unknown';
  $studentsCount = isset($students) && is_array($students) ? count($students) : 0;
  echo '<div style="background:#fff7cc;border:1px solid #ffe5a1;padding:12px;margin:12px;border-radius:6px;color:#422;">';
  echo '<strong>DEBUG:</strong> hasRegistrations=' . ($hasRegistrations ? 'yes' : 'no') . ' | hasPost=' . $hasPostVar . ' | total=' . intval($total) . ' | fetched=' . intval($studentsCount);
  echo '</div>';
}
?>

  <div class="summary-cards">
    <div class="card"><span class="icon"><i class='bx bx-user'></i></span><div><h3><?= $total ?></h3><p>Total Students</p></div></div>
    <div class="card"><span class="icon"><i class='bx bx-user-check'></i></span><div><h3><?= $active ?></h3><p>Active</p></div></div>
    <div class="card"><span class="icon"><i class='bx bx-time-five'></i></span><div><h3><?= $pending ?></h3><p>Pending</p></div></div>
    <div class="card"><span class="icon"><i class='bx bx-user-x'></i></span><div><h3><?= $banned ?></h3><p>Banned</p></div></div>
  </div>

  <div class="user-filters">
    <input type="text" id="searchInput" placeholder="Search students by name or email">
    <select id="statusFilter">
      <option value="">All Status</option>
      <option value="active">Active</option>
      <option value="pending">Pending</option>
      <option value="banned">Banned</option>
    </select>
    <select id="registrationTypeFilter" style="margin-left:8px;">
      <option value="">All Types</option>
      <option value="registration">Regular</option>
      <option value="post">Post-UTME</option>
    </select>
  </div>

  <div class="users-list" id="studentsList">
    <?php if (!empty($hasRegistrations)): ?>
  <?php foreach ($students as $s): ?>
  <div class="user-card" data-status="<?= htmlspecialchars($s['status'] ?? 'pending') ?>" data-id="<?= $s['id'] ?>" data-type="<?= htmlspecialchars($s['registration_type'] ?? 'registration') ?>">
          <div class="card-left">
            <?php $passportThumb = $s['passport_path'] ?? null; ?>
            <img src="<?= htmlspecialchars($passportThumb ?: '../../public/assets/images/hq-logo.jpeg') ?>" class="avatar-sm card-avatar" onerror="this.src='../../public/assets/images/hq-logo.jpeg'">
            <div class="card-meta">
              <div class="card-name"><?= htmlspecialchars($s['first_name'] . ' ' . ($s['last_name'] ?: '')) ?></div>
              <div class="card-email"><?= htmlspecialchars($s['email'] ?? $s['user_name'] ?? '') ?></div>
              <div class="card-badges">
                <span class="role-badge role-student">Student</span>
                <span class="status-badge <?= ($s['status']==='paid' || $s['status']==='confirmed') ? 'status-active' : 'status-pending' ?>"><?= htmlspecialchars(ucfirst($s['status'])) ?></span>
              </div>
            </div>
          </div>
          <div class="card-right">
            <div class="card-actions">
              <!-- view icon removed as requested -->
              <?php if (!empty($s['status'])): ?>
                <button class="btn btn-approve inline-confirm" data-id="<?= $s['id'] ?>">Confirm</button>
                <button class="btn btn-banish inline-reject" data-id="<?= $s['id'] ?>">Reject</button>
              <?php endif; ?>

                <!-- Export registration (zip) - always available for registrations -->
                <button class="btn btn-export" type="button" data-id="<?= $s['id'] ?>" data-type="<?= htmlspecialchars($s['registration_type'] ?? 'registration') ?>" onclick="return false;">Export</button>
              <form method="post" action="index.php?pages=students&action=delete&id=<?= $s['id'] ?>" class="inline-form student-delete-form">
                <input type="hidden" name="csrf_token" value="<?= $csrf; ?>">
                <button type="submit" class="btn-banish">Delete</button>
              </form>
            </div>
            <div class="card-details" style="margin-top:10px;padding:12px;border-radius:6px;background:#fff;">
              <div><strong>DOB:</strong> <?= htmlspecialchars($s['date_of_birth'] ?? '-') ?></div>
              <div><strong>Address:</strong> <?= htmlspecialchars(strlen($s['home_address']??'')>80 ? substr($s['home_address'],0,80).'...' : ($s['home_address']??'-')) ?></div>
              <div><strong>Emergency:</strong> <?= htmlspecialchars(($s['emergency_contact_name'] ?? '-') . ' / ' . ($s['emergency_contact_phone'] ?? '-')) ?></div>
              <div style="margin-top:8px;"><a href="#" class="view-registration" data-id="<?= $s['id'] ?>" data-type="<?= htmlspecialchars($s['registration_type'] ?? 'registration') ?>">View full registration</a></div>
            </div>
          </div>
        </div>
      <?php endforeach; ?>

      <!-- Pagination -->
      <?php if (!empty($total) && isset($perPage)): $pages = ceil($total / $perPage); ?>
        <div class="pagination" style="margin-top:16px;display:flex;gap:8px;align-items:center;">
          <?php for ($p=1;$p<=$pages;$p++): ?>
            <a href="index.php?pages=students&page=<?= $p ?>" class="btn <?= $p==($page??1)?'btn-active':'' ?>"><?= $p ?></a>
          <?php endfor; ?>
        </div>
      <?php endif; ?>

    <?php else: ?>
      <?php foreach ($students as $s):
        $status = $s['is_active']==1 ? 'Active' : ($s['is_active']==0 ? 'Pending' : 'Banned');
        $roleClass = 'role-student';
        // Try to find a registration linked to this user so we can show passport and allow export
        $regStmt = $pdo->prepare('SELECT id, passport_path, status, email FROM student_registrations WHERE user_id = ? LIMIT 1');
        $regStmt->execute([$s['id']]);
        $regRow = $regStmt->fetch(PDO::FETCH_ASSOC);
        // If no registration linked by user_id (older/guest registrations), try matching by email
        if (!$regRow) {
          $regStmt2 = $pdo->prepare('SELECT id, passport_path, status FROM student_registrations WHERE email = ? LIMIT 1');
          $regStmt2->execute([$s['email']]);
          $regRow = $regStmt2->fetch(PDO::FETCH_ASSOC);
        }
        $linkedRegId = $regRow['id'] ?? null;
        $passportThumb = $regRow['passport_path'] ?? ($s['avatar'] ?? null);
      ?>
  <div class="user-card" data-status="<?= $s['is_active']==1?'active':($s['is_active']==0?'pending':'banned') ?>" data-id="<?= $linkedRegId ?? '' ?>" data-type="<?= isset($linkedRegId) && $linkedRegId ? 'registration' : 'user' ?>">
        <div class="card-left">
          <img src="<?= htmlspecialchars($passportThumb ?: '../../public/assets/images/hq-logo.jpeg') ?>" class="avatar-sm card-avatar" onerror="this.src='../../public/assets/images/hq-logo.jpeg'">
          <div class="card-meta">
            <div class="card-name"><?= htmlspecialchars($s['name']) ?></div>
            <div class="card-email"><?= htmlspecialchars($s['email']) ?></div>
            <div class="card-badges">
              <span class="role-badge <?= $roleClass ?>">Student</span>
              <span class="status-badge <?= $status==='Active' ? 'status-active' : ($status==='Pending' ? 'status-pending' : 'status-banned') ?>"><?= $status ?></span>
            </div>
          </div>
        </div>
        <div class="card-right">
          <div class="card-actions">
            <!-- view icon removed as requested -->
              <?php if ($s['id'] != 1 && $s['id'] != $_SESSION['user']['id']): ?>
              <?php if ($s['is_active'] == 1): ?>
                <form method="post" action="index.php?pages=students&action=deactivate&id=<?= $s['id'] ?>" class="inline-form">
                  <input type="hidden" name="csrf_token" value="<?= $csrf; ?>">
                  <button type="submit" class="btn-banish">Deactivate</button>
                </form>
              <?php elseif ($s['is_active'] == 0): ?>
                <form method="post" action="index.php?pages=students&action=activate&id=<?= $s['id'] ?>" class="inline-form">
                  <input type="hidden" name="csrf_token" value="<?= $csrf; ?>">
                  <button type="submit" class="btn-approve">Activate</button>
                </form>
              <?php else: ?>
                <form method="post" action="index.php?pages=students&action=activate&id=<?= $s['id'] ?>" class="inline-form">
                  <input type="hidden" name="csrf_token" value="<?= $csrf; ?>">
                  <button type="submit" class="btn-approve">Reactivate</button>
                </form>
              <?php endif; ?>
              <?php if (!empty($linkedRegId)): ?>
                <button class="btn btn-export" type="button" data-id="<?= $linkedRegId ?>" data-type="registration">Export</button>
              <?php endif; ?>
              <form method="post" action="index.php?pages=students&action=delete&id=<?= $s['id'] ?>" class="inline-form student-delete-form">
                <input type="hidden" name="csrf_token" value="<?= $csrf; ?>">
                <button type="submit" class="btn-banish">Delete</button>
              </form>
            <?php endif; ?>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>

</div>

<?php include '../includes/footer.php'; ?>

<script>
// Replace native confirm for student delete forms with SweetAlert2
document.addEventListener('submit', function(e){
  var f = e.target.closest && e.target.closest('.student-delete-form');
  if (!f) return;
  e.preventDefault();
  Swal.fire({
    title: 'Delete?',
    text: 'Delete this record? This action cannot be undone.',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#d33',
    confirmButtonText: 'Yes, delete'
  }).then(function(res){ if (res.isConfirmed) f.submit(); });
});
</script>
<script>
// Client-side search/filter
const searchInput = document.getElementById('searchInput');
const statusFilter = document.getElementById('statusFilter');
const registrationTypeFilter = document.getElementById('registrationTypeFilter');
const studentsList = document.getElementById('studentsList');

function filterStudents(){
  const q = searchInput.value.toLowerCase();
  const status = statusFilter.value;
  const regType = registrationTypeFilter ? registrationTypeFilter.value : '';
  document.querySelectorAll('#studentsList .user-card').forEach(card=>{
    const name = card.querySelector('.card-name').textContent.toLowerCase();
    const email = card.querySelector('.card-email').textContent.toLowerCase();
    const cardStatus = card.dataset.status;
    const matchesQ = q==='' || name.includes(q) || email.includes(q);
    const matchesStatus = status==='' || cardStatus===status;
    const cardType = card.getAttribute('data-type') || '';
    const matchesType = regType==='' || cardType === regType;
    card.style.display = (matchesQ && matchesStatus && matchesType) ? '' : 'none';
  });
}
searchInput.addEventListener('input', filterStudents);
statusFilter.addEventListener('change', filterStudents);
if (registrationTypeFilter) registrationTypeFilter.addEventListener('change', filterStudents);


searchInput.addEventListener('input', filterStudents);
statusFilter.addEventListener('change', filterStudents);

// View registration link behavior: fetch registration JSON and show modal with Confirm/Reject
const __students_csrf = '<?= $csrf ?>';
document.querySelectorAll('.view-registration').forEach(btn => {
  btn.addEventListener('click', function(e) {
    e.preventDefault();
    const id = this.dataset.id;
    const body = new URLSearchParams();
  body.append('action', 'view_registration');
  body.append('id', id);
  // include registration type (if provided by link)
  var type = this.getAttribute('data-type') || 'registration';
  body.append('type', type);
    // include CSRF token if desired by server-side protections
    body.append('csrf_token', __students_csrf);

  (typeof window.hqFetchCompat === 'function' ? window.hqFetchCompat('index.php?pages=students', { method: 'POST', credentials: 'same-origin', headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With':'XMLHttpRequest' }, body: body.toString() }) : fetch('index.php?pages=students', { method: 'POST', credentials: 'same-origin', headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With':'XMLHttpRequest' }, body: body.toString() }))
    .then(function(r){ if (r && r._parsed) return Promise.resolve(r._parsed); if (r && typeof r.json === 'function') return r.json(); return Promise.resolve(r); })
    .then(resp => {
        if (resp.success) {
            const d = resp.data;
            var html = '<div style="max-height:640px;overflow:auto;padding-right:6px;">';
            // passport
            if (d.passport_photo) html += '<div style="text-align:center;margin-bottom:12px;"><img src="'+d.passport_photo+'" style="max-width:160px;border-radius:6px;" onerror="this.style.display=\'none\'"></div>';

            // Detect post-utme by presence of jamb_registration_number or olevel_results_parsed or institution
            var isPost = !!(d.jamb_registration_number || d.institution || d.olevel_results_parsed);
            if (isPost) {
              html += '<h3 style="margin-top:0">Post-UTME Registration</h3>';
              html += '<div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">';
              html += '<div><strong>Institution:</strong> ' + (d.institution||'-') + '</div>';
              html += '<div><strong>Registered At:</strong> ' + (d.created_at||'-') + '</div>';
              html += '<div><strong>Name:</strong> ' + ((d.first_name||'') + ' ' + (d.surname||'' || '')) + '</div>';
              html += '<div><strong>Gender:</strong> ' + (d.gender||'-') + '</div>';
              html += '<div><strong>DOB:</strong> ' + (d.dob||'-') + '</div>';
              html += '<div><strong>Email:</strong> ' + (d.email||'-') + '</div>';
              html += '<div style="grid-column:1/3"><strong>Address:</strong> ' + (d.address||'-') + '</div>';
              html += '</div>';

              // JAMB
              html += '<h4 style="margin-top:12px">JAMB</h4>';
              html += '<div><strong>Reg No:</strong> ' + (d.jamb_registration_number||'-') + ' &nbsp; <strong>Score:</strong> ' + (d.jamb_score||'-') + '</div>';

              // Subjects table (O'Level)
              if (d.olevel_results_parsed && Array.isArray(d.olevel_results_parsed.subjects)) {
                html += '<h4 style="margin-top:12px">O\'Level Subjects & Grades</h4>';
                html += '<table style="width:100%;border-collapse:collapse">';
                html += '<thead><tr style="background:#f6f7fb"><th style="text-align:left;padding:8px;border:1px solid #eee">Subject</th><th style="text-align:left;padding:8px;border:1px solid #eee">Grade</th></tr></thead><tbody>';
                d.olevel_results_parsed.subjects.forEach(function(s){ html += '<tr><td style="padding:8px;border:1px solid #eee">' + (s.subject||'') + '</td><td style="padding:8px;border:1px solid #eee">' + (s.grade||'') + '</td></tr>'; });
                html += '</tbody></table>';
                if (d.olevel_results_parsed.waec_token || d.olevel_results_parsed.waec_serial) {
                  html += '<div style="margin-top:8px"><strong>WAEC Token:</strong> ' + (d.olevel_results_parsed.waec_token||'-') + ' &nbsp; <strong>Serial:</strong> ' + (d.olevel_results_parsed.waec_serial||'-') + '</div>';
                }
                if (d.olevel_results_parsed.raw_text) html += '<div style="margin-top:8px"><strong>Raw O\'Level Text:</strong><pre style="white-space:pre-wrap">' + (d.olevel_results_parsed.raw_text||'') + '</pre></div>';
              }

              // Schools
              html += '<h4 style="margin-top:12px">Schools</h4>';
              html += '<div><strong>Primary:</strong> ' + (d.primary_school||'-') + ' <em>(' + (d.primary_year_ended||'-') + ')</em></div>';
              html += '<div><strong>Secondary:</strong> ' + (d.secondary_school||'-') + ' <em>(' + (d.secondary_year_ended||'-') + ')</em></div>';

              // Parent / Sponsor / NOK
              html += '<h4 style="margin-top:12px">Parent / Sponsor / Next of Kin</h4>';
              html += '<div><strong>Father:</strong> ' + (d.father_name||'-') + ' — ' + (d.father_phone||'-') + ' — ' + (d.father_occupation||'-') + '</div>';
              html += '<div><strong>Mother:</strong> ' + (d.mother_name||'-') + ' — ' + (d.mother_phone||'-') + ' — ' + (d.mother_occupation||'-') + '</div>';
              html += '<div><strong>Parent Email:</strong> ' + (d.parent_email||'-') + '</div>';
              html += '<div><strong>Sponsor:</strong> ' + (d.sponsor_name||'-') + ' — ' + (d.sponsor_phone||'-') + ' — ' + (d.sponsor_relationship||'-') + '</div>';
              html += '<div><strong>Next of Kin:</strong> ' + (d.nok_name||'-') + ' — ' + (d.nok_phone||'-') + ' — ' + (d.nok_relationship||'-') + '</div>';

              // Course & Institution choices
              html += '<h4 style="margin-top:12px">Course & Institution Choices</h4>';
              html += '<div><strong>Course 1:</strong> ' + (d.course_first_choice||'-') + ' &nbsp; <strong>Course 2:</strong> ' + (d.course_second_choice||'-') + '</div>';
              html += '<div><strong>Institution choice:</strong> ' + (d.institution_first_choice||'-') + '</div>';

              // Extras
              html += '<div style="margin-top:12px"><strong>JAMB Subjects:</strong> ' + (d.jamb_subjects ? (typeof d.jamb_subjects === 'string' ? d.jamb_subjects : JSON.stringify(d.jamb_subjects)) : '-') + '</div>';
              html += '<div style="margin-top:8px"><strong>Notes:</strong> ' + (d.notes||'-') + '</div>';

            } else {
              // regular registration rendering (existing behavior)
              html += '<h4>'+ (d.first_name||'') + ' ' + (d.last_name||'') + '</h4>';
              html += '<p><strong>Email:</strong> ' + (d.email||'') + '</p>';
              if (d.date_of_birth) html += '<p><strong>Date of Birth:</strong> ' + d.date_of_birth + '</p>';
              if (d.home_address) html += '<p><strong>Home Address:</strong> ' + d.home_address + '</p>';
              if (d.previous_education) html += '<p><strong>Previous Education:</strong> ' + d.previous_education + '</p>';
              if (d.jamb_registration_number) html += '<p><strong>JAMB Reg No:</strong> ' + d.jamb_registration_number + ' — Score: ' + (d.jamb_score||'') + '</p>';
              if (d.institution) html += '<p><strong>Institution:</strong> ' + d.institution + '</p>';
              if (d.status) html += '<p><strong>Status:</strong> ' + d.status + '</p>';
              if (d.created_at) html += '<p><strong>Registered At:</strong> ' + d.created_at + '</p>';
            }

            html += '</div>';
            document.getElementById('registrationContent').innerHTML = html;
        const modal = document.getElementById('registrationViewModal');
        modal.dataset.regId = id;
        modal.style.display = 'flex';
      } else {
        Swal.fire('Error', resp.error || 'Registration not found', 'error');
      }
    }).catch(err => { console.error(err); Swal.fire('Error','Failed to load registration','error'); });
  });
});
</script>

<script>
// Create payment link via AJAX and show result
async function createPaymentLink(studentId) {
  try {
    const body = new URLSearchParams();
    body.append('action', 'create_payment_link');
    body.append('id', studentId);

  const res = await (typeof window.hqFetchCompat === 'function' ? window.hqFetchCompat('index.php?pages=students', { method: 'POST', credentials: 'same-origin', headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' }, body: body.toString() }) : fetch('index.php?pages=students', { method: 'POST', credentials: 'same-origin', headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' }, body: body.toString() }));
    const data = (res && res._parsed) ? res._parsed : (res && typeof res.json === 'function' ? await res.json() : res);
    if (data.success) {
      Swal.fire({
        title: 'Payment Link Created ✅',
        html: `
          <p>Reference: <code>${data.reference}</code></p>
          <p><a href="${data.link}" target="_blank">${data.link}</a></p>
        `,
        icon: 'success',
        timer: 10000,
        showConfirmButton: true
      });
    } else {
      Swal.fire('Error', data.error || 'Unknown error', 'error');
    }
  } catch (err) {
    Swal.fire('Error', err.message || 'Unexpected error', 'error');
  }
}

// Hook up button in registration modal
const regCreatePaymentBtn = document.getElementById('regCreatePaymentBtn');
if (regCreatePaymentBtn) {
  regCreatePaymentBtn.addEventListener('click', () => {
    const id = regModal.dataset.regId;
    if (!id) return Swal.fire('Error','No registration selected','error');
    createPaymentLink(id);
  });
}
</script>

<!-- Approve & Message Modal -->
<div id="studentModal" class="modal" style="display:none;position:fixed;z-index:9999;left:0;top:0;width:100%;height:100%;background:rgba(0,0,0,0.4);align-items:center;justify-content:center;">
  <div class="modal-content" style="background:#fff;padding:18px;border-radius:8px;max-width:560px;width:94%;box-shadow:0 8px 24px rgba(0,0,0,0.2);">
    <h3 id="modalTitle">Message Student</h3>
    <form id="modalForm" method="post">
      <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
      <div style="margin-bottom:8px;"><label>To: <span id="modalStudentName"></span> (<span id="modalStudentEmail"></span>)</label></div>
      <div style="margin-bottom:8px;"><label>Message</label><textarea name="message" rows="6" required style="width:100%;padding:8px;border:1px solid #ccc;border-radius:6px;"></textarea></div>
      <div style="display:flex;align-items:center;gap:12px;margin-bottom:8px;"><label style="display:flex;align-items:center;gap:8px;"><input type="checkbox" name="activate" id="modalActivate"> Activate student</label></div>
      <div style="display:flex;justify-content:flex-end;gap:8px;"><button type="button" id="modalCancel" class="btn">Cancel</button><button type="submit" class="btn btn-approve">Send & Close</button></div>
    </form>
  </div>
</div>

<script>
// Modal wiring
const modal = document.getElementById('studentModal');
const modalTitle = document.getElementById('modalTitle');
const modalStudentName = document.getElementById('modalStudentName');
const modalStudentEmail = document.getElementById('modalStudentEmail');
const modalForm = document.getElementById('modalForm');
const modalCancel = document.getElementById('modalCancel');

function openStudentModal(id, name, email){
  modal.style.display = 'flex';
  modalStudentName.textContent = name;
  modalStudentEmail.textContent = email;
  modalForm.action = `index.php?pages=students&action=send_message&id=${id}`;
}

modalCancel.addEventListener('click', ()=> modal.style.display='none');

// Attach openers to each student card view button (augment existing)
document.querySelectorAll('.user-card .btn-view').forEach(btn=>{
  btn.addEventListener('contextmenu', e=>e.preventDefault());
  btn.addEventListener('dblclick', e=>{
    // double-click to open approve/message modal
    const card = btn.closest('.user-card');
    const id = btn.dataset.userId;
    const name = card.querySelector('.card-name').textContent.trim();
    const email = card.querySelector('.card-email').textContent.trim();
    openStudentModal(id, name, email);
  });
});

// Close modal when clicking outside content
modal.addEventListener('click', (e)=>{ if(e.target===modal) modal.style.display='none'; });
</script>

<!-- Registration View & Approve/Reject Modal -->
<div id="registrationViewModal" class="modal" style="display:none;position:fixed;z-index:9999;left:0;top:0;width:100%;height:100%;background:rgba(0,0,0,0.45);align-items:center;justify-content:center;">
  <div class="modal-content" style="background:#fff;padding:18px;border-radius:8px;max-width:780px;width:96%;box-shadow:0 8px 24px rgba(0,0,0,0.2);">
    <h3 id="regModalTitle">Registration</h3>
    <div id="registrationContent" style="margin-bottom:12px;"></div>
    <div style="display:flex;gap:8px;justify-content:flex-end;">
      <button id="regClose" type="button" class="btn">Close</button>
      <form id="regConfirmForm" method="post" style="display:inline;">
        <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
        <button type="submit" class="btn btn-approve">Confirm</button>
      </form>
      <button id="regRejectBtn" class="btn btn-banish">Reject</button>
      <button id="regCreatePaymentBtn" class="btn header-cta" style="margin-left:8px;">Create payment link</button>
    </div>
  </div>
</div>

<script>
// Registration modal handlers
const regModal = document.getElementById('registrationViewModal');
const regClose = document.getElementById('regClose');
const regConfirmForm = document.getElementById('regConfirmForm');
const regRejectBtn = document.getElementById('regRejectBtn');

regClose.addEventListener('click', ()=> regModal.style.display='none');
regModal.addEventListener('click', (e)=>{ if (e.target === regModal) regModal.style.display='none'; });

// When confirm form is submitted, POST to confirm_registration
// Helper to POST action and reload
async function postAction(url, formData){
  const res = await (typeof window.hqFetchCompat === 'function' ? window.hqFetchCompat(url, { method: 'POST', body: formData, credentials: 'same-origin', headers: {'X-Requested-With':'XMLHttpRequest'} }) : fetch(url, { method: 'POST', body: formData, credentials: 'same-origin', headers: {'X-Requested-With':'XMLHttpRequest'} }));
  let payload = null;
  try { payload = (res && res._parsed) ? res._parsed : (res && typeof res.json === 'function' ? await res.json() : null); } catch (e) { payload = null; }
  if (!res.ok || !payload) {
    const msg = payload && (payload.message || payload.error) ? (payload.message || payload.error) : 'Server error';
    await Swal.fire('Error', msg, 'error');
    throw new Error(msg);
  }

  const ok = (payload.status && payload.status === 'ok') || (payload.success === true);
  if (!ok) {
    const msg = payload.message || payload.error || 'Operation failed';
    await Swal.fire('Error', msg, 'error');
    throw new Error(msg);
  }

  // Show success with optional details (reference / email_sent / email)
  let details = '';
  if (payload.reference) details += `<div style="margin-top:8px"><strong>Reference:</strong> <code>${payload.reference}</code></div>`;
  if (typeof payload.email_sent !== 'undefined') details += `<div><strong>Email sent:</strong> ${payload.email_sent ? 'Yes' : 'No'}</div>`;
  if (payload.email) details += `<div><strong>Email:</strong> ${payload.email}</div>`;
  const message = payload.message || 'Operation completed';
  const html = message + (details ? `<hr/>${details}` : '');
  await Swal.fire({ title: 'Success', html: html, icon: 'success' });
  return payload;
}

// Confirm (modal) handler
regConfirmForm.addEventListener('submit', async function(e){
  e.preventDefault();
  const id = regModal.dataset.regId;
  if (!id) return Swal.fire('Error','No registration selected','error');
  const choice = await Swal.fire({ title: 'Confirm registration?', text: 'Create payment reference to send to registrant?', icon:'question', showDenyButton:true, showCancelButton:true, confirmButtonText:'Confirm only', denyButtonText:'Create payment & confirm' });
  try {
    if (choice.isConfirmed) {
      const fd = new FormData(); fd.append('csrf_token','<?= $csrf ?>');
  const payload = await postAction(`index.php?pages=students&action=confirm_registration&id=${id}`, fd);
  // success shown by postAction; reload to reflect changes
  window.location = 'index.php?pages=students';
    } else if (choice.isDenied) {
      const { value: formValues } = await Swal.fire({
        title: 'Create payment',
        html: '<input id="swal-amount" class="swal2-input" placeholder="Amount">' +
              '<select id="swal-method" class="swal2-select"><option value="bank">Bank Transfer</option><option value="online">Online</option></select>',
        focusConfirm: false,
        preConfirm: () => ({ amount: document.getElementById('swal-amount').value, method: document.getElementById('swal-method').value })
      });
      if (!formValues) return;
      const amt = parseFloat(formValues.amount || 0);
      if (!amt || amt <= 0) return Swal.fire('Error','Provide a valid amount','error');
      const fd = new FormData(); fd.append('csrf_token','<?= $csrf ?>'); fd.append('create_payment','1'); fd.append('amount', amt); fd.append('method', formValues.method || 'bank');
  const payload = await postAction(`index.php?pages=students&action=confirm_registration&id=${id}`, fd);
  window.location = 'index.php?pages=students';
    }
  } catch (err) {
    Swal.fire('Error','Failed to confirm','error');
  }
});

// Reject (modal) via SweetAlert2 textarea
regRejectBtn.addEventListener('click', ()=>{
  const id = regModal.dataset.regId;
  if (!id) return Swal.fire('Error','No registration selected','error');
  Swal.fire({
    title: 'Reject registration',
    input: 'textarea',
    inputLabel: 'Reason (optional)',
    inputPlaceholder: 'Enter a reason for rejection',
    showCancelButton: true,
    confirmButtonText: 'Reject',
    cancelButtonText: 'Cancel',
    inputAttributes: { 'aria-label': 'Rejection reason' }
  }).then(result=>{
    if (result.isConfirmed) {
      (async ()=>{
        try {
      const fd = new FormData(); fd.append('csrf_token', '<?= $csrf ?>'); fd.append('reason', result.value || '');
  const payload = await postAction(`index.php?pages=students&action=reject_registration&id=${id}`, fd);
          // update UI: remove buttons and mark status
          const card = document.querySelector(`.user-card[data-status][data-id='${id}']`) || document.querySelector(`.user-card [data-id='${id}']`)?.closest('.user-card');
          if (card) {
            card.querySelectorAll('.inline-confirm, .inline-reject').forEach(b=>b.remove());
            const badge = card.querySelector('.status-badge'); if (badge) { badge.textContent = 'Rejected'; badge.classList.remove('status-pending'); badge.classList.remove('status-active'); badge.classList.add('status-banned'); }
          }
        } catch (e) { Swal.fire('Error','Failed to reject','error'); }
      })();
    }
  });
});

// Delegated inline Confirm/Reject handlers
document.addEventListener('click', function(e){
  const t = e.target.closest('.inline-confirm');
    if (t) {
    const id = t.dataset.id;
    if (!id) return Swal.fire('Error','No registration id','error');
    // Ask if admin wants to create a payment right away
    Swal.fire({
      title: 'Confirm registration?',
      text: 'Do you want to create a payment reference to send to the registrant now?',
      icon: 'question',
      showDenyButton: true,
      showCancelButton: true,
      confirmButtonText: 'Confirm only',
      denyButtonText: 'Create payment & confirm'
    }).then(async res=>{
        if (res.isConfirmed) {
              const fd=new FormData(); fd.append('csrf_token','<?= $csrf ?>');
              try {
              const payload = await postAction(`index.php?pages=students&action=confirm_registration&id=${id}`, fd);
                // postAction already displayed success and details. update UI: hide confirm/reject buttons and set status badge
                const card = document.querySelector(`.user-card[data-status][data-id='${id}']`) || document.querySelector(`.user-card [data-id='${id}']`)?.closest('.user-card');
                if (card) {
                  card.querySelectorAll('.inline-confirm, .inline-reject').forEach(b=>b.remove());
                  const badge = card.querySelector('.status-badge'); if (badge) { badge.textContent = 'Confirmed'; badge.classList.remove('status-pending'); badge.classList.add('status-active'); }
                }
              } catch(e){ Swal.fire('Error','Failed to confirm','error'); }
      } else if (res.isDenied) {
        // Prompt for amount and method
        const { value: formValues } = await Swal.fire({
          title: 'Create payment',
          html:
            '<input id="swal-amount" class="swal2-input" placeholder="Amount">' +
            '<select id="swal-method" class="swal2-select"><option value="bank">Bank Transfer</option><option value="online">Online</option></select>',
          focusConfirm: false,
          preConfirm: () => {
            return {
              amount: document.getElementById('swal-amount').value,
              method: document.getElementById('swal-method').value
            }
          }
        });
        if (!formValues) return;
        const amt = parseFloat(formValues.amount || 0);
        if (!amt || amt <= 0) return Swal.fire('Error','Provide a valid amount','error');
        const fd=new FormData(); fd.append('csrf_token','<?= $csrf ?>'); fd.append('create_payment','1'); fd.append('amount', amt); fd.append('method', formValues.method || 'bank');
        try {
          const payload = await postAction(`index.php?pages=students&action=confirm_registration&id=${id}`, fd);
          // update UI similar to above
          const card = document.querySelector(`.user-card[data-status][data-id='${id}']`) || document.querySelector(`.user-card [data-id='${id}']`)?.closest('.user-card');
          if (card) {
            card.querySelectorAll('.inline-confirm, .inline-reject').forEach(b=>b.remove());
            const badge = card.querySelector('.status-badge'); if (badge) { badge.textContent = 'Confirmed'; badge.classList.remove('status-pending'); badge.classList.add('status-active'); }
          }
        } catch(e){ Swal.fire('Error','Failed to create payment','error'); }
      }
    });
    return;
  }
  const r = e.target.closest('.inline-reject');
    if (r) {
    const id = r.dataset.id;
    if (!id) return Swal.fire('Error','No registration id','error');
    Swal.fire({
      title: 'Reject registration',
      input: 'textarea',
      inputLabel: 'Reason (optional)',
      inputPlaceholder: 'Reason for rejection',
      showCancelButton: true,
      confirmButtonText: 'Reject'
  }).then(result=>{ if (result.isConfirmed) { const fd=new FormData(); fd.append('csrf_token','<?= $csrf ?>'); fd.append('reason', result.value || ''); postAction(`index.php?pages=students&action=reject_registration&id=${id}`, fd).catch(err=>Swal.fire('Error','Failed to reject','error')); } });
    return;
  }
});
</script>

<script>
// Export registration button handler
document.addEventListener('click', function(e){
  var btn = e.target.closest && e.target.closest('[data-id]') && (e.target.closest('[data-id]').classList.contains('btn') || e.target.classList.contains('btn')) ? e.target.closest('[data-id]') : null;
  // safer: find elements with explicit btn-export class or data-export attribute
  if (!btn) btn = e.target.closest('.btn-export');
  if (!btn) return;
  if (!btn.classList.contains('btn-export')) return; // only handle export buttons
  e.preventDefault();
  var id = btn.getAttribute('data-id');
  if (!id) return Swal.fire('Error','Registration id missing','error');
  Swal.fire({
    title: 'Export registration',
    text: 'This will download a ZIP containing the registration details and passport (if available). Continue?',
    icon: 'question',
    showCancelButton: true,
    confirmButtonText: 'Export',
  }).then(function(res){
    if (!res.isConfirmed) return;
    // Open export endpoint in new tab so the file download begins
  var type = btn.getAttribute('data-type') || 'registration';
  var url = 'api/export_registration.php?id=' + encodeURIComponent(id) + '&type=' + encodeURIComponent(type);
    window.open(url, '_blank');
  });
});
</script>

</body>
</html>
