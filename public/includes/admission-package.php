<?php

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/functions.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use setasign\Fpdi\Fpdi;

function hqAdmissionSiteInfo(PDO $pdo): array {
    try {
        $stmt = $pdo->query("SELECT * FROM site_settings ORDER BY id ASC LIMIT 1");
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (is_array($row) && !empty($row)) {
            return $row;
        }
    } catch (Throwable $e) {
    }

    return [
        'site_name' => 'HIGH Q SOLID ACADEMY',
        'contact_address' => '8 Pineapple Avenue, Aiyetoro Maya, Ikorodu',
        'contact_phone' => '0807 208 8794',
        'contact_email' => 'info@hqacademy.com',
    ];
}

function hqAdmissionVerificationTokens(string $paymentReference, int $registrationId, string $source): array {
    $seed = strtoupper($source . '|' . $registrationId . '|' . $paymentReference);
    $serial = 'HQA-' . date('Y') . '-' . strtoupper(substr($source, 0, 3)) . '-' . str_pad((string)$registrationId, 5, '0', STR_PAD_LEFT) . '-' . strtoupper(substr(sha1($seed), 0, 6));
    $verifyCode = strtoupper(substr(hash('sha256', $seed), 0, 12));

    return [
        'serial' => $serial,
        'verification_code' => $verifyCode,
    ];
}

function hqAdmissionProgramsForUniversal(PDO $pdo, array $registration): array {
    $payload = [];
    if (!empty($registration['payload'])) {
        $payload = json_decode((string)$registration['payload'], true);
        if (!is_array($payload)) {
            $payload = [];
        }
    }

    $programIds = [];
    foreach (['program_ids', 'selected_programs', 'course_ids'] as $key) {
        if (!empty($payload[$key]) && is_array($payload[$key])) {
            foreach ($payload[$key] as $pid) {
                $pid = (int)$pid;
                if ($pid > 0) {
                    $programIds[] = $pid;
                }
            }
        }
    }

    $programIds = array_values(array_unique($programIds));
    if (!empty($programIds)) {
        try {
            $placeholders = implode(',', array_fill(0, count($programIds), '?'));
            $stmt = $pdo->prepare("SELECT title FROM courses WHERE id IN ($placeholders) ORDER BY title ASC");
            $stmt->execute($programIds);
            $titles = array_values(array_filter(array_map('strval', $stmt->fetchAll(PDO::FETCH_COLUMN))));
            if (!empty($titles)) {
                return $titles;
            }
        } catch (Throwable $e) {
        }
    }

    $labelMap = [
        'jamb' => 'JAMB/UTME Preparation',
        'waec' => 'WAEC/NECO/GCE Preparation',
        'postutme' => 'Post-UTME Preparation',
        'digital' => 'Digital Skills Training',
        'international' => 'International Program',
    ];

    $programType = strtolower(trim((string)($registration['program_type'] ?? '')));
    return [$labelMap[$programType] ?? ucfirst($programType ?: 'Program')];
}

