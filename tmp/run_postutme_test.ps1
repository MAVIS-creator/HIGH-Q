# PowerShell script to submit a fake Post-UTME registration to the local site and print the response
$ErrorActionPreference = 'Stop'
$root = Split-Path -Parent $MyInvocation.MyCommand.Definition
$appUrl = $env:APP_URL
if ([string]::IsNullOrWhiteSpace($appUrl)) { $appUrl = 'http://127.0.0.1/HIGH-Q' }
$url = $appUrl.TrimEnd('/') + '/public/post-utme.php'
$session = New-Object Microsoft.PowerShell.Commands.WebRequestSession

# Fetch the registration page to get CSRF token and cookies
Write-Host "Fetching registration form..."
$resp = Invoke-WebRequest -Uri $url -WebSession $session -UseBasicParsing
$html = $resp.Content
$tokMatch = [regex]::Match($html, 'name="_csrf_token" value="([^"]+)"')
if (-not $tokMatch.Success) { Write-Host "Failed to find CSRF token in form."; exit 2 }
$token = $tokMatch.Groups[1].Value
Write-Host "CSRF token:" $token

# Prepare form fields (minimal required for Post-UTME validation)
$form = @{
    '_csrf_token' = $token
    'registration_type' = 'postutme'
    'first_name_post' = 'Automated'
    'surname' = 'Tester'
    'other_name' = 'Bot'
    'post_gender' = 'male'
    'address' = '123 Test Lane'
    'parent_phone' = '08012345678'
    'email_post' = 'test+postutme@example.local'
    'jamb_registration_number' = 'JAMB-TEST-0001'
    'jamb_score' = '80'
    'jamb_subj_1' = 'English'
    'jamb_score_1' = '80'
    'jamb_subj_2' = 'Biology'
    'jamb_score_2' = '70'
    'jamb_subj_3' = 'Chemistry'
    'jamb_score_3' = '65'
    'jamb_subj_4' = 'Physics'
    'jamb_score_4' = '60'
    'course_first_choice' = 'Test Course'
    'father_name' = 'Parent One'
    'father_phone' = '08011111111'
    'mother_name' = 'Parent Two'
    'mother_phone' = '08022222222'
    'primary_school' = 'Test Primary'
    'primary_year_ended' = '2010'
    'secondary_school' = 'Test Secondary'
    'secondary_year_ended' = '2016'
    'exam_type' = 'WAEC'
    'candidate_name' = 'Automated Tester'
    'exam_number' = 'WAEC1234567'
    'exam_year_month' = '2024-05'
    'olevel_subj_1' = 'English Language'
    'olevel_grade_1' = 'A1'
    'olevel_subj_2' = 'Mathematics'
    'olevel_grade_2' = 'B2'
    'olevel_subj_3' = 'Civic Education'
    'olevel_grade_3' = 'B2'
    'olevel_subj_4' = 'Biology'
    'olevel_grade_4' = 'C5'
    'waec_token' = 'WTOKEN123'
    'waec_serial' = 'WSERIAL123'
    'post_tutor_fee' = '0'
    'agreed_terms' = 'on'
}

# Attach passport file (use the tmp test file)
$passportPath = Join-Path $root 'test_passport.jpg'
if (-not (Test-Path $passportPath)) { Write-Host "Passport file missing: $passportPath"; exit 3 }
$form.Add('passport', (Get-Item $passportPath))

Write-Host "Submitting Post-UTME registration (this will follow redirects) ..."
try {
    $postResp = Invoke-WebRequest -Uri $url -Method Post -WebSession $session -Form $form -MaximumRedirection 0 -AllowUnencryptedAuthentication -ErrorAction Stop
    # If we get here without exception, we likely got a 200 with no redirect
    Write-Host "Submission returned status code:" $postResp.StatusCode
    Write-Host "Response headers:"
    $postResp.Headers
    Write-Host "Response content (first 400 chars):"
    $content = $postResp.Content
    Write-Host ($content.Substring(0,[Math]::Min(400,$content.Length)))
} catch [System.Net.WebException] {
    $we = $_.Exception.Response
     if ($null -ne $we) {
        $status = $we.StatusCode.Value__
        Write-Host "Submission resulted in HTTP status:" $status
        $loc = $we.GetResponseHeader('Location')
        if ($loc) { Write-Host "Redirect Location:" $loc }
        $sr = New-Object System.IO.StreamReader($we.GetResponseStream())
        $body = $sr.ReadToEnd()
        Write-Host "Response body (first 400 chars):"
        Write-Host ($body.Substring(0,[Math]::Min(400,$body.Length)))
    } else {
        Write-Host "WebException without response:" $_.Exception.Message
    }
}

# After submit, query DB via PHP helper script
Write-Host "\nQuerying DB for last post_utme registration (using PHP helper)..."
$php = 'php'
$phpScript = Join-Path $root 'query_last_postutme.php'
if (-not (Test-Path $phpScript)) { Write-Host "PHP query script missing: $phpScript"; exit 4 }
$phpProc = & $php $phpScript
Write-Host $phpProc
