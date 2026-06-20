# Varner OS — Dashboard Manual

A guide to every section of the Varner Equipment inventory management system.

---

## 1. Dashboard

The landing page when you first log in. Shows a high-level overview of your inventory.

**What you see:**
- **Total Units** — count of all active inventory items
- **Featured Units** — units marked as featured on the homepage
- **Recently Updated** — last 5 units that were edited, with timestamps
- **Quick Stats** — breakdown by category or status (In Stock, Pending Sale, Sold)
- **Recent Activity** — latest ledger entries across all units

**No actions to take here** — it's a read-only summary. Use the sidebar to navigate to the section you need.

---

## 2. Add / Edit

The unit editor. This is where you create new inventory items and update existing ones.

### Creating a new unit
1. Click **Add / Edit** in the sidebar.
2. Click **Add New Unit** (top right).
3. Fill out the fields (see below).
4. Click **Save** (bottom of the form).

### Editing an existing unit
1. Click **Inventory List** (or search from Add/Edit).
2. Click a unit's row.
3. Edit any field.
4. Click **Save**.

### All fields explained

| Field | Details |
|-------|---------|
| **Stock #** | Your internal stock number (text). Not serialized — for reference only. |
| **Year** | Equipment year (4-digit). |
| **Make / Brand** | Dropdown of known brands. If yours isn't listed, ask an admin to add it. |
| **Model** | Model number/name (text). |
| **Category** | Primary category (Tractor, Trailer, Implement, etc.). |
| **Subcategory** | A sub-group within the category (e.g. "Skid Steer" under "Tractor"). |
| **Sub-Subcategory** | A further refinement (e.g. "Track" vs "Wheel" under Skid Steer). |
| **Price** | Integer dollars (no cents). |
| **Hours / Miles** | Usage meter value. Change the unit type (hours/miles) next to the field. |
| **VIN / Serial** | Vehicle identification number or serial number. |
| **Location** | Physical location of the unit. |
| **Description** | Rich text body. Supports bold, lists, and paragraphs. Used on the public showroom page. |
| **Stock Status** | `In Stock` — visible on the website and in the Facebook feed. `Pending Sale` — tagged as pending. `Sold` — removed from public view. `Draft` — hidden from the site and feed (useful for staging). |
| **Images** | Drag-and-drop or click to upload. First image is the primary thumbnail. Long-press/drag to reorder. |

### Toggles (below the main fields)

| Toggle | What it does |
|--------|-------------|
| **Featured Unit** | Pins this unit to the top of the homepage. |
| **Website Visibility** | Shows/hides from the public showroom. Off by default for new units. |
| **Sync to Meta/Facebook** | Includes this unit in the Facebook Catalog CSV feed. |
| **Draft (Hidden)** | Shortcut to set stock status to Draft. Toggle off to return to In Stock. |

### Implements / Attachments
Below the toggles, you can attach implements (e.g. a loader bucket, mower deck, etc.). Each row has:
- Title
- Price
- Description
- Image

Click **Add Implement** to add a row. Click the image area to upload a picture. The X removes the row.

---

## 3. Inventory List

A spreadsheet-style table of every unit. Use this to browse, search, filter, and take bulk actions.

**Columns:**
| Column | What it means |
|--------|---------------|
| **STOCK#** | Stock number (click the row to edit). |
| **YEAR** | Year of manufacture. |
| **MAKE** | Brand. |
| **MODEL** | Model number/name. |
| **PRICE** | Listing price. Green = In Stock, Yellow = Pending Sale, Red = Sold/Gone. |
| **STATUS** | Current stock status with colored badge. |
| **DAYS IN STOCK** | How long the unit has been listed. |
| **CATEGORY** | Primary category. |
| **LOCATION** | Physical location. |
| **HOURS/MILES** | Meter reading + unit type. |
| **FEATURED** | Toggle on/off (click the toggle directly, no need to open the editor). |
| **DRAFT** | Toggle on/off — instantly hides/republishes the unit. |
| **ACTIONS** | Edit (same as clicking the row) and Delete (soft-delete, can be restored). |

**Search:** Start typing to filter the list — searches across stock#, make, model, year, and category.

**Category filter:** Use the bar above the table to show only Tractors, Trailers, or Implements.

**Pagination:** 20 units per page. Navigate at the bottom.

---

## 4. History

Two sections:

### Audit Log (global)
A complete, filterable log of every change made to any unit. Shows:
- **User** — who made the change
- **Action** — created, updated, deleted, restored, etc.
- **Summary** — brief description (e.g. "Updated: price, description")
- **Timestamp** — when it happened

