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
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../includes/functions.php';
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
            // If the path is prefixed with the configured app path (e.g. /HIGH-Q) or is site-root-relative,
            // map it to the project filesystem under the public/ folder so we can read the file.
            $appPath = parse_url(app_url(), PHP_URL_PATH) ?: '';
            if ($appPath && strpos($candidate, $appPath) === 0) {
                // remove the appPath prefix and resolve relative to project root
                $candidate = __DIR__ . '/../../' . ltrim(substr($candidate, strlen($appPath)), '/');
            } elseif (strpos($candidate, '/') === 0) {
                // site-root-relative paths (starting with '/') map to project root/public/
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
