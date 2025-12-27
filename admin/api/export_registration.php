<?php
// admin/api/export_registration.php
$logFile = __DIR__ . '/../../storage/logs/export_errors.log';
// Basic error/shutdown handlers to capture fatal errors when run under Apache
ini_set('display_errors', 0);
error_reporting(E_ALL);
set_error_handler(function($errno, $errstr, $errfile, $errline) use ($logFile){
    $msg = "[".date('c')."] PHP Error: $errstr in $errfile:$errline (errno=$errno)\n";
    @file_put_contents($logFile, $msg, FILE_APPEND | LOCK_EX);
});
register_shutdown_function(function() use ($logFile){
    $err = error_get_last();
    if ($err) {
        $msg = "[".date('c')."] Shutdown: " . ($err['message'] ?? '') . " in " . ($err['file'] ?? '') . ":" . ($err['line'] ?? '') . "\n";
        @file_put_contents($logFile, $msg, FILE_APPEND | LOCK_EX);
    }
});

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

// Check for bulk CSV export action
$action = $_GET['action'] ?? '';

if ($action === 'export_csv') {
    // Export all registrations to CSV
    requirePermission('academic');
    
    $source = $_GET['source'] ?? 'regular';
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

// PDF Export for bulk registrations
if ($action === 'export_pdf') {
    requirePermission('academic');
    
    // Require Dompdf
    require_once __DIR__ . '/../../vendor/autoload.php';
    
    if (!class_exists('\Dompdf\Dompdf')) {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'PDF library not installed']);
        exit;
    }
    
    $source = $_GET['source'] ?? 'regular';
    $table = 'student_registrations';
    
    if ($source === 'postutme') {
        $table = 'post_utme_registrations';
    } elseif ($source === 'universal') {
        $table = 'universal_registrations';
    }
    
    try {
        $check = $pdo->query("SHOW TABLES LIKE '$table'")->fetch();
        if (!$check) {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Table not found']); 
            exit;
        }
        
        $stmt = $pdo->query("SELECT * FROM $table ORDER BY created_at DESC");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($rows)) {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'No registrations found']); 
            exit;
        }
        
        // Build HTML for PDF
        $html = '<!DOCTYPE html><html><head><meta charset="UTF-8">
        <style>
            body { font-family: Arial, sans-serif; font-size: 10px; }
            h1 { color: #0b1a2c; font-size: 18px; border-bottom: 2px solid #ffd600; padding-bottom: 10px; }
            table { width: 100%; border-collapse: collapse; margin-top: 15px; }
            th { background: #0b1a2c; color: #ffd600; padding: 8px 5px; text-align: left; font-size: 9px; }
            td { padding: 6px 5px; border-bottom: 1px solid #ddd; font-size: 9px; }
            tr:nth-child(even) { background: #f9f9f9; }
            .header-logo { text-align: center; margin-bottom: 20px; }
            .header-logo h2 { color: #0b1a2c; margin: 0; }
            .header-logo p { color: #666; margin: 5px 0; }
            .footer { text-align: center; margin-top: 20px; font-size: 8px; color: #666; }
        </style></head><body>';
        
        $html .= '<div class="header-logo">';
        $html .= '<h2>HIGH Q SOLID ACADEMY</h2>';
        $html .= '<p>Registration Export - ' . ucfirst($source) . ' | Generated: ' . date('F j, Y g:i A') . '</p>';
        $html .= '</div>';
        
        $html .= '<h1>All Registrations (' . count($rows) . ' total)</h1>';
        $html .= '<table><thead><tr>';
        $html .= '<th>ID</th><th>Name</th><th>Email</th><th>Phone</th><th>Program</th><th>Status</th><th>Date</th>';
        $html .= '</tr></thead><tbody>';
        
        foreach ($rows as $row) {
            $name = trim(($row['surname'] ?? $row['last_name'] ?? '') . ' ' . ($row['other_names'] ?? $row['first_name'] ?? ''));
            $program = $row['program_type'] ?? $row['exam_type'] ?? $row['course_first_choice'] ?? '-';
            $html .= '<tr>';
            $html .= '<td>' . htmlspecialchars($row['id']) . '</td>';
            $html .= '<td>' . htmlspecialchars($name) . '</td>';
            $html .= '<td>' . htmlspecialchars($row['email'] ?? '-') . '</td>';
            $html .= '<td>' . htmlspecialchars($row['phone'] ?? $row['phone_number'] ?? '-') . '</td>';
            $html .= '<td>' . htmlspecialchars($program) . '</td>';
            $html .= '<td>' . htmlspecialchars($row['status'] ?? $row['payment_status'] ?? '-') . '</td>';
            $html .= '<td>' . htmlspecialchars(date('M j, Y', strtotime($row['created_at']))) . '</td>';
            $html .= '</tr>';
        }
        
        $html .= '</tbody></table>';
        $html .= '<div class="footer">HIGH Q SOLID ACADEMY - Always Ahead of Others | © ' . date('Y') . '</div>';
        $html .= '</body></html>';
        
        // Generate PDF
        $options = new \Dompdf\Options();
        $options->set('isRemoteEnabled', true);
        $options->set('isHtml5ParserEnabled', true);
        
        $dompdf = new \Dompdf\Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();
        
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="registrations_' . $source . '_' . date('Y-m-d_His') . '.pdf"');
        echo $dompdf->output();
        exit;
        
    } catch (Throwable $e) {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'PDF Export failed: ' . $e->getMessage()]);
        exit;
    }
}

header('Content-Type: application/json');

// Quick runtime checks
if (!class_exists('ZipArchive')) {
    $m = 'ZipArchive extension is not available on this PHP installation. Please enable the zip extension.';
    @file_put_contents($logFile, "[".date('c')."] Export error: $m\n", FILE_APPEND | LOCK_EX);
    echo json_encode(['success'=>false,'error'=>$m]);
    exit;
}
$regId = intval($_GET['id'] ?? 0);
if (!$regId) { echo json_encode(['success'=>false,'error'=>'Missing id']); exit; }
try {
    $stmt = $pdo->prepare('SELECT sr.*, GROUP_CONCAT(sp.course_id) AS courses FROM student_registrations sr LEFT JOIN student_programs sp ON sp.registration_id = sr.id WHERE sr.id = ? GROUP BY sr.id');
    $stmt->execute([$regId]); $r = $stmt->fetch(PDO::FETCH_ASSOC);
    $foundType = 'student_registrations';
    if (!$r) {
        // Try post_utme_registrations as a fallback
        $stmt2 = $pdo->prepare('SELECT pur.* FROM post_utme_registrations pur WHERE pur.id = ? LIMIT 1');
        $stmt2->execute([$regId]);
        $r = $stmt2->fetch(PDO::FETCH_ASSOC);
        if ($r) {
            $foundType = 'post_utme_registrations';
        }
    }
    if (!$r) { echo json_encode(['success'=>false,'error'=>'Not found']); exit; }
    $tmp = sys_get_temp_dir() . '/hq_export_' . uniqid();
    @mkdir($tmp);
    // copy passport if exists (do this before generating HTML so we can reference the actual filename)
    $passportSaved = null;
    if (!empty($r['passport_path'])) {
        $pp = $r['passport_path'];
        $ext = '';
        if (preg_match('#^https?://#i', $pp)) {
            // download remote file
            $tmpFile = $tmp . '/passport_download';
            $ch = curl_init($pp);
            $fp = fopen($tmpFile, 'w');
            curl_setopt($ch, CURLOPT_FILE, $fp);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 20);
            curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            fclose($fp);
            if ($httpCode >= 200 && $httpCode < 300 && filesize($tmpFile) > 0) {
                // try to detect extension from MIME
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mime = finfo_file($finfo, $tmpFile);
                finfo_close($finfo);
                $map = ['image/jpeg'=>'jpg','image/png'=>'png','image/jpg'=>'jpg'];
                $ext = isset($map[$mime]) ? $map[$mime] : pathinfo(parse_url($pp, PHP_URL_PATH), PATHINFO_EXTENSION);
                if (!$ext) $ext = 'jpg';
                rename($tmpFile, $tmp . '/passport.' . $ext);
                $passportSaved = 'passport.' . $ext;
            } else {
                @unlink($tmpFile);
            }
        } else {
            // treat as local path (may be absolute or site-relative)
            $candidate = $pp;
            // if starts with /HIGH-Q, try realpath relative to project root
            if (strpos($candidate, '/HIGH-Q') === 0) {
                $candidate = __DIR__ . '/../../' . ltrim($candidate, '/');
            }
            if (file_exists($candidate)) {
                $ext = pathinfo($candidate, PATHINFO_EXTENSION);
                copy($candidate, $tmp . '/passport.' . $ext);
                $passportSaved = 'passport.' . $ext;
            }
        }
    }

    // create a simple HTML copy
    $html = '<html><body>';
    $html .= '<h1>Registration #' . htmlspecialchars($r['id']) . ' (' . htmlspecialchars($foundType) . ')</h1>';
    // Normalize passport field name for display (post_utme may use passport_photo)
    if ($foundType === 'post_utme_registrations') {
        if (isset($r['passport_photo']) && !isset($r['passport_path'])) $r['passport_path'] = $r['passport_photo'];
        // For post-UTME, show a curated set of fields only (hide regular-only fields)
        // Complete Post-UTME field list (covers sponsor / next-of-kin / waec fields and others)
        $fieldsToShow = [
            // identity & contact
            'institution','first_name','surname','other_name','gender','address','date_of_birth','date_of_birth_post','parent_phone','email','nin_number','state_of_origin','local_government','place_of_birth','nationality','marital_status','disability','religion','mode_of_entry',
            // jamb
            'jamb_registration_number','jamb_score','jamb_subjects',
            // course/institution choices
            'course_first_choice','course_second_choice','institution_first_choice',
            // parent details
            'father_name','father_phone','father_email','father_occupation','mother_name','mother_phone','mother_occupation',
            // education history
            'primary_school','primary_year_ended','secondary_school','secondary_year_ended',
            // sponsor & next of kin
            'sponsor_name','sponsor_address','sponsor_email','sponsor_phone','sponsor_relationship',
            'next_of_kin_name','next_of_kin_address','next_of_kin_email','next_of_kin_phone','next_of_kin_relationship',
            // exam / olevel
            'exam_type','candidate_name','exam_number','exam_year_month','olevel_results','waec_token','waec_serial',
            // system/payment
            'passport_photo','payment_status','form_fee_paid','tutor_fee_paid','created_at','updated_at'
        ];
        // If passport exists we'll include it below as an <img>
        if (!empty($r['passport_path']) && $passportSaved !== null) {
            $html .= '<div style="margin:12px 0;"><strong>Passport:</strong><br>';
            $html .= '<img src="' . htmlspecialchars($passportSaved) . '" alt="Passport" style="max-width:200px;border:1px solid #ccc;padding:4px"/>';
            $html .= '</div>';
        }
        foreach ($fieldsToShow as $k) {
            if (!array_key_exists($k, $r)) continue;
            $v = $r[$k];
            if (is_null($v)) $v = '';
            // if JSON fields, pretty-print
            if (in_array($k, ['jamb_subjects','olevel_results']) && $v) {
                $decoded = json_decode($v, true);
                if (is_array($decoded)) $v = '<pre>' . htmlspecialchars(json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) . '</pre>';
            } else {
                $v = htmlspecialchars((string)$v);
            }
            $html .= '<p><strong>' . htmlspecialchars($k) . ':</strong> ' . $v . '</p>';
        }
        $html .= '</body></html>';
    } else {
        // default: dump all fields for student_registrations
        foreach ($r as $k=>$v) { $val = is_null($v) ? '' : (string)$v; $html .= '<p><strong>' . htmlspecialchars((string)$k) . ':</strong> ' . htmlspecialchars($val) . '</p>'; }
        $html .= '</body></html>';
    }
    file_put_contents($tmp . '/registration.html', $html);
    $zipPath = $tmp . '.zip';
    $zip = new ZipArchive();
    if ($zip->open($zipPath, ZipArchive::CREATE)!==TRUE) { echo json_encode(['success'=>false,'error'=>'Cannot create zip']); exit; }
    $zip->addFile($tmp . '/registration.html','registration.html');
    if ($passportSaved && file_exists($tmp . '/' . $passportSaved)) {
        // add passport using the saved filename inside the zip
        $zip->addFile($tmp . '/' . $passportSaved, $passportSaved);
    }
    $zip->close();
    // serve zip
    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="registration_' . $r['id'] . '.zip"');
    readfile($zipPath);
    // cleanup
    @unlink($zipPath);
    array_map('unlink', glob($tmp . '/*'));
    @rmdir($tmp);
    exit;
} catch (Throwable $e) { echo json_encode(['success'=>false,'error'=>$e->getMessage()]); exit; }
