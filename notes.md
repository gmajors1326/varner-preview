# Workspace Guidelines & Audit Notes

## Workspace Rules (User Instructions)
> [!IMPORTANT]
> - **Do Not add extra code, functions, text, images unless approved.**
> - **Make suggestions** on how to improve code, functions, text, and images.
> - **Do Not delete** any code, functions, text, and images directly without approval.
> - **Improve and optimize** when necessary and delete duplicate code.
> - **Delete images** in file folders that are not being used, upon approval.
> - **Use `tar` to zip files every time.**

---

## 1. Markdown (.md) Files Review

### [DEPLOY.md](file:///c:/Users/Greg/Desktop/Varner%20Equipent/DEPLOY.md)
* **Purpose**: Serves as the theme & plugin packaging runbook.
* **Findings**:
  - The script sections outline a manual sync from full theme (`varner-v23`) to lite theme (`varner-lite`) via `Copy-Item`. 
  - **Risk**: Porting `functions.php`, `header.php`, and `index.php` from `v23` → `lite` will overwrite newer, vital updates currently in `varner-lite` (like the mobile menu and CSS scroll reveal logic).
* **Suggestions**:
  - Update `DEPLOY.md` sync instructions once `varner-v23` has been updated with the latest mobile features, to prevent developer mistakes from overwriting files.

### [SKILL.md](file:///c:/Users/Greg/Desktop/Varner%20Equipent/SKILL.md)
* **Purpose**: Master technical manual.
* **Findings**:
  - Mentions that category/sub-category mapping constants are handled inline in `src/App.jsx`.
* **Suggestions**:
  - Recommend extracting these mappings to a separate React file (e.g. `src/constants/categoryMap.js`) to clean up `src/App.jsx`.

### [PRIVATE-CORE-BLUEPRINT.md](file:///c:/Users/Greg/Desktop/Varner%20Equipent/varner-equipment-theme-v23/varner-v23/PRIVATE-CORE-BLUEPRINT.md)
* **Purpose**: System architecture/data design rules.
* **Findings**:
  - Well-defined boundaries on visual builders and data models. No changes needed.

---

## 2. Code Review & Duplication Audit

### Theme Synchronization Mismatch
There is a significant difference between the files in `varner-equipment-theme-v23/varner-v23/` and `varner-equipment-theme-lite/varner-lite/`:
* **`varner-lite/header.php`**: Contains a fully responsive slide-out mobile menu drawer, hamburger animations, mobile accordions, and loads breadcrumbs via `varner_render_breadcrumbs()`.
* **`varner-v23/header.php`**: Lacks all mobile responsive menu code (desktop navbar only).
* **`varner-lite/index.php`**: Includes category link hrefs on the category grid cards. `varner-v23/index.php` has plain buttons without category links.
* **`varner-lite/footer.php`**: Implements a scroll observer script for scroll-reveal animations.

**Suggestion**: 
Port the mobile responsive menu drawer, accordions, category card links, and scroll-reveal code from `varner-lite` into the master theme `varner-v23`. This establishes `varner-v23` as the true master source.

### Redundant Files & Folders
The following directories and files are exact duplicates or older backups:
1. **`varner-plugin-rebuild/`**: Duplicate of the plugin containing older code (lacks Mobile PWA integration).
2. **`varner-os-plugin-v23/`**: Another old copy of the plugin.
3. **`varner-backup-20260507/`**: Older date backup of themes and plugins.
4. **`varner-backup-temp/`**: Older temporary backup directory.
5. **`varner-equipment-theme-v23/test_extract/`**: Temp unzipped extraction.
6. **`patch-backend.php`**: Temporary patch script in the root directory.
7. **`varner-backend-UPLOAD.php`**: Duplicate copy of `varner-backend.php`.

**Suggestion**: 
Safely delete these folders and files after backup approval to clean up 100+ MB of redundant space and reduce developer confusion.

### Duplicate PHP Logic
* **Breadcrumbs**: `varner-lite/functions.php` contains the full `varner_render_breadcrumbs()` PHP logic, whereas `varner-v23` uses a modular `partials/breadcrumb.php` template file.
  - **Suggestion**: Remove `varner_render_breadcrumbs()` from `functions.php` and load `partials/breadcrumb.php` in both themes.
* **Video CPT**: `varner-lite/functions.php` has `varner_register_video_cpt()`. If video post type is handled by the plugin, this function is duplicate and should be removed.

---

## 3. Unused Image Assets Audit

The following images are in the directories but are never referenced in any code files:

### Root `Images/` Directory (55 files total)
* **Unused Inventory Mockups**:
  - `10CH-WoodDeckCarHauler_Gallery-2.webp`, `10CH-WoodDeckCarHauler_Gallery-3.jpg`, `10CH-WoodDeckCarHauler_Gallery-5.jpg.webp`, `10CH.webp`, `10CH2.webp`, `10CH3.webp`, `10CH4.webp`, `10CH5.webp`
  - `114LP3.webp`, `14LP.webp`, `14LP2.webp`, `14LP4.webp`, `14LP6.webp`, `14LP7.webp`, `14LP8.webp`
  - `60EC-WoodDeckCarHauler_Gallery-1.jpg`
  - `Branson3510_1.webp` to `Branson3510_4.webp`
  - `DF1.webp` to `DF4.webp`
  - `Digger1.webp` to `Digger3.webp`
  - `Krone_1.webp`, `Krone_2.webp`, `kron_3.webp`, `krone_4.webp`, `krone_5.webp`
  - `Legend1.webp` to `Legend3.webp`
  - `Mahindra-OJA-1126.jpg`
  - `img (1).webp` to `img (5).webp`, `img.webp`
  - `img_0216_w2lhk34wo.heic.jpeg`
* **Used Brand Logos (Keep these)**:
  - `BigTex_white.png`, `CMTruckbeds_white.png`, `DuetzFahr_white.png`, `KRONE_white.png`, `MacDon_white.png`, `Mahindra_white.png`, `McHALE_white.png`, `ROXR_white.png`, `TYM_white.png`, `TitanTrailersMFG_white.png`, `Triton_white.png`, `Zetor_white.png`

### Theme `varner-v23/Images/` Folder (28 files total)
* **Unused Images**: All images in this folder are redundant. Brand logos are loaded from the theme's `assets/` folder, and mockups (`deutz-fahr1.webp` to `deutz-fahr4.webp`, etc.) are unreferenced.
  - **Suggestion**: Delete the entire `Images/` folder inside the theme since logo assets are already under `assets/`.

### Root `public/` Directory (6 files total)
* **Unused Mockups**:
  - `imp1.jpg`, `imp2.jpg`, `imp3.jpg`, `left.jpg`, `mahindra.jpg`, `rear.jpg`
  - **Suggestion**: Delete these files since they are not loaded by Vite or React in the code.
