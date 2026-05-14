# Varner Equipment - Theme Build Script
# This script rebuilds both the Full and Lite theme ZIPs with Linux compatibility (forward slashes)

$root = Get-Location

Write-Host "Building Varner Equipment Theme Packages..." -ForegroundColor Cyan

# 1. Full Theme (v23)
Write-Host "Zipping Full Theme (v23)..."
$v23Zip = Join-Path $root "varner-equipment-theme-v23.zip"
if (Test-Path $v23Zip) { Remove-Item $v23Zip -Force }
cmd /c "cd varner-equipment-theme-v23 && tar -a -c -f ../varner-equipment-theme-v23.zip varner-v23"

# 2. Lite Theme
Write-Host "Zipping Lite Theme..."
$liteZip = Join-Path $root "varner-equipment-theme-v23-lite.zip"
if (Test-Path $liteZip) { Remove-Item $liteZip -Force }
cmd /c "cd varner-equipment-theme-lite && tar -a -c -f ../varner-equipment-theme-v23-lite.zip varner-lite"

Write-Host "Build Complete!" -ForegroundColor Green
Write-Host "Artifacts generated:"
Write-Host " - $v23Zip"
Write-Host " - $liteZip"
