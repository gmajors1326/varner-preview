# Full Website SEO Audit — varnerequipment.com

## Executive Summary

- **Overall SEO Health Score:** 86 / 100
- **Business Type Detected:** Local Service / Equipment Dealership
- **Summary:** The website's SEO architecture is highly optimized, leveraging modern web practices like the Speculation Rules API for sub-second page rendering, NitroPack for server-side caching, and structured schema graphs. Key improvements lie in fixing mobile layout overflows, resolving minor accessibility/label gaps, and prioritizing the LCP hero image.

---

## 1. Technical SEO (Score: 92)

### Discovered Sitemaps
* [wp-sitemap.xml](https://varnerequipment.com/wp-sitemap.xml) (WordPress core index)
* [sitemap-inventory.xml/](https://varnerequipment.com/sitemap-inventory.xml/) (Plugin-generated custom inventory sitemap)

### Analysis
* **Crawlability & Indexability:** The site successfully outputs clean canonical links across page templates. Crucially, when active searches or filters are present (`$has_active_filters`), the site dynamically inserts a `<meta name="robots" content="noindex, follow">` tag. This is an excellent defense against index bloat and duplicate content.
* **HTTPS/SSL:** Configured and active.
* **Redirects:** Correctly resolves to the canonical version without chains.

---

## 2. Structured Data & Schema (Score: 95)

### Implemented Schema JSON-LD Blocks
* **LocalBusiness:** Injected in `header.php` including `GeoCoordinates` (+38.7652, -108.1061), `PostalAddress` (1375 US-50, Delta, CO), and verified social links (`sameAs` pointing to Facebook and YouTube).
* **Product:** Injected via `varner_listing_schema()` on individual equipment pages, containing spec fields (year, make, model, category).
* **ItemList:** Injected on category and inventory index pages to structure listing arrays for search engine robots.

---

## 3. Performance & Core Web Vitals (Score: 85)

### Strengths
* **Speculation Rules API:** Active in `<head>` for prerendering `/equipment/*` paths (`eagerness: moderate`), providing near-instant visual loading for search visitors.
* **Asset Optimization:** Enqueues NitroPack cached resources (`x-nitro-cache: HIT`), and preloads critical fonts (`Inter.woff2`) to prevent Cumulative Layout Shift (CLS).
* **Resource Hints:** Correctly declares `dns-prefetch` for external dependencies like Google Tag Manager and YouTube.

### Recommendations
* **LCP Prioritization:** The LCP hero image lacks a `fetchpriority="high"` hint. Adding this attribute to the primary logo or hero slide in `header.php` ensures the browser downloads it immediately.
* **Speculation Rules Extension:** The speculation rules block can be expanded to prefetch common landing pages like `/inventory/` and `/financing/`.

---

## 4. Mobile & Accessibility (Score: 78)

### Key Metrics
* **Touch Targets:** Good sizes, no overlaps detected.
* **Viewport Meta:** Correctly declared.
* **Horizontal Scroll (Warning):** The mobile scanner detected horizontal overflow. A side-scrolling page degrades UX and can trigger mobile-friendliness flags in search engines. Check layouts for elements exceeding viewport limits.
* **Form Labels (Warning):** The semantic check identified 3 `<input>` fields missing matching `<label>` elements or `aria-label` tags, which lowers the site's accessibility compliance.

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
Built by agricidaniel — Join the AI Marketing Hub community
Free  → https://www.skool.com/ai-marketing-hub
Pro   → https://www.skool.com/ai-marketing-hub-pro
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
