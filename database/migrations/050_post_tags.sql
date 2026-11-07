SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Post tags (blog redesign 2026-11-07) — comma-separated, edited in CMS → Posts; shown as chips that search the blog.
-- The WordPress posts had no tags, so every existing post starts empty (no invented tags).

ALTER TABLE posts
    ADD COLUMN tags VARCHAR(500) NULL AFTER category_id;
