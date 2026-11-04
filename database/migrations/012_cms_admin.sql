SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Phase 3 — CMS admin
-- · users.must_change_password: forces a new password on the next login (seeded admin with the seed password)
-- · lead_notes: append-only internal notes on a lead (no update/delete route exists)
-- · menus.locale / faqs.locale: content locale (th default · en · zh) — menus are edited per locale
-- · menu_items.description / icon: the header mega menu shows a short description and an icon per link

ALTER TABLE users
    ADD COLUMN must_change_password TINYINT(1) NOT NULL DEFAULT 0 AFTER is_active;

-- Only the seeded account that still has the seed password (005_seed_initial_admin.sql) — never a changed one
UPDATE users SET must_change_password = 1
WHERE username = 'admin'
  AND password_hash = '$2y$10$UIuuRA4gZzug0xNz/xJNuOaTK/gAs6gL1Klc2kLN2baGurhl27bbe';

CREATE TABLE IF NOT EXISTS lead_notes (
    id         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    lead_id    BIGINT UNSIGNED NOT NULL,
    user_id    BIGINT UNSIGNED     NULL,
    body       TEXT            NOT NULL,
    created_at DATETIME        NOT NULL,
    updated_at DATETIME        NOT NULL,
    PRIMARY KEY (id),
    KEY idx_lead_notes_lead_id (lead_id, created_at),
    KEY idx_lead_notes_user_id (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE menus
    ADD COLUMN locale VARCHAR(10) NOT NULL DEFAULT 'th' AFTER location,
    DROP INDEX uniq_menus_location,
    ADD UNIQUE KEY uniq_menus_location_locale (location, locale, alive);

ALTER TABLE menu_items
    ADD COLUMN description VARCHAR(255) NULL AFTER label,
    ADD COLUMN icon        VARCHAR(50)  NULL AFTER description;

ALTER TABLE faqs
    ADD COLUMN locale VARCHAR(10) NOT NULL DEFAULT 'th' AFTER group_key,
    DROP INDEX idx_faqs_group,
    ADD KEY idx_faqs_group (locale, group_key, status, sort);
