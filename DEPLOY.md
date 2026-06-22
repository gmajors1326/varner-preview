# Varner OS / Theme Deployment Runbook

---

## Changelog

### 2026-06-17 (v1.23.181)
- **iPad Landscape Responsiveness Fixes**: Resolved the overlap between the hero buttons and the quick search bar by adjusting the hero section height (from `h-[85vh]` to `min-h-[750px]`), increasing container padding-bottom (to `lg:pb-48`), and introducing a smooth responsive scale for the hero H1 title (`text-4xl sm:text-5xl md:text-6xl lg:text-7xl xl:text-8xl`) to prevent excessive wrapping on tablet viewports.

### 2026-06-17 (v1.23.177)
- **Pages Management API Hardening & UI**: Added secure REST endpoints (`/pages`, `/page-templates`) and a management sub-panel in Settings. Consolidated duplicate route registrations. Gated endpoints on granular meta capabilities (`edit_page`, `delete_page`, and `publish_pages`). Added server-side validation whitelists for theme templates, and blocked trashing or draft-status toggling of critical system pages (home page, blog page, privacy page, or showroom).
- **UX & Safety Enhancements**: Built sticky quick-jump section navigation at the top of the Settings Page Editor. Integrated client-side confirmation prompts to safeguard destructive actions.
- **Form Responsive Grid Optimizations**: Redesigned the equipment references grid on both [page-service-request.php](file:///c:/Users/Greg/Desktop/Varner%20Equipent/varner-equipment-theme-lite/varner-lite/page-service-request.php) and [page-parts-request.php](file:///c:/Users/Greg/Desktop/Varner%20Equipent/varner-equipment-theme-lite/varner-lite/page-parts-request.php) to use `grid-cols-1 sm:grid-cols-2 lg:grid-cols-5`. This prevents input squishing on tablet/laptop viewports by folding cleanly into a balanced grid, with the "Hours/Meter" field spanning full-width on medium sizes and resolving to a single row on desktop/large laptop screens.
- **Trailer Length Selection Options**: Conditionally displays trailer length selection only when category includes "trailer", and transitioned length input from a free-text string to a dropdown select covering 8–53 ft.
- **Brand Sync & Tailwind/Build Fixes**: Synced brand options (added Zetor, renamed Titan MFG to Titan Trailers) across theme menus, REST configurations, and ACF structures. Added safelist configuration for arbitrary utility classes in `tailwind.config.js` and hardened `build.ps1` and `zip_helper.py` against Windows reserved `nul` files.

### 2026-06-09
- **varner-lite promoted to sole master theme**: `varner-equipment-theme-v23/` archived to `_archive/`. Tailwind build source (`src/input.css`, `tailwind.config.js`) migrated into `varner-lite/`. `build.ps1` updated — now produces one theme ZIP (`varner-equipment-theme-v23-lite.zip`) instead of two. No changes to deployed site (varner-lite was already the active theme).

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
- `varner-equipment-theme-v23-lite.zip`

> **Note**: `varner-equipment-theme-v23.zip` is retired. `varner-lite` is the sole master theme. The old v23 source is archived under `_archive/varner-equipment-theme-v23/`.

## Workspace Rule

- Do not create or use temporary theme/plugin folders (for example: `temp-theme` or `temp-plugin`).
- Make all edits directly in the versioned source/artifact folders (for example: `varner-equipment-theme-lite/varner-lite/`) and then rebuild the deployment ZIP.

## Theme & Plugin ZIP Packaging (Unified Script)

We use a unified PowerShell script `build.ps1` to compile Tailwind CSS, build the React inventory app, auto-increment the plugin version, and package the theme and plugin into ZIP archives.

**`varner-lite` is the sole master theme.** All edits go directly into `varner-equipment-theme-lite/varner-lite/`. The old `varner-v23` source is archived under `_archive/`.

### Automated Unified Build (Recommended)
To rebuild the theme and plugin in one step:
```powershell
.\build.ps1
```
> [!TIP]
> Each run of `.\build.ps1` auto-increments the plugin patch version (e.g. `1.23.1` → `1.23.2`), forces WordPress to prompt for an overwrite, and busts CDN/browser cache for all enqueued scripts on WP Engine.

### Theme ZIP packaging note

Do NOT use `tar -a -c -f` for ZIP creation — PowerShell's `tar` creates POSIX TAR archives, not valid ZIPs. WordPress rejects them with "Incompatible Archive." Always use `build.ps1` which uses Python's `zipfile` module for proper ZIP creation.

## Plugin Install or Update

> [!IMPORTANT]
> **WP Engine Ephemeral Filesystem Constraint**:
> The WP Engine SSH gateway uses ephemeral container sessions. Any files written to `/home/wpe-user` or `/tmp` are destroyed as soon as the SSH connection closes.
> Only the shared WordPress site folder `/sites/varnerequipdev` (and its subdirectories) is persistent.
> Because SCP/SFTP subsystems are disabled on the gateway, files must be streamed in binary mode over SSH to the persistent volume.

### 1. Stream the Plugin ZIP to Remote Persistent Storage
Run this Python command from the local workspace root:
```powershell
python -c "import subprocess; subprocess.run(['ssh', '-o', 'StrictHostKeyChecking=no', '-i', r'C:\Users\Greg\.ssh\id_ed25519_wpe', 'varnerequipdev@varnerequipdev.ssh.wpengine.net', 'cat > /sites/varnerequipdev/varner-os-plugin-v23.zip'], stdin=open('varner-os-plugin-v23.zip', 'rb'))"
```

### 2. Install/Update via Remote WP-CLI
Connect to the server and trigger the installation pointing to the persistent ZIP path:
```powershell
ssh -o StrictHostKeyChecking=no -i C:\Users\Greg\.ssh\id_ed25519_wpe varnerequipdev@varnerequipdev.ssh.wpengine.net "wp plugin install /sites/varnerequipdev/varner-os-plugin-v23.zip --force --path=/sites/varnerequipdev"
```

### 3. Clean up the Remote ZIP
```powershell
ssh -o StrictHostKeyChecking=no -i C:\Users\Greg\.ssh\id_ed25519_wpe varnerequipdev@varnerequipdev.ssh.wpengine.net "rm /sites/varnerequipdev/varner-os-plugin-v23.zip"
```

### 4. Activate & Run dbDelta
If the plugin is not active, run `wp plugin activate varner-os-plugin-v23 --path=/sites/varnerequipdev`. Deactivate and reactivate once to run `dbDelta` and schedule the daily session cleanup cron.

## Theme Install or Update

1. Stream the theme ZIP in the same manner as the plugin ZIP (using `/sites/varnerequipdev/varner-equipment-theme-v23-lite.zip`).
   * **Note**: WordPress CLI expects the ZIP filename to match the theme slug folder name inside it (`varner-lite/`). Therefore, you must rename the uploaded zip to `varner-lite.zip` before running the install command.
2. Rename and run:
   ```powershell
   ssh -o StrictHostKeyChecking=no -i C:\Users\Greg\.ssh\id_ed25519_wpe varnerequipdev@varnerequipdev.ssh.wpengine.net "mv /sites/varnerequipdev/varner-equipment-theme-v23-lite.zip /sites/varnerequipdev/varner-lite.zip && wp theme install /sites/varnerequipdev/varner-lite.zip --force --path=/sites/varnerequipdev"
   ```
3. Confirm `style.css` is at the theme root.
4. Clean up the theme ZIP on the remote server:
   ```powershell
   ssh -o StrictHostKeyChecking=no -i C:\Users\Greg\.ssh\id_ed25519_wpe varnerequipdev@varnerequipdev.ssh.wpengine.net "rm -f /sites/varnerequipdev/varner-lite.zip"
   ```

## Post-Install Checks

1. Clear app/server/CDN caches.
2. Verify header logo renders.
   - Preferred source: media attachment titled `VarnerEquipment_red`.
   - Fallback source: bundled theme logo assets.

## REST Endpoints

See the plugin `readme.txt` or [`SKILL.md`](./SKILL.md) §9 for the complete REST API reference.

## Quick Verification in wp-admin

1. Open `Varner OS > Configuration`.
2. Confirm the React app mounts.
3. Confirm fallback server-rendered table shows recent sessions (last 25).
4. Confirm inventory create/update/delete/restore actions write ledger entries to `wp_varner_inventory_ledger`.

## Mobile Companion (PWA) & Audit Log Notes

1. **Access Link**: Dynamically intercepting `/mobile-app/` route loads the mobile-optimized, standalone HTML console.
2. **Dynamic QR Generation**: QR Code inside the desktop `Mobile Companion` tab points to `https://[your-domain]/mobile-app/?token=[TOKEN]`.
3. **Session Audit Logging**: 
   * When a device authenticates using a token, its session details are logged to `wp_varner_user_sessions`.
   * Managers can track **Who** logged in (associated WP user), **When** (precise timestamp and rolling 30-minute session status), and **Where** (client IP address and device User-Agent header, e.g. "Safari on iPhone").
   * Changes made via the phone are written to the append-only table `wp_varner_inventory_ledger` detailing the creator, action, and fields changed.
4. **PWA Manifest & Service Worker**: Dynamic virtual endpoints served via `/manifest.json` and `/sw.js` trigger app installation on iOS/Android and cache files.
