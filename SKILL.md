# Varner Equipment OS & Theme - Master Skill

This document serves as the comprehensive technical manual for the Varner Equipment digital ecosystem, including the React-powered inventory management system (Varner OS) and the dual-version WordPress themes.

---

## 1. Architecture Overview
The project is split into three main components that work in tandem:

-   **Varner OS (React Application)**: Located in the root directory. This is the front-end interface for inventory management, built with Vite, Tailwind CSS, and Lucide icons.
-   **Varner OS Plugin (`varner-os-plugin-v23`)**: The WordPress bridge. It registers the `equipment` Custom Post Type (CPT), provides the REST API endpoints for the React app, and handles ACF field registration.
-   **Varner Equipment Themes (v23 & Lite)**: The public-facing websites. They handle faceted search, inventory displays, and SEO-optimized category pages.

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

## 3. Faceted Search (FacetWP)
The project utilizes **FacetWP** for high-performance, AJAX-powered filtering on all inventory listing pages. **FacetWP is an installed and required plugin dependency** — the full theme's filtering will not function without it active.

-   **Mechanism**: FacetWP intercepts `WP_Query` when `facetwp => true` is present in the arguments.
-   **Facets**:
    -   `inventory_search`: Keyword searching.
    -   `inventory_category`: Hierarchical checkbox tree (source: `acf/category`).
    -   `inventory_make`: Brand checkboxes (source: `acf/make`).
    -   `inventory_condition`: New/Used toggle.
    -   `inventory_price`: Price range slider.
    -   `inventory_year`: Year dropdown.
    -   `inventory_pagination`: AJAX "Load More" pager.
-   **Legacy Note**: The manual PHP logic in `varner_get_filter_data()` and `partials/filter-sidebar.php` is deprecated but kept for fallback or Lite version stability if FacetWP is inactive.

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
    Copy-Item '.\dist\index.html' '.\varner-os-plugin-v23-unpacked\varner-os-plugin-v23\dist\index.html' -Force
    Copy-Item '.\dist\assets'     '.\varner-os-plugin-v23-unpacked\varner-os-plugin-v23\dist\assets'     -Recurse -Force
    ```
4.  Rebuild the plugin ZIP. See `DEPLOY.md` for the exact command.

### Editing Themes
1.  Apply changes to `varner-equipment-theme-v23`.
2.  **Mandatory Lite Sync**: Always sync modified PHP files (header, footer, functions, partials) to `varner-equipment-theme-lite`.
3.  **Rebuild both ZIPs**: After syncing, rebuild `varner-equipment-theme-v23.zip` and `varner-equipment-theme-v23-lite.zip`. See `DEPLOY.md` for the exact commands.

---

## 6. Deployment & Packaging
> **See `DEPLOY.md` for the canonical packaging commands, ZIP structure rules, and post-install checks.** The commands below are for reference only; `DEPLOY.md` is the source of truth.

All deployment artifacts are generated as ZIP files in the root directory.

### Command Execution (PowerShell — run from project root):
```powershell
# Build React
npm run build

# Sync Plugin Assets
Copy-Item '.\dist\index.html' '.\varner-os-plugin-v23-unpacked\varner-os-plugin-v23\dist\index.html' -Force
Copy-Item '.\dist\assets'     '.\varner-os-plugin-v23-unpacked\varner-os-plugin-v23\dist\assets'     -Recurse -Force

# Package Everything
if (Test-Path '.\varner-equipment-theme-v23.zip')      { Remove-Item '.\varner-equipment-theme-v23.zip'      -Force }
if (Test-Path '.\varner-equipment-theme-v23-lite.zip') { Remove-Item '.\varner-equipment-theme-v23-lite.zip' -Force }
if (Test-Path '.\varner-os-plugin-v23.zip')            { Remove-Item '.\varner-os-plugin-v23.zip'            -Force }
Compress-Archive -Path '.\varner-equipment-theme-v23\varner-equipment-theme'  -DestinationPath '.\varner-equipment-theme-v23.zip'
Compress-Archive -Path '.\varner-equipment-theme-lite\varner-equipment-theme' -DestinationPath '.\varner-equipment-theme-v23-lite.zip'
Compress-Archive -Path '.\varner-os-plugin-v23-unpacked\varner-os-plugin-v23' -DestinationPath '.\varner-os-plugin-v23.zip'
```

---

## 7. Troubleshooting & SQL Cheat Sheet
Run these queries via **wp-admin > Tools > phpMyAdmin** or WP-CLI (`wp db query "..."`).
-   **Verify Meta Counts**: `SELECT meta_value, COUNT(*) FROM wp_postmeta WHERE meta_key = 'category' GROUP BY meta_value;`
-   **Reset Brand Transient**: `DELETE FROM wp_options WHERE option_name LIKE '_transient_varner_brand_counts%';`
-   **Identify Orphans**: Units missing a `category` can be found via: `SELECT post_id FROM wp_postmeta WHERE meta_key = 'category' AND meta_value = '';`
