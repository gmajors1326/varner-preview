# Varner OS / Theme Deployment Runbook

---

## Changelog

### 2026-05-13
- **Native Sidebar Consolidation**: Removed `facet-sidebar.php` and `filter-sidebar.php`. Created `inventory-sidebar.php` which combines native dual-range sliders with "Applied Filters" pill logic and auto-submission.
- **Documentation Cleanup**: Removed FacetWP references from `SKILL.md` and `DEPLOY.md` to reflect the actual native implementation.

### 2026-05-07
- **Dynamic Inventory Segments**: Implemented custom rewrite rules for `/inventory/new`, `/inventory/used`, `/inventory/tractors`, `/inventory/trailers`, `/inventory/attachments`, `/inventory/hay-equipment`, and `/inventory/misc`.
- **Segment SEO & Auto-filtering**: Logic added to `functions.php` via `varner_get_segment_seo()` to provide unique H1/Subheadings/Blurbs and automatic pre-filtering for each segment.
- **Menu Restructuring**: Updated `header.php` main navigation and `index.php` category grid to link directly to the new optimized segments.
- **Category Expansion**: Updated `varner-backend.php` with 20+ specific equipment categories (e.g., Balers, Mowers, Horse Trailers) to improve inventory classification.
- **Typography Fixes**: Relaxed H1 leading and tracking in `page-equipment-listing.php` and `single-equipment.php` to prevent layout "squenching" on long titles.

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
- `varner-equipment-theme-v23-lite.zip`

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
| `varner-equipment-theme-v23.zip` | `varner-equipment-theme-v23/varner-v23/` |
| `varner-equipment-theme-v23-lite.zip` | `varner-equipment-theme-lite/varner-lite/` |

### Rebuild both ZIPs (run from project root)

```powershell
# Sync changed files from full → lite
# REPLACE the filenames below with whichever files you actually changed
Copy-Item '.\varner-equipment-theme-v23\varner-equipment-theme\page-brand.php' '.\varner-equipment-theme-lite\varner-equipment-theme\page-brand.php' -Force
Copy-Item '.\varner-equipment-theme-v23\varner-equipment-theme\functions.php'  '.\varner-equipment-theme-lite\varner-equipment-theme\functions.php'  -Force

# Full ZIP
if (Test-Path '.\varner-equipment-theme-v23.zip') { Remove-Item '.\varner-equipment-theme-v23.zip' -Force }
# Using tar ensures forward slashes for Linux compatibility
cmd /c "cd varner-equipment-theme-v23 && tar -a -c -f ../varner-equipment-theme-v23.zip varner-v23"

# Lite ZIP
if (Test-Path '.\varner-equipment-theme-v23-lite.zip') { Remove-Item '.\varner-equipment-theme-v23-lite.zip' -Force }
cmd /c "cd varner-equipment-theme-lite && tar -a -c -f ../varner-equipment-theme-v23-lite.zip varner-lite"
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

| Method | Endpoint | Auth | Notes |
|--------|----------|------|-------|
| `GET` | `/varner/v1/inventory` | public | All active listings |
| `POST` | `/varner/v1/inventory` | editor | Create new unit |
| `GET` | `/varner/v1/inventory/deleted` | editor | Soft-deleted units |
| `PATCH` | `/varner/v1/inventory/{id}` | editor | Update unit fields |
| `DELETE` | `/varner/v1/inventory/{id}` | editor | Soft delete |
| `POST` | `/varner/v1/inventory/{id}/restore` | editor | Restore soft-deleted unit |
| `DELETE` | `/varner/v1/inventory/{id}/permanent` | editor | Permanent delete |
| `GET` | `/varner/v1/inventory/{id}/ledger` | editor | Paginated ledger entries |
| `POST` | `/varner/v1/media` | editor | Upload media attachment |
| `GET` | `/varner/v1/brands` | editor | Get brand list |
| `POST` | `/varner/v1/brands` | editor | Save brand list |
| `GET` | `/varner/v1/categories` | editor | Get category list |
| `POST` | `/varner/v1/categories` | editor | Save category list |
| `GET` | `/varner/v1/sessions` | admin | Supports `active` and `user` filters; paginated |
| `GET` | `/varner/v1/me` | logged-in | Current user info |
| `POST` | `/varner/v1/logout` | logged-in | Destroy session |

## Quick Verification in wp-admin

1. Open `Varner OS > Configuration`.
2. Confirm the React app mounts.
3. Confirm fallback server-rendered table shows recent sessions (last 25).
4. Confirm inventory create/update/delete/restore actions write ledger entries to `wp_varner_inventory_ledger`.
