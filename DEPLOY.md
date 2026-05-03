# Varner OS / Theme Deployment Runbook

---

## Changelog

### 2026-05-02
- **YouTube — Visit Our Channel button**: `index.php` hero video section now links to `https://www.youtube.com/@varnerequipment`
- **YouTube — Video embed**: "See Our Machines In Action" section uses click-to-play embed for video `goF_3TspZ6k`; thumbnail auto-loads from YouTube
- **Brand logo ticker**: Each logo in the scrolling ticker now links to its brand page (`/brands/[slug]`)
- **Product Videos nav link**: Changed from internal WordPress page to `https://www.youtube.com/@VarnerEquipment` (opens in new tab)
- **Live Stock Ledger label**: Removed red "Live Stock Ledger" text above the inventory pulse bar on homepage
- **Theme filter — Stock # and VIN**: Added Stock Number and VIN/Serial search inputs to the theme inventory filter sidebar (`partials/filter-sidebar.php`); query logic added to `varner_build_inventory_query()` in `functions.php` (uses LIKE for partial match)
- **OS Plugin dashboard**: Rebuilt stale dist — restores Sold Units and Pending Sales metric cards and Import/Export buttons in Quick Operations
- **OS Plugin filter**: Added Stock # and VIN/Serial search to the React FilterSidebar (both horizontal desktop bar and mobile slide-out panel); applied filter pills and Clear All support included

---

## Deployment Artifacts

- `varner-os-plugin-v23.zip`
- `varner-equipment-theme-v23.zip`

## Workspace Rule

- Do not create or use temporary theme/plugin folders (for example: `temp-theme` or `temp-plugin`).
- Make all edits directly in the versioned source/artifact folders (for example: `varner-equipment-theme-v23/`) and then rebuild the deployment ZIP.

## Theme ZIP Packaging Rule (Important)

- Both ZIPs must be rebuilt after **every** theme file change.
- Each ZIP must contain a top-level `varner-equipment-theme/` folder.
- `style.css` must be inside that folder: `varner-equipment-theme/style.css`.
- Do not ZIP only the folder contents (that causes WordPress theme install/load errors).
- When editing files, always edit in the versioned source folder first, then sync to the lite folder, then rebuild both ZIPs.

### Source folders
| ZIP | Source folder |
|-----|--------------|
| `varner-equipment-theme-v23.zip` | `varner-equipment-theme-v23/varner-equipment-theme/` |
| `varner-equipment-theme-v23-lite.zip` | `varner-equipment-theme-lite/varner-equipment-theme/` |

### Rebuild both ZIPs (run from project root)

```powershell
# Sync changed files from full → lite (example for page-brand.php and functions.php)
Copy-Item '.\varner-equipment-theme-v23\varner-equipment-theme\page-brand.php' '.\varner-equipment-theme-lite\varner-equipment-theme\page-brand.php' -Force
Copy-Item '.\varner-equipment-theme-v23\varner-equipment-theme\functions.php'  '.\varner-equipment-theme-lite\varner-equipment-theme\functions.php'  -Force

# Full ZIP
if (Test-Path '.\varner-equipment-theme-v23.zip') { Remove-Item '.\varner-equipment-theme-v23.zip' -Force }
Compress-Archive -Path '.\varner-equipment-theme-v23\varner-equipment-theme' -DestinationPath '.\varner-equipment-theme-v23.zip' -Force

# Lite ZIP
if (Test-Path '.\varner-equipment-theme-v23-lite.zip') { Remove-Item '.\varner-equipment-theme-v23-lite.zip' -Force }
Compress-Archive -Path '.\varner-equipment-theme-lite\varner-equipment-theme' -DestinationPath '.\varner-equipment-theme-v23-lite.zip' -Force
```

## Plugin React Build (when src/App.jsx changes)

Any time `src/App.jsx` is edited, the React app must be rebuilt and the dist files copied into the plugin before zipping.

```powershell
# 1. Build
npm run build

# 2. Copy built files into plugin (keeps existing jpg assets)
Copy-Item '.\dist\index.html' '.\varner-os-plugin-v23-unpacked\varner-os-plugin-v23\dist\index.html' -Force
Copy-Item '.\dist\assets'     '.\varner-os-plugin-v23-unpacked\varner-os-plugin-v23\dist\assets'     -Recurse -Force

# 3. Zip plugin
Remove-Item '.\varner-os-plugin-v23.zip' -Force -ErrorAction SilentlyContinue
Compress-Archive -Path '.\varner-os-plugin-v23-unpacked\varner-os-plugin-v23' -DestinationPath '.\varner-os-plugin-v23.zip'
```

## Plugin Install or Update

1. Upload/install `varner-os-plugin-v23.zip`.
2. Activate the plugin.
3. Deactivate and reactivate once to run `dbDelta` and schedule the daily session cleanup cron.

## Theme Install or Update

1. Upload/install `varner-equipment-theme-v23.zip`.
2. Confirm `style.css` is at the theme root (already fixed in v23 package).

## Post-Install Checks

1. Clear app/server/CDN caches.
2. Verify header logo renders.
   - Preferred source: media attachment titled `VarnerEquipment_red`.
   - Fallback source: bundled theme logo assets.

## Plugin REST Endpoints (v23)

- `GET /varner/v1/me`
- `POST /varner/v1/logout`
- `GET /varner/v1/inventory/{id}/ledger` (paginated)
- `GET /varner/v1/sessions` (admin-only; supports `active` and `user` filters; paginated)

## Quick Verification in wp-admin

1. Open `Varner OS > Configuration`.
2. Confirm the React app mounts.
3. Confirm fallback server-rendered table shows recent sessions (last 25).
4. Confirm inventory create/update/delete/restore actions write ledger entries to `wp_varner_inventory_ledger`.
