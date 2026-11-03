SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Page shells for every URL in project/PLAN.md "URL map" + the two existing categories + base settings
-- ‼️ All pages are draft: the public sees 404 until Phase 4 writes the content and publishes them.
--    Admins preview them with ?preview=1. Titles are working titles; Phase 4 replaces them with the real Thai copy.
-- Idempotent: inserted only when no live row has the same path / slug / key.
-- Thai values are written as hex — the decoded text is in the comment above the row.

INSERT INTO pages (path, title, template, robots, status, sort, created_at, updated_at)
SELECT '/', 'Home', 'home', 'index', 'draft', 0, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM pages WHERE path = '/' AND deleted_at IS NULL);

INSERT INTO pages (path, title, template, robots, status, sort, created_at, updated_at)
SELECT '/mlm-software-omc/', 'MLM Software by OMC', 'product', 'index', 'draft', 10, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM pages WHERE path = '/mlm-software-omc/' AND deleted_at IS NULL);

INSERT INTO pages (path, title, template, robots, status, sort, created_at, updated_at)
SELECT '/features/', 'Features', 'features', 'index', 'draft', 20, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM pages WHERE path = '/features/' AND deleted_at IS NULL);

INSERT INTO pages (path, title, template, robots, status, sort, created_at, updated_at)
SELECT '/compensation-plans/', 'Compensation Plans', 'plans', 'index', 'draft', 30, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM pages WHERE path = '/compensation-plans/' AND deleted_at IS NULL);

INSERT INTO pages (path, title, template, robots, status, sort, created_at, updated_at)
SELECT '/compensation-plans/binary/', 'Binary Plan', 'plan', 'index', 'draft', 31, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM pages WHERE path = '/compensation-plans/binary/' AND deleted_at IS NULL);

INSERT INTO pages (path, title, template, robots, status, sort, created_at, updated_at)
SELECT '/compensation-plans/unilevel/', 'Unilevel Plan', 'plan', 'index', 'draft', 32, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM pages WHERE path = '/compensation-plans/unilevel/' AND deleted_at IS NULL);

INSERT INTO pages (path, title, template, robots, status, sort, created_at, updated_at)
SELECT '/compensation-plans/matrix/', 'Matrix Plan', 'plan', 'index', 'draft', 33, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM pages WHERE path = '/compensation-plans/matrix/' AND deleted_at IS NULL);

INSERT INTO pages (path, title, template, robots, status, sort, created_at, updated_at)
SELECT '/compensation-plans/board/', 'Board Plan', 'plan', 'index', 'draft', 34, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM pages WHERE path = '/compensation-plans/board/' AND deleted_at IS NULL);

INSERT INTO pages (path, title, template, robots, status, sort, created_at, updated_at)
SELECT '/compensation-plans/stair-step/', 'Stair Step Plan', 'plan', 'index', 'draft', 35, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM pages WHERE path = '/compensation-plans/stair-step/' AND deleted_at IS NULL);

INSERT INTO pages (path, title, template, robots, status, sort, created_at, updated_at)
SELECT '/compensation-plans/hybrid/', 'Hybrid Plan', 'plan', 'index', 'draft', 36, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM pages WHERE path = '/compensation-plans/hybrid/' AND deleted_at IS NULL);

INSERT INTO pages (path, title, template, robots, status, sort, created_at, updated_at)
SELECT '/services/', 'Solutions', 'services', 'index', 'draft', 40, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM pages WHERE path = '/services/' AND deleted_at IS NULL);

INSERT INTO pages (path, title, template, robots, status, sort, created_at, updated_at)
SELECT '/solutions/supplements/', 'Supplements', 'industry', 'index', 'draft', 41, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM pages WHERE path = '/solutions/supplements/' AND deleted_at IS NULL);

INSERT INTO pages (path, title, template, robots, status, sort, created_at, updated_at)
SELECT '/solutions/cosmetics/', 'Cosmetics', 'industry', 'index', 'draft', 42, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM pages WHERE path = '/solutions/cosmetics/' AND deleted_at IS NULL);

INSERT INTO pages (path, title, template, robots, status, sort, created_at, updated_at)
SELECT '/solutions/health-wellness/', 'Health & Wellness', 'industry', 'index', 'draft', 43, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM pages WHERE path = '/solutions/health-wellness/' AND deleted_at IS NULL);

