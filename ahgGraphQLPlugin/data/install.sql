-- ahgGraphQLPlugin install.sql
-- Query analytics logging table

CREATE TABLE IF NOT EXISTS ahg_graphql_log (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    api_key_id INT UNSIGNED NULL,
    operation_name VARCHAR(255) NULL,
    complexity_score INT UNSIGNED NULL,
    depth INT UNSIGNED NULL,
    execution_time_ms INT UNSIGNED NULL,
    success TINYINT(1) DEFAULT 1,
    created_at DATETIME NOT NULL,
    INDEX idx_api_key_id (api_key_id),
    INDEX idx_created_at (created_at),
    INDEX idx_operation_name (operation_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
