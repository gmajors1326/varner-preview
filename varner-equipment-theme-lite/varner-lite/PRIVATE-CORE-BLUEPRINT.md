# Private-Core Master Blueprint

Purpose: Keep Varner Equipment’s operational data (inventory, service, parts, contact) portable and independent of any front-end theme or page builder. This prevents plugin bloat and guarantees data survives theme swaps or redesigns.

## Data Ownership Strategy
- Source of truth: WordPress + ACF fields registered in `varner-backend.php` for the `equipment` CPT. Forms (service, parts, contact) store submissions server-side (DB) and send email copies.
- No reliance on visual builders (Elementor/Divi/etc.) for core data. Front-end can change; data model stays.
- All business objects (inventory units, media, service/parts requests, contact messages) map to explicit DB tables or ACF-backed postmeta. Avoid opaque plugin data silos.

## Core Objects
- Equipment (CPT `equipment`): year, make, model, stock number, VIN/serial, price, condition, stock status, category, description, seller info, media.
- Service Requests: customer identity, address, equipment details, service needs, appointment date, prior history.
- Parts Requests: customer identity, address, equipment details, parts needed, preferred date, prior history.
- Contact Messages: name, email, phone, message.

## Persistence Guidelines
- WordPress REST API: `show_in_rest` enabled for `equipment`; ACF fields exposed under `acf` for integration. Use authenticated POST/PUT to update ACF values; keep writes authenticated (App Passwords/JWT).
- Database layer (optional mirror for reporting/performance): tables such as `wp_varner_equipment`, `wp_varner_equipment_media`, `wp_varner_service_requests`, `wp_varner_parts_requests`, `wp_varner_contact_messages`, plus reference tables for brands/categories.
- Media: store canonical URLs in `inventory_media`/ACF; do not depend on page-builder galleries.

## Front-End Independence
- Theme/Builder agnostic: Any redesign consumes the same REST/DB schema. Avoid page-builder-specific shortcodes for data.
- Menus and templates may change; data contracts do not. Treat ACF field keys/names as the stable contract.

## Change Control
- When adding/removing fields, update ACF registration (`varner-backend.php`) and, if mirroring, the DB schema. Maintain backward-compatible field names where possible.
- On slug/URL changes, use Yoast Premium Redirects to preserve SEO and inbound links; data stays intact.

## Export/Portability
- Periodic exports of ACF field groups (JSON) and data (WP All Export/DB dumps) to keep portable snapshots.
- Inventory and request data should be exportable without the theme or any visual builder installed.

## What to Avoid
- Storing core data in page-builder widgets/shortcodes.
- Relying on third-party plugins that own your inventory schema.
- Embedding business data only in HTML/WYSIWYG without structured fields.