INSERT INTO pages (path, title, template, robots, status, sort, created_at, updated_at)
SELECT '/solutions/distributor/', 'Distributor', 'industry', 'index', 'draft', 44, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM pages WHERE path = '/solutions/distributor/' AND deleted_at IS NULL);

INSERT INTO pages (path, title, template, robots, status, sort, created_at, updated_at)
SELECT '/solutions/franchise/', 'Franchise', 'industry', 'index', 'draft', 45, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM pages WHERE path = '/solutions/franchise/' AND deleted_at IS NULL);

INSERT INTO pages (path, title, template, robots, status, sort, created_at, updated_at)
SELECT '/solutions/international/', 'International', 'industry', 'index', 'draft', 46, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM pages WHERE path = '/solutions/international/' AND deleted_at IS NULL);

INSERT INTO pages (path, title, template, robots, status, sort, created_at, updated_at)
SELECT '/solutions/custom/', 'Custom', 'industry', 'index', 'draft', 47, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM pages WHERE path = '/solutions/custom/' AND deleted_at IS NULL);

INSERT INTO pages (path, title, template, robots, status, sort, created_at, updated_at)
SELECT '/ocpb-mlm/', 'OCPB License', 'default', 'index', 'draft', 50, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM pages WHERE path = '/ocpb-mlm/' AND deleted_at IS NULL);

INSERT INTO pages (path, title, template, robots, status, sort, created_at, updated_at)
SELECT '/ocpb-mlm2/', 'OCPB License 2', 'default', 'index', 'draft', 51, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM pages WHERE path = '/ocpb-mlm2/' AND deleted_at IS NULL);

INSERT INTO pages (path, title, template, robots, status, sort, created_at, updated_at)
SELECT '/pricing/', 'Pricing', 'pricing', 'index', 'draft', 60, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM pages WHERE path = '/pricing/' AND deleted_at IS NULL);

INSERT INTO pages (path, title, template, robots, status, sort, created_at, updated_at)
SELECT '/demo/', 'Request a Demo', 'demo', 'index', 'draft', 61, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM pages WHERE path = '/demo/' AND deleted_at IS NULL);

INSERT INTO pages (path, title, template, robots, status, sort, created_at, updated_at)
SELECT '/about/', 'About OMC', 'about', 'index', 'draft', 70, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM pages WHERE path = '/about/' AND deleted_at IS NULL);

INSERT INTO pages (path, title, template, robots, status, sort, created_at, updated_at)
SELECT '/contact/', 'Contact', 'contact', 'index', 'draft', 71, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM pages WHERE path = '/contact/' AND deleted_at IS NULL);

INSERT INTO pages (path, title, template, robots, status, sort, created_at, updated_at)
SELECT '/faq/', 'FAQ', 'faq', 'index', 'draft', 72, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM pages WHERE path = '/faq/' AND deleted_at IS NULL);

INSERT INTO pages (path, title, template, robots, status, sort, created_at, updated_at)
SELECT '/support/', 'Support', 'support', 'index', 'draft', 73, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM pages WHERE path = '/support/' AND deleted_at IS NULL);

INSERT INTO pages (path, title, template, robots, status, sort, created_at, updated_at)
SELECT '/blog/', 'Blog', 'blog', 'index', 'draft', 80, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM pages WHERE path = '/blog/' AND deleted_at IS NULL);

INSERT INTO pages (path, title, template, robots, status, sort, created_at, updated_at)
SELECT '/portfolio/', 'Case Studies', 'portfolio', 'index', 'draft', 81, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM pages WHERE path = '/portfolio/' AND deleted_at IS NULL);

