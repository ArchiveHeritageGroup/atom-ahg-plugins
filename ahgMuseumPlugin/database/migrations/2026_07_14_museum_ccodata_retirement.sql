-- Museum ccoData retirement (Step 4, 2026-07-14)
-- Homes the last museum-form fields that previously lived only in the ccoData
-- property blob into the canonical museum_metadata table, so museum records have a
-- single source of truth. Idempotent: adds each column only if it is missing
-- (MySQL 8 has no ADD COLUMN IF NOT EXISTS, so we guard via information_schema).
-- Additive/non-destructive; safe to re-run.

DROP PROCEDURE IF EXISTS ahg_museum_add_ccodata_cols;
DELIMITER //
CREATE PROCEDURE ahg_museum_add_ccodata_cols()
BEGIN
    IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'museum_metadata' AND COLUMN_NAME = 'title_type') THEN
        ALTER TABLE museum_metadata ADD COLUMN title_type VARCHAR(100) DEFAULT NULL;
    END IF;
    IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'museum_metadata' AND COLUMN_NAME = 'credit_line') THEN
        ALTER TABLE museum_metadata ADD COLUMN credit_line TEXT DEFAULT NULL;
    END IF;
    IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'museum_metadata' AND COLUMN_NAME = 'creator_display') THEN
        ALTER TABLE museum_metadata ADD COLUMN creator_display TEXT DEFAULT NULL;
    END IF;
    IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'museum_metadata' AND COLUMN_NAME = 'materials_display') THEN
        ALTER TABLE museum_metadata ADD COLUMN materials_display TEXT DEFAULT NULL;
    END IF;
    IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'museum_metadata' AND COLUMN_NAME = 'height_value') THEN
        ALTER TABLE museum_metadata ADD COLUMN height_value VARCHAR(50) DEFAULT NULL;
    END IF;
    IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'museum_metadata' AND COLUMN_NAME = 'width_value') THEN
        ALTER TABLE museum_metadata ADD COLUMN width_value VARCHAR(50) DEFAULT NULL;
    END IF;
    IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'museum_metadata' AND COLUMN_NAME = 'depth_value') THEN
        ALTER TABLE museum_metadata ADD COLUMN depth_value VARCHAR(50) DEFAULT NULL;
    END IF;
END //
DELIMITER ;
CALL ahg_museum_add_ccodata_cols();
DROP PROCEDURE IF EXISTS ahg_museum_add_ccodata_cols;
