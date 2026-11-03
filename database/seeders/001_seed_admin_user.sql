-- ข้อมูลตั้งต้นที่ต้องรันจริงบนทุกเครื่อง
-- ‼️ เปลี่ยนรหัสผ่านทันทีหลังเข้าระบบครั้งแรก — บัญชีตั้งต้นคือ support / 394950
SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;

INSERT INTO users (username, display_name, email, password_hash, role, locale, theme, is_active, created_at, updated_at)
SELECT 'support', 'Support', NULL,
       '$2y$10$EwoQjuaFtVHH1MGRWvOpDOvCH5IAH8/OTK3hWbvs0vYK/O3OoT2da',
       'admin', 'th', 'light', 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM users WHERE username = 'support');
