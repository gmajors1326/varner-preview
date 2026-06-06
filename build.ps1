# Varner Equipment - Unified Build & Packaging Script
# Compiles React, syncs assets, auto-bumps plugin version, and packages plugin + themes.

$ErrorActionPreference = 'Stop'

$root = Get-Location

Write-Host "=============================================" -ForegroundColor Cyan
Write-Host "Starting Varner Equipment Unified Build..." -ForegroundColor Cyan
Write-Host "=============================================" -ForegroundColor Cyan

# 1. Compile React Application
Write-Host "`n[1/5] Building React Application..." -ForegroundColor Yellow
npm run build
if ($LASTEXITCODE -ne 0) {
    Write-Host "React build failed! Aborting." -ForegroundColor Red
    exit $LASTEXITCODE
}

# 2. Sync React Build to Plugin Folder
Write-Host "`n[2/5] Syncing React build into plugin..." -ForegroundColor Yellow
$pluginFolder = "varner-os-plugin-v23-unpacked\varner-os-plugin-v23"
Remove-Item "$pluginFolder\dist" -Recurse -Force -ErrorAction SilentlyContinue
Copy-Item ".\dist" "$pluginFolder\" -Recurse -Force

# 3. Auto-Increment Plugin Version (Busts CDN/WP Cache & Forces Overwrite Prompt)
Write-Host "`n[3/5] Auto-incrementing plugin version..." -ForegroundColor Yellow
$pluginFile = "$pluginFolder\varner-os-plugin-v23.php"
if (Test-Path $pluginFile) {
    $pluginContent = Get-Content $pluginFile -Raw
    if ($pluginContent -match 'Version:\s*(\d+)\.(\d+)\.(\d+)') {
        $major = $Matches[1]
        $minor = $Matches[2]
        $patch = [int]$Matches[3] + 1
        $newVer = "$major.$minor.$patch"
        
        # Replace version numbers in header
        $pluginContent = $pluginContent -replace 'Version:\s*\d+\.\d+\.\d+', "Version: $newVer"
        $pluginContent = $pluginContent -replace 'Version\s*\d+\.\d+\.\d+\s*-\s*React-powered', "Version $newVer - React-powered"
        Set-Content $pluginFile $pluginContent -NoNewline
        Write-Host "Plugin version bumped to: $newVer" -ForegroundColor Green
    } else {
        Write-Host "Warning: Could not match version regex in plugin header!" -ForegroundColor Red
    }
} else {
    Write-Host "Error: Plugin entry file not found at $pluginFile" -ForegroundColor Red
}

# 4. Package Plugin Zip
Write-Host "`n[4/5] Zipping Varner OS Plugin..." -ForegroundColor Yellow
$pluginZipName = "varner-os-plugin-v23.zip"
$pluginZip = Join-Path $root $pluginZipName
if (Test-Path $pluginZip) { Remove-Item $pluginZip -Force }
Push-Location "varner-os-plugin-v23-unpacked"
tar -a -c -f "../$pluginZipName" varner-os-plugin-v23
Pop-Location
Write-Host "Plugin packaged -> $pluginZip" -ForegroundColor Green

# 5. Compile Tailwind CSS & Package Themes
Write-Host "`n[5/5] Compiling Tailwind CSS, Syncing, & Packaging Themes..." -ForegroundColor Yellow

$themeV23Path = "varner-equipment-theme-v23\varner-v23"
$themeLitePath = "varner-equipment-theme-lite\varner-lite"

# Sync PHP template files and style.css from V23 to Lite theme to ensure compatibility
Write-Host "Syncing PHP templates and style.css to Lite theme..." -ForegroundColor Cyan
Get-ChildItem -Path "$themeV23Path\*.php" | ForEach-Object {
    Copy-Item $_.FullName -Destination "$themeLitePath\" -Force
}
Copy-Item "$themeV23Path\style.css" -Destination "$themeLitePath\" -Force

# Sync partials and acf-json directories
Remove-Item "$themeLitePath\partials" -Recurse -Force -ErrorAction SilentlyContinue
Copy-Item "$themeV23Path\partials" -Destination "$themeLitePath\partials" -Recurse -Force

Remove-Item "$themeLitePath\acf-json" -Recurse -Force -ErrorAction SilentlyContinue
Copy-Item "$themeV23Path\acf-json" -Destination "$themeLitePath\acf-json" -Recurse -Force

# Compile Tailwind CSS in the full theme
Write-Host "Compiling Tailwind CSS..." -ForegroundColor Cyan
Push-Location $themeV23Path
npx tailwindcss -i ./src/input.css -o ./assets/css/tailwind.css --minify
Pop-Location

# Copy compiled tailwind.css to the Lite theme
Copy-Item "$themeV23Path\assets\css\tailwind.css" -Destination "$themeLitePath\assets\css\tailwind.css" -Force


# Full Theme (v23)
$v23ZipName = "varner-equipment-theme-v23.zip"
$v23Zip = Join-Path $root $v23ZipName
if (Test-Path $v23Zip) { Remove-Item $v23Zip -Force }
Push-Location "varner-equipment-theme-v23"
tar -a -c -f "../$v23ZipName" varner-v23
Pop-Location
Write-Host "Full Theme packaged -> $v23Zip" -ForegroundColor Green

# Lite Theme
$liteZipName = "varner-equipment-theme-v23-lite.zip"
$liteZip = Join-Path $root $liteZipName
if (Test-Path $liteZip) { Remove-Item $liteZip -Force }
Push-Location "varner-equipment-theme-lite"
tar -a -c -f "../$liteZipName" varner-lite
Pop-Location
Write-Host "Lite Theme packaged -> $liteZip" -ForegroundColor Green

Write-Host "`n=============================================" -ForegroundColor Green
Write-Host "Unified Build Complete! All ZIP files updated." -ForegroundColor Green
Write-Host "=============================================" -ForegroundColor Green
