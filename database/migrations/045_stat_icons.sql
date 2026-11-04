SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Icons for the two verified stats (mockup: icon beside each figure). Only fills an empty icon — CMS edits win.
UPDATE settings
SET value = JSON_SET(value, '$.items[0].icon', 'calendar'), updated_at = NOW()
WHERE setting_key = 'stats' AND deleted_at IS NULL
  AND JSON_UNQUOTE(JSON_EXTRACT(value, '$.items[0].value')) = '{founded_year}'
  AND JSON_UNQUOTE(JSON_EXTRACT(value, '$.items[0].icon')) = '';

UPDATE settings
SET value = JSON_SET(value, '$.items[1].icon', 'shield'), updated_at = NOW()
WHERE setting_key = 'stats' AND deleted_at IS NULL
  AND JSON_UNQUOTE(JSON_EXTRACT(value, '$.items[1].value')) = '{years}'
  AND JSON_UNQUOTE(JSON_EXTRACT(value, '$.items[1].icon')) = '';
