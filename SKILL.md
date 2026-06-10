# Varner Equipment OS & Theme - Master Skill

This document serves as the comprehensive technical manual for the Varner Equipment digital ecosystem, including the React-powered inventory management system (Varner OS) and the WordPress theme.

---

## 1. Architecture Overview
The project is split into three main components that work in tandem:

-   **Varner OS (React Application)**: Located in the root directory. This is the front-end interface for inventory management, built with Vite, React, and Lucide icons.
-   **Varner OS Plugin (`varner-os-plugin-v23`)**: The WordPress bridge. It registers the `equipment` Custom Post Type (CPT), provides the REST API endpoints for the React app, and handles ACF field registration.
-   **Varner Equipment Theme (`varner-lite`)**: The sole master theme. Handles the public-facing website: inventory displays, native filtering, SEO-optimized category pages, and all front-end pages. `varner-equipment-theme-v23` was retired June 2026 and is archived under `_archive/`.

---

## 2. Inventory Hierarchy & Data Model
The system uses a strict 3-level taxonomy stored in `wp_postmeta`:

1.  **Category** (e.g., Attachments, Tractors, Trailers)
2.  **Sub-Category** (e.g., Farm Attachments, Buckets, Blades)
3.  **Sub-Sub-Category** (e.g., Auger, Angle, Track Channels)

### Data Consistency
-   **React Editor**: Sub-sub-category options are driven by category selection logic in `src/App.jsx`. No standalone mapping constant currently exists in the codebase — sub-sub values are handled inline per category context.
-   **Theme Queries**: Logic is centralized in `functions.php` (specifically `varner_build_inventory_query` and `varner_get_segment_seo`).
-   **Dynamic Segments**: Custom rewrite rules map `/inventory/{segment}` to `page-equipment-listing.php`. Each segment has unique SEO metadata and automatic filters defined in `varner_get_segment_seo`.

---

## 3. Inventory Filtering (Native)
The project utilizes a custom **native PHP/SQL filtering system** for high-performance filtering on all inventory listing pages.

-   **Mechanism**: Filtering is handled via `WP_Query` meta-queries constructed in `varner_build_inventory_query()`.
-   **Sidebar**: Centralized in `partials/inventory-sidebar.php`.
-   **Fields**:
    -   `inventory_search`: Keyword searching (`s`).
    -   `inventory_category`: Category checkboxes.
    -   `inventory_make`: Brand checkboxes.
    -   `inventory_condition`: New/Used toggle.
    -   `inventory_price`: Price range sliders and inputs.
    -   `inventory_year`: Year range sliders and inputs.
-   **Legacy Note**: The previous FacetWP integration and `filter-sidebar.php` have been consolidated into the current native implementation.

---

## 4. Branding & Mega Menu
Authorized brands are managed in three locations to ensure full integration:

1.  **Public UI**: `header.php` (Mega Menu grid).
2.  **REST API**: `varner-backend.php` (`varner_api_get_brands` default array).
3.  **ACF Backend**: `varner-backend.php` (Choices array in the equipment field group).

---

## 5. Development Workflows

### Editing the Inventory Editor (React)
1.  Modify code in `src/`.
2.  Run `npm run build` from the project root.
3.  **Sync to Plugin** (PowerShell):
    ```powershell
    Remove-Item '.\varner-os-plugin-v23-unpacked\varner-os-plugin-v23\dist' -Recurse -Force -ErrorAction SilentlyContinue
    Copy-Item '.\dist' '.\varner-os-plugin-v23-unpacked\varner-os-plugin-v23\' -Recurse -Force
    ```
4.  Rebuild the plugin ZIP. See `DEPLOY.md` for the exact command.

### Editing Themes
1.  Apply **all changes directly to `varner-equipment-theme-lite/varner-lite/`** — this is the sole master theme.
2.  Compile Tailwind if CSS changes were made: `build.ps1` handles this automatically, or run manually from inside `varner-lite/`: `npx tailwindcss -i ./src/input.css -o ./assets/css/tailwind.css --minify`
3.  Run `build.ps1` to package `varner-equipment-theme-v23-lite.zip`. See `DEPLOY.md` for deployment commands.

> **Note**: `varner-equipment-theme-v23` is retired. Do not edit it. It lives in `_archive/` for reference only.

---

## 6. Deployment & Packaging
> **See `DEPLOY.md` for the canonical packaging commands, ZIP structure rules, and post-install checks.** The commands below are for reference only; `DEPLOY.md` is the source of truth.

All deployment artifacts are generated as ZIP files in the root directory.

### Command Execution (PowerShell — run from project root):
```powershell
# Recommended: run the unified build script
.\build.ps1

# This script does:
# 1. npm run build  (React)
# 2. Syncs dist/ into the plugin folder
# 3. Auto-increments plugin patch version
# 4. Compiles Tailwind CSS in varner-lite/
# 5. Packages varner-os-plugin-v23.zip
# 6. Packages varner-equipment-theme-v23-lite.zip
```

---

## 7. Troubleshooting & SQL Cheat Sheet
Run these queries via **wp-admin > Tools > phpMyAdmin** or WP-CLI (`wp db query "..."`).
-   **Verify Meta Counts**: `SELECT meta_value, COUNT(*) FROM wp_postmeta WHERE meta_key = 'category' GROUP BY meta_value;`
-   **Reset Brand Transient**: `DELETE FROM wp_options WHERE option_name LIKE '_transient_varner_brand_counts%';`
-   **Identify Orphans**: Units missing a `category` can be found via: `SELECT post_id FROM wp_postmeta WHERE meta_key = 'category' AND meta_value = '';`
-   **Active Mobile Tokens**: `SELECT option_name, option_value FROM wp_options WHERE option_name LIKE '_transient_varner_mobile_token_%';`

---

## 8. Mobile Companion PWA & Token Authentication
The Mobile Companion is a fully responsive Progressive Web App that runs on any Android or iOS device.

### Authentication Design:
* Passwordless session tokens are generated via `POST /varner/v1/mobile/token` on the desktop app, which are stored as transients expiring in 30 minutes.
* Tokens are validated by mapping the `X-Varner-Mobile-Token` HTTP header in `determine_current_user` to the generating User ID.
* The REST authentication filter bypasses nonces when a valid token is provided, allowing secure stateless calls even if WordPress session cookies are cached on the device browser.

### PWA Architecture:
* Dynamically registers routes for `/mobile-app/` (HTML app shell), `/manifest.json` (configuration), and `/sw.js` (service worker caching) directly in the plugin main file.
* Integrates directly with native mobile cameras using `<input type="file" accept="image/*" capture="environment">` inside `src/App.jsx`.
* Session updates, device parameters (user agent details), and client IP addresses are automatically registered in the `wp_varner_user_sessions` auditing table.
