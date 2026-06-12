# Varner Equipment — Workspace Notes & Intelligence
*Last updated: June 12, 2026*

---

## Workspace Rules

> [!IMPORTANT]
> - **Do not add** code, functions, text, or images without approval.
> - **Do not delete** anything without approval.
> - **Make suggestions** for improvements — don't act on them unilaterally.
> - **Use `tar`** to zip files every time. Never use Compress-Archive or zip.
> - **Use `tar`** even for single files — consistency matters for WP Engine compatibility.

### SSH / Deployment Rules

> [!IMPORTANT]
> **For binary files (ZIPs):** Stream via Python subprocess stdin — this is the only reliable method for WP Engine.
> ```powershell
> python -c "import subprocess; subprocess.run(['ssh', '-o', 'StrictHostKeyChecking=no', '-i', r'C:\Users\Greg\.ssh\id_ed25519_wpe', 'varnerequipdev@varnerequipdev.ssh.wpengine.net', 'cat > /sites/varnerequipdev/file.zip'], stdin=open('file.zip', 'rb'))"
> ```
> **For text files (PHP, etc.):** Use the PowerShell pipeline method:
> ```powershell
> Get-Content "local\path\file.php" -Raw -Encoding UTF8 | ssh -o StrictHostKeyChecking=no -i C:\Users\Greg\.ssh\id_ed25519_wpe varnerequipdev@varnerequipdev.ssh.wpengine.net "cat > /sites/varnerequipdev/wp-content/themes/varner-lite/file.php"
> ```
> **WP Engine SSH gateway uses ephemeral containers.** Only `/sites/varnerequipdev/` is persistent. Files written to `/home/wpe-user` or `/tmp` are destroyed when the connection closes.

---

## Project Structure (Current — June 2026)

```
Varner Equipment/
├── src/                          ← React app source (Varner OS)
├── dist/                         ← Compiled React build output
├── varner-os-plugin-v23-unpacked/ ← Plugin source (PHP + dist goes here)
├── varner-equipment-theme-lite/  ← ✅ ACTIVE MASTER THEME
│   └── varner-lite/
│       ├── src/input.css         ← Tailwind CSS source
│       ├── tailwind.config.js    ← Tailwind config
│       ├── assets/css/tailwind.css ← Compiled Tailwind output
│       ├── partials/             ← breadcrumb.php, equipment-card.php, inventory-sidebar.php
│       └── *.php                 ← All theme templates
├── _archive/                     ← Archived (not active)
│   ├── varner-equipment-theme-v23/  ← Retired June 2026
│   └── varner-equipment-theme-v23.zip
├── build.ps1                     ← Unified build script (React + Tailwind + ZIPs)
├── DEPLOY.md                     ← Deployment runbook (canonical)
├── SKILL.md                      ← Technical architecture reference
├── └── notes.md                      ← This file
```

### Deployment Artifacts (produced by build.ps1)
| Artifact | Purpose |
|---|---|
| `varner-os-plugin-v23.zip` | Plugin — upload to WP Engine |
| `varner-equipment-theme-v23-lite.zip` | Theme — upload to WP Engine |

---

## Architecture Intelligence

### Theme: varner-lite is the sole master
- `varner-equipment-theme-v23` was **retired June 9, 2026** and moved to `_archive/`.
- **All future theme edits go into `varner-equipment-theme-lite/varner-lite/` only.**
- `build.ps1` compiles Tailwind directly inside varner-lite and produces one theme ZIP.
- The `varner-lite` slug is the active WordPress theme on WP Engine.
- 
### Plugin: varner-os-plugin-v23
- The plugin is the **bridge** between the React app and WordPress.
- It handles: Equipment CPT registration, REST API (`/varner/v1`), ACF field sync, asset loading, session tracking, and mobile PWA routing.
- **Every `build.ps1` run auto-increments the patch version** (e.g. `1.23.4 → 1.23.5`). This forces WP to prompt for an overwrite on install and busts CDN cache.
- The plugin must be uploaded as a ZIP and installed via `wp plugin install --force`. Do not use the WP admin uploader — it has size limits.

### React App (Varner OS)
- Built with **Vite + React + Tailwind**. Entry: `src/main.jsx`, root component: `src/App.jsx`.
- Mounts on `#varner-inventory-app` / `.varner-inventory-app-mount` — present in WP admin pages and the `[varner_showroom]` shortcode.
- API calls go through `src/utils/api.js` → `apiFetch()`. The base URL is `window.varnerData.rest_url + '/varner/v1'`. The nonce is injected via `wp_localize_script`.
- `src/App.jsx` is large (1,302 lines). It's functional but could be split into smaller component files over time.

### REST API Authentication
- **Admin sessions**: Standard WP nonce (`X-WP-Nonce` header). Nonces expire after 12–24 hours — a stale nonce is the most common cause of `Failed to load inventory` errors.
- **Mobile sessions**: 16-char hex token passed as `X-Varner-Mobile-Token` header. Tokens expire after 30 minutes of inactivity. Max 3 active tokens per user.
- **Public**: `GET /inventory` is public (filtered by `show_on_website` for non-editors). All write operations require `edit_posts` capability.

---

## Known Issues & Bugs Fixed

| Date | Issue | Fix |
|---|---|---|
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

### Brands — Three Places to Keep in Sync
When adding or removing an authorized brand, update **all three**:
1. `header.php` — Mega Menu brand grid
2. `rest-api.php` — `varner_api_get_brands()` default array
3. `varner-backend.php` — ACF field choices array

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
