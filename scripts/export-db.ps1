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

function Resolve-MySqlDump {
    $cmd = Get-Command mysqldump -ErrorAction SilentlyContinue
    if ($cmd) { return $cmd.Path }

    $candidates = @()
    if ($env:XAMPP_HOME) { $candidates += (Join-Path $env:XAMPP_HOME "mysql\bin\mysqldump.exe") }
    $candidates += "C:\xampp\mysql\bin\mysqldump.exe"
    $candidates += "C:\Program Files\MySQL\MySQL Server 8.0\bin\mysqldump.exe"
    $candidates += "C:\Program Files\MySQL\MySQL Server 5.7\bin\mysqldump.exe"

    foreach ($c in $candidates) {
        if (Test-Path $c) { return $c }
    }

    throw "mysqldump not found. Add it to PATH or set XAMPP_HOME to your XAMPP folder."
}

$mysqldumpExe = Resolve-MySqlDump

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

& $mysqldumpExe @dumpArgs | Out-File -FilePath $outPath -Encoding utf8

Write-Host "Done."
