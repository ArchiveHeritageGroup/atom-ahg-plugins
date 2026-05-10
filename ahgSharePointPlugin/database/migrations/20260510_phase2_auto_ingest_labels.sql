-- Phase 2.A — add the auto-ingest label allowlist column to sharepoint_drive.
--
-- A drive's auto/declare ingest fires only when the changed item's
-- _ComplianceTag matches one of the labels listed here. NULL/empty disables
-- auto ingest entirely (drive can still be used for manual push).
--
-- JSON encoded array, e.g. ["Archive-Permanent","Confidential-7yr"].
-- Mirrored in heratio/packages/ahg-sharepoint/database/migrations/.
--
-- Note: framework rule forbids ADD COLUMN IF NOT EXISTS — install task must
-- introspect information_schema before running.

ALTER TABLE sharepoint_drive
    ADD COLUMN auto_ingest_labels TEXT DEFAULT NULL
        COMMENT 'JSON array of compliance tag names that trigger auto-ingest in mode B';
