# SEO Action Plan — varnerequipment.com

This document outlines the prioritized action items based on the SEO audit for Varner Equipment.

## Phase 1: Critical & High Fixes (Week 1)

### 1. Fix Mobile Horizontal Scroll
* **Priority:** High
* **Finding (THINK):** The visual scanner detected horizontal scroll behavior on the mobile homepage. This indicates elements extending beyond the standard screen width, which degrades user experience and impacts mobile-friendly SEO rankings.
* **Action (ACT):** Audited layout widths. Check for elements using raw viewport or screen-width properties (`w-screen` vs `w-full`) without hiding overflow. Add `overflow-x-hidden` to outer layout wrapper containers where needed.
* **Verification (ACCEPT):** Inspect the homepage on a mobile viewport and ensure there is no horizontal play or side-scrolling.

---

## Phase 2: High-Impact Improvements (Weeks 2-3)

### 2. Resolve Unlabeled Form Inputs
* **Priority:** Medium
* **Finding (THINK):** Accessibility check returned 3 inputs missing proper `<label>` elements or `aria-label` attributes.
* **Action (ACT):** Locate form fields in `varner-lite` templates (e.g., search/filter forms, newsletter inputs) and associate each `<input>` element with a corresponding `<label>` tag using matching `id`/`for` attributes, or add `aria-label` attributes.
* **Verification (ACCEPT):** Re-run the accessibility checks (`agent_ux_check.py`) and confirm no unlabeled inputs are flagged.

### 3. Add LCP Hero Image Preload Hint
* **Priority:** Medium
* **Finding (THINK):** The LCP candidate image is not prioritized for preload, which delays the initial contentful paint on first-visit loads.
* **Action (ACT):** Add `fetchpriority="high"` and check if a matching `<link rel="preload">` is present for the hero image in `header.php`.
* **Verification (ACCEPT):** Run `preload_check.py` and confirm that the hero image's LCP preload recommendation is resolved.

---

## Phase 3: Content & Authority (Month 2)

### 4. Expand Speculation Rules
* **Priority:** Low
* **Finding (THINK):** Speculation Rules API is correctly implemented in `header.php` to prerender `/equipment/*` links. However, it can be extended to other high-traffic pages (like `/inventory/` and `/financing/`).
* **Action (ACT):** Update the JSON block in `header.php` to include other primary routes that users are highly likely to click next.
* **Verification (ACCEPT):** Validate the prerender speculation block using browser developer tools during page loads.

---

## Phase 4: Monitoring & Iteration (Ongoing)

### 5. Google Search Console & XML Sitemap Coverage
* **Priority:** Low
* **Finding (THINK):** Custom sitemaps are present but sitemap indices need monitoring in GSC.
* **Action (ACT):** Ensure `wp-sitemap.xml` and `sitemap-inventory.xml` are correctly submitted in Google Search Console. Monitor crawl logs and index coverage monthly.
