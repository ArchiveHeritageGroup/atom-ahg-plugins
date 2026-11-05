-- Federation Plugin Database Schema
-- Creates tables for managing federation peers and harvest logging
-- NOTE: ENUM values are stored in ahg_dropdown table (ahgCorePlugin)
--       Use AhgTaxonomyService for dropdown values

-- Federation Peer table
-- Stores configuration for remote OAI-PMH endpoints to harvest from
CREATE TABLE IF NOT EXISTS federation_peer (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL COMMENT 'Human-readable name for the peer repository',
    peer_type VARCHAR(64) NOT NULL DEFAULT 'oai_pmh' COMMENT 'oai_pmh, atom_local, sharepoint_graph_search (read by the union catalogue and federated search)',
    base_url VARCHAR(500) NOT NULL COMMENT 'Base URL of the OAI-PMH endpoint',
    oai_identifier VARCHAR(255) NULL COMMENT 'Optional OAI repository identifier',
    api_key VARCHAR(255) NULL COMMENT 'Optional API key for authentication',
    description TEXT NULL COMMENT 'Description of the peer repository',
    contact_email VARCHAR(255) NULL COMMENT 'Contact email for the peer repository',

    -- Harvesting configuration
    default_metadata_prefix VARCHAR(50) DEFAULT 'oai_dc' COMMENT 'Preferred metadata format',
    default_set VARCHAR(255) NULL COMMENT 'Default set to harvest (null = all)',
    harvest_interval_hours INT DEFAULT 24 COMMENT 'How often to harvest (in hours)',

    -- Status
    is_active TINYINT(1) DEFAULT 1 COMMENT 'Whether this peer is active for harvesting',
    last_harvest_at DATETIME NULL COMMENT 'Timestamp of last successful harvest',
    last_harvest_status VARCHAR(50) NULL COMMENT 'Status of last harvest (success, partial, failed)',
    last_harvest_records INT DEFAULT 0 COMMENT 'Number of records in last harvest',

    -- Timestamps
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    -- Indexes
    INDEX idx_peer_active (is_active),
    INDEX idx_peer_url (base_url(255)),
    UNIQUE INDEX idx_peer_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Federation Harvest Log table
-- Records each harvest action for auditing and debugging
CREATE TABLE IF NOT EXISTS federation_harvest_log (
    id INT PRIMARY KEY AUTO_INCREMENT,
    peer_id INT NOT NULL COMMENT 'Reference to federation_peer',
    information_object_id INT NOT NULL COMMENT 'Reference to information_object',
    source_oai_identifier VARCHAR(500) NOT NULL COMMENT 'OAI identifier from source repository',
    harvest_date DATETIME NOT NULL COMMENT 'When the record was harvested',
    metadata_format VARCHAR(50) NOT NULL COMMENT 'Metadata format used (oai_dc, oai_heritage, etc.)',
    action VARCHAR(50) NOT NULL COMMENT 'Action taken - uses ahg_dropdown federation_harvest_action',

    -- Indexes
    INDEX idx_harvest_peer (peer_id),
    INDEX idx_harvest_object (information_object_id),
    INDEX idx_harvest_source (source_oai_identifier(255)),
    INDEX idx_harvest_date (harvest_date),
    INDEX idx_harvest_action (action),

    -- Foreign keys
    CONSTRAINT fk_harvest_peer
        FOREIGN KEY (peer_id) REFERENCES federation_peer(id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_harvest_object
        FOREIGN KEY (information_object_id) REFERENCES information_object(id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Federation Harvest Session table
-- Tracks overall harvest sessions for reporting
CREATE TABLE IF NOT EXISTS federation_harvest_session (
    id INT PRIMARY KEY AUTO_INCREMENT,
    peer_id INT NOT NULL COMMENT 'Reference to federation_peer',
    started_at DATETIME NOT NULL COMMENT 'When the harvest started',
    completed_at DATETIME NULL COMMENT 'When the harvest completed',
    status VARCHAR(50) DEFAULT 'running' COMMENT 'Session status - uses ahg_dropdown federation_session_status',

    -- Harvest parameters
    metadata_prefix VARCHAR(50) NOT NULL,
    harvest_from DATETIME NULL COMMENT 'From date parameter',
    harvest_until DATETIME NULL COMMENT 'Until date parameter',
    harvest_set VARCHAR(255) NULL COMMENT 'Set parameter',
    is_full_harvest TINYINT(1) DEFAULT 0 COMMENT 'Whether this was a full harvest',

    -- Statistics
    records_total INT DEFAULT 0,
    records_created INT DEFAULT 0,
    records_updated INT DEFAULT 0,
    records_deleted INT DEFAULT 0,
    records_skipped INT DEFAULT 0,
    records_errors INT DEFAULT 0,

    -- Error tracking
    error_message TEXT NULL,

    -- User who initiated
    initiated_by INT NULL COMMENT 'User ID who started the harvest',

    -- Indexes
    INDEX idx_session_peer (peer_id),
    INDEX idx_session_status (status),
    INDEX idx_session_started (started_at),

    -- Foreign keys
    CONSTRAINT fk_session_peer
        FOREIGN KEY (peer_id) REFERENCES federation_peer(id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add metadata format support to OAI settings
-- This allows enabling/disabling the Heritage format in OAI settings
INSERT IGNORE INTO setting (name, scope, editable, deleteable, source_culture)
VALUES
    ('oai_heritage_format_enabled', 'oai', 1, 0, 'en'),
    ('federation_enabled', 'federation', 1, 0, 'en');

INSERT IGNORE INTO setting_i18n (id, culture, value)
SELECT id, 'en', '1'
FROM setting
WHERE name IN ('oai_heritage_format_enabled', 'federation_enabled')
AND NOT EXISTS (
    SELECT 1 FROM setting_i18n si WHERE si.id = setting.id AND si.culture = 'en'
);

-- ============================================================
-- FEDERATED SEARCH TABLES (#88)
-- ============================================================

-- Search API configuration for peers
-- Enables real-time federated search across institutions
CREATE TABLE IF NOT EXISTS federation_peer_search (
    id INT PRIMARY KEY AUTO_INCREMENT,
    peer_id INT NOT NULL COMMENT 'Reference to federation_peer',
    search_api_url VARCHAR(500) NULL COMMENT 'Search API endpoint URL (if different from OAI)',
    search_api_key VARCHAR(255) NULL COMMENT 'API key for search endpoint',
    search_enabled TINYINT(1) DEFAULT 1 COMMENT 'Enable federated search for this peer',
    search_timeout_ms INT DEFAULT 5000 COMMENT 'Timeout for search requests in milliseconds',
    search_max_results INT DEFAULT 50 COMMENT 'Maximum results to request from this peer',
    search_priority INT DEFAULT 100 COMMENT 'Priority for result ranking (lower = higher priority)',
    last_search_at DATETIME NULL,
    last_search_status VARCHAR(50) NULL COMMENT 'Status - uses ahg_dropdown federation_search_status',
    avg_response_time_ms INT DEFAULT 0 COMMENT 'Average response time',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_peer_search_enabled (search_enabled),
    INDEX idx_peer_search_priority (search_priority),
    UNIQUE INDEX idx_peer_search_peer (peer_id),
    CONSTRAINT fk_peer_search_peer
        FOREIGN KEY (peer_id) REFERENCES federation_peer(id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Cache for federated search results
CREATE TABLE IF NOT EXISTS federation_search_cache (
    id INT PRIMARY KEY AUTO_INCREMENT,
    query_hash VARCHAR(64) NOT NULL COMMENT 'SHA256 hash of search query',
    peer_id INT NOT NULL COMMENT 'Reference to federation_peer',
    results_json MEDIUMTEXT NOT NULL COMMENT 'Cached search results as JSON',
    result_count INT DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    expires_at DATETIME NOT NULL COMMENT 'Cache expiration time',

    INDEX idx_cache_query (query_hash),
    INDEX idx_cache_peer (peer_id),
    INDEX idx_cache_expires (expires_at),
    UNIQUE INDEX idx_cache_query_peer (query_hash, peer_id),
    CONSTRAINT fk_cache_peer
        FOREIGN KEY (peer_id) REFERENCES federation_peer(id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Federated search log for analytics
CREATE TABLE IF NOT EXISTS federation_search_log (
    id INT PRIMARY KEY AUTO_INCREMENT,
    query_text VARCHAR(500) NOT NULL,
    query_hash VARCHAR(64) NOT NULL,
    user_id INT NULL,
    peers_queried INT DEFAULT 0,
    peers_responded INT DEFAULT 0,
    peers_timeout INT DEFAULT 0,
    peers_error INT DEFAULT 0,
    total_results INT DEFAULT 0,
    total_time_ms INT DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_search_log_hash (query_hash),
    INDEX idx_search_log_user (user_id),
    INDEX idx_search_log_date (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- VOCABULARY SYNC TABLES (#89)
-- ============================================================

-- Vocabulary sync configuration per peer
CREATE TABLE IF NOT EXISTS federation_vocab_sync (
    id INT PRIMARY KEY AUTO_INCREMENT,
    peer_id INT NOT NULL COMMENT 'Reference to federation_peer',
    taxonomy_id INT NOT NULL COMMENT 'Reference to taxonomy',
    sync_direction VARCHAR(50) DEFAULT 'pull' COMMENT 'Direction - uses ahg_dropdown federation_sync_direction',
    sync_enabled TINYINT(1) DEFAULT 1,
    conflict_resolution VARCHAR(50) DEFAULT 'skip' COMMENT 'Conflict strategy - uses ahg_dropdown federation_conflict_resolution',
    sync_interval_hours INT DEFAULT 24,
    last_sync_at DATETIME NULL,
    last_sync_status VARCHAR(50) NULL COMMENT 'Status - uses ahg_dropdown federation_session_status',
    last_sync_terms_added INT DEFAULT 0,
    last_sync_terms_updated INT DEFAULT 0,
    last_sync_conflicts INT DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_vocab_sync_peer (peer_id),
    INDEX idx_vocab_sync_taxonomy (taxonomy_id),
    INDEX idx_vocab_sync_enabled (sync_enabled),
    UNIQUE INDEX idx_vocab_sync_unique (peer_id, taxonomy_id),
    CONSTRAINT fk_vocab_sync_peer
        FOREIGN KEY (peer_id) REFERENCES federation_peer(id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_vocab_sync_taxonomy
        FOREIGN KEY (taxonomy_id) REFERENCES taxonomy(id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Term mapping between local and remote terms
CREATE TABLE IF NOT EXISTS federation_term_mapping (
    id INT PRIMARY KEY AUTO_INCREMENT,
    peer_id INT NOT NULL,
    local_term_id INT NOT NULL COMMENT 'Local term ID',
    remote_term_id VARCHAR(255) NOT NULL COMMENT 'Remote term identifier',
    remote_term_name VARCHAR(500) NOT NULL COMMENT 'Remote term name for display',
    taxonomy_id INT NOT NULL,
    mapping_status VARCHAR(50) DEFAULT 'matched' COMMENT 'Status - uses ahg_dropdown federation_mapping_status',
    last_synced_at DATETIME NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_term_map_peer (peer_id),
    INDEX idx_term_map_local (local_term_id),
    INDEX idx_term_map_remote (remote_term_id),
    INDEX idx_term_map_taxonomy (taxonomy_id),
    UNIQUE INDEX idx_term_map_unique (peer_id, local_term_id),
    CONSTRAINT fk_term_map_peer
        FOREIGN KEY (peer_id) REFERENCES federation_peer(id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Vocabulary change tracking for propagation
CREATE TABLE IF NOT EXISTS federation_vocab_change (
    id INT PRIMARY KEY AUTO_INCREMENT,
    taxonomy_id INT NOT NULL,
    term_id INT NULL COMMENT 'NULL for taxonomy-level changes',
    change_type VARCHAR(50) NOT NULL COMMENT 'Type - uses ahg_dropdown federation_change_type',
    old_value TEXT NULL,
    new_value TEXT NULL,
    propagated_to_peers TEXT NULL COMMENT 'JSON array of peer IDs that received this change',
    created_by INT NULL COMMENT 'User who made the change',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_vocab_change_taxonomy (taxonomy_id),
    INDEX idx_vocab_change_term (term_id),
    INDEX idx_vocab_change_type (change_type),
    INDEX idx_vocab_change_date (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Vocabulary sync session log
CREATE TABLE IF NOT EXISTS federation_vocab_sync_log (
    id INT PRIMARY KEY AUTO_INCREMENT,
    peer_id INT NOT NULL,
    taxonomy_id INT NOT NULL,
    sync_direction VARCHAR(50) NOT NULL COMMENT 'Direction - uses ahg_dropdown federation_sync_direction',
    started_at DATETIME NOT NULL,
    completed_at DATETIME NULL,
    status VARCHAR(50) DEFAULT 'running' COMMENT 'Status - uses ahg_dropdown federation_session_status',
    terms_added INT DEFAULT 0,
    terms_updated INT DEFAULT 0,
    terms_skipped INT DEFAULT 0,
    conflicts INT DEFAULT 0,
    error_message TEXT NULL,
    initiated_by INT NULL,

    INDEX idx_vocab_log_peer (peer_id),
    INDEX idx_vocab_log_taxonomy (taxonomy_id),
    INDEX idx_vocab_log_status (status),
    INDEX idx_vocab_log_date (started_at),
    CONSTRAINT fk_vocab_log_peer
        FOREIGN KEY (peer_id) REFERENCES federation_peer(id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Loan requests between federation members (#151, same table as Heratio's,
-- heratio#1203). On AtoM a member is a federation_peer; 0 = this institution.
-- No foreign keys: both member ids and item_ref are soft references.
CREATE TABLE IF NOT EXISTS federation_loan_request (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    requesting_member_id INT NOT NULL COMMENT 'federation_peer.id asking to borrow; 0 = this institution',
    holding_member_id INT NOT NULL COMMENT 'federation_peer.id that holds the item; 0 = this institution',
    item_ref VARCHAR(255) NULL COMMENT 'local information_object id (our record or the harvested copy), soft ref',
    item_title VARCHAR(1024) NULL COMMENT 'label for the requested item, captured at request time',
    purpose VARCHAR(2048) NULL COMMENT 'why the loan is requested (exhibition, research, conservation, etc.)',
    status VARCHAR(32) NOT NULL DEFAULT 'requested' COMMENT 'requested, approved, declined, in_transit, returned, cancelled',
    needed_from DATE NULL COMMENT 'start of the requested loan window',
    needed_to DATE NULL COMMENT 'end of the requested loan window',
    notes TEXT NULL COMMENT 'notes, with a dated line per status change',
    decided_by VARCHAR(255) NULL COMMENT 'user who last changed the status',
    decided_at DATETIME NULL COMMENT 'when the status last changed',
    created_at DATETIME NULL,
    updated_at DATETIME NULL,
    INDEX idx_flr_status (status),
    INDEX idx_flr_requesting (requesting_member_id),
    INDEX idx_flr_holding (holding_member_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Public "Join the network" requests (#151, same table as Heratio's). Approval
-- is a label only; adding the institution as a peer stays a separate step.
CREATE TABLE IF NOT EXISTS federation_join_request (
    id INT PRIMARY KEY AUTO_INCREMENT,
    institution_name VARCHAR(255) NOT NULL COMMENT 'name of the institution asking to join',
    contact_name VARCHAR(255) NULL COMMENT 'person submitting the request',
    contact_email VARCHAR(255) NULL COMMENT 'contact email for follow-up',
    base_url VARCHAR(1024) NULL COMMENT 'public catalogue or site URL',
    what_they_share TEXT NULL COMMENT 'collections they would contribute',
    notes TEXT NULL COMMENT 'notes from the requester, then review notes',
    status VARCHAR(32) NOT NULL DEFAULT 'pending' COMMENT 'pending, reviewing, approved, declined',
    reviewed_by VARCHAR(255) NULL COMMENT 'administrator who last changed the status',
    reviewed_at DATETIME NULL COMMENT 'when the status last changed',
    created_at DATETIME NULL,
    updated_at DATETIME NULL,
    INDEX idx_fjr_status (status),
    INDEX idx_fjr_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
