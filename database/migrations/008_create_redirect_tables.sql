SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Redirects managed in the CMS + log of 404s so an admin can turn a 404 into a redirect from one screen
--
-- redirects.match_type: exact  = from_path must equal the request path
--                       prefix = request path starts with from_path (old WordPress taxonomies like /author/<name>/)
-- redirects.code:       301 | 302 | 410 (410 has no to_path)
-- to_path is an internal decoded path (/contact/) or a full URL (https://...)
--
-- ‼️ hits / last_hit_at are traffic counters, updated on every matching request
--    without an audit row — same reason login_attempts is not audited (see project/NOTES.md)

CREATE TABLE IF NOT EXISTS redirects (
    id          BIGINT UNSIGNED   NOT NULL AUTO_INCREMENT,
    from_path   VARCHAR(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
    match_type  VARCHAR(10)       NOT NULL DEFAULT 'exact',
    to_path     VARCHAR(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NULL,
    code        SMALLINT UNSIGNED NOT NULL DEFAULT 301,
    note        VARCHAR(255)          NULL,
    hits        INT UNSIGNED      NOT NULL DEFAULT 0,
    last_hit_at DATETIME              NULL,
    enabled     TINYINT(1)        NOT NULL DEFAULT 1,
    created_at  DATETIME          NOT NULL,
    updated_at  DATETIME          NOT NULL,
    deleted_at  DATETIME              NULL,
    alive       TINYINT AS (IF(deleted_at IS NULL, 1, NULL)) STORED,
    PRIMARY KEY (id),
    UNIQUE KEY uniq_redirects_from_path (from_path, alive),
    KEY idx_redirects_match (match_type, enabled),
    KEY idx_redirects_deleted_at (deleted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- one row per missing path; repeated hits only bump the counter
-- deleted_at = dismissed by an admin (or resolved with a redirect); a new hit brings it back
CREATE TABLE IF NOT EXISTS not_found_log (
    id            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    path          VARCHAR(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
    referrer      VARCHAR(500)        NULL,
    user_agent    VARCHAR(255)        NULL,
    hits          INT UNSIGNED    NOT NULL DEFAULT 1,
    first_seen_at DATETIME        NOT NULL,
    last_seen_at  DATETIME        NOT NULL,
    created_at    DATETIME        NOT NULL,
    updated_at    DATETIME        NOT NULL,
    deleted_at    DATETIME            NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uniq_not_found_path (path),
    KEY idx_not_found_last_seen (last_seen_at),
    KEY idx_not_found_deleted_at (deleted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