function hqAdmissionDataByPaymentReference(PDO $pdo, string $paymentReference): ?array {
    $stmt = $pdo->prepare('SELECT * FROM payments WHERE reference = ? LIMIT 1');
    $stmt->execute([$paymentReference]);
    $payment = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$payment) {
        return null;
    }

    $site = hqAdmissionSiteInfo($pdo);
    $metadata = [];
    if (!empty($payment['metadata'])) {
        $metadata = json_decode((string)$payment['metadata'], true);
        if (!is_array($metadata)) {
            $metadata = [];
        }
    }

    $universal = null;
    try {
        $stmt = $pdo->prepare('SELECT * FROM universal_registrations WHERE payment_reference = ? LIMIT 1');
        $stmt->execute([$paymentReference]);
        $universal = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    } catch (Throwable $e) {
    }

    if ($universal) {
        $registrationId = (int)$universal['id'];
        $fullName = trim(($universal['first_name'] ?? '') . ' ' . ($universal['last_name'] ?? ''));
        $programTitles = hqAdmissionProgramsForUniversal($pdo, $universal);
        $programsText = implode(', ', $programTitles);
        $tokens = hqAdmissionVerificationTokens($paymentReference, $registrationId, 'universal');

        return [
            'source' => 'universal',
            'registration_id' => $registrationId,
            'payment_id' => (int)$payment['id'],
            'payment_reference' => $paymentReference,
            'amount' => (float)$payment['amount'],
            'full_name' => $fullName !== '' ? $fullName : trim((string)($metadata['name'] ?? 'Student')),
            'email' => trim((string)($universal['email'] ?? ($metadata['email'] ?? ''))),
            'phone' => trim((string)($universal['phone'] ?? ($metadata['phone'] ?? ''))),
            'program_titles' => $programTitles,
            'programs_text' => $programsText,
            'site_name' => $site['site_name'] ?? 'HIGH Q SOLID ACADEMY',
            'contact_address' => $site['contact_address'] ?? '',
            'contact_phone' => $site['contact_phone'] ?? '',
            'contact_email' => $site['contact_email'] ?? '',
            'serial' => $tokens['serial'],
            'verification_code' => $tokens['verification_code'],
            'program_type' => $universal['program_type'] ?? ($payment['registration_type'] ?? ''),
        ];
    }

    $studentReg = null;
    if (!empty($payment['student_id'])) {
        try {
            $stmt = $pdo->prepare('SELECT * FROM student_registrations WHERE user_id = ? ORDER BY created_at DESC LIMIT 1');
            $stmt->execute([(int)$payment['student_id']]);
            $studentReg = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        } catch (Throwable $e) {
        }
    }

    if ($studentReg) {
        $programTitles = [];
        try {
            $stmt = $pdo->prepare('SELECT c.title FROM student_programs sp JOIN courses c ON c.id = sp.course_id WHERE sp.registration_id = ? ORDER BY c.title ASC');
            $stmt->execute([(int)$studentReg['id']]);
            $programTitles = array_values(array_filter(array_map('strval', $stmt->fetchAll(PDO::FETCH_COLUMN))));
        } catch (Throwable $e) {
        }
        if (empty($programTitles)) {
            $programTitles = ['Selected Program'];
        }

        $registrationId = (int)$studentReg['id'];
        $tokens = hqAdmissionVerificationTokens($paymentReference, $registrationId, 'student');
        $fullName = trim(($studentReg['first_name'] ?? '') . ' ' . ($studentReg['last_name'] ?? ''));

        return [
            'source' => 'student',
            'registration_id' => $registrationId,
            'payment_id' => (int)$payment['id'],
            'payment_reference' => $paymentReference,
            'amount' => (float)$payment['amount'],
            'full_name' => $fullName !== '' ? $fullName : trim((string)($metadata['name'] ?? 'Student')),
            'email' => trim((string)($metadata['email'] ?? '')),
            'phone' => trim((string)($metadata['phone'] ?? '')),
            'program_titles' => $programTitles,
            'programs_text' => implode(', ', $programTitles),
            'site_name' => $site['site_name'] ?? 'HIGH Q SOLID ACADEMY',
            'contact_address' => $site['contact_address'] ?? '',
            'contact_phone' => $site['contact_phone'] ?? '',
            'contact_email' => $site['contact_email'] ?? '',
            'serial' => $tokens['serial'],
            'verification_code' => $tokens['verification_code'],
            'program_type' => $payment['registration_type'] ?? '',
        ];
    }

    return null;
}

