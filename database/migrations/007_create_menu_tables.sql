SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Site menus (header mega nav, footer columns)
-- location values are English keys: header | footer
-- A menu item links either to a page (page_id — follows the page if its path changes) or to a raw url.

CREATE TABLE IF NOT EXISTS menus (
    id         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    location   VARCHAR(30)     NOT NULL,
    name       VARCHAR(100)    NOT NULL,
    created_at DATETIME        NOT NULL,
    updated_at DATETIME        NOT NULL,
    deleted_at DATETIME            NULL,
    alive      TINYINT AS (IF(deleted_at IS NULL, 1, NULL)) STORED,
    PRIMARY KEY (id),
    UNIQUE KEY uniq_menus_location (location, alive),
    KEY idx_menus_deleted_at (deleted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS menu_items (
    id         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    menu_id    BIGINT UNSIGNED NOT NULL,
    parent_id  BIGINT UNSIGNED     NULL,
    label      VARCHAR(150)    NOT NULL,
    url        VARCHAR(500)        NULL,
    page_id    BIGINT UNSIGNED     NULL,
    sort       INT             NOT NULL DEFAULT 0,
    created_at DATETIME        NOT NULL,
    updated_at DATETIME        NOT NULL,
    deleted_at DATETIME            NULL,
    PRIMARY KEY (id),
    KEY idx_menu_items_menu_id (menu_id, parent_id, sort),
    KEY idx_menu_items_parent_id (parent_id),
    KEY idx_menu_items_page_id (page_id),
    KEY idx_menu_items_deleted_at (deleted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
