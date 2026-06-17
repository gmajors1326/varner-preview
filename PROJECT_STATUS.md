# Varner OS — Project Notes

**Project:** Varner Equipment — custom inventory website, mobile companion (PWA), and Facebook catalog automation
**Purpose of this document:** Single reference for project status, version history, key decisions, the deployment process, and outstanding work.
**Last updated:** June 17, 2026
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
| Development | WP Engine — `varnerequipdev` | All work to date. Accessed via WP-CLI over SSH. |
| Production | _[TBD]_ | Not yet provisioned / cut over. See §7 Open Items. |

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
| `main` | `1.23.178` | Cumulative updates, Pages API hardening, responsive grid improvements, and documentation audit sweep: Pages REST API (with server-side protected page guards, meta-caps, and validation), safety confirm dialogs, trailer length dropdown, Zetor/Titan brand sync, Tailwind config safelists, ZIP packaging script robustness, responsive grid layout optimization for service/parts requests on tablet/laptop viewports, and swept documentation files for legacy paths, REST capabilities, and audit ledger log contradictions. |
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

---

## 6. Deployment Runbook (Dev)

> Sequence-strict. Each phase gates the next. All commands run from the local machine against WP Engine dev.

**Phase 0 — Pre-flight & commit (local)**
1. Confirm rollback backups exist on the server and are **test-restorable** (not just present).
2. Commit the working tree locally (do not push yet) so the deployed artifact maps to a real commit.

**Phase 1 — Build & version gate (local)**
1. Run `.\build.ps1`.
2. **Gate (must pass):** version bumped in **both** the plugin header and theme `style.css`.
   * **React Assets Note**: `dist/assets/` contains new content-hashed filenames only if React files were modified. For PHP-only changes, the hashes remain unchanged; do not stop the deployment in this case, as the plugin version bump handles the necessary cache-busting. Otherwise, if React changed and hashes did not, **STOP**.

**Phase 2 — Sequential deploy & cache flush (remote)**
1. Stream ZIP to server.
2. Integrity check: `ls -l <zip> && unzip -t <zip> >/dev/null && echo OK`.
3. Install with `--force`.
4. Confirm exact slug + version via `wp plugin list --name=... --fields=name,version`.
5. Repeat for theme (only after plugin succeeds; theme overwrite is active-theme, so backups must be confirmed first).
6. Flush **all three layers**: object cache (`wp cache flush`), page/CDN cache (`wp page-cache flush` or WP Engine portal "Clear all caches").

**Phase 3 — Validation** (see §8 for the matrix). Run security/scope checks against **origin** (post-flush), confirm performance, admin UI, and PWA.

**Phase 4 — Resolution**
- Pass → `git push`.
- Fail → restore from backup ZIPs, **re-flush all caches** (so you debug origin code, not cached new-code responses), `git reset --soft HEAD~1` to keep changes for debugging.

**Failure tiers (what triggers a rollback):**
- **Rollback-grade:** security leak, admin won't mount, front-end white-screen, performance regression.
- **Fix-forward:** cosmetic UI, stale icon.

**Packaging note:** `build.ps1` uses Python's `zipfile` module (and `tools/zip_helper.py` for the plugin) to create archives with proper forward-slash Unix paths. Do NOT use PowerShell's `tar -a -c -f` (which makes POSIX TAR archives) or `Compress-Archive` because WordPress rejects them. Confirm extracted trees have real nested directories.

---

## 7. Open Operational Items

These are **not** code-review items; they gate production go-live.

1. **SMTP / email deliverability — BLOCKING.**
   `wp_mail` has no working delivery path by default on WP Engine. Customer **forms** (contact/service/parts/employment) and any **email login** depend on it. Without it, customer leads silently vanish.
   - **Dev/testing:** FluentSMTP → a real service (Gmail app-password, or a transactional free tier such as Brevo/Mailgun) so `wp_mail` delivers and flows can be tested.
   - **Production:** send from the dealership domain (`@varnerequipment.com`) with **SPF + DKIM** configured. Confirm whether that domain is on Google Workspace or another provider — it determines the setup.
   - **Test before relying on it:** send a test email and verify arrival; don't assume "gmail-to-gmail worked in dev" proves production deliverability.

2. **Magic-link / Magic Login Pro.**
   Decision is to use the Magic Login Pro plugin rather than the custom build. Before it's usable: SMTP must be live; configure post-login redirect to `/mobile-app/`; restrict to staff roles; verify mobile behavior and email-scanner pre-fetch handling. Keep a non-email break-glass login path so a mail outage is never a total lockout.

3. **Sandhills / TractorHouse migration.**
   The dealer is handling the export. **Do not cancel Sandhills until data is pulled.** Critical: export the actual **photo files**, not links — equipment photos often live on Sandhills' servers and go dark on cancellation. Confirm export format (spreadsheet vs. login-only) and whether photos download as files. Re-host all images in the WordPress media library.

4. **Form email routing.**
   Decide recipient(s) for each form type (contact / service / parts / employment) — single inbox or split by department. Currently routes to one address; confirm with the owner.

5. **Team page content.**
   Awaiting staff headshots and short bios (Ashley, Devin, and team) for the "Our Team" page.

6. **Production deployment.**
   All work to date is on dev. Go-live is its own checklist: provisioning, real staff accounts, domain email, SPF/DKIM, migrated inventory, and DNS cutover.

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
| Meta Live Sync | ✅ Resolved (Commerce Manager verification pending live URL setup) |
| Magic-link feature | ⏸ Parked (`feature/magic-link`, `1.23.151`); superseded by Magic Login Pro plan |
| Pages Management REST API & panel | ✅ Resolved (`1.23.177`) |
| Destructive Action confirmation dialogs | ✅ Resolved (`1.23.177`) |
| Mobile token input & neutral prompt | ✅ Resolved (`1.23.177`) |
| SMTP / email deliverability | ⛔ Not set up — blocks go-live |
| Sandhills migration | ⏳ Pending (dealer-led; do not cancel until data pulled) |
| Form email routing | ⏳ Pending owner decision |
| Team page content | ⏳ Awaiting photos + bios |
| Production cutover | ⏳ Not started |

---

_End of document._
