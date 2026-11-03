-- ผู้ใช้ระบบ
-- locale และ theme เก็บที่นี่เพื่อให้ค่าที่ผู้ใช้เลือกคงอยู่ข้ามอุปกรณ์
SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS users (
    id            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    username      VARCHAR(100)    NOT NULL,
    display_name  VARCHAR(150)    NOT NULL,
    email         VARCHAR(190)        NULL,
    password_hash VARCHAR(255)    NOT NULL,
    role          VARCHAR(50)     NOT NULL DEFAULT 'staff',
    locale        VARCHAR(10)     NOT NULL DEFAULT 'th',
    theme         VARCHAR(10)     NOT NULL DEFAULT 'light',
    is_active     TINYINT(1)      NOT NULL DEFAULT 1,
    created_at    DATETIME        NOT NULL,
    updated_at    DATETIME        NOT NULL,
    deleted_at    DATETIME            NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uniq_users_username (username, deleted_at),
    KEY idx_users_role (role),
    KEY idx_users_deleted_at (deleted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
