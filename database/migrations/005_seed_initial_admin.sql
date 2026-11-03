SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Initial admin account for the new CMS.
-- Inserted only when the users table is empty, so it never overrides real accounts.
-- The password was generated randomly and given to the owner once; change it after first login.
-- (The standard's 001_seed_admin_user.sql uses a password shared by every project, so it is not used here.)
INSERT INTO users (username, display_name, email, password_hash, role, locale, theme, is_active, created_at, updated_at)
SELECT 'admin', 'Administrator', NULL,
       '$2y$10$UIuuRA4gZzug0xNz/xJNuOaTK/gAs6gL1Klc2kLN2baGurhl27bbe',
       'admin', 'th', 'light', 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM users);
