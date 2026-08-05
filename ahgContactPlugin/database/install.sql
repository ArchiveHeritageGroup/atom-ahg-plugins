-- ---------------------------------------------------------------------------
-- Moved from atom-framework/database/install.sql.
-- These tables belong to ahgContactPlugin and are created when this plugin is installed,
-- rather than for every installation regardless of need. Ordered by dependency;
-- each table is followed by its own seed data.
-- ---------------------------------------------------------------------------

-- Table: contact_information_extended
CREATE TABLE IF NOT EXISTS `contact_information_extended` (
  `id` int NOT NULL AUTO_INCREMENT,
  `contact_information_id` int NOT NULL,
  `title` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Mr, Mrs, Dr, Prof, etc.',
  `role` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Job title/position',
  `department` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Department/Division',
  `cell` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Mobile/Cell phone',
  `id_number` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'ID/Passport number',
  `alternative_email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Secondary email',
  `alternative_phone` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Secondary phone',
  `preferred_contact_method` VARCHAR(41) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'email, phone, cell, fax, mail',
  `language_preference` varchar(16) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Preferred communication language',
  `notes` text COLLATE utf8mb4_unicode_ci COMMENT 'Additional notes',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_contact_id` (`contact_information_id`),
  CONSTRAINT `fk_contact_info_ext` FOREIGN KEY (`contact_information_id`) REFERENCES `contact_information` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


