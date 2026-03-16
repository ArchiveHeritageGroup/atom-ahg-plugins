-- =====================================================
-- Getty AAT Local Cache Table
-- =====================================================
-- Stores AAT terms locally for fast autocomplete.
-- Populated via: php symfony museum:aat-sync
-- =====================================================

CREATE TABLE IF NOT EXISTS `getty_aat_cache` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `aat_id` varchar(20) NOT NULL COMMENT 'AAT numeric ID e.g. 300033618',
  `uri` varchar(255) NOT NULL COMMENT 'Full Getty URI',
  `pref_label` varchar(512) NOT NULL COMMENT 'English preferred label',
  `scope_note` text COMMENT 'Definition/scope note',
  `broader_label` varchar(512) DEFAULT NULL COMMENT 'Immediate broader term label',
  `broader_id` varchar(20) DEFAULT NULL COMMENT 'Immediate broader term AAT ID',
  `category` varchar(50) NOT NULL DEFAULT 'general' COMMENT 'object_types, materials, techniques, styles_periods, general',
  `synced_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_aat_id` (`aat_id`),
  KEY `idx_category` (`category`),
  KEY `idx_pref_label` (`pref_label`(100)),
  FULLTEXT KEY `ft_label` (`pref_label`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