-- /นโยบายความเป็นส่วนตัวข/  ·  นโยบายความเป็นส่วนตัวของข้อมูล (Privacy Policy)
INSERT INTO pages (path, title, template, robots, status, sort, created_at, updated_at)
SELECT CONVERT(X'2FE0B899E0B982E0B8A2E0B89AE0B8B2E0B8A2E0B884E0B8A7E0B8B2E0B8A1E0B980E0B89BE0B987E0B899E0B8AAE0B988E0B8A7E0B899E0B895E0B8B1E0B8A7E0B8822F' USING utf8mb4) COLLATE utf8mb4_bin, CONVERT(X'E0B899E0B982E0B8A2E0B89AE0B8B2E0B8A2E0B884E0B8A7E0B8B2E0B8A1E0B980E0B89BE0B987E0B899E0B8AAE0B988E0B8A7E0B899E0B895E0B8B1E0B8A7E0B882E0B8ADE0B887E0B882E0B989E0B8ADE0B8A1E0B8B9E0B8A520285072697661637920506F6C69637929' USING utf8mb4) COLLATE utf8mb4_bin, 'policy', 'index', 'draft', 90, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM pages WHERE path = CONVERT(X'2FE0B899E0B982E0B8A2E0B89AE0B8B2E0B8A2E0B884E0B8A7E0B8B2E0B8A1E0B980E0B89BE0B987E0B899E0B8AAE0B988E0B8A7E0B899E0B895E0B8B1E0B8A7E0B8822F' USING utf8mb4) COLLATE utf8mb4_bin AND deleted_at IS NULL);

-- /นโยบายการใช้คุกกี้-cookies-policy/  ·  นโยบายการใช้คุกกี้ (Cookies Policy)
INSERT INTO pages (path, title, template, robots, status, sort, created_at, updated_at)
SELECT CONVERT(X'2FE0B899E0B982E0B8A2E0B89AE0B8B2E0B8A2E0B881E0B8B2E0B8A3E0B983E0B88AE0B989E0B884E0B8B8E0B881E0B881E0B8B5E0B9892D636F6F6B6965732D706F6C6963792F' USING utf8mb4) COLLATE utf8mb4_bin, CONVERT(X'E0B899E0B982E0B8A2E0B89AE0B8B2E0B8A2E0B881E0B8B2E0B8A3E0B983E0B88AE0B989E0B884E0B8B8E0B881E0B881E0B8B5E0B9892028436F6F6B69657320506F6C69637929' USING utf8mb4) COLLATE utf8mb4_bin, 'policy', 'index', 'draft', 91, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM pages WHERE path = CONVERT(X'2FE0B899E0B982E0B8A2E0B89AE0B8B2E0B8A2E0B881E0B8B2E0B8A3E0B983E0B88AE0B989E0B884E0B8B8E0B881E0B881E0B8B5E0B9892D636F6F6B6965732D706F6C6963792F' USING utf8mb4) COLLATE utf8mb4_bin AND deleted_at IS NULL);

INSERT INTO pages (path, title, template, robots, status, sort, created_at, updated_at)
SELECT '/mlm-program/', 'MLM Program', 'landing', 'index', 'draft', 100, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM pages WHERE path = '/mlm-program/' AND deleted_at IS NULL);

INSERT INTO pages (path, title, template, robots, status, sort, created_at, updated_at)
SELECT '/mlm-system/', 'MLM System', 'landing', 'index', 'draft', 101, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM pages WHERE path = '/mlm-system/' AND deleted_at IS NULL);

INSERT INTO pages (path, title, template, robots, status, sort, created_at, updated_at)
SELECT '/direct-sales-software/', 'Direct Sales Software', 'landing', 'index', 'draft', 102, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM pages WHERE path = '/direct-sales-software/' AND deleted_at IS NULL);

INSERT INTO pages (path, title, template, robots, status, sort, created_at, updated_at)
SELECT '/direct-sales-system/', 'Direct Sales System', 'landing', 'index', 'draft', 103, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM pages WHERE path = '/direct-sales-system/' AND deleted_at IS NULL);

INSERT INTO pages (path, title, template, robots, status, sort, created_at, updated_at)
SELECT '/mlm-development/', 'MLM Development', 'landing', 'index', 'draft', 104, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM pages WHERE path = '/mlm-development/' AND deleted_at IS NULL);

INSERT INTO pages (path, title, template, robots, status, sort, created_at, updated_at)
SELECT '/mlm-programming/', 'MLM Programming', 'landing', 'index', 'draft', 105, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM pages WHERE path = '/mlm-programming/' AND deleted_at IS NULL);

INSERT INTO pages (path, title, template, robots, status, sort, created_at, updated_at)
SELECT '/mlm-software-thailand/', 'MLM Software Thailand', 'landing', 'index', 'draft', 106, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM pages WHERE path = '/mlm-software-thailand/' AND deleted_at IS NULL);

