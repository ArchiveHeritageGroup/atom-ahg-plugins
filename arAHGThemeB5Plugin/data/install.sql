-- AHG Theme B5 Plugin - Install SQL
-- Tables specific to theme functionality
-- Version: 1.0.0
-- 
-- NOTE: Run atom-framework/database/install.sql FIRST for core tables

-- Level of Description Sector mapping (shared by all sector plugins)
-- Each sector plugin populates its own entries
CREATE TABLE IF NOT EXISTS level_of_description_sector (
    id INT AUTO_INCREMENT PRIMARY KEY,
    term_id INT NOT NULL,
    sector VARCHAR(50) NOT NULL,
    display_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_term_sector (term_id, sector),
    INDEX idx_sector (sector),
    INDEX idx_term_id (term_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Register theme plugin
INSERT IGNORE INTO atom_plugin (name, is_enabled, load_order, version) VALUES
('arAHGThemeB5Plugin', 1, 5, '1.0.0');
