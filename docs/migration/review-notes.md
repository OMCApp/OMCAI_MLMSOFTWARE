## Review notes (checked by hand after the extraction run)

### Counts
- The live site has **36 posts**, not 37: 27 in ข่าวขายตรง and 9 in ประกัน. The sitemap-based baseline, `/blog/` pages 1–3 and every page of both category archives were cross-checked, and none links to a 37th post. The baseline README and PLAN say "37 posts (11 insurance)"; both numbers are miscounts.

### `/ocpb-mlm/` vs `/ocpb-mlm2/`: not duplicates
- The text similarity is about **0.3%** (5-gram Jaccard), and no blocks are identical (see the JSON above).
- `/ocpb-mlm/` is the real Thai page: OCPB / สคบ. registration service, requirements, timeline and document numbers.
- `/ocpb-mlm2/` is **the SeoCrawler theme's demo page** with a new title ("ขอใบอนุญาติ สคบ.2"). Its body is English SEO-agency filler ("We're an expert search marketing and optimization agency", "SEO Training", "Get A Quote") and the demo client logos.
- Both pages share the same title intent and the same (empty) meta description, and each page's canonical points to itself.
- **Recommendation:** do not migrate `/ocpb-mlm2/` content. Either point it to `/ocpb-mlm/` with a 301 (preferred, since nothing on it is worth keeping) or keep the URL with `canonical` → `/ocpb-mlm/`. Confirm with GSC whether it gets any impressions.

### Pages that are theme demo content (do not reuse the copy or images)
- `/services/`: the whole body is SeoCrawler demo content in English (SEO Audit, Link Building, a "Basic / Personal / Team / Cloud Plan" price table, demo client logos and a YouTube demo video). None of it is about MLM software. Keep only the URL. The page needs new copy (PLAN: Solutions hub).
- `/portfolio/`: the listing is the 9 theme demo portfolio items (Architek, Dell, HP…). These are already in the redirect map → `/portfolio/`.
- Images in `images.csv` used only by `/services/`, `/ocpb-mlm2/` or `/portfolio/` (for example `2019/09/judson.png`, `techcom.png`, `the-arc.png` and `icon-*.png`) are theme demo assets. They were copied so the old URLs do not 404, but they should not appear on the new pages.

### Numbers on the old pages that are not verified (QUESTIONS.md: do not show)
- Home counters: **137** Completed Sites · **1200** Happy Customers · **11k** Working Hours · **23** Awards Winning. These are the theme's demo values.
- `/ocpb-mlm/` counters: **665+** ธุรกิจขายตรง · 1200 Happy Customers · 11k Completed Sites · 23 Awards Winning.
- The experience claims contradict each other: the home slider says "ประสบการณ์ทำงาน**มากกว่า 15 ปี**", while the home and `/mlm-software-omc/` body say "ประสบการณ์ทำงาน**กว่า 10 ปี** ผลงานกว่า 100 หน่วยงาน". Ask the client which figure is true.

### Title cleanup
- Every `<title>` on the old site ends with the same ~500-character keyword list (" - โปรแกรมขายตรง,ระบบขายตรง,ระบบMLM,…"). `title_clean` keeps only the part before it and drops a bare " - MLM SOFTWARE" site-name segment.
- The home page becomes just "Home", and several pages get generic English titles ("Services", "Contact", "Portfolio"). These need new Thai titles with the target intent.
- Post titles: `title_clean` (Rank Math title), `h1` (theme title) and `jsonld_headline` sometimes differ. Several H1s are cut off by the author, for example "…ต้องมี MLM Softwar" on `/2026/07/14/mlm-software-system/`. Use the clean title for `<title>` and fix the cut-off H1s by hand.

### Meta descriptions
- All 10 pages have **no meta description** except the two policy pages. The two category pages have only the category name (6–10 chars).
- `/2024/07/15/…` has a 17-char description and `/2024/12/16/…` a 63-char one. `/2025/10/21/…` is 340 chars and will be truncated.

### H1
- No H1: `/`, `/ocpb-mlm/` and both category pages.
- Two H1s: `/2026/07/14/mlm-software-system/` and both policy pages, where the theme title and an H1 inside the content are both present. Content H1s were demoted to H2 in `content_html` (see `content_h1_demoted`).

### Contact data found (see `contact-info.json`)
- Phone **099-109-5544** (site-wide). The live site has **broken `tel:` links**: the header uses `tel:+0991095544`, which is an invalid format, and the contact page uses `tel:+6691095544`, which is missing a digit (should be `+66991095544`).
- LINE **omc54** (`https://line.me/ti/p/~omc54`, site-wide). The insurance posts use extra lin.ee links for OMC Broker: `lin.ee/z38T9i5`, `lin.ee/Mzs7evb` and `lin.ee/GWr9HdL`.
- Email: **sale@omc.co.th** (site-wide), **info@omc.co.th** (contact page) and **projectyak@hotmail.com**, a personal Hotmail address in the site-wide footer. Confirm with the client whether it should stay public.
- Address: อาคาร ขจรประศาสน์ เลขที่ 3/3 ซอยเลี่ยงเมืองนนทบุรี 14 ตำบลท่าทราย อำเภอเมืองนนทบุรี จังหวัด นนทบุรี 11000.
- Company: **บริษัท ออนไลน์ มาร์เก็ตติ้ง คอมมิวนิเคชั่น จำกัด** (footer). No company registration number or tax ID appears anywhere on the site.
- Facebook: `facebook.com/mlmsoftwarebyomc` and `m.me/mlmsoftwarebyomc`. Google My Maps embed in the footer, plus the `goo.gl/maps/pJ6HygtzB7hW9VS5A` link on the contact page.

### Live-site defect worth knowing
- Every page prints PHP warnings from the visitor-counter widget in the footer (`Undefined array key "yesterday_view" … "month_view" …`). The new site drops this widget.

### Images
- All referenced images were found in the backup, so nothing needed downloading and nothing is missing.
- About 68 MB of the total is the **unsized originals** (`role=original`), copied only to generate responsive sizes later. They sit in a separate commit so the lead can drop them if repository size matters.
- Most source images are large PNGs (1–1.9 MB, 1254–1920 px). Convert them to WebP/AVIF when generating sizes.
- `alt_suggested` is built only from text already on the page: the caption, then the nearest preceding heading, then the post or page title. Review it before importing. Suggestions on demo pages come from English demo headings and should be ignored.