function hqAdmissionHtml(array $data, bool $forPdf = false): string {
    $today = date('F j, Y');
    $fullName = htmlspecialchars($data['full_name'] ?? 'Student', ENT_QUOTES, 'UTF-8');
    $programsText = htmlspecialchars($data['programs_text'] ?? 'your chosen programme(s)', ENT_QUOTES, 'UTF-8');
    $siteName = htmlspecialchars($data['site_name'] ?? 'HIGH Q SOLID ACADEMY', ENT_QUOTES, 'UTF-8');
    $address = htmlspecialchars($data['contact_address'] ?? '', ENT_QUOTES, 'UTF-8');
    $phone = htmlspecialchars($data['contact_phone'] ?? '', ENT_QUOTES, 'UTF-8');
    $email = htmlspecialchars($data['contact_email'] ?? '', ENT_QUOTES, 'UTF-8');
    $serial = htmlspecialchars($data['serial'] ?? '', ENT_QUOTES, 'UTF-8');
    $verify = htmlspecialchars($data['verification_code'] ?? '', ENT_QUOTES, 'UTF-8');
    $ref = htmlspecialchars($data['payment_reference'] ?? '', ENT_QUOTES, 'UTF-8');
    $rid = (int)($data['registration_id'] ?? 0);

    if ($forPdf) {
        return '<!DOCTYPE html><html><head><meta charset="UTF-8"><style>
            @page { margin: 28px 34px; }
            body { font-family: Arial, sans-serif; color: #1f2937; font-size: 12pt; line-height: 1.7; }
            .title { text-align:center; font-size:18pt; font-weight:bold; margin: 10px 0 18px; text-decoration: underline; }
            .meta { margin-top: 20px; padding: 12px; border: 1px solid #d1d5db; background: #f9fafb; }
            .meta strong { display:inline-block; min-width: 160px; }
        </style></head><body>
            <div class="title">ADMISSION LETTER</div>
            <p><strong>Date:</strong> ' . $today . '</p>
            <p>Dear <strong>' . $fullName . '</strong>,</p>
            <p>We are pleased to offer you provisional admission into <strong>' . $programsText . '</strong> at <strong>' . $siteName . '</strong>.</p>
            <p>Your payment has been confirmed successfully. Kindly print your payment receipt and this admission letter, then bring both documents to the centre for clearance and onboarding.</p>
            <p><strong>Important instructions:</strong></p>
            <ol>
                <li>Print and keep your payment receipt.</li>
                <li>Print and keep this admission letter.</li>
                <li>Bring both documents to the centre during working hours.</li>
                <li>Present the documents for verification before class placement or final onboarding.</li>
            </ol>
            <p>If you have any questions, contact us via the details below.</p>
            <p>We look forward to your success with us.</p>
            <div style="margin-top:34px;">
                <div>______________________________</div>
                <div><strong>Admissions Office</strong></div>
                <div>' . $siteName . '</div>
            </div>
            <div class="meta">
                <div><strong>Admission Serial:</strong> ' . $serial . '</div>
                <div><strong>Verification Code:</strong> ' . $verify . '</div>
                <div><strong>Payment Reference:</strong> ' . $ref . '</div>
                <div><strong>Registration ID:</strong> ' . $rid . '</div>
                <div><strong>Centre Contact:</strong> ' . $phone . ' / ' . $email . '</div>
                <div><strong>Centre Address:</strong> ' . $address . '</div>
            </div>
        </body></html>';
    }

    return '<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Admission Letter</title>
        <style>
            body{background:#f7f7f7;font-family:Arial,sans-serif}
            .letter{max-width:860px;margin:24px auto;padding:28px;border:1px solid #eee;border-radius:12px;background:#fff}
            .title{text-align:center;font-size:24px;font-weight:700;margin:10px 0 18px}
            .content{font-size:16px;line-height:1.7;color:#222}
            .meta{margin-top:24px;padding:16px;border:1px solid #e5e7eb;border-radius:10px;background:#f8fafc}
            .meta-row{margin-bottom:6px}
            .btn{display:inline-block;background:#FFD600;color:#000;padding:10px 20px;border-radius:6px;text-decoration:none;font-weight:bold}
        </style></head><body>
        <div class="letter">
            <div class="title">Admission Letter</div>
            <div class="content">
                <p><strong>Date:</strong> ' . $today . '</p>
                <p>Dear <strong>' . $fullName . '</strong>,</p>
                <p>We are pleased to offer you provisional admission into <strong>' . $programsText . '</strong> at <strong>' . $siteName . '</strong>.</p>
                <p>Your payment has been confirmed successfully. Kindly print your payment receipt and this admission letter, then bring both documents to the centre for clearance and onboarding.</p>
                <p><strong>Instructions to follow:</strong></p>
                <ol>
                    <li>Print your receipt.</li>
                    <li>Print your admission letter.</li>
                    <li>Bring both documents to the centre.</li>
                    <li>Present them for verification before final clearance.</li>
                </ol>
                <p>If you have questions, contact us via ' . $phone . ' or ' . $email . '.</p>
                <p>We look forward to your success with us.</p>
            </div>
            <div class="meta">
                <div class="meta-row"><strong>Admission Serial:</strong> ' . $serial . '</div>
                <div class="meta-row"><strong>Verification Code:</strong> ' . $verify . '</div>
                <div class="meta-row"><strong>Payment Reference:</strong> ' . $ref . '</div>
                <div class="meta-row"><strong>Registration ID:</strong> ' . $rid . '</div>
            </div>
            <div style="margin-top:18px"><a class="btn" href="?ref=' . rawurlencode($data['payment_reference'] ?? '') . '&format=pdf">Download PDF</a></div>
        </div></body></html>';
}

function hqGenerateAdmissionLetterFile(PDO $pdo, array $data): array {
    $templatePath = __DIR__ . '/../uploads/Admission Letter.pdf';
    $dir = __DIR__ . '/../../storage/admission_letters';
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
    $safeSerial = preg_replace('/[^A-Za-z0-9_-]+/', '-', (string)($data['serial'] ?? 'admission-letter'));
    $filepath = $dir . '/' . $safeSerial . '.pdf';

    try {
        if (is_file($templatePath)) {
            $pdf = new Fpdi();
            $pdf->AddPage();
            $pdf->setSourceFile($templatePath);
            $tplId = $pdf->importPage(1);
            $pdf->useTemplate($tplId, 0, 0, 210, 297);

            $pdf->SetFont('Arial', 'B', 18);
            $pdf->SetTextColor(0, 0, 0);
            $pdf->SetXY(0, 75);
            $pdf->Cell(210, 10, 'ADMISSION LETTER', 0, 1, 'C');
            $pdf->Line(70, 86, 140, 86);

            $pdf->SetFont('Arial', '', 12);
            $pdf->SetXY(25, 100);
            $pdf->SetFont('Arial', 'B', 12);
            $pdf->Write(6, 'Date: ');
            $pdf->SetFont('Arial', '', 12);
            $pdf->Write(6, date('F j, Y'));

            $pdf->SetXY(25, 115);
            $pdf->Write(6, 'Dear ');
            $pdf->SetFont('Arial', 'B', 12);
            $pdf->Write(6, (string)($data['full_name'] ?? 'Student'));
            $pdf->SetFont('Arial', '', 12);
            $pdf->Write(6, ',');

            $pdf->SetXY(25, 130);
            $pdf->MultiCell(160, 7, 'We are pleased to offer you provisional admission into ' . ($data['programs_text'] ?? 'your chosen programme(s)') . ' at ' . ($data['site_name'] ?? 'HIGH Q SOLID ACADEMY') . '.', 0, 'J');
            $pdf->SetXY(25, 156);
            $pdf->MultiCell(160, 7, 'Your payment has been confirmed. Kindly print your receipt and this admission letter, and bring both documents to the centre for verification and onboarding.', 0, 'J');
            $pdf->SetXY(25, 184);
            $pdf->MultiCell(160, 7, "Instructions:\n1. Print the payment receipt.\n2. Print this admission letter.\n3. Bring both documents to the centre.\n4. Present them for verification before final clearance.", 0, 'L');
            $pdf->SetXY(25, 224);
            $pdf->MultiCell(160, 7, 'For enquiries, contact us via the details in the letterhead above.', 0, 'J');
            $pdf->SetXY(25, 242);
            $pdf->Cell(60, 0.5, '', 'T', 1, 'L');
            $pdf->SetXY(25, 247);
            $pdf->SetFont('Arial', 'B', 12);
            $pdf->Write(6, 'Admissions Office');

            $pdf->SetXY(118, 242);
            $pdf->SetTextColor(60, 60, 60);
            $pdf->SetFont('Arial', 'B', 8);
            $pdf->MultiCell(70, 4.5, 'Serial: ' . ($data['serial'] ?? '') . "\nVerify: " . ($data['verification_code'] ?? '') . "\nRef: " . ($data['payment_reference'] ?? '') . "\nReg ID: " . (string)($data['registration_id'] ?? ''), 0, 'L');

            $pdf->Output('F', $filepath);
        } else {
            $dompdf = new \Dompdf\Dompdf((new \Dompdf\Options()));
            $dompdf->loadHtml(hqAdmissionHtml($data, true));
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();
            file_put_contents($filepath, $dompdf->output());
        }
    } catch (Throwable $e) {
        $dompdf = new \Dompdf\Dompdf((new \Dompdf\Options()));
        $dompdf->loadHtml(hqAdmissionHtml($data, true));
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        file_put_contents($filepath, $dompdf->output());
    }

    return [
        'path' => $filepath,
        'filename' => basename($filepath),
    ];
}

function hqSendAdmissionPackageEmail(PDO $pdo, string $paymentReference, ?string $receiptFilePath = null): array {
    $data = hqAdmissionDataByPaymentReference($pdo, $paymentReference);
    if (!$data || empty($data['email'])) {
        return ['sent' => false, 'reason' => 'admission-data-missing'];
    }

    $letter = hqGenerateAdmissionLetterFile($pdo, $data);
    $subject = 'Your Admission Letter and Next Steps - ' . ($data['site_name'] ?? 'HIGH Q SOLID ACADEMY');
    $receiptUrl = app_url('receipt.php?ref=' . rawurlencode($paymentReference));
    $html = '<p>Dear <strong>' . htmlspecialchars($data['full_name'], ENT_QUOTES, 'UTF-8') . '</strong>,</p>'
        . '<p>Your payment has been confirmed successfully.</p>'
        . '<p>Your admission letter is attached to this email. Kindly print your admission letter and your payment receipt, then bring both documents to the centre for verification and onboarding.</p>'
        . '<p><strong>Required documents to bring:</strong></p>'
        . '<ol><li>Printed admission letter</li><li>Printed payment receipt</li></ol>'
        . '<p>You can also download your receipt here: <a href="' . htmlspecialchars($receiptUrl, ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars($receiptUrl, ENT_QUOTES, 'UTF-8') . '</a></p>'
        . '<p><strong>Admission Serial:</strong> ' . htmlspecialchars($data['serial'], ENT_QUOTES, 'UTF-8') . '<br>'
        . '<strong>Verification Code:</strong> ' . htmlspecialchars($data['verification_code'], ENT_QUOTES, 'UTF-8') . '</p>'
        . '<p>Regards,<br>' . htmlspecialchars($data['site_name'], ENT_QUOTES, 'UTF-8') . '</p>';

    $attachments = [];
    if (!empty($letter['path']) && is_readable($letter['path'])) {
        $attachments[] = $letter['path'];
    }
    if (!empty($receiptFilePath) && is_readable($receiptFilePath)) {
        $attachments[] = $receiptFilePath;
    }

    $sent = sendEmail($data['email'], $subject, $html, $attachments);
    return [
        'sent' => (bool)$sent,
        'data' => $data,
        'letter_path' => $letter['path'] ?? null,
    ];
}

