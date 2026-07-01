# Varner Equipment — Workspace Notes & Intelligence
*Last updated: July 1, 2026*

---

## Workspace Rules

> [!IMPORTANT]
> - **Do not add** code, functions, text, or images without approval.
> - **Do not delete** anything without approval.
> - **Make suggestions** for improvements — don't act on them unilaterally.
> - **Use `build.ps1` or Python's `zipfile` module (via `tools/zip_helper.py`)** to zip files. Never use PowerShell's `tar -a -c -f` (which creates POSIX TAR archives) or `Compress-Archive` because WordPress rejects them.
> - **Keep `readme.txt` version in sync** with the plugin header and `style.css` after every version bump. The root `readme.txt` is a separate file from the one inside the plugin ZIP — both must reflect the current version.

### SSH / Deployment Rules

> [!IMPORTANT]
> **For binary files (ZIPs):** Two reliable methods:
> 1. Native PowerShell `<` piping (used in `DEPLOY.md`):
>    ```powershell
>    ssh -i ~/.ssh/id_ed25519_wpe user@host "cat > /sites/path/file.zip" < file.zip
>    ```
> 2. Python subprocess stdin:
>    ```powershell
>    python -c "import subprocess; subprocess.run(['ssh', '-o', 'StrictHostKeyChecking=no', '-i', r'C:\Users\Greg\.ssh\id_ed25519_wpe', 'varnerequipdev@varnerequipdev.ssh.wpengine.net', 'cat > /sites/varnerequipdev/file.zip'], stdin=open('file.zip', 'rb'))"
>    ```
> **For text files (PHP, etc.):** Use the PowerShell pipeline method:
> ```powershell
> Get-Content "local\path\file.php" -Raw -Encoding UTF8 | ssh -o StrictHostKeyChecking=no -i C:\Users\Greg\.ssh\id_ed25519_wpe varnerequipdev@varnerequipdev.ssh.wpengine.net "cat > /sites/varnerequipdev/wp-content/themes/varner-lite/file.php"
> ```
> **WP Engine SSH gateway uses ephemeral containers.** Only `/sites/varnerequipdev/` is persistent. Files written to `/home/wpe-user` or `/tmp` are destroyed when the connection closes.

---

## Architecture

See [`SKILL.md`](./SKILL.md) for the complete architecture reference (component roles, data model, API auth, SQL cheat sheet). See [`DEPLOY.md`](./DEPLOY.md) for the canonical build & deploy runbook.

Notable:
- `varner-lite` is the sole master theme; `varner-equipment-theme-v23` is archived in `_archive/`.
- `build.ps1` auto-increments the plugin patch version (e.g. `1.23.4 → 1.23.5`).
- After any bulk import, run: `varner_os_schedule_catalog_regeneration(true);`
- `src/App.jsx` (1,302 lines) is functional but could be split into smaller components.

---

## Known Issues & Bugs Fixed

| Date | Issue | Fix |
|---|---|---|
| Jun 17, 2026 | Form grid responsiveness | Redesigned the equipment details grid on service and parts request pages to use `grid-cols-1 sm:grid-cols-2 lg:grid-cols-5` to prevent squished inputs on tablet/laptop viewports. Files: `page-service-request.php`, `page-parts-request.php`. |
| Jun 17, 2026 | PWA login length & cold launch | Set secure access token `maxLength` to 32 characters in mobile login form. Replaced prominent red session expired error on initial cold launch with a neutral slate instruction banner. File: `MobileAppLayout.jsx`. |
| Jun 17, 2026 | Trailer Length conditional select | Added conditional rendering so trailer length only displays for trailer categories, replacing raw text input with standardized 8–53 ft dropdown select. File: `UnitEditorPanel.jsx`. |
| Jun 17, 2026 | Page Editor settings panel upgrades | Added Page Editor quick-jump sticky sub-navigation, page deletion safety confirmation dialogs, and a WordPress Pages management REST API and UI list. Files: `SettingsTab.jsx`, `rest-api.php`, `App.jsx`, `MobileAppLayout.jsx`. |
| Jun 12, 2026 | PWA Redesign (Midnight V2.4) | Visual skinning for Midnight/Sunlight togglable theme, centered Varner logo PNG, 2-tab bottom nav, slide-out hamburger drawer, 100% database-backed VIN plate scanner camera, browser-based Tesseract.js OCR text pre-fill, and outline clone cards. Files: `MobileAppLayout.jsx`, `App.jsx`, `helpers.js`, `varner-backend.php`. |
| Jun 9, 2026 | `f.map is not a function` — inventory failed to load | `apiFetch('/inventory/deleted')` failure inside `Promise.all` killed the entire load. Fixed by splitting the calls and adding defensive `Array.isArray()` checks. |
| — | Stale nonce after long WP session | `apiFetch` throws `Request failed: 401`. User must refresh the page to get a new nonce. No code fix — WP behavior. |
| Jun 9, 2026 | Login button styling | Changed button from blue to red (`#dc2626` / `#b91c1c` hover), added `margin-top: 50px` to space it below Remember Me. File: `varner-os-plugin-v23.php` branded login CSS. |
| Jun 9, 2026 | Login branding changes | Renamed "Varner OS" → "Varner Equipment OS" in heading + logo title. Added `#login h1 {text-align:center}` for centering. File: `varner-os-plugin-v23.php` login_enqueue_scripts. |
| Jun 9, 2026 | PWA mobile safe area | Added `body { padding: env(safe-area-inset-*) }` to push content below status bar (time/battery) on mobile PWA standalone mode. File: `varner-os-plugin-v23.php` mobile-app HTML shell. |
| Jun 9, 2026 | PWA iPhone scrolling + button | Changed `h-screen` → `h-dvh` (fixes iOS 100vh bottom toolbar overlap), increased `pb-24` → `pb-28` for nav clearance, made "PUBLISH NEW UNIT" button larger (`py-6`, `text-sm`, 22px icon, `gap-3`, `mt-6`). File: `MobileAppLayout.jsx`. |

