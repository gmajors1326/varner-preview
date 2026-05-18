# Varner Equipment - Theme Build Script
# Using tar -a for maximum compatibility (standard ZIP format with forward slashes)

$root = Get-Location

Write-Host "Building Varner Equipment Theme Packages..." -ForegroundColor Cyan

# 1. Compile Tailwind CSS
Write-Host "Compiling Tailwind CSS..." -ForegroundColor Yellow
Push-Location "varner-equipment-theme-v23\varner-v23"
npx tailwindcss -i ./src/input.css -o ./assets/css/tailwind.css --minify
Pop-Location
Copy-Item "varner-equipment-theme-v23\varner-v23\assets\css\tailwind.css" -Destination "varner-equipment-theme-lite\varner-lite\assets\css\tailwind.css" -Force

# 2. Full Theme (v23)
Write-Host "Zipping Full Theme (v23)..."
$v23Zip = Join-Path $root "varner-equipment-theme-v23.zip"
if (Test-Path $v23Zip) { Remove-Item $v23Zip -Force }
Push-Location "varner-equipment-theme-v23"
tar -a -c -f $v23Zip varner-v23
Pop-Location

# 2. Lite Theme
Write-Host "Zipping Lite Theme..."
$liteZip = Join-Path $root "varner-equipment-theme-v23-lite.zip"
if (Test-Path $liteZip) { Remove-Item $liteZip -Force }
Push-Location "varner-equipment-theme-lite"
tar -a -c -f $liteZip varner-lite
Pop-Location

Write-Host "Build Complete!" -ForegroundColor Green
Write-Host "Artifacts generated:"
Write-Host " - $v23Zip"
Write-Host " - $liteZip"
