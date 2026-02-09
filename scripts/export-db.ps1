param(
    [string]$OutputFile = "highq.sql"
    [int]$IntervalMinutes = 5,
    [switch]$Once = $false
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
    "--databases",
    $dbName
)

$versionOut = & $mysqldumpExe --version 2>$null
if ($versionOut -notmatch "MariaDB") {
    $dumpArgs = @("--set-gtid-purged=OFF") + $dumpArgs
}

if ($dbPass) {
    $dumpArgs = @("--password=$dbPass") + $dumpArgs
}

function Get-DbSignature {
    $sigQuery = "SELECT IFNULL(SUM(CRC32(CONCAT(TABLE_NAME,IFNULL(UPDATE_TIME,''),TABLE_ROWS))),0) AS sig FROM information_schema.tables WHERE table_schema = '$dbName';"
    $args = @(
        "--host=$dbHost",
        "--user=$dbUser",
        "--database=$dbName",
        "--batch",
        "--skip-column-names",
        "-e",
        $sigQuery
    )
    if ($dbPass) { $args = @("--password=$dbPass") + $args }
    $raw = & $mysqlExe @args 2>$null
    return ($raw | Select-Object -First 1).Trim()
}

function Export-Db {
    Write-Host "Exporting database '$dbName' to $outPath ..."
    & $mysqldumpExe @dumpArgs | Out-File -FilePath $outPath -Encoding utf8
    Write-Host "Done."
}

if ($Once) {
    Export-Db
    exit 0
}

Write-Host "Watching for changes every $IntervalMinutes minute(s). Press Ctrl+C to stop."
$lastSig = Get-DbSignature
if (-not $lastSig) { $lastSig = "0" }
Export-Db

while ($true) {
    Start-Sleep -Seconds ($IntervalMinutes * 60)
    $currentSig = Get-DbSignature
    if (-not $currentSig) { $currentSig = "0" }
    if ($currentSig -ne $lastSig) {
        $lastSig = $currentSig
        Export-Db
    }
}