---

## Open Items (Awaiting Approval)

### 🟡 Cleanup — Unused Images
The following directories contain unreferenced files that are safe to delete once approved:

**Root `Images/` folder** (~40 unused mockup images):
- All `.webp` / `.jpg` inventory mockup photos (car haulers, tractors, implements, etc.)
- **Keep**: The 12 brand logo PNGs (`BigTex_white.png`, `Mahindra_white.png`, etc.)

**Root `public/` folder** (6 files — all unused):
- `imp1.jpg`, `imp2.jpg`, `imp3.jpg`, `left.jpg`, `mahindra.jpg`, `rear.jpg`
- These are not referenced by Vite, React, or any PHP template.

### 🟡 Cleanup — Redundant Code in functions.php
- `varner_register_video_cpt()` exists in `varner-lite/functions.php`. If Video CPT is fully managed by the plugin, this is a duplicate and should be removed from the theme.
- `varner_render_breadcrumbs()` may be duplicated between `functions.php` and `partials/breadcrumb.php` — worth auditing before next theme release.

### 🟢 Low Priority — Future Improvements
- **Split `src/App.jsx`**: At 1,302 lines it handles too much. Extracting the inventory editor, list view, and modal logic into separate components would improve maintainability.
- **Inventory pagination**: The React app loads all equipment at once. For large catalogs (200+ units) this could get slow. The REST API already supports pagination — the frontend just needs to opt in.
- **Facebook Marketplace live sync**: The Marketplace tab UI is complete but the actual Meta API integration is not wired. Currently informational only.
- **Public showroom filter UX**: The `[varner_showroom]` shortcode renders the React app publicly, but there's no polished public-facing filter UI yet — it shows the full admin inventory list.

---

## Quick Reference

### Brands — Single Source of Truth (SSOT)
Brands are defined in one place: `varner_default_brands()` in `varner-backend.php`.
- ACF `make` field uses `array_combine( varner_default_brands(), ... )`
- REST API fallback calls `varner_default_brands()` directly
- Theme nav fallback calls it via `function_exists` guard
- Add/remove brands only in `varner_default_brands()` — the rest follows.
- DB option `varner_brands` overrides the default at runtime.

### Inventory Taxonomy (3 levels, stored in wp_postmeta)
```
Category → Subcategory → Sub-Subcategory
e.g. Tractors → Compact Tractors → 4WD
```
Category tree constants live in `src/constants/inventoryConstants.js`.

### Useful WP-CLI / SQL
```sql
-- Verify category distribution
SELECT meta_value, COUNT(*) FROM wp_postmeta WHERE meta_key = 'category' GROUP BY meta_value;

-- Find units missing a category
SELECT post_id FROM wp_postmeta WHERE meta_key = 'category' AND meta_value = '';

-- Reset brand transient cache
DELETE FROM wp_options WHERE option_name LIKE '_transient_varner_brand_counts%';

-- View active mobile tokens
SELECT option_name FROM wp_options WHERE option_name LIKE '_transient_varner_mobile_token_%';

-- View recent inventory ledger entries
SELECT post_id, action, display_name, summary, created_at FROM wp_varner_inventory_ledger ORDER BY created_at DESC LIMIT 20;
```

### WP Engine SSH
```
Host:     varnerequipdev.ssh.wpengine.net
User:     varnerequipdev
Key:      C:\Users\Greg\.ssh\id_ed25519_wpe
Site dir: /sites/varnerequipdev
WP path:  /sites/varnerequipdev (used with --path for WP-CLI)
```
