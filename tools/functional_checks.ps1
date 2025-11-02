# Functional checks: attempt simple GETs against key pages and print status and small snippet
$urls = @(
    'http://localhost/HIGH-Q/admin/pages/students.php',
    'http://localhost/HIGH-Q/public/payments_wait.php',
    'http://localhost/HIGH-Q/public/register.php'
)
Write-Output "Starting lightweight functional checks..."
foreach ($u in $urls) {
    Write-Output "\n--- Requesting: $u ---"
    try {
        $r = Invoke-WebRequest -Uri $u -UseBasicParsing -TimeoutSec 10
        Write-Output "Status: $($r.StatusCode)"
        $body = $r.Content
        $len = if ($body) { $body.Length } else { 0 }
        Write-Output "Body length: $len"
        if ($len -gt 0) {
            $snippet = $body.Substring(0,[math]::Min(800,$len))
            Write-Output "Snippet:\n$snippet"
        }
    } catch {
        Write-Output "Request failed: $_"
    }
}
