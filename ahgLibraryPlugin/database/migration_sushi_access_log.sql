-- Migration: library_sushi_access_log
-- Tracks all SUSHI harvest requests (inbound from vendors + outbound to providers)
-- Powers the SUSHI settings admin UI access-log tab

CREATE TABLE IF NOT EXISTS library_sushi_access_log (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    requestor_id    VARCHAR(255) NULL COMMENT 'SUSHI X-Requestor-Id header',
    customer_id     VARCHAR(255) NULL COMMENT 'SUSHI X-Customer-Id header',
    report_type     VARCHAR(20)  NOT NULL COMMENT 'TR_J1, DR, PR, IR, TR_J3',
    period_begin    DATE         NULL,
    period_end      DATE         NULL,
    status_code     SMALLINT    NULL COMMENT 'HTTP status code returned',
    records_returned INT UNSIGNED DEFAULT 0 COMMENT 'Number of usage records in response',
    ip_address      VARCHAR(45) NULL,
    user_agent      VARCHAR(500) NULL,
    created_at      TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_sushi_log_report   (report_type),
    INDEX idx_sushi_log_customer (customer_id),
    INDEX idx_sushi_log_created  (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;