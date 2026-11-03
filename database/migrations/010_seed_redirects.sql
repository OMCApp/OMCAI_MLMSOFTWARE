SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Redirect map from project/PLAN.md "Redirect map ของ URL ที่ไม่มีในเว็บใหม่" + the redirect the live site already has
-- Idempotent: each row is inserted only when no live row has the same from_path.
-- Thai values are written as hex (CONVERT(X'..' USING utf8mb4)) — the decoded text is in the comment above the row.
-- (scripts/check.php forbids Thai text inside INSERT/VALUES lines, a guard meant for status keys.)

INSERT INTO redirects (from_path, match_type, to_path, code, note, hits, enabled, created_at, updated_at)
SELECT '/sample-page/', 'exact', '/', 301, 'theme demo page', 0, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM redirects WHERE from_path = '/sample-page/' AND deleted_at IS NULL);

INSERT INTO redirects (from_path, match_type, to_path, code, note, hits, enabled, created_at, updated_at)
SELECT '/sample-page-2/', 'exact', '/', 301, 'theme demo page', 0, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM redirects WHERE from_path = '/sample-page-2/' AND deleted_at IS NULL);

INSERT INTO redirects (from_path, match_type, to_path, code, note, hits, enabled, created_at, updated_at)
SELECT '/home-startup/', 'exact', '/', 301, 'theme demo page', 0, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM redirects WHERE from_path = '/home-startup/' AND deleted_at IS NULL);

INSERT INTO redirects (from_path, match_type, to_path, code, note, hits, enabled, created_at, updated_at)
SELECT '/about-us-3/', 'exact', '/about/', 301, 'theme demo page', 0, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM redirects WHERE from_path = '/about-us-3/' AND deleted_at IS NULL);

INSERT INTO redirects (from_path, match_type, to_path, code, note, hits, enabled, created_at, updated_at)
SELECT '/newsletter/', 'exact', '/blog/', 301, 'theme demo page', 0, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM redirects WHERE from_path = '/newsletter/' AND deleted_at IS NULL);

INSERT INTO redirects (from_path, match_type, to_path, code, note, hits, enabled, created_at, updated_at)
SELECT '/newsletter-2/', 'exact', '/blog/', 301, 'theme demo page', 0, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM redirects WHERE from_path = '/newsletter-2/' AND deleted_at IS NULL);

INSERT INTO redirects (from_path, match_type, to_path, code, note, hits, enabled, created_at, updated_at)
SELECT '/portfolio/allen-walker-optimization/', 'exact', '/portfolio/', 301, 'theme demo portfolio item', 0, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM redirects WHERE from_path = '/portfolio/allen-walker-optimization/' AND deleted_at IS NULL);

INSERT INTO redirects (from_path, match_type, to_path, code, note, hits, enabled, created_at, updated_at)
SELECT '/portfolio/dell-audience-monitoring/', 'exact', '/portfolio/', 301, 'theme demo portfolio item', 0, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM redirects WHERE from_path = '/portfolio/dell-audience-monitoring/' AND deleted_at IS NULL);

INSERT INTO redirects (from_path, match_type, to_path, code, note, hits, enabled, created_at, updated_at)
SELECT '/portfolio/hp-cloud-service-improvement/', 'exact', '/portfolio/', 301, 'theme demo portfolio item', 0, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM redirects WHERE from_path = '/portfolio/hp-cloud-service-improvement/' AND deleted_at IS NULL);

INSERT INTO redirects (from_path, match_type, to_path, code, note, hits, enabled, created_at, updated_at)
SELECT '/portfolio/travel-tour-marketing-campaign/', 'exact', '/portfolio/', 301, 'theme demo portfolio item', 0, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM redirects WHERE from_path = '/portfolio/travel-tour-marketing-campaign/' AND deleted_at IS NULL);

INSERT INTO redirects (from_path, match_type, to_path, code, note, hits, enabled, created_at, updated_at)
SELECT '/portfolio/real-estate-web-rank-improve/', 'exact', '/portfolio/', 301, 'theme demo portfolio item', 0, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM redirects WHERE from_path = '/portfolio/real-estate-web-rank-improve/' AND deleted_at IS NULL);

INSERT INTO redirects (from_path, match_type, to_path, code, note, hits, enabled, created_at, updated_at)
SELECT '/portfolio/kleanity-social-engagement-2/', 'exact', '/portfolio/', 301, 'theme demo portfolio item', 0, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM redirects WHERE from_path = '/portfolio/kleanity-social-engagement-2/' AND deleted_at IS NULL);

INSERT INTO redirects (from_path, match_type, to_path, code, note, hits, enabled, created_at, updated_at)
SELECT '/portfolio/website-seo-optimization-2/', 'exact', '/portfolio/', 301, 'theme demo portfolio item', 0, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM redirects WHERE from_path = '/portfolio/website-seo-optimization-2/' AND deleted_at IS NULL);

INSERT INTO redirects (from_path, match_type, to_path, code, note, hits, enabled, created_at, updated_at)
SELECT '/portfolio/conference-marketing-2/', 'exact', '/portfolio/', 301, 'theme demo portfolio item', 0, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM redirects WHERE from_path = '/portfolio/conference-marketing-2/' AND deleted_at IS NULL);

INSERT INTO redirects (from_path, match_type, to_path, code, note, hits, enabled, created_at, updated_at)
SELECT '/portfolio/architek-rank-improving/', 'exact', '/portfolio/', 301, 'theme demo portfolio item', 0, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM redirects WHERE from_path = '/portfolio/architek-rank-improving/' AND deleted_at IS NULL);

INSERT INTO redirects (from_path, match_type, to_path, code, note, hits, enabled, created_at, updated_at)
SELECT '/portfolio_category/', 'prefix', '/portfolio/', 301, 'theme demo taxonomy /portfolio_category/*', 0, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM redirects WHERE from_path = '/portfolio_category/' AND deleted_at IS NULL);

