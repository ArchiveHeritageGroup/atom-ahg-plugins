-- ---------------------------------------------------------------------------
-- Moved from atom-framework/database/install.sql.
-- These tables belong to ahgSearchPlugin and are created when this plugin is installed,
-- rather than for every installation regardless of need. Ordered by dependency;
-- each table is followed by its own seed data.
-- ---------------------------------------------------------------------------

-- Table: saved_search
CREATE TABLE IF NOT EXISTS `saved_search` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `search_params` json NOT NULL,
  `entity_type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'informationobject',
  `search_url` text COLLATE utf8mb4_unicode_ci,
  `result_count` int DEFAULT NULL,
  `is_public` tinyint(1) NOT NULL DEFAULT '0',
  `is_global` tinyint(1) DEFAULT '0',
  `display_order` int DEFAULT '100',
  `share_token` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notify_on_new` tinyint(1) NOT NULL DEFAULT '0',
  `notification_frequency` VARCHAR(34) COLLATE utf8mb4_unicode_ci DEFAULT 'weekly' COMMENT 'daily, weekly, monthly',
  `last_notification_at` datetime DEFAULT NULL,
  `last_result_count` int DEFAULT NULL,
  `usage_count` int NOT NULL DEFAULT '0',
  `last_used_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `share_token` (`share_token`),
  KEY `idx_saved_search_user` (`user_id`),
  KEY `idx_saved_search_entity` (`entity_type`),
  KEY `idx_saved_search_public` (`is_public`),
  KEY `idx_saved_search_notify` (`notify_on_new`,`notification_frequency`),
  KEY `idx_global` (`is_global`,`display_order`),
  CONSTRAINT `fk_saved_search_user` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: saved_search_i18n
CREATE TABLE IF NOT EXISTS `saved_search_i18n` (
  `id` int NOT NULL,
  `culture` varchar(7) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'en',
  `name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id`,`culture`),
  CONSTRAINT `fk_saved_search_i18n` FOREIGN KEY (`id`) REFERENCES `saved_search` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: saved_search_log
CREATE TABLE IF NOT EXISTS `saved_search_log` (
  `id` int NOT NULL AUTO_INCREMENT,
  `saved_search_id` int NOT NULL,
  `user_id` int DEFAULT NULL,
  `executed_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `result_count` int DEFAULT NULL,
  `execution_time_ms` int DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_log_search` (`saved_search_id`),
  KEY `idx_log_date` (`executed_at`),
  KEY `idx_log_user` (`user_id`),
  CONSTRAINT `fk_saved_search_log` FOREIGN KEY (`saved_search_id`) REFERENCES `saved_search` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: saved_search_tag
CREATE TABLE IF NOT EXISTS `saved_search_tag` (
  `id` int NOT NULL AUTO_INCREMENT,
  `saved_search_id` int NOT NULL,
  `tag` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_search_tag` (`saved_search_id`,`tag`),
  KEY `idx_tag` (`tag`),
  CONSTRAINT `fk_saved_search_tag` FOREIGN KEY (`saved_search_id`) REFERENCES `saved_search` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: search_popular
CREATE TABLE IF NOT EXISTS `search_popular` (
  `id` int NOT NULL AUTO_INCREMENT,
  `search_hash` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `search_query` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `search_params` json DEFAULT NULL,
  `entity_type` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'informationobject',
  `search_count` int DEFAULT '1',
  `last_searched` datetime DEFAULT CURRENT_TIMESTAMP,
  `avg_results` float DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_search_popular_hash` (`search_hash`),
  KEY `idx_search_popular_count` (`search_count` DESC),
  KEY `idx_search_popular_last` (`last_searched`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: search_settings
CREATE TABLE IF NOT EXISTS `search_settings` (
  `id` int NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `setting_value` text COLLATE utf8mb4_unicode_ci,
  `description` text COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_search_settings_key` (`setting_key`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ahgSearchPlugin: No database tables required.
-- All search functionality uses Elasticsearch and existing AtoM tables.
