param(
    [string]$OutputFile = "highq.sql"
)

$ErrorActionPreference = "Stop"

$repoRoot = Resolve-Path (Join-Path $PSScriptRoot "..")
$envFile = Join-Path $repoRoot ".env"

function Load-EnvFile([string]$path) {
    if (-not (Test-Path $path)) { return }
    Get-Content $path | ForEach-Object {
        $line = $_.Trim()
        if ($line -eq "" -or $line.StartsWith("#")) { return }
        $parts = $line -split "=", 2
        if ($parts.Length -ne 2) { return }
        $key = $parts[0].Trim()
        $val = $parts[1].Trim()
        if ($val.StartsWith('"') -and $val.EndsWith('"')) {
            $val = $val.Substring(1, $val.Length - 2)
        }
        [System.Environment]::SetEnvironmentVariable($key, $val, "Process")
    }
}

Load-EnvFile $envFile

$dbHost = $env:DB_HOST
$dbUser = $env:DB_USER
$dbPass = $env:DB_PASS
$dbName = $env:DB_NAME

if (-not $dbHost) { $dbHost = "127.0.0.1" }
if (-not $dbName) { $dbName = "highq" }

if (-not $dbUser) {
    throw "DB_USER is not set. Add it to .env or pass it in the environment."
}

$outPath = Join-Path $repoRoot $OutputFile

$dumpArgs = @(
    "--host=$dbHost",
    "--user=$dbUser",
    "--single-transaction",
    "--routines",
    "--triggers",
    "--events",
    "--default-character-set=utf8mb4",
    "--skip-comments",
    "--skip-add-locks",
    "--set-gtid-purged=OFF",
    "--databases",
    $dbName
)

if ($dbPass) {
    $dumpArgs = @("--password=$dbPass") + $dumpArgs
}

Write-Host "Exporting database '$dbName' to $outPath ..."

& mysqldump @dumpArgs | Out-File -FilePath $outPath -Encoding utf8

Write-Host "Done."
