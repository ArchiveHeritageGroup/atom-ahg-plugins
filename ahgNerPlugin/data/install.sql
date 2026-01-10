-- AHG NER Plugin Database Tables

CREATE TABLE IF NOT EXISTS ahg_ner_extraction (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    object_id INT NOT NULL,
    backend_used VARCHAR(50) DEFAULT 'local',
    status VARCHAR(50) DEFAULT 'pending',
    entity_count INT DEFAULT 0,
    extracted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_object (object_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS ahg_ner_entity (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    extraction_id BIGINT UNSIGNED,
    object_id INT NOT NULL,
    entity_type VARCHAR(50) NOT NULL,
    entity_value VARCHAR(500) NOT NULL,
    confidence DECIMAL(5,4) DEFAULT 1.0000,
    status VARCHAR(50) DEFAULT 'pending',
    linked_actor_id INT NULL,
    reviewed_by INT NULL,
    reviewed_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_object (object_id),
    INDEX idx_type (entity_type),
    INDEX idx_status (status),
    INDEX idx_extraction (extraction_id),
    FOREIGN KEY (extraction_id) REFERENCES ahg_ner_extraction(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS ahg_ner_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Default settings
INSERT INTO ahg_ner_settings (setting_key, setting_value) VALUES
    ('backend', 'local'),
    ('api_url', 'http://192.168.0.112:5002/ner/v1'),
    ('api_key', 'ner_demo_ahg_internal_2026'),
    ('auto_extract', '0'),
    ('require_review', '1')
ON DUPLICATE KEY UPDATE setting_key = setting_key;
