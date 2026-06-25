# Varner Equipment - Unified Build & Packaging Script
# Compiles React, syncs assets, auto-bumps plugin version, and packages plugin + theme.
# varner-lite is the sole master theme. varner-v23 is archived.

$ErrorActionPreference = 'Stop'

$root = Get-Location

Write-Host "=============================================" -ForegroundColor Cyan
Write-Host "Starting Varner Equipment Unified Build..."   -ForegroundColor Cyan
Write-Host "=============================================" -ForegroundColor Cyan

# 1. Compile React Application
Write-Host "`n[1/4] Building React Application..." -ForegroundColor Yellow
npm run build
if ($LASTEXITCODE -ne 0) {
    Write-Host "React build failed! Aborting." -ForegroundColor Red
    exit $LASTEXITCODE
}

# 2. Sync React Build to Plugin Folder
Write-Host "`n[2/4] Syncing React build into plugin..." -ForegroundColor Yellow
$pluginFolder = "varner-os-plugin-v23-unpacked\varner-os-plugin-v23"
Remove-Item "$pluginFolder\dist" -Recurse -Force -ErrorAction SilentlyContinue
Copy-Item ".\dist" "$pluginFolder\" -Recurse -Force

# 3. Auto-Increment Plugin Version (Busts CDN/WP Cache & Forces Overwrite Prompt)
Write-Host "`n[3/4] Auto-incrementing plugin and theme versions..." -ForegroundColor Yellow
$pluginFile = "$pluginFolder\varner-os-plugin-v23.php"
if (Test-Path $pluginFile) {
    $pluginContent = Get-Content $pluginFile -Raw
    if ($pluginContent -match 'Version:\s*(\d+)\.(\d+)\.(\d+)') {
        $major = $Matches[1]
        $minor = $Matches[2]
        $patch = [int]$Matches[3] + 1
        $newVer = "$major.$minor.$patch"

        $pluginContent = $pluginContent -replace 'Version:\s*\d+\.\d+\.\d+', "Version: $newVer"
        $pluginContent = $pluginContent -replace 'Version\s*\d+\.\d+\.\d+\s*-\s*React-powered', "Version $newVer - React-powered"
        Set-Content $pluginFile $pluginContent -NoNewline
        Write-Host "Plugin version bumped to: $newVer" -ForegroundColor Green

        # Also bump theme version in style.css to match
        $themeStyleFile = "varner-equipment-theme-lite\varner-lite\style.css"
        if (Test-Path $themeStyleFile) {
            $styleContent = Get-Content $themeStyleFile -Raw
            if ($styleContent -match 'Version:\s*\d+\.\d+\.\d+') {
                $styleContent = $styleContent -replace 'Version:\s*\d+\.\d+\.\d+', "Version: $newVer"
                Set-Content $themeStyleFile $styleContent -NoNewline
                Write-Host "Theme version bumped to: $newVer" -ForegroundColor Green
            } else {
                Write-Host "Warning: Could not match version regex in theme style.css!" -ForegroundColor Red
            }
        } else {
            Write-Host "Warning: Theme style.css not found at $themeStyleFile" -ForegroundColor Red
        }

        # Sync version to plugin's readme.txt
        $pluginReadmeFile = "$pluginFolder\readme.txt"
        if (Test-Path $pluginReadmeFile) {
            $readmeContent = Get-Content $pluginReadmeFile -Raw
            if ($readmeContent -match 'Version:\s*\d+\.\d+\.\d+') {
                $readmeContent = $readmeContent -replace 'Version:\s*\d+\.\d+\.\d+', "Version: $newVer"
                Set-Content $pluginReadmeFile $readmeContent -NoNewline
                Write-Host "Plugin readme version bumped to: $newVer" -ForegroundColor Green
            } else {
                Write-Host "Warning: Could not match version regex in plugin readme.txt!" -ForegroundColor Red
            }
        } else {
            Write-Host "Warning: Plugin readme.txt not found at $pluginReadmeFile" -ForegroundColor Red
        }
    } else {
        Write-Host "Warning: Could not match version regex in plugin header!" -ForegroundColor Red
    }
} else {
    Write-Host "Error: Plugin entry file not found at $pluginFile" -ForegroundColor Red
}

# 4. Compile Tailwind CSS & Package Theme + Plugin
Write-Host "`n[4/4] Compiling Tailwind CSS & Packaging..." -ForegroundColor Yellow

$themePath = "varner-equipment-theme-lite\varner-lite"

# Compile Tailwind CSS inside varner-lite (the master theme)
Write-Host "Compiling Tailwind CSS..." -ForegroundColor Cyan
Push-Location $themePath
npx tailwindcss -i ./src/input.css -o ./assets/css/tailwind.css --minify
Pop-Location

# Package Plugin ZIP
$pluginZipName = "varner-os-plugin-v23.zip"
$pluginZip = Join-Path $root $pluginZipName
if (Test-Path $pluginZip) { Remove-Item $pluginZip -Force }
python tools/zip_helper.py $pluginZip "varner-os-plugin-v23-unpacked/varner-os-plugin-v23"
Write-Host "Plugin packaged -> $pluginZip" -ForegroundColor Green

# Package Theme ZIP (files at root — required for WP admin upload compatibility)
$themeZipName = "varner-equipment-theme-v23-lite.zip"
$themeZip = Join-Path $root $themeZipName
if (Test-Path $themeZip) { Remove-Item $themeZip -Force }
python -c "
import zipfile, os
src = os.path.abspath('varner-equipment-theme-lite/varner-lite')
exclude_dirs = {'src', '.git', '__pycache__'}
exclude_files = {'.DS_Store', 'tailwind.config.js', 'nul', 'package.json'}
with zipfile.ZipFile(r'$themeZip', 'w', zipfile.ZIP_DEFLATED) as z:
    for root, dirs, files in os.walk(src):
        dirs[:] = [d for d in dirs if d not in exclude_dirs]
        dirs.sort(); files.sort()
        for f in files:
            if f.startswith('.git') or f in exclude_files or f.endswith('.md'): continue
            if f.endswith('.md'): continue
            fp = os.path.join(root, f)
            arcname = os.path.relpath(fp, src).replace(os.sep, '/')
            z.write(fp, arcname)
print('Theme ZIP: files at root level (no prefix)')
"
Write-Host "Theme packaged -> $themeZip" -ForegroundColor Green

Write-Host "`n=============================================" -ForegroundColor Green
Write-Host "Unified Build Complete!"                        -ForegroundColor Green
Write-Host "  Plugin: $pluginZipName"                      -ForegroundColor Green
Write-Host "  Theme:  $themeZipName"                       -ForegroundColor Green
Write-Host "=============================================" -ForegroundColor Green
