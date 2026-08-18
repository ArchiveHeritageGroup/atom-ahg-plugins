-- ==========================================================================
-- AHG Authority Resolution Engine - schema install
-- Plugin: ahgAuthorityResolutionPlugin
-- Identical to packages/ahg-authority-resolution/database/install.sql on the
-- Laravel Heratio side. Schemas converge here.
--
-- Design: coexist + project. ahg_ner_entity remains the canonical extraction
-- pool used by ahgPrivacyPlugin / ahgLibraryPlugin / ahgDiscoveryPlugin.
-- ahg_mention promotes selected rows into the authority-resolution workflow.
-- On a 'link' decision the resolver writes both ahg_mention_decision (audit)
-- AND back-updates ahg_ner_entity.linked_actor_id (existing consumer contract).
--
-- No FK constraints to base AtoM tables (matches existing ahg_ner_entity
-- convention - decouples from base schema migrations).
-- All enumerated values are VARCHAR + COMMENT (no ENUM per CLAUDE.md).
-- ==========================================================================

-- One workflow row per promoted NER entity.
CREATE TABLE IF NOT EXISTS ahg_mention (
    id                   BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    ner_entity_id        BIGINT UNSIGNED NOT NULL,
    object_id            INT NOT NULL,
    entity_type          VARCHAR(20) NOT NULL COMMENT 'PERSON, ORG, GPE, PLACE',
    state                VARCHAR(30) NOT NULL DEFAULT 'pending'
                         COMMENT 'pending, linked, parked, rejected, new_record_created',
    promoted_at          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at           DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
                         ON UPDATE CURRENT_TIMESTAMP,
    -- Assign / Workflow (Task 12): routes a mention through ahgWorkflowPlugin.
    assigned_to_user_id  INT NULL COMMENT 'user.id of the archivist the mention is assigned to',
    assigned_by_user_id  INT NULL COMMENT 'user.id of the archivist who performed the assignment',
    assigned_at          DATETIME NULL COMMENT 'timestamp of the most recent assignment',
    workflow_task_id     BIGINT UNSIGNED NULL COMMENT 'ahg_workflow_task.id created/reused for this mention',
    PRIMARY KEY (id),
    UNIQUE KEY uq_mention_ner_entity (ner_entity_id),
    KEY idx_mention_object (object_id),
    KEY idx_mention_state (state, entity_type),
    KEY idx_mention_assigned (assigned_to_user_id),
    KEY idx_mention_workflow_task (workflow_task_id),
    CONSTRAINT fk_mention_ner_entity
        FOREIGN KEY (ner_entity_id) REFERENCES ahg_ner_entity (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Neighbourhood context packet (1:1 with mention).
-- Scalars as columns, variable-shape lists as JSON.
CREATE TABLE IF NOT EXISTS ahg_mention_context (
    id                       BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    mention_id               BIGINT UNSIGNED NOT NULL,
    character_offset_start   INT UNSIGNED NULL,
    character_offset_end     INT UNSIGNED NULL,
    paragraph_offset_start   INT UNSIGNED NULL,
    paragraph_offset_end     INT UNSIGNED NULL,
    surrounding_text_before  TEXT NULL COMMENT 'up to 150 chars before mention',
    surrounding_text_after   TEXT NULL COMMENT 'up to 150 chars after mention',
    ner_model_version        VARCHAR(100) NULL,
    real_confidence          DECIMAL(6,4) NULL
                             COMMENT 'NULL until upstream API exposes per-entity scores',
    co_occurring_entities    JSON NULL
                             COMMENT 'Array of {ner_entity_id?, value, type, distance_tokens}',
    nearby_dates             JSON NULL
                             COMMENT 'Array of {value, normalized?, distance_tokens}',
    nearby_places            JSON NULL
                             COMMENT 'Array of {value, term_id?, distance_tokens}',
    role_language_tokens     JSON NULL
                             COMMENT 'Array of {token, position_offset, kind: kinship|witness|location|movement|other}',
    computed_at              DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_context_mention (mention_id),
    CONSTRAINT fk_context_mention
        FOREIGN KEY (mention_id) REFERENCES ahg_mention (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Ranked candidates surfaced per mention. Cached after Task 3, scored at Task 4.
CREATE TABLE IF NOT EXISTS ahg_mention_candidate (
    id                       BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    mention_id               BIGINT UNSIGNED NOT NULL,
    rank_position            TINYINT UNSIGNED NOT NULL,
    candidate_source         VARCHAR(30) NOT NULL
                             COMMENT 'mysql_actor, fuseki_agent, mysql_term, fuseki_place',
    candidate_authority_id   INT NULL
                             COMMENT 'actor.id or term.id; NULL when candidate is Fuseki-only',
    candidate_fuseki_uri     VARCHAR(2048) NULL,
    candidate_display_name   VARCHAR(1000) NOT NULL,
    name_similarity_score    DECIMAL(6,4) NOT NULL DEFAULT 0,
    evidence_signals         JSON NULL
                             COMMENT 'Per-dimension match|conflict|silent|absent for temporal/geographic/relational/role/conflict (persons,orgs) or geographic/hierarchical/document_prior/co_occurring/conflict/scale (places)',
    evidence_data            JSON NULL
                             COMMENT 'Underlying values per dimension for the UI to render',
    composite_score          DECIMAL(6,4) NULL
                             COMMENT 'Weighted aggregate after Task 4 scoring',
    computed_at              DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_candidate_mention_rank (mention_id, rank_position),
    KEY idx_candidate_authority (candidate_authority_id),
    CONSTRAINT fk_candidate_mention
        FOREIGN KEY (mention_id) REFERENCES ahg_mention (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Immutable decision audit. One row per decision event.
-- Frozen snapshots so "what did the archivist see" is answerable from this row alone.
CREATE TABLE IF NOT EXISTS ahg_mention_decision (
    id                            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    mention_id                    BIGINT UNSIGNED NOT NULL,
    decision_type                 VARCHAR(30) NOT NULL
                                  COMMENT 'link, link_different, create_new, park, reject',
    chosen_candidate_id           BIGINT UNSIGNED NULL,
    chosen_authority_id           INT NULL
                                  COMMENT 'actor.id or term.id depending on entity_type',
    original_system_top_score     DECIMAL(6,4) NULL
                                  COMMENT 'Engine score of top candidate at decision time',
    archivist_user_id             INT NOT NULL,
    decided_at                    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fuseki_graph_uri              VARCHAR(2048) NULL
                                  COMMENT 'Set asynchronously by Task 8 RDF-Star writer',
    evidence_snapshot             JSON NULL
                                  COMMENT 'Frozen copy of evidence_signals + evidence_data shown at decision time',
    candidates_visible_snapshot   JSON NULL
                                  COMMENT 'Array of {candidate_id, display_name, rank} visible at decision',
    PRIMARY KEY (id),
    KEY idx_decision_mention (mention_id, decided_at),
    KEY idx_decision_archivist (archivist_user_id, decided_at),
    CONSTRAINT fk_decision_mention
        FOREIGN KEY (mention_id) REFERENCES ahg_mention (id) ON DELETE CASCADE,
    CONSTRAINT fk_decision_candidate
        FOREIGN KEY (chosen_candidate_id) REFERENCES ahg_mention_candidate (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Park queue. One active row per mention (UNIQUE on mention_id).
CREATE TABLE IF NOT EXISTS ahg_mention_park (
    id                          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    mention_id                  BIGINT UNSIGNED NOT NULL,
    parked_by_user_id           INT NOT NULL,
    parked_at                   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    reason                      TEXT NOT NULL,
    new_candidate_available     TINYINT(1) NOT NULL DEFAULT 0
                                COMMENT 'Set to 1 by Task 7 re-scan job when authority store has new candidates since parking',
    new_candidate_check_at      DATETIME NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_park_mention (mention_id),
    KEY idx_park_user (parked_by_user_id, parked_at),
    KEY idx_park_new_candidate (new_candidate_available, parked_at),
    CONSTRAINT fk_park_mention
        FOREIGN KEY (mention_id) REFERENCES ahg_mention (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- NER feedback capture (Task 9). One row per reject decision; captures the
-- rejected span + reason so the model can be retrained on negatives. Export
-- via auth-res:export-ner-feedback writes JSONL/CoNLL to disk and flips
-- training_exported=1 + exported_at=NOW().
CREATE TABLE IF NOT EXISTS ahg_ner_feedback (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    mention_id BIGINT UNSIGNED NOT NULL,
    ner_entity_id BIGINT UNSIGNED NOT NULL,
    decision_id BIGINT UNSIGNED NOT NULL,
    source_text MEDIUMTEXT NOT NULL,
    mention_value VARCHAR(1000) NOT NULL,
    mention_entity_type VARCHAR(20) NOT NULL,
    mention_offset_start INT UNSIGNED NULL,
    mention_offset_end INT UNSIGNED NULL,
    rejection_reason TEXT NOT NULL,
    archivist_user_id INT NOT NULL,
    ner_model_version VARCHAR(100) NULL,
    training_exported TINYINT(1) NOT NULL DEFAULT 0,
    exported_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_feedback_decision (decision_id),
    KEY idx_feedback_mention (mention_id),
    KEY idx_feedback_unexported (training_exported, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- External authority lookup cache (VIAF/Wikidata/GeoNames/TGN/GND/ISNI/SAGNC).
CREATE TABLE IF NOT EXISTS ahg_authority_lookup_cache (
    id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    source          VARCHAR(30) NOT NULL
                    COMMENT 'viaf, wikidata, geonames, tgn, gnd, isni, sagnc',
    entity_type     VARCHAR(20) NOT NULL COMMENT 'PERSON, ORG, PLACE',
    query_text      VARCHAR(500) NOT NULL,
    payload         JSON NOT NULL,
    license_note    VARCHAR(500) NULL,
    retrieved_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    ttl_seconds     INT UNSIGNED NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_cache_source_query (source, entity_type, query_text),
    KEY idx_cache_retrieved (retrieved_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- Merged in from database/seed_candidate_config.sql on 2026-08-18.
--
-- It sat beside install.sql and was never run by install-plugin-schema.php, so
-- a clean install silently lacked whatever it defines. Our own instances had it
-- because someone applied the file by hand. A plugin's schema is install.sql.
--
-- Unguarded INSERTs are rewritten to INSERT IGNORE so re-running stays safe;
-- on a fresh database the result is identical.
-- ---------------------------------------------------------------------------

-- ==========================================================================
-- Seed candidate-generation config for the authority-resolution engine.
-- Inserted into ahg_settings(setting_group='authority_resolution',
-- setting_key='authority_resolution.candidate_top_n').
--
-- The CandidateGeneratorService reads this key to decide how many ranked
-- candidates to persist per ahg_mention. Falls back to 5 when missing.
--
-- Idempotent (INSERT IGNORE on UNIQUE setting_key). Run manually:
--   MYSQL_PWD="<pw>" mysql --defaults-file=/dev/null -u root archive \
--     < seed_candidate_config.sql
-- ==========================================================================

INSERT IGNORE INTO ahg_settings (
    setting_key,
    setting_group,
    setting_type,
    setting_value,
    description,
    is_sensitive,
    created_at,
    updated_at
) VALUES (
    'authority_resolution.candidate_top_n',
    'authority_resolution',
    'integer',
    '5',
    'Top N candidates surfaced per mention.',
    0,
    NOW(),
    NOW()
);

-- ---------------------------------------------------------------------------
-- Merged in from database/seed_lookup_settings.sql on 2026-08-18.
--
-- It sat beside install.sql and was never run by install-plugin-schema.php, so
-- a clean install silently lacked whatever it defines. Our own instances had it
-- because someone applied the file by hand. A plugin's schema is install.sql.
--
-- Unguarded INSERTs are rewritten to INSERT IGNORE so re-running stays safe;
-- on a fresh database the result is identical.
-- ---------------------------------------------------------------------------

-- ==========================================================================
-- Seed external-lookup adapter settings for the authority-resolution engine.
-- Inserted into ahg_settings(setting_group='authority_resolution',
-- setting_key='authority_resolution.lookup.<source>.<param>').
--
-- All sources default to enabled=0 so a fresh install never makes external
-- HTTP calls without an admin explicitly opting in via
--   /admin/authorityResolution/settings/lookup.
--
-- Sources:
--   viaf       - VIAF AutoSuggest         (no key, CC0)
--   wikidata   - Wikidata wbsearchentities (no key, CC0)
--   geonames   - GeoNames searchJSON       (free key, CC BY)
--   tgn        - Getty TGN SPARQL          (no key, ODbL)              [stub]
--   gnd        - DNB GND lobid             (no key, CC0)               [stub]
--   isni       - ISNI                      (key required, restrictive) [stub]
--   sagnc      - South African Geographical Names Council              [stub]
--
-- Plus precedence array for merging conflicting pre-fill values.
--
-- Idempotent (INSERT IGNORE on UNIQUE setting_key). Run manually with the
-- archive root password from config/config.php Propel param:
--   MYSQL_PWD="<pw>" mysql --defaults-file=/dev/null -u root archive \
--     < seed_lookup_settings.sql
-- ==========================================================================

-- VIAF (Virtual International Authority File)
INSERT IGNORE INTO ahg_settings (
    setting_key, setting_group, setting_type, setting_value,
    description, is_sensitive, created_at, updated_at
) VALUES
('authority_resolution.lookup.viaf.enabled', 'authority_resolution', 'boolean', '0',
 'Enable VIAF AutoSuggest lookup for person/org pre-fill.', 0, NOW(), NOW()),
('authority_resolution.lookup.viaf.rate_limit', 'authority_resolution', 'integer', '60',
 'Maximum VIAF requests per minute.', 0, NOW(), NOW()),
('authority_resolution.lookup.viaf.cache_ttl', 'authority_resolution', 'integer', '604800',
 'VIAF response cache TTL in seconds (default 7 days).', 0, NOW(), NOW()),
('authority_resolution.lookup.viaf.license_note', 'authority_resolution', 'string', 'CC0-1.0',
 'License under which VIAF data is redistributable.', 0, NOW(), NOW()),
('authority_resolution.lookup.viaf.license_url', 'authority_resolution', 'string', 'https://creativecommons.org/publicdomain/zero/1.0/',
 'URL of VIAF license.', 0, NOW(), NOW());

-- Wikidata
INSERT IGNORE INTO ahg_settings (
    setting_key, setting_group, setting_type, setting_value,
    description, is_sensitive, created_at, updated_at
) VALUES
('authority_resolution.lookup.wikidata.enabled', 'authority_resolution', 'boolean', '0',
 'Enable Wikidata wbsearchentities lookup for any entity type.', 0, NOW(), NOW()),
('authority_resolution.lookup.wikidata.rate_limit', 'authority_resolution', 'integer', '120',
 'Maximum Wikidata requests per minute.', 0, NOW(), NOW()),
('authority_resolution.lookup.wikidata.cache_ttl', 'authority_resolution', 'integer', '604800',
 'Wikidata response cache TTL in seconds (default 7 days).', 0, NOW(), NOW()),
('authority_resolution.lookup.wikidata.license_note', 'authority_resolution', 'string', 'CC0-1.0',
 'License under which Wikidata data is redistributable.', 0, NOW(), NOW()),
('authority_resolution.lookup.wikidata.license_url', 'authority_resolution', 'string', 'https://creativecommons.org/publicdomain/zero/1.0/',
 'URL of Wikidata license.', 0, NOW(), NOW());

-- GeoNames
INSERT IGNORE INTO ahg_settings (
    setting_key, setting_group, setting_type, setting_value,
    description, is_sensitive, created_at, updated_at
) VALUES
('authority_resolution.lookup.geonames.enabled', 'authority_resolution', 'boolean', '0',
 'Enable GeoNames searchJSON lookup for place pre-fill.', 0, NOW(), NOW()),
('authority_resolution.lookup.geonames.rate_limit', 'authority_resolution', 'integer', '60',
 'Maximum GeoNames requests per minute (free tier).', 0, NOW(), NOW()),
('authority_resolution.lookup.geonames.cache_ttl', 'authority_resolution', 'integer', '2592000',
 'GeoNames response cache TTL in seconds (default 30 days).', 0, NOW(), NOW()),
('authority_resolution.lookup.geonames.license_note', 'authority_resolution', 'string', 'CC BY 4.0',
 'License under which GeoNames data is redistributable.', 0, NOW(), NOW()),
('authority_resolution.lookup.geonames.license_url', 'authority_resolution', 'string', 'https://creativecommons.org/licenses/by/4.0/',
 'URL of GeoNames license.', 0, NOW(), NOW()),
('authority_resolution.lookup.geonames.username', 'authority_resolution', 'string', '',
 'GeoNames API username (required when enabled). Sign up: https://www.geonames.org/login', 0, NOW(), NOW());

-- TGN (Getty Thesaurus of Geographic Names) - STUB
INSERT IGNORE INTO ahg_settings (
    setting_key, setting_group, setting_type, setting_value,
    description, is_sensitive, created_at, updated_at
) VALUES
('authority_resolution.lookup.tgn.enabled', 'authority_resolution', 'boolean', '0',
 'Enable Getty TGN SPARQL lookup (stub adapter; integration deferred).', 0, NOW(), NOW()),
('authority_resolution.lookup.tgn.rate_limit', 'authority_resolution', 'integer', '30',
 'Maximum TGN SPARQL requests per minute.', 0, NOW(), NOW()),
('authority_resolution.lookup.tgn.cache_ttl', 'authority_resolution', 'integer', '2592000',
 'TGN response cache TTL in seconds (default 30 days).', 0, NOW(), NOW()),
('authority_resolution.lookup.tgn.license_note', 'authority_resolution', 'string', 'ODbL 1.0',
 'License under which TGN data is redistributable.', 0, NOW(), NOW()),
('authority_resolution.lookup.tgn.license_url', 'authority_resolution', 'string', 'https://opendatacommons.org/licenses/odbl/1-0/',
 'URL of TGN license.', 0, NOW(), NOW());

-- GND (Deutsche Nationalbibliothek Integrated Authority File) - STUB
INSERT IGNORE INTO ahg_settings (
    setting_key, setting_group, setting_type, setting_value,
    description, is_sensitive, created_at, updated_at
) VALUES
('authority_resolution.lookup.gnd.enabled', 'authority_resolution', 'boolean', '0',
 'Enable GND lobid lookup (stub adapter; integration deferred).', 0, NOW(), NOW()),
('authority_resolution.lookup.gnd.rate_limit', 'authority_resolution', 'integer', '60',
 'Maximum GND requests per minute.', 0, NOW(), NOW()),
('authority_resolution.lookup.gnd.cache_ttl', 'authority_resolution', 'integer', '604800',
 'GND response cache TTL in seconds (default 7 days).', 0, NOW(), NOW()),
('authority_resolution.lookup.gnd.license_note', 'authority_resolution', 'string', 'CC0-1.0',
 'License under which GND data is redistributable.', 0, NOW(), NOW()),
('authority_resolution.lookup.gnd.license_url', 'authority_resolution', 'string', 'https://creativecommons.org/publicdomain/zero/1.0/',
 'URL of GND license.', 0, NOW(), NOW());

-- ISNI (International Standard Name Identifier) - STUB
INSERT IGNORE INTO ahg_settings (
    setting_key, setting_group, setting_type, setting_value,
    description, is_sensitive, created_at, updated_at
) VALUES
('authority_resolution.lookup.isni.enabled', 'authority_resolution', 'boolean', '0',
 'Enable ISNI SRU lookup (stub adapter; integration deferred). Requires institutional credentials.', 0, NOW(), NOW()),
('authority_resolution.lookup.isni.rate_limit', 'authority_resolution', 'integer', '30',
 'Maximum ISNI requests per minute.', 0, NOW(), NOW()),
('authority_resolution.lookup.isni.cache_ttl', 'authority_resolution', 'integer', '604800',
 'ISNI response cache TTL in seconds (default 7 days).', 0, NOW(), NOW()),
('authority_resolution.lookup.isni.license_note', 'authority_resolution', 'string', 'ISNI Terms of Use',
 'License under which ISNI data is redistributable.', 0, NOW(), NOW()),
('authority_resolution.lookup.isni.license_url', 'authority_resolution', 'string', 'https://isni.org/page/terms-of-use/',
 'URL of ISNI terms.', 0, NOW(), NOW());

-- SAGNC (South African Geographical Names Council) - STUB
INSERT IGNORE INTO ahg_settings (
    setting_key, setting_group, setting_type, setting_value,
    description, is_sensitive, created_at, updated_at
) VALUES
('authority_resolution.lookup.sagnc.enabled', 'authority_resolution', 'boolean', '0',
 'Enable SAGNC lookup (stub adapter; integration deferred).', 0, NOW(), NOW()),
('authority_resolution.lookup.sagnc.rate_limit', 'authority_resolution', 'integer', '30',
 'Maximum SAGNC requests per minute.', 0, NOW(), NOW()),
('authority_resolution.lookup.sagnc.cache_ttl', 'authority_resolution', 'integer', '2592000',
 'SAGNC response cache TTL in seconds (default 30 days).', 0, NOW(), NOW()),
('authority_resolution.lookup.sagnc.license_note', 'authority_resolution', 'string', 'SAGNC Open Data',
 'License under which SAGNC data is redistributable.', 0, NOW(), NOW()),
('authority_resolution.lookup.sagnc.license_url', 'authority_resolution', 'string', '',
 'URL of SAGNC license (TBD).', 0, NOW(), NOW());

-- Precedence: when two adapters return different values for the same field,
-- the first source in this JSON array wins. Stored as a JSON string the
-- PrefillEngine decodes into a PHP array.
INSERT IGNORE INTO ahg_settings (
    setting_key, setting_group, setting_type, setting_value,
    description, is_sensitive, created_at, updated_at
) VALUES
('authority_resolution.lookup.precedence', 'authority_resolution', 'json',
 '["viaf","wikidata","geonames","tgn","gnd","isni","sagnc"]',
 'Pre-fill merge precedence. First source wins when fields conflict.', 0, NOW(), NOW());

-- ---------------------------------------------------------------------------
-- Merged in from database/seed_role_language.sql on 2026-08-18.
--
-- It sat beside install.sql and was never run by install-plugin-schema.php, so
-- a clean install silently lacked whatever it defines. Our own instances had it
-- because someone applied the file by hand. A plugin's schema is install.sql.
--
-- Unguarded INSERTs are rewritten to INSERT IGNORE so re-running stays safe;
-- on a fresh database the result is identical.
-- ---------------------------------------------------------------------------

-- ==========================================================================
-- Seed role-language tokens for the authority-resolution engine.
-- Inserted into ahg_settings(setting_group='authority_resolution',
-- setting_key='authority_resolution.role_language_tokens').
-- Idempotent (INSERT IGNORE on UNIQUE setting_key).
-- ==========================================================================

INSERT IGNORE INTO ahg_settings (
    setting_key,
    setting_group,
    setting_type,
    setting_value,
    description,
    is_sensitive,
    created_at,
    updated_at
) VALUES (
    'authority_resolution.role_language_tokens',
    'authority_resolution',
    'json',
    JSON_OBJECT(
        'kinship', JSON_ARRAY(
            'son of', 'daughter of', 'child of', 'children of',
            'father of', 'mother of', 'parent of', 'parents of',
            'brother of', 'sister of', 'sibling of',
            'wife of', 'husband of', 'spouse of',
            'descendant of', 'ancestor of',
            'uncle of', 'aunt of', 'cousin of', 'nephew of', 'niece of',
            'grandson of', 'granddaughter of', 'grandfather of', 'grandmother of'
        ),
        'witness', JSON_ARRAY(
            'witnessed by', 'witness was', 'witnesses were',
            'signed by', 'attested by', 'testified by',
            'present at', 'in the presence of', 'in attendance',
            'co-signed by', 'countersigned by'
        ),
        'location', JSON_ARRAY(
            'located in', 'located at', 'situated in', 'situated at',
            'found at', 'found in', 'based in', 'based at',
            'residing at', 'residing in', 'resident of', 'resident at',
            'dwelling at', 'dwelling in', 'living at', 'living in',
            'born at', 'born in', 'born on',
            'died at', 'died in', 'died on',
            'buried at', 'buried in'
        ),
        'movement', JSON_ARRAY(
            'travelled to', 'traveled to', 'travelled from', 'traveled from',
            'moved to', 'moved from', 'relocated to', 'relocated from',
            'returned from', 'returned to', 'departed for', 'departed from',
            'journeyed to', 'journeyed from',
            'sailed from', 'sailed to', 'sailed for',
            'arrived at', 'arrived in', 'arrived from',
            'fled to', 'fled from', 'escaped to', 'escaped from',
            'emigrated to', 'immigrated to', 'migrated to'
        ),
        'other', JSON_ARRAY(
            'officiated by', 'officiated at',
            'ruled by', 'ruled over', 'governed by', 'governed',
            'owned by', 'owned', 'possessed by',
            'served by', 'served as', 'served at', 'served in',
            'appointed by', 'appointed as', 'appointed to',
            'succeeded by', 'succeeded',
            'preceded by', 'preceded',
            'employed by', 'employed at', 'worked for', 'worked at',
            'educated at', 'studied at', 'graduated from',
            'founded', 'founded by', 'co-founded',
            'commanded by', 'commanded', 'led by', 'led'
        )
    ),
    'Role-language tokens for authority-resolution context derivation. Keys are kinds (kinship/witness/location/movement/other); values are lowercased token lists.',
    0,
    NOW(),
    NOW()
);

-- ---------------------------------------------------------------------------
-- Merged in from database/seed_workflow.sql on 2026-08-18.
--
-- It sat beside install.sql and was never run by install-plugin-schema.php, so
-- a clean install silently lacked whatever it defines. Our own instances had it
-- because someone applied the file by hand. A plugin's schema is install.sql.
--
-- Unguarded INSERTs are rewritten to INSERT IGNORE so re-running stays safe;
-- on a fresh database the result is identical.
-- ---------------------------------------------------------------------------

-- ==========================================================================
-- AHG Authority Resolution Engine - workflow seed (Task 12: Assign / Workflow)
-- Plugin: ahgAuthorityResolutionPlugin
--
-- Seeds a minimal "Authority Resolution Review" workflow definition into the
-- ahgWorkflowPlugin tables so that assigning a mention can route it through
-- the existing Workflow plugin. AssignmentService::assign() passes this
-- workflow id explicitly to WorkflowService::startWorkflow(), so the
-- ahg_mention object never has to satisfy getApplicableWorkflow()'s
-- information_object scope lookup.
--
-- Fixed ids 200 / 200 are well clear of the ahgWorkflowPlugin seed range
-- (ids 1, 100, 101) to avoid collisions on a converged DB.
--
-- Copyright (C) 2026 Johan Pieterse
-- Plain Sailing Information Systems
-- Email: johan@plainsailingisystems.co.za
--
-- This file is part of the AHG Authority Resolution Engine plugin for
-- AtoM Heratio. Licensed under the GNU General Public License v3.0 or later,
-- matching the parent atom-ahg-plugins repository.
-- ==========================================================================

-- Workflow definition. scope_type='global', applies_to='ahg_mention'.
INSERT IGNORE INTO `ahg_workflow`
    (`id`, `name`, `description`, `scope_type`, `scope_id`, `trigger_event`,
     `applies_to`, `is_active`, `is_default`, `require_all_steps`,
     `allow_parallel`, `notification_enabled`)
VALUES
    (200, 'Authority Resolution Review',
     'Routes an authority-resolution mention to an archivist for review and linking.',
     'global', NULL, 'submit', 'ahg_mention', 1, 0, 1, 0, 1);

-- Single review step. pool_enabled so the task is claimable.
INSERT IGNORE INTO `ahg_workflow_step`
    (`id`, `workflow_id`, `name`, `description`, `step_order`, `step_type`,
     `action_required`, `pool_enabled`, `is_optional`, `is_active`, `instructions`)
VALUES
    (200, 200, 'Review',
     'Review the mention, evaluate candidate authorities and record a link, park or reject decision.',
     1, 'review', 'approve_reject', 1, 0, 1,
     'Open the mention review screen, weigh the evidence-scored candidates and record a decision.');
