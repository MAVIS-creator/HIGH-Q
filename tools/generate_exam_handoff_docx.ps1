Add-Type -AssemblyName System.IO.Compression.FileSystem

function Escape-Xml([string]$text) {
    if ($null -eq $text) { return '' }
    return [System.Security.SecurityElement]::Escape($text)
}

function New-DocParagraph([string]$text, [bool]$bold = $false) {
    $escaped = Escape-Xml $text
    if ([string]::IsNullOrWhiteSpace($escaped)) {
        return '<w:p/>'
    }

    $runProps = if ($bold) { '<w:rPr><w:b/></w:rPr>' } else { '' }
    return "<w:p><w:r>$runProps<w:t xml:space='preserve'>$escaped</w:t></w:r></w:p>"
}

function Convert-MarkdownToParagraphs([string[]]$lines) {
    $paragraphs = New-Object System.Collections.Generic.List[string]

    foreach ($line in $lines) {
        $trimmed = $line.TrimEnd()

        if ($trimmed -match '^(#+)\s+(.*)$') {
            $level = $matches[1].Length
            $prefix = switch ($level) {
                1 { 'TITLE: ' }
                2 { 'SECTION: ' }
                3 { 'SUBSECTION: ' }
                default { 'HEADING: ' }
            }
            $paragraphs.Add((New-DocParagraph ($prefix + $matches[2]) $true))
            continue
        }

        if ($trimmed -match '^\|') {
            $paragraphs.Add((New-DocParagraph (($trimmed -replace '\|', ' | ').Trim()) $false))
            continue
        }

        if ($trimmed -match '^\s*[-*]\s+(.*)$') {
            $paragraphs.Add((New-DocParagraph ('- ' + $matches[1]) $false))
            continue
        }

        if ($trimmed -match '^\s*\d+\.\s+(.*)$') {
            $paragraphs.Add((New-DocParagraph $trimmed $false))
            continue
        }

        if ($trimmed -eq '') {
            $paragraphs.Add('<w:p/>')
            continue
        }

        $paragraphs.Add((New-DocParagraph $trimmed $false))
    }

    return $paragraphs
}

