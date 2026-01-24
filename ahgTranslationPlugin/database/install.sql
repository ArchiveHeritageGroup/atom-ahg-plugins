-- ahgTranslationPlugin tables
CREATE TABLE IF NOT EXISTS `ahg_translation_settings` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `setting_key` VARCHAR(128) NOT NULL,
  `setting_value` TEXT NULL,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_setting_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `ahg_translation_draft` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `object_id` BIGINT UNSIGNED NOT NULL,
  `entity_type` VARCHAR(64) NOT NULL DEFAULT 'information_object',
  `field_name` VARCHAR(64) NOT NULL,
  `source_culture` VARCHAR(8) NOT NULL,
  `target_culture` VARCHAR(8) NOT NULL DEFAULT 'en',
  `source_hash` CHAR(64) NOT NULL,
  `source_text` LONGTEXT NOT NULL,
  `translated_text` LONGTEXT NOT NULL,
  `status` ENUM('draft','applied','rejected') NOT NULL DEFAULT 'draft',
  `created_by_user_id` BIGINT UNSIGNED NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `applied_at` DATETIME NULL,
  PRIMARY KEY (`id`),
  KEY `idx_object_field` (`object_id`, `field_name`),
  KEY `idx_status` (`status`),
  UNIQUE KEY `uk_draft_dedupe` (`object_id`, `field_name`, `source_culture`, `target_culture`, `source_hash`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `ahg_translation_log` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `object_id` BIGINT UNSIGNED NULL,
  `field_name` VARCHAR(64) NULL,
  `source_culture` VARCHAR(8) NULL,
  `target_culture` VARCHAR(8) NULL,
  `endpoint` VARCHAR(255) NULL,
  `http_status` INT NULL,
  `ok` TINYINT(1) NOT NULL DEFAULT 0,
  `error` TEXT NULL,
  `elapsed_ms` INT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_object` (`object_id`),
  KEY `idx_ok` (`ok`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Default settings (safe insert)
INSERT INTO `ahg_translation_settings` (`setting_key`, `setting_value`)
  SELECT 'mt.endpoint', 'http://127.0.0.1:5100/translate'
  WHERE NOT EXISTS (SELECT 1 FROM `ahg_translation_settings` WHERE `setting_key`='mt.endpoint');

INSERT INTO `ahg_translation_settings` (`setting_key`, `setting_value`)
  SELECT 'mt.timeout_seconds', '30'
  WHERE NOT EXISTS (SELECT 1 FROM `ahg_translation_settings` WHERE `setting_key`='mt.timeout_seconds');

INSERT INTO `ahg_translation_settings` (`setting_key`, `setting_value`)
  SELECT 'mt.target_culture', 'en'
  WHERE NOT EXISTS (SELECT 1 FROM `ahg_translation_settings` WHERE `setting_key`='mt.target_culture');
