<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

header('Content-Type: application/json');

try {
    requirePermission('sentinel');

    $reportDir = realpath(__DIR__ . '/../../storage/scan_reports');
    if (!$reportDir || !is_dir($reportDir)) {
        throw new Exception('Scan reports directory not found.');
    }

    $requestedFile = isset($_GET['file']) ? basename((string)$_GET['file']) : '';
    $scanId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

    $candidateFiles = [];

    if ($requestedFile !== '') {
        $candidatePath = $reportDir . DIRECTORY_SEPARATOR . $requestedFile;
        if (is_file($candidatePath)) {
            $candidateFiles[] = $candidatePath;
        }
    }

    if (empty($candidateFiles) && $scanId > 0) {
        try {
            $pdo->exec("CREATE TABLE IF NOT EXISTS security_scans (
                id INT AUTO_INCREMENT PRIMARY KEY,
                scan_type VARCHAR(20) NOT NULL,
                status VARCHAR(20) NOT NULL DEFAULT 'completed',
                threat_count INT NOT NULL DEFAULT 0,
                report_file VARCHAR(255) NULL,
                scan_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                duration INT NOT NULL DEFAULT 0,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_scan_date (scan_date),
                INDEX idx_scan_type (scan_type)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        } catch (Throwable $e) {}

        $stmt = $pdo->prepare('SELECT scan_type, scan_date, report_file FROM security_scans WHERE id = ? LIMIT 1');
        $stmt->execute([$scanId]);
        $scanRow = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($scanRow) {
            if (!empty($scanRow['report_file'])) {
                $candidatePath = $reportDir . DIRECTORY_SEPARATOR . basename((string)$scanRow['report_file']);
                if (is_file($candidatePath)) {
                    $candidateFiles[] = $candidatePath;
                }
            }

            if (empty($candidateFiles)) {
                $all = glob($reportDir . DIRECTORY_SEPARATOR . '*.json') ?: [];
                rsort($all);
                $scanType = strtolower((string)($scanRow['scan_type'] ?? ''));
                $scanTs = strtotime((string)($scanRow['scan_date'] ?? '')) ?: time();

                usort($all, static function(string $a, string $b) use ($scanType, $scanTs): int {
                    $ma = @filemtime($a) ?: 0;
                    $mb = @filemtime($b) ?: 0;
                    $sa = abs($ma - $scanTs);
                    $sb = abs($mb - $scanTs);

                    $aName = strtolower(basename($a));
                    $bName = strtolower(basename($b));
                    $aTypeBoost = ($scanType !== '' && strpos($aName, $scanType) !== false) ? -1 : 0;
                    $bTypeBoost = ($scanType !== '' && strpos($bName, $scanType) !== false) ? -1 : 0;

                    $aScore = $sa + ($aTypeBoost * 600);
                    $bScore = $sb + ($bTypeBoost * 600);

                    return $aScore <=> $bScore;
                });

                $candidateFiles = array_slice($all, 0, 8);
            }
        }
    }

    if (empty($candidateFiles)) {
        $all = glob($reportDir . DIRECTORY_SEPARATOR . '*.json') ?: [];
        rsort($all);
        $candidateFiles = array_slice($all, 0, 8);
    }

    $loaded = null;
    foreach ($candidateFiles as $file) {
        $raw = @file_get_contents($file);
        if ($raw === false || trim($raw) === '') continue;
        $json = json_decode($raw, true);
        if (!is_array($json)) continue;

        $report = $json['scan_data']['report'] ?? $json['report'] ?? null;
        if (!is_array($report)) continue;

        $loaded = [
            'report' => $report,
            'file' => basename($file),
        ];
        break;
    }

    if (!$loaded) {
        throw new Exception('Report file could not be loaded for this scan.');
    }

    echo json_encode([
        'status' => 'ok',
        'report' => $loaded['report'],
        'file' => $loaded['file'],
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage(),
    ]);
}
