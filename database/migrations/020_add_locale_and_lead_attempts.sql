SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Phase 4 — multilingual public site (th default at the root, en under /en/, zh under /zh/) + lead form throttle
--
-- locale             th | en | zh   (key, not a display name — standard rule 11)
-- translation_group  shared id of every language version of the same page / post / category
--                    (NULL = no translations yet). Paths stay unique per row: /features/ · /en/features/ · /zh/features/
--
-- ‼️ Column names `locale` / `translation_group` are a contract with the CMS (Phase 3) — do not rename.
-- Existing rows become Thai, each in its own new group.

ALTER TABLE pages
    ADD COLUMN locale            VARCHAR(5)  NOT NULL DEFAULT 'th' AFTER path,
    ADD COLUMN translation_group CHAR(36)        NULL AFTER locale,
    ADD KEY idx_pages_translation_group (translation_group),
    ADD KEY idx_pages_locale (locale, status);

ALTER TABLE posts
    ADD COLUMN locale            VARCHAR(5)  NOT NULL DEFAULT 'th' AFTER slug,
    ADD COLUMN translation_group CHAR(36)        NULL AFTER locale,
    ADD KEY idx_posts_translation_group (translation_group),
    ADD KEY idx_posts_locale (locale, status, published_at);

ALTER TABLE categories
    ADD COLUMN locale            VARCHAR(5)  NOT NULL DEFAULT 'th' AFTER slug,
    ADD COLUMN translation_group CHAR(36)        NULL AFTER locale,
    ADD KEY idx_categories_translation_group (translation_group);

UPDATE pages      SET translation_group = UUID() WHERE translation_group IS NULL;
UPDATE posts      SET translation_group = UUID() WHERE translation_group IS NULL;
UPDATE categories SET translation_group = UUID() WHERE translation_group IS NULL;

-- Lead tracking
--   locale        language of the page the form was sent from (en/zh visitors get a reply in their language)
--   landing_page  path of the page where the form was submitted (source_url keeps the full URL incl. query)
--   referrer      external referrer of the visit (scheme + host + path, no query string)
--   utm           JSON {"first": {source, medium, campaign, term, content}, "current": {...}}
--                 first = first-touch UTM from the 30-day cookie · current = UTM on the submitting page
ALTER TABLE leads
    ADD COLUMN locale       VARCHAR(5)   NOT NULL DEFAULT 'th' AFTER form_key,
    ADD COLUMN landing_page VARCHAR(500)     NULL AFTER source_url,
    ADD COLUMN referrer     VARCHAR(500)     NULL AFTER landing_page;

-- Every POST /lead/ attempt (valid or not) — rate limit per IP, same idea as login_attempts.
-- Counter table written on every request → not audited (NOTES.md, like login_attempts / redirects.hits).
CREATE TABLE IF NOT EXISTS lead_attempts (
    id           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    ip_address   VARCHAR(45)     NOT NULL,
    outcome      VARCHAR(20)     NOT NULL,
    attempted_at DATETIME        NOT NULL,
    PRIMARY KEY (id),
    KEY idx_lead_attempts_ip (ip_address, attempted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
