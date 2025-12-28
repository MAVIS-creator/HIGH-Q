<?php
// Quick FPDI test
require_once __DIR__ . '/../vendor/autoload.php';

use setasign\Fpdi\Fpdi;

$templatePath = __DIR__ . '/../public/uploads/Admission Letter.pdf';

echo "Testing FPDI...\n";
echo "Template path: $templatePath\n";
echo "Exists: " . (file_exists($templatePath) ? "YES" : "NO") . "\n";

if (file_exists($templatePath)) {
    try {
        $pdf = new Fpdi();
        $pageCount = $pdf->setSourceFile($templatePath);
        echo "SUCCESS! Page count: $pageCount\n";
        
        // Try to import and use template
        $pdf->AddPage();
        $tplId = $pdf->importPage(1);
        $pdf->useTemplate($tplId, 0, 0, 210, 297);
        
        // Add some test text
        $pdf->SetFont('Arial', 'B', 16);
        $pdf->SetXY(0, 70);
        $pdf->Cell(210, 10, 'TEST ADMISSION LETTER', 0, 1, 'C');
        
        $pdf->SetFont('Arial', '', 12);
        $pdf->SetXY(25, 100);
        $pdf->Write(6, 'Dear Test Student,');
        
        // Save test PDF
        $outputPath = __DIR__ . '/../storage/fpdi-test.pdf';
        $pdf->Output('F', $outputPath);
        echo "PDF saved to: $outputPath\n";
        
    } catch (Exception $e) {
        echo "ERROR: " . $e->getMessage() . "\n";
    }
} else {
    echo "Template file not found!\n";
}
