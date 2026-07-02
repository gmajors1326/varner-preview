# Varner OS — Project Notes

**Project:** Varner Equipment — custom inventory website, mobile companion (PWA), and Facebook catalog feed (automated) / Marketplace listings (manual)
**Purpose of this document:** Single reference for project status, version history, key decisions, the deployment process, and outstanding work.
**Last updated:** July 1, 2026
**Maintainer:** Greg

---

## 1. Overview

Varner OS is a custom WordPress build that replaces a third-party inventory platform (Sandhills / TractorHouse) for Varner Equipment. It consists of two packages plus a build pipeline:

| Component | Package | Role |
| :--- | :--- | :--- |
| Theme | `varner-lite` | Public website (listings, single-unit pages, contact/service/parts/employment forms) |
| Plugin | `varner-os-plugin-v23` | Backend inventory system, REST API, admin React app, mobile PWA companion, Facebook catalog feed |
| Build | `build.ps1` | Compiles the React app (Vite/Tailwind), bumps version, packages both ZIPs |

**Design principle:** the dealership owns its own data. Inventory lives in WordPress (custom post type + custom tables), media is re-hosted in the WordPress media library (never hot-linked to a third party), and nothing is tied to a page builder — so the site stays portable and the dealer is never a hostage to a vendor.

---

## 2. Environments

| Environment | Host | Notes |
| :--- | :--- | :--- |
| Development / Production | WP Engine — `varnerequipdev` | Single WP Engine install serves both roles. Point the real domain (e.g. `varnerequipment.com`) at this install for go-live. |

Deployment to dev is performed with WP-CLI over SSH (stream ZIP → `wp plugin/theme install --force` → verify version → flush caches). See §6.

---

## 3. Version & Branch Map

> **Important:** version numbers overlap in range across branches but represent **different feature sets**. Always check the branch, not just the number.

| Branch | Version | Feature set |
| :--- | :--- | :--- |
| `main` | `1.23.148` | Pre-magic-link state, redeployed. Standard WordPress-login redirect on `/mobile-app/`. |
| `main` | `1.23.149` | `1.23.148` + author read-scope hardening (Split Option). Deployed and verified. |
| `main` | `1.23.150` | `1.23.149` + Meta Live Sync fixes (200 OK headers, synchronous writes, delete hook, stale root file cleanup). Deployed and verified. |
| `main` | `1.23.151` | `1.23.150` + Meta Live Sync bulk debouncing (O(1) loop writes) and Varnish cache bypass (`DONOTCACHEPAGE`). Deployed and verified. |
| `main` | `1.23.154` | `1.23.151` + options-based lock and dirty flags (non-autoloaded) + init sweep thundering herd guard + WP All Import complete hook. Deployed and E2E verified. |
| `main` | `1.23.160` | Finance cards editor in Settings tab, `file_exists()` asset resolution, ZIP packaging fix (`zip_helper.py` replaces `tar`), sidebar nav restructuring, handleClone bug fix. Deployed. |
| `main` | `1.23.163` | Tailwind CDN removed → locally compiled `tailwind.css` with `filemtime()` versioning. Tesseract SRI integrity + crossorigin added. |
| `main` | `1.23.164` | Build bump after SRI + Tailwind swap. ZIPs rebuilt. |
| `main` | `1.23.178` | Cumulative updates, Pages API hardening, responsive grid improvements, and documentation audit sweep: Pages REST API (with server-side protected page guards, meta-caps, and validation), safety confirm dialogs, trailer length dropdown, Zetor/Titan brand sync, Tailwind config safelists, ZIP packaging script robustness, responsive grid layout optimization for service/parts requests on tablet/laptop viewports. |
| `main` | `1.23.181` | iPad Landscape Responsiveness Fix: resolved overlap between hero buttons and quick search bar by adjusting hero section height, container padding-bottom, and responsive title font sizes. |
| `main` | `1.23.221` | Current head. PWA redesign (Midnight V2.4 theme, VIN plate scanner, outline clone cards), PWA login flow improvements, trailer length dropdown, page editor upgrades with safety confirm dialogs, responsive form grid fixes. |
| `feature/magic-link` | `1.23.151` | Passwordless email magic-link authentication. Fully built, reviewed, and parked on `feature/magic-link` branch. **Not live.** |

**Magic-link work is parked, not deleted.** The `feature/magic-link` branch holds the fully assembled source files **and** the built `1.23.151` ZIP. Before relying on it as a restore point, confirm it rebuilds cleanly to a working `1.23.151` (see §7).

---

## 4. Security Remediation — Completed

A senior-level security review was run across the theme and plugin. Findings and resolutions:

