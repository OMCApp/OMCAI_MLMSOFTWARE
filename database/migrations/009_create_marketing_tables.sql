SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;

-- FAQ, social proof, leads, site settings
--
-- ‼️ testimonials / case_studies may only be "published" when they are real and approved
--    (QUESTIONS.md) — the public site hides the block entirely while nothing is published.
--
-- status keys:
--    faqs, testimonials, case_studies   draft | published
--    leads                              new | contacted | qualified | won | lost
-- leads.form_key:                       demo | quote | consult | contact | migration

CREATE TABLE IF NOT EXISTS faqs (
    id         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    question   VARCHAR(500)    NOT NULL,
    answer     TEXT            NOT NULL,
    group_key  VARCHAR(50)     NOT NULL DEFAULT 'general',
    sort       INT             NOT NULL DEFAULT 0,
    status     VARCHAR(20)     NOT NULL DEFAULT 'draft',
    created_at DATETIME        NOT NULL,
    updated_at DATETIME        NOT NULL,
    deleted_at DATETIME            NULL,
    PRIMARY KEY (id),
    KEY idx_faqs_group (group_key, status, sort),
    KEY idx_faqs_deleted_at (deleted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS testimonials (
    id           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    person_name  VARCHAR(150)    NOT NULL,
    person_title VARCHAR(150)        NULL,
    company      VARCHAR(150)        NULL,
    quote        TEXT            NOT NULL,
    photo_id     BIGINT UNSIGNED     NULL,
    consent_note VARCHAR(255)        NULL,
    status       VARCHAR(20)     NOT NULL DEFAULT 'draft',
    sort         INT             NOT NULL DEFAULT 0,
    created_at   DATETIME        NOT NULL,
    updated_at   DATETIME        NOT NULL,
    deleted_at   DATETIME            NULL,
    PRIMARY KEY (id),
    KEY idx_testimonials_status (status, sort),
    KEY idx_testimonials_photo_id (photo_id),
    KEY idx_testimonials_deleted_at (deleted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS case_studies (
    id               BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    slug             VARCHAR(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
    title            VARCHAR(255)    NOT NULL,
    client_name      VARCHAR(150)        NULL,
    industry         VARCHAR(100)        NULL,
    summary          TEXT                NULL,
    content          LONGTEXT            NULL,
    cover_image_id   BIGINT UNSIGNED     NULL,
    results          JSON                NULL,
    status           VARCHAR(20)     NOT NULL DEFAULT 'draft',
    published_at     DATETIME            NULL,
    sort             INT             NOT NULL DEFAULT 0,
    seo_title        VARCHAR(255)        NULL,
    meta_description VARCHAR(500)        NULL,
    created_at       DATETIME        NOT NULL,
    updated_at       DATETIME        NOT NULL,
    deleted_at       DATETIME            NULL,
    alive            TINYINT AS (IF(deleted_at IS NULL, 1, NULL)) STORED,
    PRIMARY KEY (id),
    UNIQUE KEY uniq_case_studies_slug (slug, alive),
    KEY idx_case_studies_status (status, sort),
    KEY idx_case_studies_cover_image_id (cover_image_id),
    KEY idx_case_studies_deleted_at (deleted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS leads (
    id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    form_key        VARCHAR(30)     NOT NULL,
    name            VARCHAR(150)    NOT NULL,
    company         VARCHAR(150)        NULL,
    business_type   VARCHAR(100)        NULL,
    phone           VARCHAR(50)         NULL,
    email           VARCHAR(190)        NULL,
    line_id         VARCHAR(100)        NULL,
    has_system      VARCHAR(20)         NULL,
    need            VARCHAR(255)        NULL,
    member_estimate VARCHAR(50)         NULL,
    message         TEXT                NULL,
    source_url      VARCHAR(500)        NULL,
    utm             JSON                NULL,
    ip              VARCHAR(45)         NULL,
    status          VARCHAR(20)     NOT NULL DEFAULT 'new',
    assigned_to     BIGINT UNSIGNED     NULL,
    notes           TEXT                NULL,
    created_at      DATETIME        NOT NULL,
    updated_at      DATETIME        NOT NULL,
    deleted_at      DATETIME            NULL,
    PRIMARY KEY (id),
    KEY idx_leads_status (status, created_at),
    KEY idx_leads_form_key (form_key, created_at),
    KEY idx_leads_assigned_to (assigned_to),
    KEY idx_leads_ip (ip, created_at),
    KEY idx_leads_deleted_at (deleted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- settings: one JSON value per key (site.brand, contact, social, verified_stats, tracking ...)
-- line_id above is the visitor's LINE ID (a text handle), not a foreign key
CREATE TABLE IF NOT EXISTS settings (
    id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    setting_key VARCHAR(100)    NOT NULL,
    value       JSON                NULL,
    created_at  DATETIME        NOT NULL,
    updated_at  DATETIME        NOT NULL,
    deleted_at  DATETIME            NULL,
    alive       TINYINT AS (IF(deleted_at IS NULL, 1, NULL)) STORED,
    PRIMARY KEY (id),
    UNIQUE KEY uniq_settings_key (setting_key, alive),
    KEY idx_settings_deleted_at (deleted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
