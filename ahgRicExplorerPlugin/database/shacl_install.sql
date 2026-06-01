-- =============================================================================
-- ahgRicExplorerPlugin - SHACL validation report storage
-- =============================================================================
-- Stores SHACL validation runs of the RiC-O graph against the RiC-O shapes
-- (tools/ric_shacl_shapes.ttl). Conventions: no ENUM, no FOREIGN KEY to core
-- tables, CREATE TABLE IF NOT EXISTS.
-- =============================================================================

--
-- Table structure for table `ric_shacl_report`
--

CREATE TABLE IF NOT EXISTS `ric_shacl_report` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `graph_uri` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `engine` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'none' COMMENT 'pyshacl, fallback, none',
  `conforms` tinyint(1) DEFAULT NULL COMMENT '1 conforms, 0 violations, NULL not run',
  `data_triples` int NOT NULL DEFAULT 0,
  `total_violations` int NOT NULL DEFAULT 0,
  `violation_count` int NOT NULL DEFAULT 0,
  `warning_count` int NOT NULL DEFAULT 0,
  `info_count` int NOT NULL DEFAULT 0,
  `statistics_json` longtext COLLATE utf8mb4_unicode_ci,
  `violations_json` longtext COLLATE utf8mb4_unicode_ci,
  `reason` varchar(1000) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `started_at` datetime DEFAULT NULL,
  `finished_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_ric_shacl_created` (`created_at`),
  KEY `idx_ric_shacl_conforms` (`conforms`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
