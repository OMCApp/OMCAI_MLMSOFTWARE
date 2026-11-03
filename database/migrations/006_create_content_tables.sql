SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Content engine: categories, media, pages, posts, revisions (PLAN.md "ตารางข้อมูล")
--
-- ‼️ path / slug columns use utf8mb4_bin, not utf8mb4_unicode_ci
--    URLs are matched exactly. unicode_ci folds case and treats some Thai marks as ignorable,
--    so two different Thai slugs could collide in a unique key or match the wrong row.
--    Thai paths are stored DECODED (/category/ข่าวขายตรง/), never percent-encoded.
--
-- ‼️ "alive" = 1 while the row is not soft-deleted, NULL after delete.
--    Unique keys include it, so a deleted row never blocks re-creating the same path/slug
--    (NULLs never collide in a unique index). standard/rule.md "unique index ต้องเผื่อ soft delete".
--
-- status / robots / template values are English keys:
--    status   draft | published
--    robots   index | noindex
--    template default | home | product | landing | contact | blog | portfolio | policy | ...

CREATE TABLE IF NOT EXISTS categories (
    id               BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    slug             VARCHAR(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
    name             VARCHAR(255)    NOT NULL,
    description      TEXT                NULL,
    seo_title        VARCHAR(255)        NULL,
    meta_description VARCHAR(500)        NULL,
    robots           VARCHAR(20)     NOT NULL DEFAULT 'index',
    sort             INT             NOT NULL DEFAULT 0,
    created_at       DATETIME        NOT NULL,
    updated_at       DATETIME        NOT NULL,
    deleted_at       DATETIME            NULL,
    alive            TINYINT AS (IF(deleted_at IS NULL, 1, NULL)) STORED,
    PRIMARY KEY (id),
    UNIQUE KEY uniq_categories_slug (slug, alive),
    KEY idx_categories_deleted_at (deleted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- media: path is relative to public/ (e.g. /wp-content/uploads/2025/05/x.webp) so old image URLs keep working
CREATE TABLE IF NOT EXISTS media (
    id         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    path       VARCHAR(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
    mime       VARCHAR(100)    NOT NULL,
    width      INT UNSIGNED        NULL,
    height     INT UNSIGNED        NULL,
    alt        VARCHAR(500)    NOT NULL DEFAULT '',
    title      VARCHAR(255)        NULL,
    sizes      JSON                NULL,
    bytes      INT UNSIGNED        NULL,
    created_at DATETIME        NOT NULL,
    updated_at DATETIME        NOT NULL,
    deleted_at DATETIME            NULL,
    alive      TINYINT AS (IF(deleted_at IS NULL, 1, NULL)) STORED,
    PRIMARY KEY (id),
    UNIQUE KEY uniq_media_path (path, alive),
    KEY idx_media_deleted_at (deleted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- pages: path is the full public path with leading and trailing slash ("/" for home, "/compensation-plans/binary/")
-- blocks: JSON list of {"type": "<block key>", "data": {...}} — rendered by app/Views/partials/blocks/<type>.php
CREATE TABLE IF NOT EXISTS pages (
    id                 BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    parent_id          BIGINT UNSIGNED     NULL,
    path               VARCHAR(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
    title              VARCHAR(255)    NOT NULL,
    template           VARCHAR(50)     NOT NULL DEFAULT 'default',
    blocks             JSON                NULL,
    seo_title          VARCHAR(255)        NULL,
    meta_description   VARCHAR(500)        NULL,
    canonical_override VARCHAR(500)        NULL,
    robots             VARCHAR(20)     NOT NULL DEFAULT 'index',
    og_image_id        BIGINT UNSIGNED     NULL,
    status             VARCHAR(20)     NOT NULL DEFAULT 'draft',
    published_at       DATETIME            NULL,
    sort               INT             NOT NULL DEFAULT 0,
    created_at         DATETIME        NOT NULL,
    updated_at         DATETIME        NOT NULL,
    deleted_at         DATETIME            NULL,
    alive              TINYINT AS (IF(deleted_at IS NULL, 1, NULL)) STORED,
    PRIMARY KEY (id),
    UNIQUE KEY uniq_pages_path (path, alive),
    KEY idx_pages_parent_id (parent_id),
    KEY idx_pages_og_image_id (og_image_id),
    KEY idx_pages_status (status, updated_at),
    KEY idx_pages_deleted_at (deleted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- posts: public URL is /{yyyy}/{mm}/{dd}/{slug}/ built from published_at (local time, like WordPress)
-- published_date is generated from published_at so the URL date can never drift from the stored date
CREATE TABLE IF NOT EXISTS posts (
    id                 BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    slug               VARCHAR(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
    title              VARCHAR(255)    NOT NULL,
    excerpt            TEXT                NULL,
    content            LONGTEXT            NULL,
    featured_image_id  BIGINT UNSIGNED     NULL,
    category_id        BIGINT UNSIGNED     NULL,
    author_name        VARCHAR(100)        NULL,
    status             VARCHAR(20)     NOT NULL DEFAULT 'draft',
    published_at       DATETIME            NULL,
    seo_title          VARCHAR(255)        NULL,
    meta_description   VARCHAR(500)        NULL,
    canonical_override VARCHAR(500)        NULL,
    robots             VARCHAR(20)     NOT NULL DEFAULT 'index',
    og_image_id        BIGINT UNSIGNED     NULL,
    legacy_url         VARCHAR(500)        NULL,
    created_at         DATETIME        NOT NULL,
    updated_at         DATETIME        NOT NULL,
    deleted_at         DATETIME            NULL,
    published_date     DATE AS (DATE(published_at)) STORED,
    alive              TINYINT AS (IF(deleted_at IS NULL, 1, NULL)) STORED,
    PRIMARY KEY (id),
    UNIQUE KEY uniq_posts_slug_date (slug, published_date, alive),
    KEY idx_posts_category_id (category_id, status, published_at),
    KEY idx_posts_featured_image_id (featured_image_id),
    KEY idx_posts_og_image_id (og_image_id),
    KEY idx_posts_status (status, published_at),
    KEY idx_posts_deleted_at (deleted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- revisions: snapshot of a page/post before each save (Phase 3 CMS writes them)
CREATE TABLE IF NOT EXISTS revisions (
    id         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    entity     VARCHAR(50)     NOT NULL,
    entity_id  BIGINT UNSIGNED NOT NULL,
    data       JSON            NOT NULL,
    user_id    BIGINT UNSIGNED     NULL,
    created_at DATETIME        NOT NULL,
    updated_at DATETIME        NOT NULL,
    deleted_at DATETIME            NULL,
    PRIMARY KEY (id),
    KEY idx_revisions_entity_id (entity, entity_id, created_at),
    KEY idx_revisions_user_id (user_id),
    KEY idx_revisions_deleted_at (deleted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