INSERT INTO redirects (from_path, match_type, to_path, code, note, hits, enabled, created_at, updated_at)
SELECT '/portfolio_tag/', 'prefix', '/portfolio/', 301, 'theme demo taxonomy /portfolio_tag/*', 0, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM redirects WHERE from_path = '/portfolio_tag/' AND deleted_at IS NULL);

INSERT INTO redirects (from_path, match_type, to_path, code, note, hits, enabled, created_at, updated_at)
SELECT '/author/', 'prefix', '/about/', 301, 'WordPress author archives /author/*', 0, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM redirects WHERE from_path = '/author/' AND deleted_at IS NULL);

INSERT INTO redirects (from_path, match_type, to_path, code, note, hits, enabled, created_at, updated_at)
SELECT '/mlmsoftware/', 'exact', '/mlm-software-omc/', 301, 'old URL (404 on live site)', 0, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM redirects WHERE from_path = '/mlmsoftware/' AND deleted_at IS NULL);

INSERT INTO redirects (from_path, match_type, to_path, code, note, hits, enabled, created_at, updated_at)
SELECT '/contact-us/', 'exact', '/contact/', 301, 'old URL (404 on live site)', 0, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM redirects WHERE from_path = '/contact-us/' AND deleted_at IS NULL);

INSERT INTO redirects (from_path, match_type, to_path, code, note, hits, enabled, created_at, updated_at)
SELECT '/contact-us-2/', 'exact', '/contact/', 301, 'old URL (404 on live site)', 0, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM redirects WHERE from_path = '/contact-us-2/' AND deleted_at IS NULL);

INSERT INTO redirects (from_path, match_type, to_path, code, note, hits, enabled, created_at, updated_at)
SELECT '/homepage-business-solutions/', 'exact', '/', 301, 'old URL (404 on live site)', 0, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM redirects WHERE from_path = '/homepage-business-solutions/' AND deleted_at IS NULL);

INSERT INTO redirects (from_path, match_type, to_path, code, note, hits, enabled, created_at, updated_at)
SELECT '/pages/', 'exact', '/', 301, 'old URL (404 on live site)', 0, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM redirects WHERE from_path = '/pages/' AND deleted_at IS NULL);

INSERT INTO redirects (from_path, match_type, to_path, code, note, hits, enabled, created_at, updated_at)
SELECT '/comments/feed/', 'exact', '/feed/', 301, 'WordPress comments feed', 0, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM redirects WHERE from_path = '/comments/feed/' AND deleted_at IS NULL);

INSERT INTO redirects (from_path, match_type, to_path, code, note, hits, enabled, created_at, updated_at)
SELECT '/index.php/', 'exact', '/', 301, 'existing redirect on live site', 0, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM redirects WHERE from_path = '/index.php/' AND deleted_at IS NULL);

INSERT INTO redirects (from_path, match_type, to_path, code, note, hits, enabled, created_at, updated_at)
SELECT '/index.php', 'exact', '/', 301, 'same as /index.php/ (files are exempt from the trailing-slash 301)', 0, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM redirects WHERE from_path = '/index.php' AND deleted_at IS NULL);

-- /2025/05/20/mlm-software-คำตอบการจัดการสต็อก/
INSERT INTO redirects (from_path, match_type, to_path, code, note, hits, enabled, created_at, updated_at)
SELECT '/mlm-software/', 'exact', CONVERT(X'2F323032352F30352F32302F6D6C6D2D736F6674776172652DE0B884E0B8B3E0B895E0B8ADE0B89AE0B881E0B8B2E0B8A3E0B888E0B8B1E0B894E0B881E0B8B2E0B8A3E0B8AAE0B895E0B987E0B8ADE0B8812F' USING utf8mb4) COLLATE utf8mb4_bin, 301, CONVERT(X'6578697374696E67207265646972656374206F6E206C697665207369746520E28094206D757374206B656570' USING utf8mb4) COLLATE utf8mb4_bin, 0, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM redirects WHERE from_path = '/mlm-software/' AND deleted_at IS NULL);

INSERT INTO redirects (from_path, match_type, to_path, code, note, hits, enabled, created_at, updated_at)
SELECT '/solutions/', 'exact', '/services/', 301, 'parent of the industry pages has no page of its own (QUESTIONS.md)', 0, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM redirects WHERE from_path = '/solutions/' AND deleted_at IS NULL);

INSERT INTO redirects (from_path, match_type, to_path, code, note, hits, enabled, created_at, updated_at)
SELECT '/wp-login.php', 'exact', NULL, 410, CONVERT(X'576F72645072657373206C6F67696E20E2809420676F6E65' USING utf8mb4) COLLATE utf8mb4_bin, 0, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM redirects WHERE from_path = '/wp-login.php' AND deleted_at IS NULL);

INSERT INTO redirects (from_path, match_type, to_path, code, note, hits, enabled, created_at, updated_at)
SELECT '/xmlrpc.php', 'exact', NULL, 410, CONVERT(X'576F7264507265737320584D4C2D52504320E2809420676F6E65' USING utf8mb4) COLLATE utf8mb4_bin, 0, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM redirects WHERE from_path = '/xmlrpc.php' AND deleted_at IS NULL);

INSERT INTO redirects (from_path, match_type, to_path, code, note, hits, enabled, created_at, updated_at)
SELECT '/wp-admin/', 'prefix', NULL, 410, CONVERT(X'576F726450726573732061646D696E20E2809420676F6E65' USING utf8mb4) COLLATE utf8mb4_bin, 0, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM redirects WHERE from_path = '/wp-admin/' AND deleted_at IS NULL);

