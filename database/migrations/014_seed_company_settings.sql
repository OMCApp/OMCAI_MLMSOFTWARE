SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Company settings confirmed by the user (project/QUESTIONS.md, 2026-11-03):
-- registered 2009-04-03 · show three emails · lead notifications to sale@omc.co.th
-- Thai address/description are not seeded here: App\Services\SiteService falls back to config/site.php until edited in the CMS.
-- Idempotent: inserted only when the key does not exist yet (never overwrites values edited in the CMS).

INSERT INTO settings (setting_key, value, created_at, updated_at)
SELECT 'company', '{"name":"OMC MLM Software","legal_name":"","founded":"2009-04-03","phones":["099-109-5544"],"emails":["projectyak@hotmail.com","sale@omc.co.th","info@omc.co.th"],"line_id":"omc54","line_url":"https://line.me/ti/p/~omc54","facebook":"https://www.facebook.com/mlmsoftwarebyomc","address":{"th":"","en":"","zh":""},"description":{"th":"","en":"","zh":""}}', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM settings WHERE setting_key = 'company' AND deleted_at IS NULL);

INSERT INTO settings (setting_key, value, created_at, updated_at)
SELECT 'notifications', '{"lead_email":"sale@omc.co.th"}', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM settings WHERE setting_key = 'notifications' AND deleted_at IS NULL);

INSERT INTO settings (setting_key, value, created_at, updated_at)
SELECT 'stats', '{"items":[]}', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM settings WHERE setting_key = 'stats' AND deleted_at IS NULL);

INSERT INTO settings (setting_key, value, created_at, updated_at)
SELECT 'trust_logos', '{"items":[]}', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM settings WHERE setting_key = 'trust_logos' AND deleted_at IS NULL);

INSERT INTO settings (setting_key, value, created_at, updated_at)
SELECT 'tracking', '{"ga4_id":"","gtm_id":""}', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM settings WHERE setting_key = 'tracking' AND deleted_at IS NULL);
