-- ============================================================
-- TripoSR - Image to 3D Model Generation
-- Database Schema for ahg3DModelPlugin
-- ============================================================

-- Job tracking table
CREATE TABLE IF NOT EXISTS `triposr_jobs` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `object_id` INT NULL COMMENT 'Link to information_object.id',
    `model_id` INT NULL COMMENT 'Link to object_3d_model.id after import',
    `input_image` VARCHAR(500) NOT NULL COMMENT 'Path to input image',
    `output_model` VARCHAR(500) NULL COMMENT 'Path to generated 3D model',
    `output_format` ENUM('glb', 'obj') DEFAULT 'glb',
    `status` ENUM('pending', 'processing', 'completed', 'failed') DEFAULT 'pending',
    `processing_mode` ENUM('local', 'remote') DEFAULT 'local',
    `processing_time` DECIMAL(10,2) NULL COMMENT 'Time in seconds',
    `error_message` TEXT NULL,
    `options` JSON NULL COMMENT 'Generation options used',
    `created_by` INT NULL COMMENT 'User who initiated',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `started_at` DATETIME NULL,
    `completed_at` DATETIME NULL,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_object_id (`object_id`),
    INDEX idx_model_id (`model_id`),
    INDEX idx_status (`status`),
    INDEX idx_created_at (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Default settings for TripoSR
INSERT INTO `viewer_3d_settings` (`setting_key`, `setting_value`, `setting_type`, `description`) VALUES
('triposr_enabled', '1', 'boolean', 'Enable TripoSR image-to-3D conversion'),
('triposr_api_url', 'http://127.0.0.1:5050', 'string', 'Local TripoSR API server URL'),
('triposr_mode', 'local', 'string', 'Processing mode: local or remote'),
('triposr_remote_url', '', 'string', 'Remote GPU server URL'),
('triposr_remote_api_key', '', 'string', 'API key for remote GPU server'),
('triposr_timeout', '300', 'integer', 'Request timeout in seconds'),
('triposr_remove_bg', '1', 'boolean', 'Remove background from input image'),
('triposr_foreground_ratio', '0.85', 'string', 'Foreground ratio after background removal'),
('triposr_mc_resolution', '256', 'integer', 'Marching cubes resolution (higher = more detail)'),
('triposr_bake_texture', '0', 'boolean', 'Bake texture into model (exports as OBJ)')
ON DUPLICATE KEY UPDATE `description` = VALUES(`description`);
