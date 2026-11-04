SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Numbered 040 (not 016–019): it needs the locale column from 020 and the migrator runs files in numeric order.
-- Translations share the slug of the original (CMS "create translation"): the URL differs only by the
-- language prefix (/2025/05/20/slug/ vs /en/2025/05/20/slug/ · /category/x/ vs /en/category/x/).
-- The unique keys therefore include locale (column from migration 020). Same slug in the same language stays unique.

ALTER TABLE posts
    DROP INDEX uniq_posts_slug_date,
    ADD UNIQUE KEY uniq_posts_slug_date (slug, published_date, locale, alive);

ALTER TABLE categories
    DROP INDEX uniq_categories_slug,
    ADD UNIQUE KEY uniq_categories_slug (slug, locale, alive);