function New-DocxFromMarkdown([string]$markdownPath, [string]$docxPath) {
    $lines = Get-Content -LiteralPath $markdownPath
    $paragraphXml = (Convert-MarkdownToParagraphs $lines) -join "`n"

    $contentTypes = @"
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
  <Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
  <Default Extension="xml" ContentType="application/xml"/>
  <Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/>
  <Override PartName="/docProps/core.xml" ContentType="application/vnd.openxmlformats-package.core-properties+xml"/>
  <Override PartName="/docProps/app.xml" ContentType="application/vnd.openxmlformats-officedocument.extended-properties+xml"/>
</Types>
"@

    $rels = @"
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/>
  <Relationship Id="rId2" Type="http://schemas.openxmlformats.org/package/2006/relationships/metadata/core-properties" Target="docProps/core.xml"/>
  <Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/extended-properties" Target="docProps/app.xml"/>
</Relationships>
"@

    $docRels = @"
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"></Relationships>
"@

    $title = [IO.Path]::GetFileNameWithoutExtension($markdownPath)
    $now = (Get-Date).ToUniversalTime().ToString("s") + 'Z'

    $core = @"
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<cp:coreProperties xmlns:cp="http://schemas.openxmlformats.org/package/2006/metadata/core-properties" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:dcterms="http://purl.org/dc/terms/" xmlns:dcmitype="http://purl.org/dc/dcmitype/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
  <dc:title>$(Escape-Xml $title)</dc:title>
  <dc:creator>OpenAI Codex</dc:creator>
  <cp:lastModifiedBy>OpenAI Codex</cp:lastModifiedBy>
  <dcterms:created xsi:type="dcterms:W3CDTF">$now</dcterms:created>
  <dcterms:modified xsi:type="dcterms:W3CDTF">$now</dcterms:modified>
</cp:coreProperties>
"@

    $app = @"
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Properties xmlns="http://schemas.openxmlformats.org/officeDocument/2006/extended-properties" xmlns:vt="http://schemas.openxmlformats.org/officeDocument/2006/docPropsVTypes">
  <Application>Microsoft Office Word</Application>
</Properties>
"@

    $document = @"
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<w:document xmlns:wpc="http://schemas.microsoft.com/office/word/2010/wordprocessingCanvas" xmlns:mc="http://schemas.openxmlformats.org/markup-compatibility/2006" xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships" xmlns:m="http://schemas.openxmlformats.org/officeDocument/2006/math" xmlns:v="urn:schemas-microsoft-com:vml" xmlns:wp14="http://schemas.microsoft.com/office/word/2010/wordprocessingDrawing" xmlns:wp="http://schemas.openxmlformats.org/drawingml/2006/wordprocessingDrawing" xmlns:w10="urn:schemas-microsoft-com:office:word" xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main" xmlns:w14="http://schemas.microsoft.com/office/word/2010/wordml" xmlns:wpg="http://schemas.microsoft.com/office/word/2010/wordprocessingGroup" xmlns:wpi="http://schemas.microsoft.com/office/word/2010/wordprocessingInk" xmlns:wne="http://schemas.microsoft.com/office/word/2006/wordml" xmlns:wps="http://schemas.microsoft.com/office/word/2010/wordprocessingShape" mc:Ignorable="w14 wp14">
  <w:body>
    $paragraphXml
    <w:sectPr>
      <w:pgSz w:w="12240" w:h="15840"/>
      <w:pgMar w:top="1440" w:right="1440" w:bottom="1440" w:left="1440" w:header="708" w:footer="708" w:gutter="0"/>
    </w:sectPr>
  </w:body>
</w:document>
"@

    if (Test-Path -LiteralPath $docxPath) {
        Remove-Item -LiteralPath $docxPath -Force
    }

    $tempDir = Join-Path ([IO.Path]::GetTempPath()) ([IO.Path]::GetRandomFileName())
    New-Item -ItemType Directory -Path $tempDir | Out-Null
    New-Item -ItemType Directory -Path (Join-Path $tempDir '_rels') | Out-Null
    New-Item -ItemType Directory -Path (Join-Path $tempDir 'word') | Out-Null
    New-Item -ItemType Directory -Path (Join-Path $tempDir 'word\_rels') | Out-Null
    New-Item -ItemType Directory -Path (Join-Path $tempDir 'docProps') | Out-Null

    Set-Content -LiteralPath (Join-Path $tempDir '[Content_Types].xml') -Value $contentTypes -Encoding UTF8
    Set-Content -LiteralPath (Join-Path $tempDir '_rels\.rels') -Value $rels -Encoding UTF8
    Set-Content -LiteralPath (Join-Path $tempDir 'word\document.xml') -Value $document -Encoding UTF8
    Set-Content -LiteralPath (Join-Path $tempDir 'word\_rels\document.xml.rels') -Value $docRels -Encoding UTF8
    Set-Content -LiteralPath (Join-Path $tempDir 'docProps\core.xml') -Value $core -Encoding UTF8
    Set-Content -LiteralPath (Join-Path $tempDir 'docProps\app.xml') -Value $app -Encoding UTF8

    [System.IO.Compression.ZipFile]::CreateFromDirectory($tempDir, $docxPath)
    Remove-Item -LiteralPath $tempDir -Recurse -Force
}

$root = Split-Path -Parent $PSScriptRoot
$targets = @(
    (Join-Path $root 'docs\EXAM_PORTAL_TEAM_ROADMAP.md'),
    (Join-Path $root 'docs\EXAM_PORTAL_FRONTEND_HANDOFF.md'),
    (Join-Path $root 'docs\EXAM_PORTAL_BACKEND_HANDOFF.md')
)

foreach ($target in $targets) {
    $docx = [IO.Path]::ChangeExtension($target, '.docx')
    New-DocxFromMarkdown $target $docx
    Write-Output "Created $docx"
}
