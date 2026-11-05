SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Sales e-mail per lead (App\Services\LeadNotifier) — the lead row is saved first, the e-mail result is recorded here.
-- notify_status: pending (not tried yet) · sent · failed (retry from the CMS) · skipped (mail not configured)
-- Leads received before this migration were never e-mailed (MAIL_ENABLED was off) → skipped.

ALTER TABLE leads
    ADD COLUMN notify_status   VARCHAR(20)  NOT NULL DEFAULT 'pending' AFTER status,
    ADD COLUMN notify_attempts INT UNSIGNED NOT NULL DEFAULT 0 AFTER notify_status,
    ADD COLUMN notify_error    VARCHAR(255)     NULL AFTER notify_attempts,
    ADD COLUMN notified_at     DATETIME         NULL AFTER notify_error,
    ADD KEY idx_leads_notify (notify_status, created_at);

UPDATE leads SET notify_status = 'skipped' WHERE notify_status = 'pending';
