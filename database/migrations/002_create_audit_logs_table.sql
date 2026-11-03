-- บันทึกว่าใครทำอะไร กับข้อมูลไหน เมื่อไหร่
-- ไม่มี updated_at และ deleted_at โดยตั้งใจ เพราะ audit log ต้องแก้และลบไม่ได้
-- ถ้าแก้ได้ก็ไม่มีความหมาย เพราะคนที่ทำผิดลบร่องรอยตัวเองได้
SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS audit_logs (
    id         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id    BIGINT UNSIGNED     NULL,
    action     VARCHAR(50)     NOT NULL,
    table_name VARCHAR(100)        NULL,
    record_id  BIGINT UNSIGNED     NULL,
    old_values JSON                NULL,
    new_values JSON                NULL,
    ip_address VARCHAR(45)         NULL,
    created_at DATETIME        NOT NULL,
    PRIMARY KEY (id),
    KEY idx_audit_user (user_id),
    KEY idx_audit_record (table_name, record_id),
    KEY idx_audit_action (action),
    KEY idx_audit_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
