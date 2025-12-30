-- ahgResearchPlugin - Data Only
-- Version: 1.0.0
-- Tables are created by atom-framework/database/install.sql


-- Default reading rooms
INSERT IGNORE INTO research_reading_room (name, code, description, capacity, is_active) VALUES
('Main Reading Room', 'MAIN', 'Primary research reading room', 20, 1),
('Special Collections', 'SPEC', 'Special collections reading room', 8, 1),
('Digital Research Lab', 'DIGI', 'Computer workstations for digital research', 12, 1);
