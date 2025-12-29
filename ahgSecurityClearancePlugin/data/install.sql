-- ahgSecurityClearancePlugin - Data Only
-- Version: 1.0.0
-- Tables are created by atom-framework/database/install.sql

-- Register plugin
('ahgSecurityClearancePlugin', 1, '1.0.0', 'security');

-- Default security classifications
INSERT IGNORE INTO security_classification (code, level, name, description, color, requires_justification, requires_approval) VALUES
('PUBLIC', 0, 'Public', 'Unrestricted access', '#28a745', 0, 0),
('INTERNAL', 1, 'Internal', 'Internal use only', '#17a2b8', 0, 0),
('CONFIDENTIAL', 2, 'Confidential', 'Limited distribution', '#ffc107', 1, 0),
('RESTRICTED', 3, 'Restricted', 'Need-to-know basis', '#fd7e14', 1, 1),
('SECRET', 4, 'Secret', 'Strictly controlled', '#dc3545', 1, 1),
('TOP_SECRET', 5, 'Top Secret', 'Maximum protection', '#6f42c1', 1, 1);

-- Default watermark types
INSERT IGNORE INTO watermark_type (code, name, position, opacity, active, sort_order) VALUES
('confidential', 'CONFIDENTIAL', 'repeat', 0.15, 1, 10),
('restricted', 'RESTRICTED', 'repeat', 0.20, 1, 20),
('draft', 'DRAFT', 'diagonal', 0.25, 1, 30),
('sample', 'SAMPLE', 'center', 0.30, 1, 40);