### 4.1 Public inventory data leak — **RESOLVED**
- **Issue:** the unauthenticated `GET /varner/v1/inventory` endpoint returned the full unit object — including internal fields (`vin`, `seller_info`, `stock_number`, `intake_date`) and staff-name tracking fields — to anyone on the internet.
- **Fix (Phase 1):** introduced a **schema-driven, default-deny field config** as the single source of truth. `varner_format_unit()` now takes a `$context` (`'edit'` vs `'public'`); the public path emits only fields explicitly flagged `public => true`. Function default is `'edit'` (full payload) so internal callers and the audit ledger are never silently stripped; only the public list path downgrades, and only when `!current_user_can('edit_posts')`. `call_for_price` strips the numeric price while keeping the boolean.

### 4.2 Performance / N+1 — **RESOLVED**
- **Issue:** unpaginated inventory list incurred per-attachment query fan-out and ACF formatting overhead.
- **Fix (Phase 2):** hybrid meta reads (bulk `get_post_meta` for plain fields with `array_key_exists` default handling; `get_field()` retained for `date_picker`, `image`, `wysiwyg`, `gallery`, and the `implements` repeater to avoid silent data loss), plus `_prime_post_caches()` on all attachment IDs (gallery + implement images + thumbnails) before serialization. Verified byte-identical output via golden-snapshot comparison.

### 4.3 Carried-over fixes — **RESOLVED**
- Per-post capability checks (`edit_post` / `delete_post`) on all inventory **write** endpoints.
- `is_array($data)` guards returning clean 400s on malformed JSON bodies.
- Form-notification fallback recipient changed from a hardcoded personal Gmail to `get_option('admin_email')`.
- Facebook catalog file relocated from web root to `wp_upload_dir()`.

### 4.4 Author read-scope tightening — **RESOLVED (`1.23.149`)**
- **Issue:** authenticated **read** endpoints gate on `edit_posts`, which the `author` role holds — so an author could read internal fields, trashed units, and audit history. (Write hardening in 4.3 did not cover reads.)
- **Decision — Split Option:**
  - **Settings & meta-sync** → `manage_options` (admin only).
  - **Per-unit ledger & inventory reads** (single / deleted / draft-list context) → `edit_others_posts` (editor + admin, blocks author).
- See §5 for the rationale and §8 for the verification matrix.

---

## 5. Decision Log

