-- Add source tracking to ingest_session so SharePoint-driven ingests can
-- reuse the wizard pipeline (IngestCommitService) without losing origin.
-- Additive, nullable, defaults preserve existing behaviour for wizard rows.
-- Mirrored in heratio/packages/ahg-sharepoint/database/migrations/.
--
-- Note: framework rule (atom-framework/CLAUDE.md) forbids ADD COLUMN IF NOT EXISTS.
-- Run defensively from a wrapper that checks information_schema first, e.g.:
--   php symfony sharepoint:install   (uses prepared schema introspection)
-- Or use the migration runner that the install task invokes.

ALTER TABLE ingest_session
    ADD COLUMN source VARCHAR(20) NOT NULL DEFAULT 'wizard'
        COMMENT 'wizard, sharepoint_auto, sharepoint_push, api',
    ADD COLUMN source_id INT DEFAULT NULL
        COMMENT 'Origin record id (e.g., sharepoint_event.id, sharepoint_user_mapping.id)';
