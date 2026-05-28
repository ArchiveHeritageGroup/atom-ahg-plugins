-- Migration: FRBR Work-Set Clustering + Override System
-- Issue #95 — ahgLibraryPlugin

-- 1. Add FRBR columns to library_item
ALTER TABLE library_item
  ADD COLUMN frbr_work_key       VARCHAR(64)  NULL  COMMENT 'SHA-256 work identifier, first 20 chars' AFTER material_type,
  ADD COLUMN frbr_override_type  ENUM('none','force_group','force_split') DEFAULT 'none' AFTER frbr_work_key,
  ADD COLUMN updated_at          TIMESTAMP    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER frbr_override_type;

CREATE INDEX idx_library_item_frbr_work_key ON library_item (frbr_work_key);
CREATE INDEX idx_library_item_frbr_override ON library_item (frbr_override_type);

-- 2. Override table: librarian can force-group or force-split works
CREATE TABLE IF NOT EXISTS library_item_frbr_override (
  id               BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  library_item_id  BIGINT UNSIGNED NOT NULL,
  target_work_key  VARCHAR(64)      NULL  COMMENT 'force_group: merge this item INTO the target work key',
  forced_split     TINYINT(1)       DEFAULT 0  COMMENT 'force_split: do NOT cluster this item with any other',
  reason           VARCHAR(500)     NULL,
  created_by       BIGINT UNSIGNED   NULL,
  created_at      TIMESTAMP        DEFAULT CURRENT_TIMESTAMP,

  FOREIGN KEY (library_item_id) REFERENCES library_item(id) ON DELETE CASCADE,
  INDEX idx_target_work_key (target_work_key),
  INDEX idx_library_item_id (library_item_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Index for fast work-set lookups on usage events
ALTER TABLE library_usage_event
  ADD COLUMN frbr_work_key VARCHAR(64) NULL AFTER library_item_id;

CREATE INDEX idx_library_usage_event_work_key ON library_usage_event (frbr_work_key);