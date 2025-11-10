# PowerShell script to submit a fake Regular registration to the local site and print the response
$ErrorActionPreference = 'Stop'
$root = Split-Path -Parent $MyInvocation.MyCommand.Definition
$appUrl = $env:APP_URL
if ([string]::IsNullOrWhiteSpace($appUrl)) { $appUrl = 'http://Phone's dead, My phone is not with me, Attendance closed' }
$url = $appUrl.TrimEnd('/') + '/public/register.php'
$session = New-Object Microsoft.PowerShell.Commands.WebRequestSession

# Fetch the registration page to get CSRF token and cookies
Write-Host "Fetching registration form..."
$resp = Invoke-WebRequest -Uri $url -WebSession $session -UseBasicParsing
$html = $resp.Content
$tokMatch = [regex]::Match($html, 'name="_csrf_token" value="([^"]+)"')
if (-not $tokMatch.Success) { Write-Host "Failed to find CSRF token in form."; exit 2 }
$token = $tokMatch.Groups[1].Value
Write-Host "CSRF token:" $token

# Find available program IDs (checkbox inputs named programs[])
#[+] Prefer a fixed-priced program (skip 'Varies')
$selectedProgram = $null
$progMatches = [regex]::Matches($html, 'name="programs\[\]"\s+value="(\d+)"[\s\S]*?<small class="program-price">\(([^)]+)\)', 'IgnoreCase')
if ($progMatches.Count -gt 0) {
    foreach ($m in $progMatches) {
        $id = $m.Groups[1].Value
        $priceText = $m.Groups[2].Value.Trim()
        if ($priceText -and ($priceText -notmatch 'Varies')) {
            # found fixed price (e.g. '₦90,000.00' or '₦90000')
            $selectedProgram = $id
            Write-Host "Found fixed-priced program id:" $id "price:" $priceText
            break
        }
    }
    if (-not $selectedProgram) {
        # fallback: pick first program if none fixed-priced
        $selectedProgram = $progMatches[0].Groups[1].Value
        Write-Host "No fixed-priced program found; picked first program id:" $selectedProgram
    }
} else {
    Write-Host "No programs found on the registration page. The form may be trimmed or server returned a different layout.";
}

# Prepare form fields (minimal required for Regular validation)
$form = @{
    '_csrf_token' = $token
    'registration_type' = 'regular'
    'first_name' = 'Automated'
    'last_name' = 'Tester'
    'email_contact' = 'test+regular@example.local'
    'phone' = '08012345678'
    'agreed_terms' = 'on'
}

# Ensure server receives explicit method and form_action to match the real form submission
$form['method'] = 'bank'
$form['form_action'] = 'regular'
# Explicit hidden field name used by server for method choice (mirrors payment_method_choice_regular)
$form['payment_method_choice_regular'] = 'bank'

# Attach passport file (use the tmp test file)
$passportPath = Join-Path $root 'test_passport.jpg'
if (-not (Test-Path $passportPath)) { Write-Host "Passport file missing: $passportPath"; exit 3 }
$form.Add('passport', (Get-Item $passportPath))

# Add a single fixed-priced program selection if available so the server creates a payment
if ($selectedProgram) {
    # For form arrays, repeat the key
    $form.Add('programs[]', $selectedProgram)
    Write-Host "Added programs[] = $selectedProgram to form"
}

Write-Host "Submitting Regular registration (expecting a redirect to payment or confirmation) ..."
try {
    # Allow one redirect so we can observe the Location (payment page) while still controlling flow
    $postResp = Invoke-WebRequest -Uri $url -Method Post -WebSession $session -Form $form -MaximumRedirection 1 -AllowUnencryptedAuthentication -ErrorAction Stop
    Write-Host "Posted form fields:" ($form.GetEnumerator() | ForEach-Object { $_.Key + '=' + ($_.Value -is [IO.FileInfo] ? '[FILE]' : $_.Value) } | Out-String)
    # Try to obtain final URI and status
    try { $finalUri = $postResp.BaseResponse.ResponseUri.AbsoluteUri } catch { $finalUri = $null }
    if ($finalUri) { Write-Host "Submission completed. Final URI:" $finalUri }
    if ($postResp.StatusCode) { Write-Host "Status code:" $postResp.StatusCode }
    if ($postResp.Headers) { Write-Host "Response headers:"; $postResp.Headers }
    $content = $postResp.Content
    Write-Host "Response content (first 400 chars):"; Write-Host ($content.Substring(0,[Math]::Min(400,$content.Length)))
} catch [System.Net.WebException] {
    $we = $_.Exception.Response
    if ($we -ne $null) {
        try { $status = $we.StatusCode.Value__ } catch { $status = 'Unknown' }
        Write-Host "Submission resulted in HTTP status:" $status
        try { $loc = $we.GetResponseHeader('Location') } catch { $loc = $null }
        if ($loc) { Write-Host "Redirect Location:" $loc }
        try {
            $sr = New-Object System.IO.StreamReader($we.GetResponseStream())
            $body = $sr.ReadToEnd()
            Write-Host "Response body (first 400 chars):"
            Write-Host ($body.Substring(0,[Math]::Min(400,$body.Length)))
        } catch { Write-Host "Unable to read response body." }
    } else {
        Write-Host "WebException without response:" $_.Exception.Message
    }
}

# After submit, query DB via PHP helper for the last regular registration and recent payments
Write-Host "\nQuerying DB for last regular registration and recent payments (using PHP helpers)..."
$php = 'php'
$phpScriptReg = Join-Path $root 'query_last_regular.php'
if (-not (Test-Path $phpScriptReg)) { Write-Host "PHP query script missing: $phpScriptReg"; exit 4 }
$phpOut = & $php $phpScriptReg
Write-Host "Last regular registration:"; Write-Host $phpOut

$phpScriptPay = Join-Path $root 'dump_payments.php'
if (-not (Test-Path $phpScriptPay)) { Write-Host "PHP dump payments script missing: $phpScriptPay"; exit 5 }
$phpOut2 = & $php $phpScriptPay
Write-Host "Recent payments:"; Write-Host $phpOut2