Use the search bar to filter by user name, unit stock#, or action type.

### Deleted Units
Units that have been soft-deleted are listed here with a **Restore** button. They remain in the database until permanently deleted.

- **Restore** — brings the unit back to In Stock.
- **Permanently Delete** — removes it from the database entirely (irreversible).
- **Restore All / Delete All** — bulk actions at the top.

---

## 5. Meta Sync

Manages the Facebook Marketplace / Meta catalog integration.

### How it works
Every 6 hours (cron job), the system generates a CSV file of all units that have **Sync to Meta/Facebook** enabled. This CSV is posted to Meta's catalog API to keep your listings current.

### What you see
- **Feed Status** — green dot = syncing is active
- **Last Sync** — timestamp of the last successful upload
- **Unit Count** — how many units are being pushed to the feed
- **CSV Preview** — a raw preview of the last generated feed rows

### Actions
- **Sync Now** — triggers an immediate feed push (bypasses the 6-hour schedule)
- **View Feed** — opens the raw CSV file

Units are excluded from the feed if:
- Stock status is Sold or Draft
- Website Visibility is off
- Facebook Sync toggle is off

---

## 6. Mobile Companion

Generates secure access tokens for the companion mobile app (installed on yard phones/tablets).

### Generating a token
1. Click **Generate Secure Access**.
2. A **QR code** appears — scan it with the phone to auto-authenticate.
3. The token link has a **handoff code** baked in; the phone never sees the raw token.

### Managing tokens
- Tokens expire **30 minutes** after last activity (sliding window).
- Hard expiry: **24 hours**, regardless of activity.
- Maximum **3 active tokens per user**.
- Tokens are tied to your WordPress user account and inherit your permissions.

### Revoking access
Click **Revoke & Reset** to invalidate the current token.

---

## 7. Video Manager

Manages the walkthrough and showcase videos on the public site.

### Adding a video
1. Click **Add Video** (top right).
2. Choose a source:
   - **YouTube Link** — paste a YouTube URL. The system extracts the video ID and embeds the player.
   - **Upload Video** — upload a video file from your computer (MP4, WebM, etc.).
3. Select a **Category** (e.g. "Walkthrough," "Product Highlight").
4. Click **PUBLISH VIDEO**.

### Editing / Deleting
- Click **Edit** on any video card to change title, link/file, or category.
- Click the trash icon to permanently delete.

### Categories
- Click **Categories** (top left) to add or remove video categories.
- Deleting a category reassigns its videos to "Uncategorized."

### Display
YouTube videos show the YouTube-generated thumbnail. Uploaded videos show a native `<video>` player with controls. Both types include the category badge and play button overlay.

---

## 8. Page Editor

Manages basic WordPress pages from within the dashboard — no need to switch to the WordPress admin.

### Listing pages
Shows all published and draft pages with:
- Title
- Status (Published / Draft)
- Template (if any)
- Last modified date
- Link to view the page

### Actions
- **Edit** — opens the page in the WordPress block editor (separate tab).
- **View** — opens the live page in a new tab.
- **Delete** — moves to trash (protected pages like the showroom cannot be deleted).

### Protected pages
The following pages cannot be deleted or set to Draft:
- Front page (homepage)
- Blog page
- Privacy Policy
- The showroom page (contains `[varner_showroom]` shortcode)

Attempting to delete or draft these will show a warning.

### Note
Page Editor is for **existing pages only**. To create a new page, use the standard WordPress admin → Pages → Add New.

---

## 9. Configuration

Site-wide settings that control the public website's appearance and behavior.

### Sections

**Hero Section**
- Hero image/headline/subtitle — the big banner at the top of the homepage.
- Overlay the hero image with a dot pattern for visual depth.

**YouTube / Video Section**
- Channel URL
- Featured video ID
- Custom thumbnail
- Section headline and descriptive text

**About / CTA Section**
- Call-to-action button text and destination URL.
- Tagline for the about area.

**Finance**
- Finance card logos and application PDF links.
- Descriptive text for the finance section.

**Employment**
- Job listings with title, type (Full-Time/Part-Time), location, and description.
- A "Now Hiring" badge option per job.
- Intro text for the careers section.

**Hours**
- Business hours displayed on the site (set via a simple text area).

**Social Links**
- Links for Facebook, Instagram, YouTube, and TikTok.
- Custom link support with label + URL.

### How to save
All sections save independently. Click the **Save** button at the bottom of each section. A green toast confirms success.

### Preview
After saving, click **Preview Changes** to see the front-end result without publishing live users will see it. Click **Publish** to push changes live.
