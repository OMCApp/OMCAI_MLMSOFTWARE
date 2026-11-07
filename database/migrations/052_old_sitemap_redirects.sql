SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Old WordPress sitemap URLs → new sitemaps (GEO/SEO audit 2026-11-07).
-- The live site advertises /wp-sitemap.xml in robots.txt and Search Console probably has it submitted;
-- after cutover these would 404. One 301 each, no chains. Only inserted when the path has no redirect yet.

INSERT INTO redirects (from_path, match_type, to_path, code, note, hits, enabled, created_at, updated_at)
SELECT '/wp-sitemap.xml', 'exact', '/sitemap.xml', 301, 'old WordPress sitemap', 0, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM redirects WHERE from_path = '/wp-sitemap.xml' AND deleted_at IS NULL);

INSERT INTO redirects (from_path, match_type, to_path, code, note, hits, enabled, created_at, updated_at)
SELECT '/wp-sitemap-posts-post-1.xml', 'exact', '/sitemap-posts.xml', 301, 'old WordPress sitemap', 0, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM redirects WHERE from_path = '/wp-sitemap-posts-post-1.xml' AND deleted_at IS NULL);

INSERT INTO redirects (from_path, match_type, to_path, code, note, hits, enabled, created_at, updated_at)
SELECT '/wp-sitemap-posts-page-1.xml', 'exact', '/sitemap-pages.xml', 301, 'old WordPress sitemap', 0, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM redirects WHERE from_path = '/wp-sitemap-posts-page-1.xml' AND deleted_at IS NULL);

INSERT INTO redirects (from_path, match_type, to_path, code, note, hits, enabled, created_at, updated_at)
SELECT '/wp-sitemap-taxonomies-category-1.xml', 'exact', '/sitemap-categories.xml', 301, 'old WordPress sitemap', 0, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM redirects WHERE from_path = '/wp-sitemap-taxonomies-category-1.xml' AND deleted_at IS NULL);

INSERT INTO redirects (from_path, match_type, to_path, code, note, hits, enabled, created_at, updated_at)
SELECT '/wp-sitemap-posts-portfolio-1.xml', 'exact', '/sitemap.xml', 301, 'old WordPress sitemap', 0, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM redirects WHERE from_path = '/wp-sitemap-posts-portfolio-1.xml' AND deleted_at IS NULL);

INSERT INTO redirects (from_path, match_type, to_path, code, note, hits, enabled, created_at, updated_at)
SELECT '/wp-sitemap-taxonomies-portfolio_category-1.xml', 'exact', '/sitemap.xml', 301, 'old WordPress sitemap', 0, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM redirects WHERE from_path = '/wp-sitemap-taxonomies-portfolio_category-1.xml' AND deleted_at IS NULL);

INSERT INTO redirects (from_path, match_type, to_path, code, note, hits, enabled, created_at, updated_at)
SELECT '/wp-sitemap-taxonomies-portfolio_tag-1.xml', 'exact', '/sitemap.xml', 301, 'old WordPress sitemap', 0, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM redirects WHERE from_path = '/wp-sitemap-taxonomies-portfolio_tag-1.xml' AND deleted_at IS NULL);

INSERT INTO redirects (from_path, match_type, to_path, code, note, hits, enabled, created_at, updated_at)
SELECT '/wp-sitemap-users-1.xml', 'exact', '/sitemap.xml', 301, 'old WordPress sitemap', 0, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM redirects WHERE from_path = '/wp-sitemap-users-1.xml' AND deleted_at IS NULL);

INSERT INTO redirects (from_path, match_type, to_path, code, note, hits, enabled, created_at, updated_at)
SELECT '/sitemap_index.xml', 'exact', '/sitemap.xml', 301, 'old WordPress sitemap', 0, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM redirects WHERE from_path = '/sitemap_index.xml' AND deleted_at IS NULL);

