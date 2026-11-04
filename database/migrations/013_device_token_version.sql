SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Device cookies (core/DeviceCookie.php) are signed over a per-user secret that includes this counter.
-- Bumping it ("log out all devices", password change, role change, deactivation) invalidates every device cookie
-- of that user at once. The secret also covers password_hash, role and is_active, so those changes revoke too.

ALTER TABLE users
    ADD COLUMN device_token_version INT UNSIGNED NOT NULL DEFAULT 0 AFTER must_change_password;
