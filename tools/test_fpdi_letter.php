<?php
// Test FPDI admission letter generation with updated positioning
require_once __DIR__ . '/vendor/autoload.php';

use setasign\Fpdi\Fpdi;

$templatePath = __DIR__ . '/public/uploads/Admission Letter.pdf';

if (!file_exists($templatePath)) {
    die("Template not found at: $templatePath");
}

echo "Creating FPDI test PDF...\n";

try {
    $pdf = new Fpdi();
    $pdf->AddPage();
    $pdf->setSourceFile($templatePath);
    $tpl = $pdf->importPage(1);
    $pdf->useTemplate($tpl, 0, 0, 210, 297);
    
    // Title - ADMISSION LETTER
    $pdf->SetFont('Arial', 'B', 18);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetXY(0, 75);
    $pdf->Cell(210, 10, 'ADMISSION LETTER', 0, 1, 'C');
    
    // Underline
    $pdf->SetDrawColor(0, 0, 0);
    $pdf->Line(70, 86, 140, 86);
    
    // Date
    $pdf->SetFont('Arial', '', 12);
    $pdf->SetXY(25, 100);
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Write(6, 'Date: ');
    $pdf->SetFont('Arial', '', 12);
    $pdf->Write(6, 'December 28, 2025');
    
    // Greeting
    $pdf->SetXY(25, 115);
    $pdf->Write(6, 'Dear ');
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Write(6, 'Test Student');
    $pdf->SetFont('Arial', '', 12);
    $pdf->Write(6, ',');
    
    // Body paragraph 1
    $pdf->SetXY(25, 130);
    $pdf->MultiCell(160, 7, "We are pleased to offer you provisional admission into JAMB Preparation, WAEC Classes at HIGH Q SOLID ACADEMY.", 0, 'J');
    
    // Body paragraph 2
    $pdf->SetXY(25, 155);
    $pdf->MultiCell(160, 7, "This admission is granted based on your expressed interest and initial screening. Further enrolment steps will be communicated to you, including documentation and class schedule.", 0, 'J');
    
    // Body paragraph 3
    $pdf->SetXY(25, 185);
    $pdf->MultiCell(160, 7, "Please keep this letter for your records. If you have any questions, contact us via the details in the letterhead above.", 0, 'J');
    
    // Body paragraph 4
    $pdf->SetXY(25, 210);
    $pdf->Write(6, "We look forward to your success with us.");
    
    // Signature section
    $pdf->SetXY(25, 235);
    $pdf->Cell(60, 0.5, '', 'T', 1, 'L'); // Signature line
    
    $pdf->SetXY(25, 240);
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Write(6, 'Admissions Office');
    
    $pdf->SetXY(25, 248);
    $pdf->SetFont('Arial', '', 11);
    $pdf->Write(6, 'HIGH Q SOLID ACADEMY');
    
    // Registration ID
    $pdf->SetXY(140, 255);
    $pdf->SetTextColor(100, 100, 100);
    $pdf->SetFont('Arial', 'I', 9);
    $pdf->Write(5, 'Reg ID: 12345');
    
    $outputPath = __DIR__ . '/storage/test-admission-fpdi.pdf';
    $pdf->Output('F', $outputPath);
    
    echo "SUCCESS! PDF saved to: $outputPath\n";
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
