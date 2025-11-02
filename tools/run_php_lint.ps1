# Run php -l across all PHP files in the repository
Get-ChildItem -Path 'c:\xampp\htdocs\HIGH-Q' -Recurse -Filter '*.php' | ForEach-Object {
    try {
        $path = $_.FullName
        $res = & php -l $path 2>&1
        Write-Output $res
    } catch {
        Write-Output "ERROR: $_"
    }
}
