-- ============================================================================
-- IIIF Validation — QC results for manifest compliance checking
-- ahgIiifPlugin migration 002
-- ============================================================================

CREATE TABLE IF NOT EXISTS iiif_validation_result (
    id INT AUTO_INCREMENT PRIMARY KEY,
    object_id INT NOT NULL,
    validation_type VARCHAR(100) NOT NULL,
    status VARCHAR(50) NOT NULL,
    details TEXT NULL,
    validated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    validated_by INT NULL,
    INDEX idx_object (object_id),
    INDEX idx_type (validation_type),
    INDEX idx_status (status),
    INDEX idx_validated (validated_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
