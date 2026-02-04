-- =============================================================================
-- ahgHeritagePlugin - Knowledge Graph Schema
-- Version: 1.0.0
-- Description: Entity relationship graph for heritage discovery
-- =============================================================================

SET FOREIGN_KEY_CHECKS=0;
SET SQL_MODE='NO_AUTO_VALUE_ON_ZERO';

-- =============================================================================
-- KNOWLEDGE GRAPH TABLES
-- =============================================================================

-- Table: heritage_entity_graph_node
-- Canonical entities (deduplicated from cache)
CREATE TABLE IF NOT EXISTS heritage_entity_graph_node (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    entity_type ENUM('person', 'organization', 'place', 'date', 'event', 'work', 'concept') NOT NULL,
    canonical_value VARCHAR(500) NOT NULL,
    normalized_value VARCHAR(500) NOT NULL,

    -- Linked authority records (if exists)
    actor_id INT DEFAULT NULL,
    term_id INT DEFAULT NULL,

    -- Metadata
    occurrence_count INT DEFAULT 1,
    confidence_avg DECIMAL(5,4) DEFAULT 1.0000,
    first_seen_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_seen_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    -- External identifiers
    wikidata_id VARCHAR(20) DEFAULT NULL,
    viaf_id VARCHAR(50) DEFAULT NULL,

    -- Display metadata
    display_label VARCHAR(255) DEFAULT NULL,
    description TEXT DEFAULT NULL,
    image_url VARCHAR(500) DEFAULT NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    UNIQUE KEY uk_type_normalized (entity_type, normalized_value),
    INDEX idx_canonical (canonical_value(100)),
    INDEX idx_actor (actor_id),
    INDEX idx_term (term_id),
    INDEX idx_occurrence (occurrence_count DESC),
    INDEX idx_entity_type (entity_type),
    INDEX idx_wikidata (wikidata_id),
    INDEX idx_viaf (viaf_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: heritage_entity_graph_edge
-- Relationships between entities
CREATE TABLE IF NOT EXISTS heritage_entity_graph_edge (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    source_node_id BIGINT UNSIGNED NOT NULL,
    target_node_id BIGINT UNSIGNED NOT NULL,

    -- Relationship type
    relationship_type ENUM(
        'co_occurrence',      -- Appear in same document
        'mentioned_with',     -- Mentioned together in text
        'associated_with',    -- General association
        'employed_by',        -- Person -> Organization
        'located_in',         -- Entity -> Place
        'occurred_at',        -- Event -> Date/Place
        'related_to',         -- Generic relation
        'same_as',            -- Duplicate/alias
        'child_of',           -- Hierarchical relationship
        'preceded_by',        -- Temporal relationship
        'followed_by'         -- Temporal relationship
    ) NOT NULL DEFAULT 'co_occurrence',

    -- Strength metrics
    weight DECIMAL(8,4) DEFAULT 1.0000,
    co_occurrence_count INT DEFAULT 1,
    confidence DECIMAL(5,4) DEFAULT 1.0000,

    -- Source tracking
    source_object_ids JSON DEFAULT NULL,

    -- Metadata
    evidence TEXT DEFAULT NULL,
    is_inferred TINYINT(1) DEFAULT 0,
    is_verified TINYINT(1) DEFAULT 0,
    verified_by INT DEFAULT NULL,
    verified_at TIMESTAMP NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    UNIQUE KEY uk_edge (source_node_id, target_node_id, relationship_type),
    INDEX idx_source (source_node_id),
    INDEX idx_target (target_node_id),
    INDEX idx_type (relationship_type),
    INDEX idx_weight (weight DESC),
    INDEX idx_cooccurrence (co_occurrence_count DESC),

    CONSTRAINT fk_graph_edge_source
        FOREIGN KEY (source_node_id) REFERENCES heritage_entity_graph_node(id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_graph_edge_target
        FOREIGN KEY (target_node_id) REFERENCES heritage_entity_graph_node(id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: heritage_entity_graph_object
-- Object-to-node mapping (which objects contain which entities)
CREATE TABLE IF NOT EXISTS heritage_entity_graph_object (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    object_id INT NOT NULL,
    node_id BIGINT UNSIGNED NOT NULL,

    mention_count INT DEFAULT 1,
    confidence DECIMAL(5,4) DEFAULT 1.0000,
    source_field VARCHAR(100) DEFAULT NULL,
    extraction_method ENUM('taxonomy', 'ner', 'pattern', 'manual') DEFAULT 'ner',

    -- Position info (for highlighting)
    positions JSON DEFAULT NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    UNIQUE KEY uk_object_node (object_id, node_id),
    INDEX idx_object (object_id),
    INDEX idx_node (node_id),
    INDEX idx_method (extraction_method),

    CONSTRAINT fk_graph_object_node
        FOREIGN KEY (node_id) REFERENCES heritage_entity_graph_node(id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: heritage_graph_build_log
-- Track graph build operations
CREATE TABLE IF NOT EXISTS heritage_graph_build_log (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    build_type ENUM('full', 'incremental', 'edges_only') NOT NULL DEFAULT 'incremental',
    status ENUM('running', 'completed', 'failed') NOT NULL DEFAULT 'running',

    nodes_created INT DEFAULT 0,
    nodes_updated INT DEFAULT 0,
    edges_created INT DEFAULT 0,
    edges_updated INT DEFAULT 0,
    objects_processed INT DEFAULT 0,

    error_message TEXT DEFAULT NULL,

    started_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    completed_at TIMESTAMP NULL,

    INDEX idx_status (status),
    INDEX idx_started (started_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS=1;
