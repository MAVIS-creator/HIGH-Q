<?php
// tools/generate_replacements_preview.php
// Scans the repo for literal "/HIGH-Q" occurrences and writes a preview report and suggested replacements
chdir(__DIR__ . '/..');
$root = getcwd();
$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root));
$matches = [];
foreach ($iterator as $file) {
    if (!$file->isFile()) continue;
    $path = $file->getPathname();
    // skip vendor and storage logs to avoid noisy matches
    if (strpos($path, DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR) !== false) continue;
    if (strpos($path, DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR) !== false) continue;
    if (strpos($path, DIRECTORY_SEPARATOR . '.git' . DIRECTORY_SEPARATOR) !== false) continue;
    $txt = @file_get_contents($path);
    if ($txt === false) continue;
    if (strpos($txt, '/HIGH-Q') !== false) {
        $lines = preg_split('/\r?\n/', $txt);
        foreach ($lines as $n => $line) {
            if (strpos($line, '/HIGH-Q') !== false) {
                $matches[] = ['file' => $path, 'line' => $n+1, 'text' => trim($line)];
            }
        }
    }
}

$out = "REPLACEMENT PREVIEW for literal '/HIGH-Q' occurrences\n";
$out .= "Generated: " . date('c') . "\n\n";
$out .= "Found " . count($matches) . " matches:\n\n";
foreach ($matches as $m) {
    $out .= $m['file'] . ":" . $m['line'] . "\n    " . $m['text'] . "\n\n";
}

$out .= "\nSUGGESTED REPLACEMENTS (review before applying):\n";
$out .= "- In admin PHP files, prefer using server-side \$HQ_BASE_URL: e.g. replace '/HIGH-Q/public/...' with rtrim(\$HQ_BASE_URL,'/') . '/public/...'.\n";
$out .= "- In admin JS, prefer window.adminUrl('pages', { ... }) for router AJAX endpoints or use window.HQ_BASE_URL + '/public/...'.\n";
$out .= "- In public-facing PHP files, prefer using APP_URL from .env (read in header) or compute base from request. e.g. APP_URL . '/uploads/...' or rtrim(\$HQ_BASE_URL,'/') . '/uploads/...'.\n\n";
$out .= "I'll create a separate adminurl preview showing concrete JS replacements for admin pages.\n";

file_put_contents('tools/patch_preview.txt', $out);

echo "Preview written to tools/patch_preview.txt (contains " . count($matches) . " matches)\n";
