-- ============================================================
-- Migration: add the valuation-history columns the service writes
--
-- HeritageAssetService::addValuation() inserts valuation_change, and callers
-- pass valuation_report_reference. Neither was in install.sql - they reached
-- live databases through an earlier migration only. So the service worked on
-- an upgraded install and failed on every fresh one with
-- "Unknown column 'valuation_change' in 'field list'", which is how it was found.
--
-- Idempotent by inspection: MySQL has no ADD COLUMN IF NOT EXISTS, so each is
-- guarded against information_schema.
-- ============================================================

SET @db := DATABASE();

SET @sql := (SELECT IF(COUNT(*) = 0,
  'ALTER TABLE `heritage_valuation_history` ADD COLUMN `valuation_change` DECIMAL(15,2) NULL AFTER `valuer_organization`',
  'DO 0') FROM information_schema.columns
  WHERE table_schema = @db AND table_name = 'heritage_valuation_history' AND column_name = 'valuation_change');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @sql := (SELECT IF(COUNT(*) = 0,
  'ALTER TABLE `heritage_valuation_history` ADD COLUMN `valuer_id` INT UNSIGNED NULL AFTER `valuer_organization`',
  'DO 0') FROM information_schema.columns
  WHERE table_schema = @db AND table_name = 'heritage_valuation_history' AND column_name = 'valuer_id');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @sql := (SELECT IF(COUNT(*) = 0,
  'ALTER TABLE `heritage_valuation_history` ADD COLUMN `valuation_report_reference` VARCHAR(100) NULL',
  'DO 0') FROM information_schema.columns
  WHERE table_schema = @db AND table_name = 'heritage_valuation_history' AND column_name = 'valuation_report_reference');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @sql := (SELECT IF(COUNT(*) = 0,
  'ALTER TABLE `heritage_valuation_history` ADD COLUMN `revaluation_surplus_change` DECIMAL(15,2) NULL',
  'DO 0') FROM information_schema.columns
  WHERE table_schema = @db AND table_name = 'heritage_valuation_history' AND column_name = 'revaluation_surplus_change');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @sql := (SELECT IF(COUNT(*) = 0,
  'ALTER TABLE `heritage_valuation_history` ADD COLUMN `valuation_type` VARCHAR(50) NULL AFTER `valuation_date`',
  'DO 0') FROM information_schema.columns
  WHERE table_schema = @db AND table_name = 'heritage_valuation_history' AND column_name = 'valuation_type');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
