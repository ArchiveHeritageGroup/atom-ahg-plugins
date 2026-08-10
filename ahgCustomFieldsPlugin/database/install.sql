-- ============================================================
-- ahgCustomFieldsPlugin Installation SQL
-- Admin-configurable custom metadata fields (EAV pattern)
-- ============================================================

-- Enablement is deliberately NOT done here.
--
-- A plugin that registers itself in atom_plugin becomes *statically enabled*,
-- and sfPluginAdminPlugin drops statically-enabled plugins from its list
-- (pluginsAction.class.php:44-46). So writing this row makes the plugin
-- invisible in the very screen an operator would use to manage it, and it
-- cannot be disabled through the interface.
--
-- Installing schema and deciding to run a plugin are separate acts. The second
-- is the operator's.

-- Version, description, category and load order live in extension.json, which
-- is what the extension manager reads. Writing them into atom_plugin from here
-- gave a second copy that drifts, and only worked at all if the operator had
-- already created the row.

-- ============================================================
-- Field definitions — admin-configurable schema
-- ============================================================
CREATE TABLE IF NOT EXISTS `custom_field_definition` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `field_key` VARCHAR(100) NOT NULL,
    `field_label` VARCHAR(255) NOT NULL,
    `field_type` VARCHAR(30) NOT NULL DEFAULT 'text'
        COMMENT 'text, textarea, date, number, boolean, dropdown, url',
    `entity_type` VARCHAR(50) NOT NULL
        COMMENT 'informationobject, actor, accession, repository, donor, function',
    `field_group` VARCHAR(100) NULL
        COMMENT 'UI section grouping label',
    `dropdown_taxonomy` VARCHAR(100) NULL
        COMMENT 'ahg_dropdown taxonomy key when field_type=dropdown',
    `is_required` TINYINT(1) DEFAULT 0,
    `is_searchable` TINYINT(1) DEFAULT 0,
    `is_visible_public` TINYINT(1) DEFAULT 1,
    `is_visible_edit` TINYINT(1) DEFAULT 1,
    `is_repeatable` TINYINT(1) DEFAULT 0,
    `default_value` VARCHAR(500) NULL,
    `help_text` VARCHAR(500) NULL,
    `validation_rule` VARCHAR(255) NULL
        COMMENT 'e.g. max:255, regex:/^[A-Z]/',
    `sort_order` INT DEFAULT 0,
    `is_active` TINYINT(1) DEFAULT 1,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_field_entity` (`field_key`, `entity_type`),
    INDEX `idx_entity_type` (`entity_type`),
    INDEX `idx_active_entity` (`is_active`, `entity_type`),
    INDEX `idx_group` (`field_group`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Field values — EAV storage
-- ============================================================
CREATE TABLE IF NOT EXISTS `custom_field_value` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `field_definition_id` BIGINT UNSIGNED NOT NULL,
    `object_id` INT NOT NULL
        COMMENT 'FK to the entity (information_object.id, actor.id, etc.)',
    `value_text` TEXT NULL,
    `value_number` DECIMAL(15,4) NULL,
    `value_date` DATE NULL,
    `value_boolean` TINYINT(1) NULL,
    `value_dropdown` VARCHAR(100) NULL
        COMMENT 'ahg_dropdown code reference',
    `sequence` INT DEFAULT 0
        COMMENT 'Ordering for repeatable fields',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_field_object` (`field_definition_id`, `object_id`),
    INDEX `idx_object` (`object_id`),
    INDEX `idx_dropdown` (`value_dropdown`),
    FOREIGN KEY (`field_definition_id`)
        REFERENCES `custom_field_definition`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
