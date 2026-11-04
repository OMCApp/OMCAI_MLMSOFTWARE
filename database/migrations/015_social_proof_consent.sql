SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Testimonials and case studies may only be published with the client's permission.
-- The CMS publish form requires the "permission received" checkbox and records who confirmed it and when.
-- Public queries (SocialProofService) only return rows where consent_confirmed_at IS NOT NULL.

ALTER TABLE testimonials
    ADD COLUMN consent_confirmed_at DATETIME        NULL AFTER consent_note,
    ADD COLUMN consent_confirmed_by BIGINT UNSIGNED NULL AFTER consent_confirmed_at;

ALTER TABLE case_studies
    ADD COLUMN consent_confirmed_at DATETIME        NULL AFTER results,
    ADD COLUMN consent_confirmed_by BIGINT UNSIGNED NULL AFTER consent_confirmed_at;

-- Rows published before this migration had no recorded permission: back to draft (none exist on staging — the site shows none)
UPDATE testimonials SET status = 'draft' WHERE status = 'published' AND deleted_at IS NULL;
UPDATE case_studies SET status = 'draft' WHERE status = 'published' AND deleted_at IS NULL;
