-- Migration: library_usage_event + SUSHI settings
-- COUNTER R5 + SUSHI 5.0 support (issue #96)
-- Adds usage event capture, report settings, and SUSHI configuration

-- ── Usage Event table ────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS library_usage_event (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    library_item_id BIGINT UNSIGNED NULL,
    patron_id BIGINT UNSIGNED NULL,
    event_type ENUM('opac_view','link_click','ir_access','search','export') NOT NULL,
    metadata JSON DEFAULT NULL COMMENT 'e.g. {"search_terms":"...","result_position":1,"format":"pdf"}',
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    session_id VARCHAR(100) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_event_type (event_type),
    INDEX idx_item (library_item_id),
    INDEX idx_patron (patron_id),
    INDEX idx_created (created_at),
    INDEX idx_type_date (event_type, created_at),
    CONSTRAINT fk_usage_item FOREIGN KEY (library_item_id) REFERENCES library_item(id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_usage_patron FOREIGN KEY (patron_id) REFERENCES library_patron(id)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── COUNTER / SUSHI Settings table ──────────────────────────────────────────
CREATE TABLE IF NOT EXISTS library_counter_settings (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed SUSHI default keys (values set via admin UI / API)
INSERT INTO library_counter_settings (setting_key, setting_value) VALUES
    ('sushi_url',               NULL),
    ('sushi_api_key',          NULL),
    ('sushi_requestor_id',      NULL),
    ('sushi_customer_id',       NULL),
    ('sushi_requestor_name',   NULL),
    ('sushi_requestor_email',  NULL),
    ('counter_report_types',   'TR_J1,DR,PR,IR,TR_J3')
ON DUPLICATE KEY UPDATE updated_at = CURRENT_TIMESTAMP;
