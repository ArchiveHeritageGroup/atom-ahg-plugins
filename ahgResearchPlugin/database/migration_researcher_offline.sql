-- Researcher Offline — sync-back targets for work done offline.
--
-- Extends the existing /research/mobile offline PWA + OfflineSyncService so a
-- researcher can, while offline on their own collected records, capture metadata
-- suggestions (curator-reviewed, never a live edit) and attach files, then sync
-- them back. Notes/sources/annotations reuse existing tables (research_annotation,
-- research_collection_item.notes) — no new table needed for those.
--
-- Additive only. Soft references (no FK to core tables). No ENUM. No atom_plugin insert.
-- Run on both archive + archeology.

-- Proposed metadata corrections/additions from offline work. These are PROPOSALS
-- queued for a curator to review — they never touch the live catalogue directly.
CREATE TABLE IF NOT EXISTS research_metadata_suggestion (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    researcher_id INT NOT NULL,
    object_id INT NOT NULL COMMENT 'information_object.id the suggestion is about',
    field VARCHAR(191) NOT NULL COMMENT 'e.g. Title, Dates, Scope and content',
    suggestion TEXT NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'open' COMMENT 'open, accepted, rejected',
    reviewed_by INT DEFAULT NULL COMMENT 'user_id of the curator who actioned it',
    reviewed_at DATETIME DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    KEY idx_rms_researcher (researcher_id),
    KEY idx_rms_object (object_id),
    KEY idx_rms_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Files a researcher attaches to a record while offline (e.g. field-work photos).
-- Stored on disk under uploads/research-offline/; this row is the metadata + path.
CREATE TABLE IF NOT EXISTS research_offline_attachment (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    researcher_id INT NOT NULL,
    object_id INT DEFAULT NULL COMMENT 'information_object.id the file relates to (nullable)',
    file_name VARCHAR(500) NOT NULL,
    mime_type VARCHAR(255) DEFAULT NULL,
    file_size BIGINT UNSIGNED DEFAULT 0,
    file_path VARCHAR(1000) NOT NULL COMMENT 'relative path under the atom root',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    KEY idx_roa_researcher (researcher_id),
    KEY idx_roa_object (object_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
