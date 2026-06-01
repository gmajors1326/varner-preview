$ErrorActionPreference = 'Stop'

$scriptDir = Split-Path -Parent $MyInvocation.MyCommand.Path
$repoRoot  = Split-Path $scriptDir
Set-Location $repoRoot

$fullZip   = Join-Path $repoRoot 'varner-equipment-theme-v23.zip'
$liteZip   = Join-Path $repoRoot 'varner-equipment-theme-v23-lite.zip'
$fullSrc   = Join-Path $repoRoot 'varner-equipment-theme-v23\varner-v23'
$liteSrc   = Join-Path $repoRoot 'varner-equipment-theme-lite\varner-lite'

if (Test-Path $fullZip) { Remove-Item $fullZip -Force }
if (Test-Path $liteZip) { Remove-Item $liteZip -Force }

Compress-Archive -Path $fullSrc -DestinationPath $fullZip -Force
Compress-Archive -Path $liteSrc -DestinationPath $liteZip -Force

Write-Host "Rebuilt theme ZIPs:" -ForegroundColor Green
Write-Host "  $fullZip"
Write-Host "  $liteZip"
