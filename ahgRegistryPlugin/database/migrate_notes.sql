-- =====================================================
-- Registry Notes (universal comments/notes)
-- Date: 2026-03-07
-- =====================================================

CREATE TABLE IF NOT EXISTS `registry_note` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `entity_type` varchar(50) NOT NULL COMMENT 'standard, vendor, erd, software, institution, group',
  `entity_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned DEFAULT NULL COMMENT 'FK to registry_user.id',
  `user_name` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `is_pinned` tinyint(1) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_note_entity` (`entity_type`, `entity_id`),
  KEY `idx_note_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
