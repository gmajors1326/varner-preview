$ErrorActionPreference = 'Stop'

$scriptDir = Split-Path -Parent $MyInvocation.MyCommand.Path
$repoRoot  = Split-Path $scriptDir
Set-Location $repoRoot

$fullZip   = Join-Path $repoRoot 'varner-equipment-theme-v23.zip'
$liteZip   = Join-Path $repoRoot 'varner-equipment-theme-v23-lite.zip'
$fullSrc   = Join-Path $repoRoot 'varner-equipment-theme-v23\varner-v23'
$liteSrc   = Join-Path $repoRoot 'varner-equipment-theme-lite\varner-lite'
# Sync V23 theme changes to Lite theme to ensure compatibility
Write-Host "Syncing PHP templates, style.css, partials, and ACF config to Lite theme..." -ForegroundColor Cyan
Get-ChildItem -Path "$fullSrc\*.php" | ForEach-Object {
    Copy-Item $_.FullName -Destination "$liteSrc\" -Force
}
Copy-Item "$fullSrc\style.css" -Destination "$liteSrc\" -Force

Remove-Item "$liteSrc\partials" -Recurse -Force -ErrorAction SilentlyContinue
Copy-Item "$fullSrc\partials" -Destination "$liteSrc\partials" -Recurse -Force

Remove-Item "$liteSrc\acf-json" -Recurse -Force -ErrorAction SilentlyContinue
Copy-Item "$fullSrc\acf-json" -Destination "$liteSrc\acf-json" -Recurse -Force

if (Test-Path $fullZip) { Remove-Item $fullZip -Force }
if (Test-Path $liteZip) { Remove-Item $liteZip -Force }

Compress-Archive -Path $fullSrc -DestinationPath $fullZip -Force
Compress-Archive -Path $liteSrc -DestinationPath $liteZip -Force

Write-Host "Rebuilt theme ZIPs:" -ForegroundColor Green
Write-Host "  $fullZip"
Write-Host "  $liteZip"
