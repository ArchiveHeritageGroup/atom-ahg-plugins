-- Table: atom_isbn_provider
CREATE TABLE IF NOT EXISTS `atom_isbn_provider` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `slug` varchar(50) NOT NULL,
  `api_endpoint` varchar(500) NOT NULL,
  `api_key_setting` varchar(100) DEFAULT NULL,
  `priority` int DEFAULT 10,
  `enabled` tinyint(1) DEFAULT 1,
  `rate_limit_per_minute` int DEFAULT 100,
  `response_format` varchar(20) DEFAULT 'json',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Default ISBN providers
INSERT IGNORE INTO atom_isbn_provider (name, slug, api_endpoint, api_key_setting, priority, enabled, rate_limit_per_minute, response_format) VALUES
('Open Library', 'openlibrary', 'https://openlibrary.org/api/books', NULL, 10, 1, 100, 'json'),
('Google Books', 'googlebooks', 'https://www.googleapis.com/books/v1/volumes', NULL, 20, 1, 100, 'json'),
('WorldCat', 'worldcat', 'https://www.worldcat.org/webservices/catalog/content/isbn/', NULL, 30, 0, 10, 'marcxml');
