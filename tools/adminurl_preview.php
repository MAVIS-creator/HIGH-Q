<?php
// tools/adminurl_preview.php
// Scans admin pages for manual HQ_BASE_URL/relative admin URL constructions and outputs suggested JS replacements using window.adminUrl().
chdir(__DIR__ . '/..');
$root = getcwd();
$files = glob('admin/pages/*.php');
$replacements = [];
foreach ($files as $f) {
    $txt = file_get_contents($f);
    if (strpos($txt, "HQ_BASE_URL") !== false || preg_match('/index.php\?pages=/', $txt)) {
        // find fetch/open XHR patterns to replace
        preg_match_all('/(fetch\([^;\n]*\)|xhr\.open\([^;\n]*\))/i', $txt, $m);
        if (!empty($m[0])) {
            foreach ($m[0] as $snippet) {
                $replacements[] = [
                    'file' => $f,
                    'original' => $snippet,
                    'suggested' => "Use window.adminUrl('pages') or window.adminUrl('pageName', { ajax:1 }) in place of full URL strings."
                ];
            }
        }
    }
}
$out = "ADMIN URL REPLACEMENTS PREVIEW\nGenerated: " . date('c') . "\n\n";
foreach ($replacements as $r) {
    $out .= $r['file'] . "\n--- original ---\n" . $r['original'] . "\n--- suggestion ---\n" . $r['suggested'] . "\n\n";
}
file_put_contents('tools/adminurl_preview.txt', $out);
echo "Admin URL preview written to tools/adminurl_preview.txt (" . count($replacements) . " snippets)\n";
