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
-   **React Editor**: Mapping is defined in `src/App.jsx` via `ATTACHMENT_SUB_SUB_MAPPING`.
-   **Theme Queries**: Logic is centralized in `functions.php` (specifically `varner_build_inventory_query` and `varner_get_segment_seo`).

---

## 3. Smart Faceted Search
The filtering system is professionally faceted, meaning it calculates unit counts dynamically based on the user's active search.

-   **Logic Location**: `varner_get_filter_data()` in `functions.php`.
-   **Mechanism**: It performs multiple "lookahead" queries. For example, when counting Manufacturers, it ignores the current Manufacturer filter but respects all other filters (Category, Year, etc.) to show only relevant available brands.
-   **Hierarchy Tree**: A single optimized SQL join builds the recursive Category tree used in the sidebar.

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
2.  Run `npm run build`.
3.  **Sync to Plugin**:
    -   Copy `dist/index.html` -> `varner-os-plugin-v23-unpacked/varner-os-plugin-v23/dist/index.html`.
    -   Replace `varner-os-plugin-v23-unpacked/varner-os-plugin-v23/dist/assets/` with the contents of `dist/assets/`.

### Editing Themes
1.  Apply changes to `varner-equipment-theme-v23`.
2.  **Mandatory Lite Sync**: Always sync modified PHP files (header, footer, functions, partials) to `varner-equipment-theme-lite`.

---

## 6. Deployment & Packaging
All deployment artifacts are generated as ZIP files in the root directory.

### Command Execution:
```bash
# Build React
npm run build

# Sync Plugin Assets (Example)
cp dist/index.html varner-os-plugin-v23-unpacked/varner-os-plugin-v23/dist/
cp dist/assets/* varner-os-plugin-v23-unpacked/varner-os-plugin-v23/dist/assets/

# Package Everything
powershell -Command "
  Compress-Archive -Path '.\varner-equipment-theme-v23\varner-equipment-theme' -DestinationPath '.\varner-equipment-theme-v23.zip' -Force;
  Compress-Archive -Path '.\varner-equipment-theme-lite\varner-equipment-theme' -DestinationPath '.\varner-equipment-theme-v23-lite.zip' -Force;
  Compress-Archive -Path '.\varner-os-plugin-v23-unpacked\varner-os-plugin-v23' -DestinationPath '.\varner-os-plugin-v23.zip' -Force;
"
```

---

## 7. Troubleshooting & SQL Cheat Sheet
-   **Verify Meta Counts**: `SELECT meta_value, COUNT(*) FROM wp_postmeta WHERE meta_key = 'category' GROUP BY meta_value;`
-   **Reset Brand Transient**: `DELETE FROM wp_options WHERE option_name LIKE '_transient_varner_brand_counts%';`
-   **Identify Orphans**: Units missing a `category` can be found via: `SELECT post_id FROM wp_postmeta WHERE meta_key = 'category' AND meta_value = '';`
