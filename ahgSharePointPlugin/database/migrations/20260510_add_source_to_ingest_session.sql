-- Add source tracking to ingest_session so SharePoint-driven ingests can
-- reuse the wizard pipeline (IngestCommitService) without losing origin.
-- Additive, nullable, defaults preserve existing behaviour for wizard rows.
-- Mirrored in heratio/packages/ahg-sharepoint/database/migrations/.

ALTER TABLE ingest_session
    ADD COLUMN IF NOT EXISTS source VARCHAR(20) NOT NULL DEFAULT 'wizard'
        COMMENT 'wizard, sharepoint, api',
    ADD COLUMN IF NOT EXISTS source_id INT DEFAULT NULL
        COMMENT 'Origin record id (e.g., sharepoint_event.id)';

-- Note: MySQL 8 supports IF NOT EXISTS on ADD COLUMN as of 8.0.29.
-- If the install runs against an older MySQL 8.0 patch, drop the IF NOT EXISTS
-- clauses (the install runner already guards against double-application).