INSERT INTO pages (path, title, template, robots, status, sort, created_at, updated_at)
SELECT '/mlm-migration/', 'MLM System Migration', 'landing', 'index', 'draft', 107, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM pages WHERE path = '/mlm-migration/' AND deleted_at IS NULL);

INSERT INTO pages (path, title, template, robots, status, sort, created_at, updated_at)
SELECT '/commission-system/', 'Commission System', 'landing', 'index', 'draft', 108, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM pages WHERE path = '/commission-system/' AND deleted_at IS NULL);

INSERT INTO pages (path, title, template, robots, status, sort, created_at, updated_at)
SELECT '/member-system/', 'Member System', 'landing', 'index', 'draft', 109, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM pages WHERE path = '/member-system/' AND deleted_at IS NULL);

-- Parents drive breadcrumbs: plans sit under /compensation-plans/, industries under /services/ (no /solutions/ page)
UPDATE pages child
JOIN pages parent ON parent.path = '/compensation-plans/' AND parent.deleted_at IS NULL
SET child.parent_id = parent.id
WHERE child.parent_id IS NULL AND child.deleted_at IS NULL
  AND child.path IN ('/compensation-plans/binary/', '/compensation-plans/unilevel/', '/compensation-plans/matrix/', '/compensation-plans/board/', '/compensation-plans/stair-step/', '/compensation-plans/hybrid/');

UPDATE pages child
JOIN pages parent ON parent.path = '/services/' AND parent.deleted_at IS NULL
SET child.parent_id = parent.id
WHERE child.parent_id IS NULL AND child.deleted_at IS NULL
  AND child.path IN ('/solutions/supplements/', '/solutions/cosmetics/', '/solutions/health-wellness/', '/solutions/distributor/', '/solutions/franchise/', '/solutions/international/', '/solutions/custom/');

-- Categories of the live site (slug = name, exactly as in docs/seo/baseline-2026-11-03/pages.csv)
-- ข่าวขายตรง
INSERT INTO categories (slug, name, robots, sort, created_at, updated_at)
SELECT CONVERT(X'E0B882E0B988E0B8B2E0B8A7E0B882E0B8B2E0B8A2E0B895E0B8A3E0B887' USING utf8mb4) COLLATE utf8mb4_bin, CONVERT(X'E0B882E0B988E0B8B2E0B8A7E0B882E0B8B2E0B8A2E0B895E0B8A3E0B887' USING utf8mb4) COLLATE utf8mb4_bin, 'index', 10, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM categories WHERE slug = CONVERT(X'E0B882E0B988E0B8B2E0B8A7E0B882E0B8B2E0B8A2E0B895E0B8A3E0B887' USING utf8mb4) COLLATE utf8mb4_bin AND deleted_at IS NULL);

-- ประกัน
INSERT INTO categories (slug, name, robots, sort, created_at, updated_at)
SELECT CONVERT(X'E0B89BE0B8A3E0B8B0E0B881E0B8B1E0B899' USING utf8mb4) COLLATE utf8mb4_bin, CONVERT(X'E0B89BE0B8A3E0B8B0E0B881E0B8B1E0B899' USING utf8mb4) COLLATE utf8mb4_bin, 'index', 20, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM categories WHERE slug = CONVERT(X'E0B89BE0B8A3E0B8B0E0B881E0B8B1E0B899' USING utf8mb4) COLLATE utf8mb4_bin AND deleted_at IS NULL);

-- Base settings — only values that are already known; everything else stays null until confirmed (QUESTIONS.md)
INSERT INTO settings (setting_key, value, created_at, updated_at)
SELECT 'site', '{"brand":"OMC MLM Software","locale":"th_TH","default_og_image":null}', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM settings WHERE setting_key = 'site' AND deleted_at IS NULL);

INSERT INTO settings (setting_key, value, created_at, updated_at)
SELECT 'organization', '{"name":"OMC MLM Software","legal_name":null,"logo":null,"telephone":null,"email":null,"same_as":[]}', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM settings WHERE setting_key = 'organization' AND deleted_at IS NULL);

INSERT INTO settings (setting_key, value, created_at, updated_at)
SELECT 'blog', '{"per_page":10,"feed_items":20}', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM settings WHERE setting_key = 'blog' AND deleted_at IS NULL);

