-- ระบบแจ้งปัญหาจากผู้ใช้
-- status และ type เก็บเป็น key ภาษาอังกฤษ แปลตอนแสดงผ่าน __()
-- ip_address จำเป็นสำหรับ rate limit ของผู้ใช้ที่ไม่ได้ล็อกอิน
SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS support_requests (
    id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id         BIGINT UNSIGNED     NULL,
    type            VARCHAR(30)     NOT NULL,
    page_url        VARCHAR(500)        NULL,
    error_reference VARCHAR(20)         NULL,
    manual_slug     VARCHAR(100)        NULL,
    message         TEXT            NOT NULL,
    user_agent      VARCHAR(500)        NULL,
    locale          VARCHAR(10)         NULL,
    theme           VARCHAR(10)         NULL,
    ip_address      VARCHAR(45)         NULL,
    attachment_path VARCHAR(500)        NULL,
    attachment_name VARCHAR(255)        NULL,
    status          VARCHAR(30)     NOT NULL DEFAULT 'new',
    admin_note      TEXT                NULL,
    created_at      DATETIME        NOT NULL,
    updated_at      DATETIME        NOT NULL,
    resolved_at     DATETIME            NULL,
    deleted_at      DATETIME            NULL,
    PRIMARY KEY (id),
    KEY idx_support_status (status, created_at),
    KEY idx_support_type (type, created_at),
    KEY idx_support_user (user_id),
    KEY idx_support_manual (manual_slug),
    KEY idx_support_deleted_at (deleted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
