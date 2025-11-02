# Run php -l across all PHP files in the repository
# Prints only files that have parse/syntax errors and returns exit code 1 when any are found.
$errorsFound = $false
Get-ChildItem -Path 'c:\xampp\htdocs\HIGH-Q' -Recurse -Filter '*.php' | ForEach-Object {
    try {
        $path = $_.FullName
        # Run php -l and capture exit code and any output
        $res = & php -l $path 2>&1
        $exitCode = $LASTEXITCODE
        if ($exitCode -ne 0) {
            Write-Output "ERROR in: $path"
            Write-Output $res
            $errorsFound = $true
        }
    } catch {
        Write-Output "EXCEPTION while linting: $path - $_"
        $errorsFound = $true
    }
}
if ($errorsFound) { exit 1 } else { Write-Output "All PHP files passed php -l."; exit 0 }