| Decision | Choice | Rationale |
| :--- | :--- | :--- |
| Leak fix approach | Schema-driven default-deny, not spot-patch | One source of truth; new fields are private unless explicitly published, so leaks can't regress. |
| Formatter default context | `'edit'` (full) | A forgotten context arg preserves admin/ledger payloads; only the single public path downgrades. |
| Repeater handling under N+1 fix | Keep `implements` on `get_field()` | Raw `get_post_meta` returns a row-count string for repeaters → silent data loss. |
| Author read-scope | Split: settings/meta-sync admin-only; ledger/inventory editor-allowed | Configuration writes reserved to owner; read-only unit history kept available to trusted staff. |
| Passwordless login | Use **Magic Login Pro** plugin; park the custom build | Offloads auth maintenance/patching; the restored `/mobile-app/` WP-login redirect carries a logged-in WP user into the OS, so no custom endpoints needed. Custom branch retained as fallback. |
| Email "from" identity | Production must send from the dealership domain, not personal Gmail | Personal `@gmail.com` can't be SPF/DKIM-authenticated; mail gets spam-filtered/rejected. |
| Category taxonomy SSOT (cleanup #13) | Deferred — not a refactor | Categories diverge across React `CATEGORY_TREE` (hierarchical) and the legacy flat ACF `category` field + theme SEO landing-page filters; they share no literal and span JS+PHP. A true single source would rewrite live `category` postmeta and SEO filters — a behavior change + data migration, out of scope for the cleanup phases. Grouped with the parked ACF dynamic-dropdown item. |
| Brands SSOT (cleanup #12) | ✅ Resolved — `varner_default_brands()` in `varner-backend.php` | ACF `make` field, REST API fallback, and theme nav all call the same function. `header.php` guards with `function_exists` + minimal 5-brand emergency fallback. The one remaining divergence (static ACF dropdown not reading `varner_brands` from DB) is a behavior change, out of scope. |

---

## 6. Deployment

See [`DEPLOY.md`](./DEPLOY.md) for the canonical deployment runbook (build, stream, install, cache flush, failure tiers).

Key references:
- Build: `.\build.ps1` (produces `varner-os-plugin-v23.zip` + `varner-equipment-theme-v23-lite.zip`)
- Version gate: confirm bump in both plugin header and theme `style.css`
- The verification matrix in §8 below applies post-deploy

---

## 7. Open Items

### 7.1 Production Go-Live
Production is the same WP Engine install — no separate provision needed. At go-live, point the real domain at the WP Engine install and update the site URL in WordPress settings.

### 7.2 Magic-Link ZIP Verification
Before relying on the `feature/magic-link` branch as a restore point, confirm it rebuilds cleanly to a working `1.23.151` ZIP. The branch holds the assembled source and a previously built ZIP, but a clean rebuild has not been verified since the Tailwind/SRI build pipeline changes.

### 7.3 Meta Live Sync — Final Verification
Meta Live Sync fixes are deployed and verified. Commerce Manager verification can proceed once the real domain is pointed at the install.

### 7.4 SMTP / Email Setup
✅ Configured. WP Mail SMTP plugin active with Brevo (Sendinblue) API key, sending from `web@varnerequipment.com`.

### 7.5 Cleanup — Unreferenced Assets
- Root `Images/` folder contains ~40 unused inventory mockup photos (keep the 12 brand logo PNGs).
- Root `public/` folder contains 6 unreferenced files: `imp1.jpg`, `imp2.jpg`, `imp3.jpg`, `left.jpg`, `mahindra.jpg`, `rear.jpg`.
- `varner_register_video_cpt()` in `varner-lite/functions.php` may duplicate Video CPT registration managed by the plugin.
- `varner_render_breadcrumbs()` may be duplicated between `functions.php` and `partials/breadcrumb.php`.

### 7.6 Future Improvements
- Split `src/App.jsx` (~1,300 lines) into smaller components.
- Implement frontend pagination in the React app for catalogs of 200+ units.
- Marketplace tab is informational only (shows feed URL, health metrics, sync logs). Catalog updates are fully automated; Marketplace free listings remain human-in-the-loop via the Quick Post tool and are not automatable through Meta's API for equipment dealers.
- Build a polished public-facing filter UI for the `[varner_showroom]` shortcode.

---

## 8. Verification Reference

### 8.1 Author read-scope matrix (target for `1.23.149`)

| Endpoint / Action | Guest | Author | Editor | Admin |
| :--- | :--- | :--- | :--- | :--- |
| `GET /inventory` (public units) | `200` (public fields) | `200` (public fields) | `200` (all fields + drafts) | `200` (all fields + drafts) |
| `GET /inventory/deleted` | `401/403` | `403` | `200` | `200` |
| `GET /inventory/{id}` | `401/403` | `403` | `200` | `200` |
| `GET /inventory/{id}/ledger` | `401/403` | `403` | `200` | `200` |
| `GET/POST /settings` | `401/403` | `403` | `403` | `200` |
| `POST /settings/preview` | `401/403` | `403` | `403` | `200` |
| `GET /meta-sync/logs` | `401/403` | `403` | `403` | `200` |
| `GET /meta-sync/health` | `401/403` | `403` | `403` | `200` |

### 8.2 Implementation checks (`1.23.149`)
- All **three** `current_user_can('edit_posts')` checks inside `varner_api_get_inventory` flip to `edit_others_posts` (post_status filter, `show_on_website` filter, and the `format_unit` context). After editing, confirm **zero** `edit_posts` references remain inside that function.
- On `/inventory/{id}`: only the **GET** block changes to `edit_others_posts`; **PATCH** and **DELETE** stay on the shared `$auth` (`edit_posts`) — do not tighten writes.
- Do **not** modify the shared `$auth` closure (reused across write/media/brands/categories/videos routes).
- Public `GET /inventory` route stays `__return_true`; only the in-handler context decision changes.
- Match on code strings, not line numbers — line numbers differ from the reviewed `1.23.151` copy.

### 8.3 Post-revert login sanity (`main` / `1.23.148`)
- Logged out → visiting `/mobile-app/` redirects to `/wp-login.php?redirect_to=...`.
- Logged in → `/mobile-app/` renders the OS dashboard directly.
- Test in a private window with a hard refresh (bypass the service-worker cache).

---

## 9. Quick Status Summary

| Item | Status |
| :--- | :--- |
| Public inventory leak | ✅ Resolved |
| N+1 / performance | ✅ Resolved |
| Write-path capability hardening | ✅ Resolved |
| Author read-scope hardening | ✅ Resolved (`1.23.149`) |
| Meta Live Sync | ✅ Resolved |
| Magic-link feature | ⏸ Parked (`feature/magic-link`, `1.23.151`); superseded by Magic Login Pro plan |
| SMTP / Email sending | ✅ Resolved |
| Destructive Action confirmation dialogs | ✅ Resolved (`1.23.178`) |
| Mobile token input & neutral prompt | ✅ Resolved (`1.23.178`) |

---

_End of document._
