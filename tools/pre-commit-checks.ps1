$ErrorActionPreference = 'Stop'

$staged = git diff --cached --name-only --diff-filter=ACM
if (-not $staged) { exit 0 }

Write-Host "[pre-commit] Running staged PHP lint..."
$phpFiles = $staged | Where-Object { $_ -like '*.php' }
foreach ($f in $phpFiles) {
    php -l $f | Out-Null
}

Write-Host "[pre-commit] Checking for inline scripts in views..."
$inlineScripts = rg -n "<script(?! src)|<script>" app/Views --pcre2 | rg -v "app/Views/errors/html/error_exception.php"
if ($LASTEXITCODE -eq 0 -and $inlineScripts) {
    Write-Host "Inline <script> blocks found in views (move to public/js):"
    Write-Host $inlineScripts
    exit 1
}

Write-Host "[pre-commit] Checking for inline styles in views..."
$inlineStyles = rg -n "<style>" app/Views | rg -v "app/Views/errors/html|app/Views/receipt/pdf.php"
if ($LASTEXITCODE -eq 0 -and $inlineStyles) {
    Write-Host "Inline <style> blocks found in views (move to public/css):"
    Write-Host $inlineStyles
    exit 1
}

Write-Host "[pre-commit] Running targeted PHPUnit tests..."
& vendor/bin/phpunit --no-coverage tests/unit/Helpers tests/unit/Support tests/feature/AdminBulkEndpointsTest.php | Out-Null

Write-Host "[pre-commit] OK"
