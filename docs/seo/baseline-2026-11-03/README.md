# SEO Baseline — mlmsoftware.omc.co.th (2026-11-03)

Snapshot of the live WordPress site taken before the rebuild. It is used to:
1. build the URL inventory and redirect map, and
2. compare against staging and production after launch.

Regenerate with:

```
php -d curl.cainfo=C:/xampp/apache/bin/curl-ca-bundle.crt tools/seo-audit/crawl.php https://mlmsoftware.omc.co.th docs/seo/baseline-YYYY-MM-DD --extra=tools/seo-audit/extra-urls.txt
```

## Files

| File | Contents |
|---|---|
| `pages.csv` | One row per URL: status, redirect hops, title, description, canonical, robots, H1, word count, schema types, image/alt counts, script/CSS counts |
| `images.csv` | Every `<img>` on every page, with its alt text |
| `internal-links.csv` | Every internal link and its anchor text (used to rebuild internal linking) |
| `url-list.txt` | All crawled URLs (sitemap + `tools/seo-audit/extra-urls.txt`) |
| `robots.txt` | The live robots.txt |
| `summary.json` | Totals |

**Not yet included:** Google Search Console data (clicks, impressions, CTR, position per page and query). It has to be exported by someone with GSC access. See "Still needed" below.

## URL inventory (95 URLs crawled)

| Group | Count | URL pattern | Plan |
|---|---|---|---|
| Blog posts | 36 (27 ข่าวขายตรง + 9 ประกัน) | `/YYYY/MM/DD/<slug>/` (Thai slugs) | **Keep the same URL**; migrate content, title, description and images |
| Core pages | `/`, `/mlm-software-omc/`, `/ocpb-mlm/`, `/ocpb-mlm2/`, `/services/`, `/blog/`, `/contact/`, `/portfolio/` | — | Keep the URL; redesign the content but keep the search intent |
| Policy pages | 2 | `/นโยบายความเป็นส่วนตัวข/`, `/นโยบายการใช้คุกกี้-cookies-policy/` | Keep the URL |
| Categories | 2 | `/category/ข่าวขายตรง/`, `/category/ประกัน/` | Keep the URL |
| Theme demo pages | 6 | `/sample-page/`, `/sample-page-2/`, `/home-startup/`, `/about-us-3/`, `/newsletter/`, `/newsletter-2/` | 301 to the nearest real page, or 410 (check GSC first) |
| Demo portfolio items | 9 | `/portfolio/<demo>/` (Dell, HP, ...) | 301 to `/portfolio/` (not real OMC work) |
| Demo portfolio taxonomies | 11 | `/portfolio_category/*`, `/portfolio_tag/*` | 301 to `/portfolio/` |
| Authors | 2 | `/author/*` (already noindex) | 301 to `/about/`, or keep as noindex |
| Existing redirect | 1 | `/mlm-software/` → 301 to the 2025/05/20 post | **Must keep** |
| Old URLs that already 404 | 6 | `/mlm-system/`, `/mlmsoftware/`, `/contact-us/`, `/contact-us-2/`, `/homepage-business-solutions/`, `/pages/` | Opportunity: 301 to the relevant new pages (they may still have backlinks) |
| Feeds | 2 | `/feed/`, `/comments/feed/` | Keep `/feed/` (RSS); 301 `/comments/feed/` to `/feed/` |
| Pagination | — | `/blog/page/N/` | Keep the same pattern |

## Main problems on the current site (opportunities in the new one)

| Issue | Pages affected |
|---|---|
| The site name is stuffed with ~40 keywords (including unrelated ones: ครีม, โสม, หน้าใส) and appended to every title; titles are 540–600 chars | ~45 pages |
| No meta description | 50 |
| H1 missing or more than one | 41 (home page has no H1) |
| Images without alt | 656 image instances |
| Home page loads 36 scripts + 18 stylesheets, 120 KB HTML | home |
| Theme demo pages and demo portfolio items are indexable | 26 |
| The sitemap is WordPress core's, not Rank Math's; the sitemap includes demo pages | — |
| Insurance articles (9 posts, `/category/ประกัน/`) sit inside an MLM software site, which dilutes the topic | 11 |
| Pages use `Article` schema; no `Organization`/`SoftwareApplication`/`BreadcrumbList`/`FAQPage` | all |

## Still needed

1. **A Google Search Console export**: Performance → last 16 months → *Pages* and *Queries* tabs (CSV), plus Indexing → Pages. Needed to decide which pages hold rankings before anything is redirected or rewritten.
2. **A fresh database export of the live DB (`omc_web`)**. The dump in the backup (`db/omc_mlmsoftware.sql`) dates from May 2024 (TheGem theme, Yoast, no published posts), so it does not match the live site.
3. Backlink list (GSC → Links → Top linked pages), to confirm which old/404 URLs deserve redirects.
