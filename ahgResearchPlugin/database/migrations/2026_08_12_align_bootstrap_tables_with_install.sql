-- ============================================================
-- Bring the ten bootstrap tables up to the shape install.sql defines
--
-- migrations/001_create_research_tables.php created ten tables with a much
-- narrower shape than database/install.sql declares for the same names - 119
-- columns narrower in total. Whichever ran first won, and CREATE TABLE IF NOT
-- EXISTS meant the other silently did nothing:
--
--   * migrations first -> stubs, and install.sql skips the tables entirely. It
--     then ABORTS partway through, at the first statement that references a
--     column the stub does not have ("Unknown column 'orcid_id' in
--     'research_researcher'"), leaving the remaining ~77 tables uncreated.
--   * install.sql first -> correct tables, and 001 is a no-op.
--
-- 001 has been made a no-op so this cannot recur. This repairs instances that
-- already ran it, by adding every column install.sql declares and the stub
-- lacks.
--
-- Generated from install.sql, so the definitions cannot drift from it again.
-- Additive and idempotent by inspection: each column is guarded against
-- information_schema. No column is dropped, no row is touched.
-- ============================================================
--
-- Each statement is guarded on BOTH the table and the column. The table guard is
-- not decoration: on an instance where install.sql has not run at all, these
-- tables do not exist, and an unguarded ALTER aborts the whole file at the first
-- one - which is the same failure this migration exists to repair.

SET @db := DATABASE();

-- ---- research_annotation: 11 column(s) ----
SET @sql := (SELECT IF(
  (SELECT COUNT(*) FROM information_schema.tables
     WHERE table_schema = @db AND table_name = 'research_annotation') = 1
  AND (SELECT COUNT(*) FROM information_schema.columns
     WHERE table_schema = @db AND table_name = 'research_annotation' AND column_name = 'project_id') = 0,
  'ALTER TABLE `research_annotation` ADD COLUMN `project_id` int DEFAULT NULL',
  'DO 0'));
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @sql := (SELECT IF(
  (SELECT COUNT(*) FROM information_schema.tables
     WHERE table_schema = @db AND table_name = 'research_annotation') = 1
  AND (SELECT COUNT(*) FROM information_schema.columns
     WHERE table_schema = @db AND table_name = 'research_annotation' AND column_name = 'entity_type') = 0,
  'ALTER TABLE `research_annotation` ADD COLUMN `entity_type` varchar(50) DEFAULT ''information_object''',
  'DO 0'));
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @sql := (SELECT IF(
  (SELECT COUNT(*) FROM information_schema.tables
     WHERE table_schema = @db AND table_name = 'research_annotation') = 1
  AND (SELECT COUNT(*) FROM information_schema.columns
     WHERE table_schema = @db AND table_name = 'research_annotation' AND column_name = 'collection_id') = 0,
  'ALTER TABLE `research_annotation` ADD COLUMN `collection_id` int DEFAULT NULL',
  'DO 0'));
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @sql := (SELECT IF(
  (SELECT COUNT(*) FROM information_schema.tables
     WHERE table_schema = @db AND table_name = 'research_annotation') = 1
  AND (SELECT COUNT(*) FROM information_schema.columns
     WHERE table_schema = @db AND table_name = 'research_annotation' AND column_name = 'digital_object_id') = 0,
  'ALTER TABLE `research_annotation` ADD COLUMN `digital_object_id` int DEFAULT NULL',
  'DO 0'));
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @sql := (SELECT IF(
  (SELECT COUNT(*) FROM information_schema.tables
     WHERE table_schema = @db AND table_name = 'research_annotation') = 1
  AND (SELECT COUNT(*) FROM information_schema.columns
     WHERE table_schema = @db AND table_name = 'research_annotation' AND column_name = 'title') = 0,
  'ALTER TABLE `research_annotation` ADD COLUMN `title` varchar(255) DEFAULT NULL',
  'DO 0'));
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @sql := (SELECT IF(
  (SELECT COUNT(*) FROM information_schema.tables
     WHERE table_schema = @db AND table_name = 'research_annotation') = 1
  AND (SELECT COUNT(*) FROM information_schema.columns
     WHERE table_schema = @db AND table_name = 'research_annotation' AND column_name = 'content_format') = 0,
  'ALTER TABLE `research_annotation` ADD COLUMN `content_format` varchar(20) DEFAULT ''text'' COMMENT ''text, html''',
  'DO 0'));
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @sql := (SELECT IF(
  (SELECT COUNT(*) FROM information_schema.tables
     WHERE table_schema = @db AND table_name = 'research_annotation') = 1
  AND (SELECT COUNT(*) FROM information_schema.columns
     WHERE table_schema = @db AND table_name = 'research_annotation' AND column_name = 'target_selector') = 0,
  'ALTER TABLE `research_annotation` ADD COLUMN `target_selector` text',
  'DO 0'));
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @sql := (SELECT IF(
  (SELECT COUNT(*) FROM information_schema.tables
     WHERE table_schema = @db AND table_name = 'research_annotation') = 1
  AND (SELECT COUNT(*) FROM information_schema.columns
     WHERE table_schema = @db AND table_name = 'research_annotation' AND column_name = 'canvas_id') = 0,
  'ALTER TABLE `research_annotation` ADD COLUMN `canvas_id` varchar(500) DEFAULT NULL',
  'DO 0'));
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @sql := (SELECT IF(
  (SELECT COUNT(*) FROM information_schema.tables
     WHERE table_schema = @db AND table_name = 'research_annotation') = 1
  AND (SELECT COUNT(*) FROM information_schema.columns
     WHERE table_schema = @db AND table_name = 'research_annotation' AND column_name = 'iiif_annotation_id') = 0,
  'ALTER TABLE `research_annotation` ADD COLUMN `iiif_annotation_id` varchar(255) DEFAULT NULL',
  'DO 0'));
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @sql := (SELECT IF(
  (SELECT COUNT(*) FROM information_schema.tables
     WHERE table_schema = @db AND table_name = 'research_annotation') = 1
  AND (SELECT COUNT(*) FROM information_schema.columns
     WHERE table_schema = @db AND table_name = 'research_annotation' AND column_name = 'tags') = 0,
  'ALTER TABLE `research_annotation` ADD COLUMN `tags` varchar(500) DEFAULT NULL',
  'DO 0'));
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @sql := (SELECT IF(
  (SELECT COUNT(*) FROM information_schema.tables
     WHERE table_schema = @db AND table_name = 'research_annotation') = 1
  AND (SELECT COUNT(*) FROM information_schema.columns
     WHERE table_schema = @db AND table_name = 'research_annotation' AND column_name = 'visibility') = 0,
  'ALTER TABLE `research_annotation` ADD COLUMN `visibility` varchar(31) DEFAULT ''private'' COMMENT ''private, shared, public''',
  'DO 0'));
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;


-- ---- research_booking: 9 column(s) ----
SET @sql := (SELECT IF(
  (SELECT COUNT(*) FROM information_schema.tables
     WHERE table_schema = @db AND table_name = 'research_booking') = 1
  AND (SELECT COUNT(*) FROM information_schema.columns
     WHERE table_schema = @db AND table_name = 'research_booking' AND column_name = 'project_id') = 0,
  'ALTER TABLE `research_booking` ADD COLUMN `project_id` int DEFAULT NULL',
  'DO 0'));
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @sql := (SELECT IF(
  (SELECT COUNT(*) FROM information_schema.tables
     WHERE table_schema = @db AND table_name = 'research_booking') = 1
  AND (SELECT COUNT(*) FROM information_schema.columns
     WHERE table_schema = @db AND table_name = 'research_booking' AND column_name = 'cancelled_at') = 0,
  'ALTER TABLE `research_booking` ADD COLUMN `cancelled_at` datetime DEFAULT NULL',
  'DO 0'));
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @sql := (SELECT IF(
  (SELECT COUNT(*) FROM information_schema.tables
     WHERE table_schema = @db AND table_name = 'research_booking') = 1
  AND (SELECT COUNT(*) FROM information_schema.columns
     WHERE table_schema = @db AND table_name = 'research_booking' AND column_name = 'cancellation_reason') = 0,
  'ALTER TABLE `research_booking` ADD COLUMN `cancellation_reason` text',
  'DO 0'));
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @sql := (SELECT IF(
  (SELECT COUNT(*) FROM information_schema.tables
     WHERE table_schema = @db AND table_name = 'research_booking') = 1
  AND (SELECT COUNT(*) FROM information_schema.columns
     WHERE table_schema = @db AND table_name = 'research_booking' AND column_name = 'checked_in_at') = 0,
  'ALTER TABLE `research_booking` ADD COLUMN `checked_in_at` datetime DEFAULT NULL',
  'DO 0'));
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @sql := (SELECT IF(
  (SELECT COUNT(*) FROM information_schema.tables
     WHERE table_schema = @db AND table_name = 'research_booking') = 1
  AND (SELECT COUNT(*) FROM information_schema.columns
     WHERE table_schema = @db AND table_name = 'research_booking' AND column_name = 'checked_out_at') = 0,
  'ALTER TABLE `research_booking` ADD COLUMN `checked_out_at` datetime DEFAULT NULL',
  'DO 0'));
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @sql := (SELECT IF(
  (SELECT COUNT(*) FROM information_schema.tables
     WHERE table_schema = @db AND table_name = 'research_booking') = 1
  AND (SELECT COUNT(*) FROM information_schema.columns
     WHERE table_schema = @db AND table_name = 'research_booking' AND column_name = 'is_walk_in') = 0,
  'ALTER TABLE `research_booking` ADD COLUMN `is_walk_in` tinyint(1) DEFAULT ''0''',
  'DO 0'));
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @sql := (SELECT IF(
  (SELECT COUNT(*) FROM information_schema.tables
     WHERE table_schema = @db AND table_name = 'research_booking') = 1
  AND (SELECT COUNT(*) FROM information_schema.columns
     WHERE table_schema = @db AND table_name = 'research_booking' AND column_name = 'rules_acknowledged') = 0,
  'ALTER TABLE `research_booking` ADD COLUMN `rules_acknowledged` tinyint(1) DEFAULT ''0''',
  'DO 0'));
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @sql := (SELECT IF(
  (SELECT COUNT(*) FROM information_schema.tables
     WHERE table_schema = @db AND table_name = 'research_booking') = 1
  AND (SELECT COUNT(*) FROM information_schema.columns
     WHERE table_schema = @db AND table_name = 'research_booking' AND column_name = 'rules_acknowledged_at') = 0,
  'ALTER TABLE `research_booking` ADD COLUMN `rules_acknowledged_at` datetime DEFAULT NULL',
  'DO 0'));
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @sql := (SELECT IF(
  (SELECT COUNT(*) FROM information_schema.tables
     WHERE table_schema = @db AND table_name = 'research_booking') = 1
  AND (SELECT COUNT(*) FROM information_schema.columns
     WHERE table_schema = @db AND table_name = 'research_booking' AND column_name = 'seat_id') = 0,
  'ALTER TABLE `research_booking` ADD COLUMN `seat_id` int DEFAULT NULL',
  'DO 0'));
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;


-- ---- research_citation_log: 5 column(s) ----
SET @sql := (SELECT IF(
  (SELECT COUNT(*) FROM information_schema.tables
     WHERE table_schema = @db AND table_name = 'research_citation_log') = 1
  AND (SELECT COUNT(*) FROM information_schema.columns
     WHERE table_schema = @db AND table_name = 'research_citation_log' AND column_name = 'citation_style') = 0,
  'ALTER TABLE `research_citation_log` ADD COLUMN `citation_style` VARCHAR(50) NOT NULL',
  'DO 0'));
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @sql := (SELECT IF(
  (SELECT COUNT(*) FROM information_schema.tables
     WHERE table_schema = @db AND table_name = 'research_citation_log') = 1
  AND (SELECT COUNT(*) FROM information_schema.columns
     WHERE table_schema = @db AND table_name = 'research_citation_log' AND column_name = 'export_format') = 0,
  'ALTER TABLE `research_citation_log` ADD COLUMN `export_format` VARCHAR(20)',
  'DO 0'));
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @sql := (SELECT IF(
  (SELECT COUNT(*) FROM information_schema.tables
     WHERE table_schema = @db AND table_name = 'research_citation_log') = 1
  AND (SELECT COUNT(*) FROM information_schema.columns
     WHERE table_schema = @db AND table_name = 'research_citation_log' AND column_name = 'session_id') = 0,
  'ALTER TABLE `research_citation_log` ADD COLUMN `session_id` VARCHAR(64)',
  'DO 0'));
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @sql := (SELECT IF(
  (SELECT COUNT(*) FROM information_schema.tables
     WHERE table_schema = @db AND table_name = 'research_citation_log') = 1
  AND (SELECT COUNT(*) FROM information_schema.columns
     WHERE table_schema = @db AND table_name = 'research_citation_log' AND column_name = 'ip_address') = 0,
  'ALTER TABLE `research_citation_log` ADD COLUMN `ip_address` VARCHAR(45)',
  'DO 0'));
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @sql := (SELECT IF(
  (SELECT COUNT(*) FROM information_schema.tables
     WHERE table_schema = @db AND table_name = 'research_citation_log') = 1
  AND (SELECT COUNT(*) FROM information_schema.columns
     WHERE table_schema = @db AND table_name = 'research_citation_log' AND column_name = 'created_at') = 0,
  'ALTER TABLE `research_citation_log` ADD COLUMN `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP',
  'DO 0'));
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;


-- ---- research_collection: 2 column(s) ----
SET @sql := (SELECT IF(
  (SELECT COUNT(*) FROM information_schema.tables
     WHERE table_schema = @db AND table_name = 'research_collection') = 1
  AND (SELECT COUNT(*) FROM information_schema.columns
     WHERE table_schema = @db AND table_name = 'research_collection' AND column_name = 'project_id') = 0,
  'ALTER TABLE `research_collection` ADD COLUMN `project_id` INT DEFAULT NULL',
  'DO 0'));
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @sql := (SELECT IF(
  (SELECT COUNT(*) FROM information_schema.tables
     WHERE table_schema = @db AND table_name = 'research_collection') = 1
  AND (SELECT COUNT(*) FROM information_schema.columns
     WHERE table_schema = @db AND table_name = 'research_collection' AND column_name = 'share_token') = 0,
  'ALTER TABLE `research_collection` ADD COLUMN `share_token` VARCHAR(64) DEFAULT NULL',
  'DO 0'));
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;


-- ---- research_collection_item: 6 column(s) ----
SET @sql := (SELECT IF(
  (SELECT COUNT(*) FROM information_schema.tables
     WHERE table_schema = @db AND table_name = 'research_collection_item') = 1
  AND (SELECT COUNT(*) FROM information_schema.columns
     WHERE table_schema = @db AND table_name = 'research_collection_item' AND column_name = 'object_type') = 0,
  'ALTER TABLE `research_collection_item` ADD COLUMN `object_type` varchar(50) DEFAULT ''information_object''',
  'DO 0'));
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @sql := (SELECT IF(
  (SELECT COUNT(*) FROM information_schema.tables
     WHERE table_schema = @db AND table_name = 'research_collection_item') = 1
  AND (SELECT COUNT(*) FROM information_schema.columns
     WHERE table_schema = @db AND table_name = 'research_collection_item' AND column_name = 'culture') = 0,
  'ALTER TABLE `research_collection_item` ADD COLUMN `culture` varchar(10) DEFAULT NULL',
  'DO 0'));
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @sql := (SELECT IF(
  (SELECT COUNT(*) FROM information_schema.tables
     WHERE table_schema = @db AND table_name = 'research_collection_item') = 1
  AND (SELECT COUNT(*) FROM information_schema.columns
     WHERE table_schema = @db AND table_name = 'research_collection_item' AND column_name = 'external_uri') = 0,
  'ALTER TABLE `research_collection_item` ADD COLUMN `external_uri` varchar(1000) DEFAULT NULL',
  'DO 0'));
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @sql := (SELECT IF(
  (SELECT COUNT(*) FROM information_schema.tables
     WHERE table_schema = @db AND table_name = 'research_collection_item') = 1
  AND (SELECT COUNT(*) FROM information_schema.columns
     WHERE table_schema = @db AND table_name = 'research_collection_item' AND column_name = 'tags') = 0,
  'ALTER TABLE `research_collection_item` ADD COLUMN `tags` varchar(500) DEFAULT NULL',
  'DO 0'));
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @sql := (SELECT IF(
  (SELECT COUNT(*) FROM information_schema.tables
     WHERE table_schema = @db AND table_name = 'research_collection_item') = 1
  AND (SELECT COUNT(*) FROM information_schema.columns
     WHERE table_schema = @db AND table_name = 'research_collection_item' AND column_name = 'reference_code') = 0,
  'ALTER TABLE `research_collection_item` ADD COLUMN `reference_code` varchar(255) DEFAULT NULL',
  'DO 0'));
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @sql := (SELECT IF(
  (SELECT COUNT(*) FROM information_schema.tables
     WHERE table_schema = @db AND table_name = 'research_collection_item') = 1
  AND (SELECT COUNT(*) FROM information_schema.columns
     WHERE table_schema = @db AND table_name = 'research_collection_item' AND column_name = 'created_at') = 0,
  'ALTER TABLE `research_collection_item` ADD COLUMN `created_at` datetime DEFAULT CURRENT_TIMESTAMP',
  'DO 0'));
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;


-- ---- research_material_request: 30 column(s) ----
SET @sql := (SELECT IF(
  (SELECT COUNT(*) FROM information_schema.tables
     WHERE table_schema = @db AND table_name = 'research_material_request') = 1
  AND (SELECT COUNT(*) FROM information_schema.columns
     WHERE table_schema = @db AND table_name = 'research_material_request' AND column_name = 'quantity') = 0,
  'ALTER TABLE `research_material_request` ADD COLUMN `quantity` int DEFAULT ''1''',
  'DO 0'));
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @sql := (SELECT IF(
  (SELECT COUNT(*) FROM information_schema.tables
     WHERE table_schema = @db AND table_name = 'research_material_request') = 1
  AND (SELECT COUNT(*) FROM information_schema.columns
     WHERE table_schema = @db AND table_name = 'research_material_request' AND column_name = 'request_type') = 0,
  'ALTER TABLE `research_material_request` ADD COLUMN `request_type` varchar(54) DEFAULT ''reading_room'' COMMENT ''reading_room, reproduction, loan, remote_access''',
  'DO 0'));
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @sql := (SELECT IF(
  (SELECT COUNT(*) FROM information_schema.tables
     WHERE table_schema = @db AND table_name = 'research_material_request') = 1
  AND (SELECT COUNT(*) FROM information_schema.columns
     WHERE table_schema = @db AND table_name = 'research_material_request' AND column_name = 'priority') = 0,
  'ALTER TABLE `research_material_request` ADD COLUMN `priority` varchar(26) DEFAULT ''normal'' COMMENT ''normal, high, rush''',
  'DO 0'));
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @sql := (SELECT IF(
  (SELECT COUNT(*) FROM information_schema.tables
     WHERE table_schema = @db AND table_name = 'research_material_request') = 1
  AND (SELECT COUNT(*) FROM information_schema.columns
     WHERE table_schema = @db AND table_name = 'research_material_request' AND column_name = 'handling_instructions') = 0,
  'ALTER TABLE `research_material_request` ADD COLUMN `handling_instructions` text',
  'DO 0'));
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @sql := (SELECT IF(
  (SELECT COUNT(*) FROM information_schema.tables
     WHERE table_schema = @db AND table_name = 'research_material_request') = 1
  AND (SELECT COUNT(*) FROM information_schema.columns
     WHERE table_schema = @db AND table_name = 'research_material_request' AND column_name = 'location_code') = 0,
  'ALTER TABLE `research_material_request` ADD COLUMN `location_code` varchar(100) DEFAULT NULL',
  'DO 0'));
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @sql := (SELECT IF(
  (SELECT COUNT(*) FROM information_schema.tables
     WHERE table_schema = @db AND table_name = 'research_material_request') = 1
  AND (SELECT COUNT(*) FROM information_schema.columns
     WHERE table_schema = @db AND table_name = 'research_material_request' AND column_name = 'shelf_location') = 0,
  'ALTER TABLE `research_material_request` ADD COLUMN `shelf_location` varchar(255) DEFAULT NULL',
  'DO 0'));
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @sql := (SELECT IF(
  (SELECT COUNT(*) FROM information_schema.tables
     WHERE table_schema = @db AND table_name = 'research_material_request') = 1
  AND (SELECT COUNT(*) FROM information_schema.columns
     WHERE table_schema = @db AND table_name = 'research_material_request' AND column_name = 'location_current') = 0,
  'ALTER TABLE `research_material_request` ADD COLUMN `location_current` varchar(255) DEFAULT NULL COMMENT ''Current location: storage/transit/reading_room/returned''',
  'DO 0'));
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @sql := (SELECT IF(
  (SELECT COUNT(*) FROM information_schema.tables
     WHERE table_schema = @db AND table_name = 'research_material_request') = 1
  AND (SELECT COUNT(*) FROM information_schema.columns
     WHERE table_schema = @db AND table_name = 'research_material_request' AND column_name = 'retrieval_scheduled_for') = 0,
  'ALTER TABLE `research_material_request` ADD COLUMN `retrieval_scheduled_for` datetime DEFAULT NULL',
  'DO 0'));
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @sql := (SELECT IF(
  (SELECT COUNT(*) FROM information_schema.tables
     WHERE table_schema = @db AND table_name = 'research_material_request') = 1
  AND (SELECT COUNT(*) FROM information_schema.columns
     WHERE table_schema = @db AND table_name = 'research_material_request' AND column_name = 'queue_id') = 0,
  'ALTER TABLE `research_material_request` ADD COLUMN `queue_id` int DEFAULT NULL',
  'DO 0'));
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @sql := (SELECT IF(
  (SELECT COUNT(*) FROM information_schema.tables
     WHERE table_schema = @db AND table_name = 'research_material_request') = 1
  AND (SELECT COUNT(*) FROM information_schema.columns
     WHERE table_schema = @db AND table_name = 'research_material_request' AND column_name = 'box_number') = 0,
  'ALTER TABLE `research_material_request` ADD COLUMN `box_number` varchar(50) DEFAULT NULL',
  'DO 0'));
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @sql := (SELECT IF(
  (SELECT COUNT(*) FROM information_schema.tables
     WHERE table_schema = @db AND table_name = 'research_material_request') = 1
  AND (SELECT COUNT(*) FROM information_schema.columns
     WHERE table_schema = @db AND table_name = 'research_material_request' AND column_name = 'folder_number') = 0,
  'ALTER TABLE `research_material_request` ADD COLUMN `folder_number` varchar(50) DEFAULT NULL',
  'DO 0'));
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @sql := (SELECT IF(
  (SELECT COUNT(*) FROM information_schema.tables
     WHERE table_schema = @db AND table_name = 'research_material_request') = 1
  AND (SELECT COUNT(*) FROM information_schema.columns
     WHERE table_schema = @db AND table_name = 'research_material_request' AND column_name = 'curatorial_approval_required') = 0,
  'ALTER TABLE `research_material_request` ADD COLUMN `curatorial_approval_required` tinyint(1) DEFAULT ''0''',
  'DO 0'));
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @sql := (SELECT IF(
  (SELECT COUNT(*) FROM information_schema.tables
     WHERE table_schema = @db AND table_name = 'research_material_request') = 1
  AND (SELECT COUNT(*) FROM information_schema.columns
     WHERE table_schema = @db AND table_name = 'research_material_request' AND column_name = 'curatorial_approved_by') = 0,
  'ALTER TABLE `research_material_request` ADD COLUMN `curatorial_approved_by` int DEFAULT NULL',
  'DO 0'));
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @sql := (SELECT IF(
  (SELECT COUNT(*) FROM information_schema.tables
     WHERE table_schema = @db AND table_name = 'research_material_request') = 1
  AND (SELECT COUNT(*) FROM information_schema.columns
     WHERE table_schema = @db AND table_name = 'research_material_request' AND column_name = 'curatorial_approved_at') = 0,
  'ALTER TABLE `research_material_request` ADD COLUMN `curatorial_approved_at` datetime DEFAULT NULL',
  'DO 0'));
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @sql := (SELECT IF(
  (SELECT COUNT(*) FROM information_schema.tables
     WHERE table_schema = @db AND table_name = 'research_material_request') = 1
  AND (SELECT COUNT(*) FROM information_schema.columns
     WHERE table_schema = @db AND table_name = 'research_material_request' AND column_name = 'paging_slip_printed') = 0,
  'ALTER TABLE `research_material_request` ADD COLUMN `paging_slip_printed` tinyint(1) DEFAULT ''0''',
  'DO 0'));
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @sql := (SELECT IF(
  (SELECT COUNT(*) FROM information_schema.tables
     WHERE table_schema = @db AND table_name = 'research_material_request') = 1
  AND (SELECT COUNT(*) FROM information_schema.columns
     WHERE table_schema = @db AND table_name = 'research_material_request' AND column_name = 'call_slip_printed_at') = 0,
  'ALTER TABLE `research_material_request` ADD COLUMN `call_slip_printed_at` datetime DEFAULT NULL',
  'DO 0'));
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @sql := (SELECT IF(
  (SELECT COUNT(*) FROM information_schema.tables
     WHERE table_schema = @db AND table_name = 'research_material_request') = 1
  AND (SELECT COUNT(*) FROM information_schema.columns
     WHERE table_schema = @db AND table_name = 'research_material_request' AND column_name = 'call_slip_printed_by') = 0,
  'ALTER TABLE `research_material_request` ADD COLUMN `call_slip_printed_by` int DEFAULT NULL',
  'DO 0'));
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @sql := (SELECT IF(
  (SELECT COUNT(*) FROM information_schema.tables
     WHERE table_schema = @db AND table_name = 'research_material_request') = 1
  AND (SELECT COUNT(*) FROM information_schema.columns
     WHERE table_schema = @db AND table_name = 'research_material_request' AND column_name = 'retrieved_by') = 0,
  'ALTER TABLE `research_material_request` ADD COLUMN `retrieved_by` int DEFAULT NULL',
  'DO 0'));
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @sql := (SELECT IF(
  (SELECT COUNT(*) FROM information_schema.tables
     WHERE table_schema = @db AND table_name = 'research_material_request') = 1
  AND (SELECT COUNT(*) FROM information_schema.columns
     WHERE table_schema = @db AND table_name = 'research_material_request' AND column_name = 'retrieved_at') = 0,
  'ALTER TABLE `research_material_request` ADD COLUMN `retrieved_at` datetime DEFAULT NULL',
  'DO 0'));
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @sql := (SELECT IF(
  (SELECT COUNT(*) FROM information_schema.tables
     WHERE table_schema = @db AND table_name = 'research_material_request') = 1
  AND (SELECT COUNT(*) FROM information_schema.columns
     WHERE table_schema = @db AND table_name = 'research_material_request' AND column_name = 'condition_notes') = 0,
  'ALTER TABLE `research_material_request` ADD COLUMN `condition_notes` text',
  'DO 0'));
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @sql := (SELECT IF(
  (SELECT COUNT(*) FROM information_schema.tables
     WHERE table_schema = @db AND table_name = 'research_material_request') = 1
  AND (SELECT COUNT(*) FROM information_schema.columns
     WHERE table_schema = @db AND table_name = 'research_material_request' AND column_name = 'sla_due_date') = 0,
  'ALTER TABLE `research_material_request` ADD COLUMN `sla_due_date` date DEFAULT NULL',
  'DO 0'));
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @sql := (SELECT IF(
  (SELECT COUNT(*) FROM information_schema.tables
     WHERE table_schema = @db AND table_name = 'research_material_request') = 1
  AND (SELECT COUNT(*) FROM information_schema.columns
     WHERE table_schema = @db AND table_name = 'research_material_request' AND column_name = 'assigned_to') = 0,
  'ALTER TABLE `research_material_request` ADD COLUMN `assigned_to` int DEFAULT NULL',
  'DO 0'));
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @sql := (SELECT IF(
  (SELECT COUNT(*) FROM information_schema.tables
     WHERE table_schema = @db AND table_name = 'research_material_request') = 1
  AND (SELECT COUNT(*) FROM information_schema.columns
     WHERE table_schema = @db AND table_name = 'research_material_request' AND column_name = 'triage_status') = 0,
  'ALTER TABLE `research_material_request` ADD COLUMN `triage_status` varchar(50) DEFAULT NULL',
  'DO 0'));
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @sql := (SELECT IF(
  (SELECT COUNT(*) FROM information_schema.tables
     WHERE table_schema = @db AND table_name = 'research_material_request') = 1
  AND (SELECT COUNT(*) FROM information_schema.columns
     WHERE table_schema = @db AND table_name = 'research_material_request' AND column_name = 'triage_by') = 0,
  'ALTER TABLE `research_material_request` ADD COLUMN `triage_by` int DEFAULT NULL',
  'DO 0'));
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @sql := (SELECT IF(
  (SELECT COUNT(*) FROM information_schema.tables
     WHERE table_schema = @db AND table_name = 'research_material_request') = 1
  AND (SELECT COUNT(*) FROM information_schema.columns
     WHERE table_schema = @db AND table_name = 'research_material_request' AND column_name = 'checkout_confirmed_at') = 0,
  'ALTER TABLE `research_material_request` ADD COLUMN `checkout_confirmed_at` datetime DEFAULT NULL',
  'DO 0'));
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @sql := (SELECT IF(
  (SELECT COUNT(*) FROM information_schema.tables
     WHERE table_schema = @db AND table_name = 'research_material_request') = 1
  AND (SELECT COUNT(*) FROM information_schema.columns
     WHERE table_schema = @db AND table_name = 'research_material_request' AND column_name = 'checkout_confirmed_by') = 0,
  'ALTER TABLE `research_material_request` ADD COLUMN `checkout_confirmed_by` int DEFAULT NULL',
  'DO 0'));
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @sql := (SELECT IF(
  (SELECT COUNT(*) FROM information_schema.tables
     WHERE table_schema = @db AND table_name = 'research_material_request') = 1
  AND (SELECT COUNT(*) FROM information_schema.columns
     WHERE table_schema = @db AND table_name = 'research_material_request' AND column_name = 'return_condition') = 0,
  'ALTER TABLE `research_material_request` ADD COLUMN `return_condition` varchar(50) DEFAULT NULL',
  'DO 0'));
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @sql := (SELECT IF(
  (SELECT COUNT(*) FROM information_schema.tables
     WHERE table_schema = @db AND table_name = 'research_material_request') = 1
  AND (SELECT COUNT(*) FROM information_schema.columns
     WHERE table_schema = @db AND table_name = 'research_material_request' AND column_name = 'return_verified_by') = 0,
  'ALTER TABLE `research_material_request` ADD COLUMN `return_verified_by` int DEFAULT NULL',
  'DO 0'));
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @sql := (SELECT IF(
  (SELECT COUNT(*) FROM information_schema.tables
     WHERE table_schema = @db AND table_name = 'research_material_request') = 1
  AND (SELECT COUNT(*) FROM information_schema.columns
     WHERE table_schema = @db AND table_name = 'research_material_request' AND column_name = 'return_verified_at') = 0,
  'ALTER TABLE `research_material_request` ADD COLUMN `return_verified_at` datetime DEFAULT NULL',
  'DO 0'));
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @sql := (SELECT IF(
  (SELECT COUNT(*) FROM information_schema.tables
     WHERE table_schema = @db AND table_name = 'research_material_request') = 1
  AND (SELECT COUNT(*) FROM information_schema.columns
     WHERE table_schema = @db AND table_name = 'research_material_request' AND column_name = 'triage_at') = 0,
  'ALTER TABLE `research_material_request` ADD COLUMN `triage_at` datetime DEFAULT NULL',
  'DO 0'));
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;


-- ---- research_password_reset: 1 column(s) ----
SET @sql := (SELECT IF(
  (SELECT COUNT(*) FROM information_schema.tables
     WHERE table_schema = @db AND table_name = 'research_password_reset') = 1
  AND (SELECT COUNT(*) FROM information_schema.columns
     WHERE table_schema = @db AND table_name = 'research_password_reset' AND column_name = 'user_id') = 0,
  'ALTER TABLE `research_password_reset` ADD COLUMN `user_id` INT NOT NULL',
  'DO 0'));
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;


-- ---- research_reading_room: 10 column(s) ----
SET @sql := (SELECT IF(
  (SELECT COUNT(*) FROM information_schema.tables
     WHERE table_schema = @db AND table_name = 'research_reading_room') = 1
  AND (SELECT COUNT(*) FROM information_schema.columns
     WHERE table_schema = @db AND table_name = 'research_reading_room' AND column_name = 'code') = 0,
  'ALTER TABLE `research_reading_room` ADD COLUMN `code` varchar(20) DEFAULT NULL',
  'DO 0'));
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @sql := (SELECT IF(
  (SELECT COUNT(*) FROM information_schema.tables
     WHERE table_schema = @db AND table_name = 'research_reading_room') = 1
  AND (SELECT COUNT(*) FROM information_schema.columns
     WHERE table_schema = @db AND table_name = 'research_reading_room' AND column_name = 'amenities') = 0,
  'ALTER TABLE `research_reading_room` ADD COLUMN `amenities` text',
  'DO 0'));
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @sql := (SELECT IF(
  (SELECT COUNT(*) FROM information_schema.tables
     WHERE table_schema = @db AND table_name = 'research_reading_room') = 1
  AND (SELECT COUNT(*) FROM information_schema.columns
     WHERE table_schema = @db AND table_name = 'research_reading_room' AND column_name = 'has_seat_management') = 0,
  'ALTER TABLE `research_reading_room` ADD COLUMN `has_seat_management` tinyint(1) DEFAULT ''0'' COMMENT ''Enable individual seat tracking''',
  'DO 0'));
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @sql := (SELECT IF(
  (SELECT COUNT(*) FROM information_schema.tables
     WHERE table_schema = @db AND table_name = 'research_reading_room') = 1
  AND (SELECT COUNT(*) FROM information_schema.columns
     WHERE table_schema = @db AND table_name = 'research_reading_room' AND column_name = 'walk_ins_allowed') = 0,
  'ALTER TABLE `research_reading_room` ADD COLUMN `walk_ins_allowed` tinyint(1) DEFAULT ''1''',
  'DO 0'));
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @sql := (SELECT IF(
  (SELECT COUNT(*) FROM information_schema.tables
     WHERE table_schema = @db AND table_name = 'research_reading_room') = 1
  AND (SELECT COUNT(*) FROM information_schema.columns
     WHERE table_schema = @db AND table_name = 'research_reading_room' AND column_name = 'walk_in_capacity') = 0,
  'ALTER TABLE `research_reading_room` ADD COLUMN `walk_in_capacity` int DEFAULT ''5''',
  'DO 0'));
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @sql := (SELECT IF(
  (SELECT COUNT(*) FROM information_schema.tables
     WHERE table_schema = @db AND table_name = 'research_reading_room') = 1
  AND (SELECT COUNT(*) FROM information_schema.columns
     WHERE table_schema = @db AND table_name = 'research_reading_room' AND column_name = 'floor_plan_path') = 0,
  'ALTER TABLE `research_reading_room` ADD COLUMN `floor_plan_path` varchar(500) DEFAULT NULL',
  'DO 0'));
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @sql := (SELECT IF(
  (SELECT COUNT(*) FROM information_schema.tables
     WHERE table_schema = @db AND table_name = 'research_reading_room') = 1
  AND (SELECT COUNT(*) FROM information_schema.columns
     WHERE table_schema = @db AND table_name = 'research_reading_room' AND column_name = 'operating_hours') = 0,
  'ALTER TABLE `research_reading_room` ADD COLUMN `operating_hours` text',
  'DO 0'));
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @sql := (SELECT IF(
  (SELECT COUNT(*) FROM information_schema.tables
     WHERE table_schema = @db AND table_name = 'research_reading_room') = 1
  AND (SELECT COUNT(*) FROM information_schema.columns
     WHERE table_schema = @db AND table_name = 'research_reading_room' AND column_name = 'advance_booking_days') = 0,
  'ALTER TABLE `research_reading_room` ADD COLUMN `advance_booking_days` int DEFAULT ''14''',
  'DO 0'));
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @sql := (SELECT IF(
  (SELECT COUNT(*) FROM information_schema.tables
     WHERE table_schema = @db AND table_name = 'research_reading_room') = 1
  AND (SELECT COUNT(*) FROM information_schema.columns
     WHERE table_schema = @db AND table_name = 'research_reading_room' AND column_name = 'max_booking_hours') = 0,
  'ALTER TABLE `research_reading_room` ADD COLUMN `max_booking_hours` int DEFAULT ''4''',
  'DO 0'));
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @sql := (SELECT IF(
  (SELECT COUNT(*) FROM information_schema.tables
     WHERE table_schema = @db AND table_name = 'research_reading_room') = 1
  AND (SELECT COUNT(*) FROM information_schema.columns
     WHERE table_schema = @db AND table_name = 'research_reading_room' AND column_name = 'cancellation_hours') = 0,
  'ALTER TABLE `research_reading_room` ADD COLUMN `cancellation_hours` int DEFAULT ''24''',
  'DO 0'));
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;


-- ---- research_researcher: 29 column(s) ----
SET @sql := (SELECT IF(
  (SELECT COUNT(*) FROM information_schema.tables
     WHERE table_schema = @db AND table_name = 'research_researcher') = 1
  AND (SELECT COUNT(*) FROM information_schema.columns
     WHERE table_schema = @db AND table_name = 'research_researcher' AND column_name = 'title') = 0,
  'ALTER TABLE `research_researcher` ADD COLUMN `title` varchar(50) DEFAULT NULL',
  'DO 0'));
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @sql := (SELECT IF(
  (SELECT COUNT(*) FROM information_schema.tables
     WHERE table_schema = @db AND table_name = 'research_researcher') = 1
  AND (SELECT COUNT(*) FROM information_schema.columns
     WHERE table_schema = @db AND table_name = 'research_researcher' AND column_name = 'affiliation_type') = 0,
  'ALTER TABLE `research_researcher` ADD COLUMN `affiliation_type` varchar(63) DEFAULT ''independent'' COMMENT ''academic, government, private, independent, student, other''',
  'DO 0'));
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @sql := (SELECT IF(
  (SELECT COUNT(*) FROM information_schema.tables
     WHERE table_schema = @db AND table_name = 'research_researcher') = 1
  AND (SELECT COUNT(*) FROM information_schema.columns
     WHERE table_schema = @db AND table_name = 'research_researcher' AND column_name = 'institution_id') = 0,
  'ALTER TABLE `research_researcher` ADD COLUMN `institution_id` int DEFAULT NULL',
  'DO 0'));
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @sql := (SELECT IF(
  (SELECT COUNT(*) FROM information_schema.tables
     WHERE table_schema = @db AND table_name = 'research_researcher') = 1
  AND (SELECT COUNT(*) FROM information_schema.columns
     WHERE table_schema = @db AND table_name = 'research_researcher' AND column_name = 'student_id') = 0,
  'ALTER TABLE `research_researcher` ADD COLUMN `student_id` varchar(100) DEFAULT NULL',
  'DO 0'));
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @sql := (SELECT IF(
  (SELECT COUNT(*) FROM information_schema.tables
     WHERE table_schema = @db AND table_name = 'research_researcher') = 1
  AND (SELECT COUNT(*) FROM information_schema.columns
     WHERE table_schema = @db AND table_name = 'research_researcher' AND column_name = 'current_project') = 0,
  'ALTER TABLE `research_researcher` ADD COLUMN `current_project` text',
  'DO 0'));
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @sql := (SELECT IF(
  (SELECT COUNT(*) FROM information_schema.tables
     WHERE table_schema = @db AND table_name = 'research_researcher') = 1
  AND (SELECT COUNT(*) FROM information_schema.columns
     WHERE table_schema = @db AND table_name = 'research_researcher' AND column_name = 'orcid_id') = 0,
  'ALTER TABLE `research_researcher` ADD COLUMN `orcid_id` varchar(50) DEFAULT NULL',
  'DO 0'));
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @sql := (SELECT IF(
  (SELECT COUNT(*) FROM information_schema.tables
     WHERE table_schema = @db AND table_name = 'research_researcher') = 1
  AND (SELECT COUNT(*) FROM information_schema.columns
     WHERE table_schema = @db AND table_name = 'research_researcher' AND column_name = 'orcid_verified') = 0,
  'ALTER TABLE `research_researcher` ADD COLUMN `orcid_verified` tinyint(1) DEFAULT ''0''',
  'DO 0'));
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @sql := (SELECT IF(
  (SELECT COUNT(*) FROM information_schema.tables
     WHERE table_schema = @db AND table_name = 'research_researcher') = 1
  AND (SELECT COUNT(*) FROM information_schema.columns
     WHERE table_schema = @db AND table_name = 'research_researcher' AND column_name = 'orcid_access_token') = 0,
  'ALTER TABLE `research_researcher` ADD COLUMN `orcid_access_token` text',
  'DO 0'));
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @sql := (SELECT IF(
  (SELECT COUNT(*) FROM information_schema.tables
     WHERE table_schema = @db AND table_name = 'research_researcher') = 1
  AND (SELECT COUNT(*) FROM information_schema.columns
     WHERE table_schema = @db AND table_name = 'research_researcher' AND column_name = 'orcid_refresh_token') = 0,
  'ALTER TABLE `research_researcher` ADD COLUMN `orcid_refresh_token` text',
  'DO 0'));
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @sql := (SELECT IF(
  (SELECT COUNT(*) FROM information_schema.tables
     WHERE table_schema = @db AND table_name = 'research_researcher') = 1
  AND (SELECT COUNT(*) FROM information_schema.columns
     WHERE table_schema = @db AND table_name = 'research_researcher' AND column_name = 'orcid_token_expires_at') = 0,
  'ALTER TABLE `research_researcher` ADD COLUMN `orcid_token_expires_at` datetime DEFAULT NULL',
  'DO 0'));
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @sql := (SELECT IF(
  (SELECT COUNT(*) FROM information_schema.tables
     WHERE table_schema = @db AND table_name = 'research_researcher') = 1
  AND (SELECT COUNT(*) FROM information_schema.columns
     WHERE table_schema = @db AND table_name = 'research_researcher' AND column_name = 'researcher_id_wos') = 0,
  'ALTER TABLE `research_researcher` ADD COLUMN `researcher_id_wos` varchar(50) DEFAULT NULL',
  'DO 0'));
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @sql := (SELECT IF(
  (SELECT COUNT(*) FROM information_schema.tables
     WHERE table_schema = @db AND table_name = 'research_researcher') = 1
  AND (SELECT COUNT(*) FROM information_schema.columns
     WHERE table_schema = @db AND table_name = 'research_researcher' AND column_name = 'scopus_id') = 0,
  'ALTER TABLE `research_researcher` ADD COLUMN `scopus_id` varchar(50) DEFAULT NULL',
  'DO 0'));
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @sql := (SELECT IF(
  (SELECT COUNT(*) FROM information_schema.tables
     WHERE table_schema = @db AND table_name = 'research_researcher') = 1
  AND (SELECT COUNT(*) FROM information_schema.columns
     WHERE table_schema = @db AND table_name = 'research_researcher' AND column_name = 'isni') = 0,
  'ALTER TABLE `research_researcher` ADD COLUMN `isni` varchar(50) DEFAULT NULL',
  'DO 0'));
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @sql := (SELECT IF(
  (SELECT COUNT(*) FROM information_schema.tables
     WHERE table_schema = @db AND table_name = 'research_researcher') = 1
  AND (SELECT COUNT(*) FROM information_schema.columns
     WHERE table_schema = @db AND table_name = 'research_researcher' AND column_name = 'researcher_type_id') = 0,
  'ALTER TABLE `research_researcher` ADD COLUMN `researcher_type_id` int DEFAULT NULL',
  'DO 0'));
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @sql := (SELECT IF(
  (SELECT COUNT(*) FROM information_schema.tables
     WHERE table_schema = @db AND table_name = 'research_researcher') = 1
  AND (SELECT COUNT(*) FROM information_schema.columns
     WHERE table_schema = @db AND table_name = 'research_researcher' AND column_name = 'timezone') = 0,
  'ALTER TABLE `research_researcher` ADD COLUMN `timezone` varchar(50) DEFAULT ''Africa/Johannesburg''',
  'DO 0'));
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @sql := (SELECT IF(
  (SELECT COUNT(*) FROM information_schema.tables
     WHERE table_schema = @db AND table_name = 'research_researcher') = 1
  AND (SELECT COUNT(*) FROM information_schema.columns
     WHERE table_schema = @db AND table_name = 'research_researcher' AND column_name = 'preferred_language') = 0,
  'ALTER TABLE `research_researcher` ADD COLUMN `preferred_language` varchar(10) DEFAULT ''en''',
  'DO 0'));
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @sql := (SELECT IF(
  (SELECT COUNT(*) FROM information_schema.tables
     WHERE table_schema = @db AND table_name = 'research_researcher') = 1
  AND (SELECT COUNT(*) FROM information_schema.columns
     WHERE table_schema = @db AND table_name = 'research_researcher' AND column_name = 'api_key') = 0,
  'ALTER TABLE `research_researcher` ADD COLUMN `api_key` varchar(64) DEFAULT NULL',
  'DO 0'));
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @sql := (SELECT IF(
  (SELECT COUNT(*) FROM information_schema.tables
     WHERE table_schema = @db AND table_name = 'research_researcher') = 1
  AND (SELECT COUNT(*) FROM information_schema.columns
     WHERE table_schema = @db AND table_name = 'research_researcher' AND column_name = 'api_key_expires_at') = 0,
  'ALTER TABLE `research_researcher` ADD COLUMN `api_key_expires_at` datetime DEFAULT NULL',
  'DO 0'));
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @sql := (SELECT IF(
  (SELECT COUNT(*) FROM information_schema.tables
     WHERE table_schema = @db AND table_name = 'research_researcher') = 1
  AND (SELECT COUNT(*) FROM information_schema.columns
     WHERE table_schema = @db AND table_name = 'research_researcher' AND column_name = 'id_verified') = 0,
  'ALTER TABLE `research_researcher` ADD COLUMN `id_verified` tinyint(1) DEFAULT ''0''',
  'DO 0'));
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @sql := (SELECT IF(
  (SELECT COUNT(*) FROM information_schema.tables
     WHERE table_schema = @db AND table_name = 'research_researcher') = 1
  AND (SELECT COUNT(*) FROM information_schema.columns
     WHERE table_schema = @db AND table_name = 'research_researcher' AND column_name = 'id_verified_by') = 0,
  'ALTER TABLE `research_researcher` ADD COLUMN `id_verified_by` int DEFAULT NULL',
  'DO 0'));
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @sql := (SELECT IF(
  (SELECT COUNT(*) FROM information_schema.tables
     WHERE table_schema = @db AND table_name = 'research_researcher') = 1
  AND (SELECT COUNT(*) FROM information_schema.columns
     WHERE table_schema = @db AND table_name = 'research_researcher' AND column_name = 'id_verified_at') = 0,
  'ALTER TABLE `research_researcher` ADD COLUMN `id_verified_at` datetime DEFAULT NULL',
  'DO 0'));
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @sql := (SELECT IF(
  (SELECT COUNT(*) FROM information_schema.tables
     WHERE table_schema = @db AND table_name = 'research_researcher') = 1
  AND (SELECT COUNT(*) FROM information_schema.columns
     WHERE table_schema = @db AND table_name = 'research_researcher' AND column_name = 'experience_level') = 0,
  'ALTER TABLE `research_researcher` ADD COLUMN `experience_level` varchar(20) NOT NULL DEFAULT ''intermediate'' COMMENT ''beginning, intermediate, advanced''',
  'DO 0'));
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @sql := (SELECT IF(
  (SELECT COUNT(*) FROM information_schema.tables
     WHERE table_schema = @db AND table_name = 'research_researcher') = 1
  AND (SELECT COUNT(*) FROM information_schema.columns
     WHERE table_schema = @db AND table_name = 'research_researcher' AND column_name = 'rejection_reason') = 0,
  'ALTER TABLE `research_researcher` ADD COLUMN `rejection_reason` text',
  'DO 0'));
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @sql := (SELECT IF(
  (SELECT COUNT(*) FROM information_schema.tables
     WHERE table_schema = @db AND table_name = 'research_researcher') = 1
  AND (SELECT COUNT(*) FROM information_schema.columns
     WHERE table_schema = @db AND table_name = 'research_researcher' AND column_name = 'expires_at') = 0,
  'ALTER TABLE `research_researcher` ADD COLUMN `expires_at` date DEFAULT NULL',
  'DO 0'));
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @sql := (SELECT IF(
  (SELECT COUNT(*) FROM information_schema.tables
     WHERE table_schema = @db AND table_name = 'research_researcher') = 1
  AND (SELECT COUNT(*) FROM information_schema.columns
     WHERE table_schema = @db AND table_name = 'research_researcher' AND column_name = 'renewal_reminder_sent') = 0,
  'ALTER TABLE `research_researcher` ADD COLUMN `renewal_reminder_sent` tinyint(1) DEFAULT ''0''',
  'DO 0'));
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @sql := (SELECT IF(
  (SELECT COUNT(*) FROM information_schema.tables
     WHERE table_schema = @db AND table_name = 'research_researcher') = 1
  AND (SELECT COUNT(*) FROM information_schema.columns
     WHERE table_schema = @db AND table_name = 'research_researcher' AND column_name = 'photo_path') = 0,
  'ALTER TABLE `research_researcher` ADD COLUMN `photo_path` varchar(500) DEFAULT NULL',
  'DO 0'));
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @sql := (SELECT IF(
  (SELECT COUNT(*) FROM information_schema.tables
     WHERE table_schema = @db AND table_name = 'research_researcher') = 1
  AND (SELECT COUNT(*) FROM information_schema.columns
     WHERE table_schema = @db AND table_name = 'research_researcher' AND column_name = 'card_number') = 0,
  'ALTER TABLE `research_researcher` ADD COLUMN `card_number` varchar(50) DEFAULT NULL',
  'DO 0'));
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @sql := (SELECT IF(
  (SELECT COUNT(*) FROM information_schema.tables
     WHERE table_schema = @db AND table_name = 'research_researcher') = 1
  AND (SELECT COUNT(*) FROM information_schema.columns
     WHERE table_schema = @db AND table_name = 'research_researcher' AND column_name = 'card_barcode') = 0,
  'ALTER TABLE `research_researcher` ADD COLUMN `card_barcode` varchar(100) DEFAULT NULL',
  'DO 0'));
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @sql := (SELECT IF(
  (SELECT COUNT(*) FROM information_schema.tables
     WHERE table_schema = @db AND table_name = 'research_researcher') = 1
  AND (SELECT COUNT(*) FROM information_schema.columns
     WHERE table_schema = @db AND table_name = 'research_researcher' AND column_name = 'card_issued_at') = 0,
  'ALTER TABLE `research_researcher` ADD COLUMN `card_issued_at` datetime DEFAULT NULL',
  'DO 0'));
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;


-- ---- research_saved_search: 16 column(s) ----
SET @sql := (SELECT IF(
  (SELECT COUNT(*) FROM information_schema.tables
     WHERE table_schema = @db AND table_name = 'research_saved_search') = 1
  AND (SELECT COUNT(*) FROM information_schema.columns
     WHERE table_schema = @db AND table_name = 'research_saved_search' AND column_name = 'project_id') = 0,
  'ALTER TABLE `research_saved_search` ADD COLUMN `project_id` int DEFAULT NULL',
  'DO 0'));
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @sql := (SELECT IF(
  (SELECT COUNT(*) FROM information_schema.tables
     WHERE table_schema = @db AND table_name = 'research_saved_search') = 1
  AND (SELECT COUNT(*) FROM information_schema.columns
     WHERE table_schema = @db AND table_name = 'research_saved_search' AND column_name = 'description') = 0,
  'ALTER TABLE `research_saved_search` ADD COLUMN `description` text',
  'DO 0'));
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @sql := (SELECT IF(
  (SELECT COUNT(*) FROM information_schema.tables
     WHERE table_schema = @db AND table_name = 'research_saved_search') = 1
  AND (SELECT COUNT(*) FROM information_schema.columns
     WHERE table_schema = @db AND table_name = 'research_saved_search' AND column_name = 'search_query') = 0,
  'ALTER TABLE `research_saved_search` ADD COLUMN `search_query` text NOT NULL',
  'DO 0'));
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @sql := (SELECT IF(
  (SELECT COUNT(*) FROM information_schema.tables
     WHERE table_schema = @db AND table_name = 'research_saved_search') = 1
  AND (SELECT COUNT(*) FROM information_schema.columns
     WHERE table_schema = @db AND table_name = 'research_saved_search' AND column_name = 'search_filters') = 0,
  'ALTER TABLE `research_saved_search` ADD COLUMN `search_filters` text',
  'DO 0'));
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @sql := (SELECT IF(
  (SELECT COUNT(*) FROM information_schema.tables
     WHERE table_schema = @db AND table_name = 'research_saved_search') = 1
  AND (SELECT COUNT(*) FROM information_schema.columns
     WHERE table_schema = @db AND table_name = 'research_saved_search' AND column_name = 'query_ast_json') = 0,
  'ALTER TABLE `research_saved_search` ADD COLUMN `query_ast_json` json DEFAULT NULL',
  'DO 0'));
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @sql := (SELECT IF(
  (SELECT COUNT(*) FROM information_schema.tables
     WHERE table_schema = @db AND table_name = 'research_saved_search') = 1
  AND (SELECT COUNT(*) FROM information_schema.columns
     WHERE table_schema = @db AND table_name = 'research_saved_search' AND column_name = 'result_snapshot_json') = 0,
  'ALTER TABLE `research_saved_search` ADD COLUMN `result_snapshot_json` json DEFAULT NULL',
  'DO 0'));
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @sql := (SELECT IF(
  (SELECT COUNT(*) FROM information_schema.tables
     WHERE table_schema = @db AND table_name = 'research_saved_search') = 1
  AND (SELECT COUNT(*) FROM information_schema.columns
     WHERE table_schema = @db AND table_name = 'research_saved_search' AND column_name = 'citation_id') = 0,
  'ALTER TABLE `research_saved_search` ADD COLUMN `citation_id` varchar(100) DEFAULT NULL',
  'DO 0'));
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @sql := (SELECT IF(
  (SELECT COUNT(*) FROM information_schema.tables
     WHERE table_schema = @db AND table_name = 'research_saved_search') = 1
  AND (SELECT COUNT(*) FROM information_schema.columns
     WHERE table_schema = @db AND table_name = 'research_saved_search' AND column_name = 'last_result_count') = 0,
  'ALTER TABLE `research_saved_search` ADD COLUMN `last_result_count` int DEFAULT NULL',
  'DO 0'));
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @sql := (SELECT IF(
  (SELECT COUNT(*) FROM information_schema.tables
     WHERE table_schema = @db AND table_name = 'research_saved_search') = 1
  AND (SELECT COUNT(*) FROM information_schema.columns
     WHERE table_schema = @db AND table_name = 'research_saved_search' AND column_name = 'search_type') = 0,
  'ALTER TABLE `research_saved_search` ADD COLUMN `search_type` varchar(50) DEFAULT ''informationobject''',
  'DO 0'));
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @sql := (SELECT IF(
  (SELECT COUNT(*) FROM information_schema.tables
     WHERE table_schema = @db AND table_name = 'research_saved_search') = 1
  AND (SELECT COUNT(*) FROM information_schema.columns
     WHERE table_schema = @db AND table_name = 'research_saved_search' AND column_name = 'total_results_at_save') = 0,
  'ALTER TABLE `research_saved_search` ADD COLUMN `total_results_at_save` int DEFAULT NULL',
  'DO 0'));
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @sql := (SELECT IF(
  (SELECT COUNT(*) FROM information_schema.tables
     WHERE table_schema = @db AND table_name = 'research_saved_search') = 1
  AND (SELECT COUNT(*) FROM information_schema.columns
     WHERE table_schema = @db AND table_name = 'research_saved_search' AND column_name = 'facets') = 0,
  'ALTER TABLE `research_saved_search` ADD COLUMN `facets` json DEFAULT NULL',
  'DO 0'));
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @sql := (SELECT IF(
  (SELECT COUNT(*) FROM information_schema.tables
     WHERE table_schema = @db AND table_name = 'research_saved_search') = 1
  AND (SELECT COUNT(*) FROM information_schema.columns
     WHERE table_schema = @db AND table_name = 'research_saved_search' AND column_name = 'alert_enabled') = 0,
  'ALTER TABLE `research_saved_search` ADD COLUMN `alert_enabled` tinyint(1) DEFAULT ''0''',
  'DO 0'));
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @sql := (SELECT IF(
  (SELECT COUNT(*) FROM information_schema.tables
     WHERE table_schema = @db AND table_name = 'research_saved_search') = 1
  AND (SELECT COUNT(*) FROM information_schema.columns
     WHERE table_schema = @db AND table_name = 'research_saved_search' AND column_name = 'alert_frequency') = 0,
  'ALTER TABLE `research_saved_search` ADD COLUMN `alert_frequency` varchar(30) DEFAULT ''weekly'' COMMENT ''daily, weekly, monthly''',
  'DO 0'));
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @sql := (SELECT IF(
  (SELECT COUNT(*) FROM information_schema.tables
     WHERE table_schema = @db AND table_name = 'research_saved_search') = 1
  AND (SELECT COUNT(*) FROM information_schema.columns
     WHERE table_schema = @db AND table_name = 'research_saved_search' AND column_name = 'last_alert_at') = 0,
  'ALTER TABLE `research_saved_search` ADD COLUMN `last_alert_at` datetime DEFAULT NULL',
  'DO 0'));
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @sql := (SELECT IF(
  (SELECT COUNT(*) FROM information_schema.tables
     WHERE table_schema = @db AND table_name = 'research_saved_search') = 1
  AND (SELECT COUNT(*) FROM information_schema.columns
     WHERE table_schema = @db AND table_name = 'research_saved_search' AND column_name = 'new_results_count') = 0,
  'ALTER TABLE `research_saved_search` ADD COLUMN `new_results_count` int DEFAULT ''0''',
  'DO 0'));
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @sql := (SELECT IF(
  (SELECT COUNT(*) FROM information_schema.tables
     WHERE table_schema = @db AND table_name = 'research_saved_search') = 1
  AND (SELECT COUNT(*) FROM information_schema.columns
     WHERE table_schema = @db AND table_name = 'research_saved_search' AND column_name = 'is_public') = 0,
  'ALTER TABLE `research_saved_search` ADD COLUMN `is_public` tinyint(1) DEFAULT ''0''',
  'DO 0'));
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;


