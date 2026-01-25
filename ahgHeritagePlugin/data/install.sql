-- =============================================================================
-- ahgHeritagePlugin - Complete Database Schema
-- Version: 1.0.0
-- Description: Heritage discovery platform with contributor system, access mediation,
--              custodian management, and analytics
-- =============================================================================

SET FOREIGN_KEY_CHECKS=0;
SET SQL_MODE='NO_AUTO_VALUE_ON_ZERO';

-- =============================================================================
-- CORE LANDING PAGE TABLES
-- =============================================================================

-- Table: heritage_landing_config
-- Institution landing page configuration
CREATE TABLE IF NOT EXISTS heritage_landing_config (
    id INT AUTO_INCREMENT PRIMARY KEY,
    institution_id INT DEFAULT NULL,

    -- Hero section
    hero_tagline VARCHAR(500) DEFAULT 'Discover our collections',
    hero_subtext VARCHAR(500) DEFAULT NULL,
    hero_search_placeholder VARCHAR(255) DEFAULT 'What are you looking for?',
    suggested_searches JSON DEFAULT NULL,

    -- Hero media
    hero_media JSON DEFAULT NULL,
    hero_rotation_seconds INT DEFAULT 8,
    hero_effect ENUM('kenburns', 'fade', 'none') DEFAULT 'kenburns',

    -- Sections enabled
    show_curated_stories TINYINT(1) DEFAULT 1,
    show_community_activity TINYINT(1) DEFAULT 1,
    show_filters TINYINT(1) DEFAULT 1,
    show_stats TINYINT(1) DEFAULT 1,
    show_recent_additions TINYINT(1) DEFAULT 1,

    -- Stats configuration
    stats_config JSON DEFAULT NULL,

    -- Styling
    primary_color VARCHAR(7) DEFAULT '#0d6efd',
    secondary_color VARCHAR(7) DEFAULT NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_institution (institution_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: heritage_filter_type
-- Available filter types system-wide
CREATE TABLE IF NOT EXISTS heritage_filter_type (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) NOT NULL UNIQUE,
    name VARCHAR(100) NOT NULL,
    icon VARCHAR(50) DEFAULT NULL,
    source_type ENUM('taxonomy', 'authority', 'field', 'custom') NOT NULL,
    source_reference VARCHAR(255) DEFAULT NULL,
    is_system TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_code (code),
    INDEX idx_source_type (source_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: heritage_institution_filter
-- Institution's filter configuration
CREATE TABLE IF NOT EXISTS heritage_institution_filter (
    id INT AUTO_INCREMENT PRIMARY KEY,
    institution_id INT DEFAULT NULL,
    filter_type_id INT NOT NULL,

    is_enabled TINYINT(1) DEFAULT 1,
    display_name VARCHAR(100) DEFAULT NULL,
    display_icon VARCHAR(50) DEFAULT NULL,
    display_order INT DEFAULT 100,
    show_on_landing TINYINT(1) DEFAULT 1,
    show_in_search TINYINT(1) DEFAULT 1,
    max_items_landing INT DEFAULT 6,

    is_hierarchical TINYINT(1) DEFAULT 0,
    allow_multiple TINYINT(1) DEFAULT 1,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_institution (institution_id),
    INDEX idx_filter_type (filter_type_id),
    INDEX idx_enabled (is_enabled),
    INDEX idx_display_order (display_order),

    CONSTRAINT fk_heritage_inst_filter_type
        FOREIGN KEY (filter_type_id) REFERENCES heritage_filter_type(id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: heritage_filter_value
-- Custom filter values for non-taxonomy filters
CREATE TABLE IF NOT EXISTS heritage_filter_value (
    id INT AUTO_INCREMENT PRIMARY KEY,
    institution_filter_id INT NOT NULL,
    value_code VARCHAR(100) NOT NULL,
    display_label VARCHAR(255) NOT NULL,
    display_order INT DEFAULT 100,
    parent_id INT DEFAULT NULL,
    filter_query JSON DEFAULT NULL,
    is_enabled TINYINT(1) DEFAULT 1,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_institution_filter (institution_filter_id),
    INDEX idx_parent (parent_id),
    INDEX idx_enabled (is_enabled),
    INDEX idx_display_order (display_order),

    CONSTRAINT fk_heritage_filter_value_inst
        FOREIGN KEY (institution_filter_id) REFERENCES heritage_institution_filter(id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_heritage_filter_value_parent
        FOREIGN KEY (parent_id) REFERENCES heritage_filter_value(id)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: heritage_curated_story
-- Featured stories/collections on landing page
CREATE TABLE IF NOT EXISTS heritage_curated_story (
    id INT AUTO_INCREMENT PRIMARY KEY,
    institution_id INT DEFAULT NULL,

    title VARCHAR(255) NOT NULL,
    subtitle VARCHAR(500) DEFAULT NULL,
    description TEXT DEFAULT NULL,
    cover_image VARCHAR(500) DEFAULT NULL,
    story_type VARCHAR(50) DEFAULT 'collection',

    link_type ENUM('collection', 'search', 'external', 'page') DEFAULT 'search',
    link_reference VARCHAR(500) DEFAULT NULL,

    item_count INT DEFAULT NULL,

    is_featured TINYINT(1) DEFAULT 0,
    display_order INT DEFAULT 100,
    is_enabled TINYINT(1) DEFAULT 1,

    start_date DATE DEFAULT NULL,
    end_date DATE DEFAULT NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_institution (institution_id),
    INDEX idx_featured (is_featured),
    INDEX idx_enabled (is_enabled),
    INDEX idx_display_order (display_order),
    INDEX idx_dates (start_date, end_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: heritage_hero_image
-- Hero images for rotation
CREATE TABLE IF NOT EXISTS heritage_hero_image (
    id INT AUTO_INCREMENT PRIMARY KEY,
    institution_id INT DEFAULT NULL,

    image_path VARCHAR(500) NOT NULL,
    caption VARCHAR(500) DEFAULT NULL,
    collection_name VARCHAR(255) DEFAULT NULL,
    link_url VARCHAR(500) DEFAULT NULL,

    display_order INT DEFAULT 100,
    is_enabled TINYINT(1) DEFAULT 1,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_institution (institution_id),
    INDEX idx_enabled (is_enabled),
    INDEX idx_display_order (display_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: heritage_discovery_log
-- Search analytics and logging
CREATE TABLE IF NOT EXISTS heritage_discovery_log (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    institution_id INT DEFAULT NULL,

    query_text VARCHAR(500) DEFAULT NULL,
    detected_language VARCHAR(10) DEFAULT 'en',
    query_intent VARCHAR(50) DEFAULT NULL,
    parsed_entities JSON DEFAULT NULL,
    expanded_terms JSON DEFAULT NULL,
    filters_applied JSON DEFAULT NULL,
    result_count INT DEFAULT 0,
    click_count INT DEFAULT 0,
    first_click_position INT DEFAULT NULL,

    user_id INT DEFAULT NULL,
    session_id VARCHAR(100) DEFAULT NULL,
    ip_address VARCHAR(45) DEFAULT NULL,
    user_agent VARCHAR(500) DEFAULT NULL,

    search_duration_ms INT DEFAULT NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_institution (institution_id),
    INDEX idx_user (user_id),
    INDEX idx_created (created_at),
    INDEX idx_query (query_text(100))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- DISCOVERY ENGINE TABLES
-- =============================================================================

-- Table: heritage_discovery_click
-- Track user clicks on search results for learning
CREATE TABLE IF NOT EXISTS heritage_discovery_click (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    search_log_id BIGINT NOT NULL,
    item_id INT NOT NULL,
    item_type VARCHAR(50) DEFAULT 'information_object',
    position INT NOT NULL,
    time_to_click_ms INT DEFAULT NULL,
    dwell_time_seconds INT DEFAULT NULL,

    session_id VARCHAR(100) DEFAULT NULL,
    user_id INT DEFAULT NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_search_log (search_log_id),
    INDEX idx_item (item_id),
    INDEX idx_session (session_id),
    INDEX idx_created (created_at),

    CONSTRAINT fk_discovery_click_log
        FOREIGN KEY (search_log_id) REFERENCES heritage_discovery_log(id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: heritage_learned_term
-- Learned synonyms and term relationships from user behavior
CREATE TABLE IF NOT EXISTS heritage_learned_term (
    id INT AUTO_INCREMENT PRIMARY KEY,
    institution_id INT DEFAULT NULL,

    term VARCHAR(255) NOT NULL,
    related_term VARCHAR(255) NOT NULL,
    relationship_type ENUM('synonym', 'broader', 'narrower', 'related', 'spelling') DEFAULT 'related',
    confidence_score DECIMAL(5,4) DEFAULT 0.5,
    usage_count INT DEFAULT 1,

    source ENUM('user_behavior', 'admin', 'taxonomy', 'external') DEFAULT 'user_behavior',
    is_verified TINYINT(1) DEFAULT 0,
    is_enabled TINYINT(1) DEFAULT 1,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    UNIQUE KEY uk_term_pair (institution_id, term, related_term),
    INDEX idx_term (term),
    INDEX idx_related (related_term),
    INDEX idx_confidence (confidence_score),
    INDEX idx_enabled (is_enabled)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: heritage_search_suggestion
-- Autocomplete suggestions built from successful searches
CREATE TABLE IF NOT EXISTS heritage_search_suggestion (
    id INT AUTO_INCREMENT PRIMARY KEY,
    institution_id INT DEFAULT NULL,

    suggestion_text VARCHAR(255) NOT NULL,
    suggestion_type ENUM('query', 'title', 'subject', 'creator', 'place') DEFAULT 'query',

    search_count INT DEFAULT 1,
    click_count INT DEFAULT 0,
    success_rate DECIMAL(5,4) DEFAULT 0.5,
    avg_results INT DEFAULT 0,

    last_searched_at TIMESTAMP NULL,
    is_curated TINYINT(1) DEFAULT 0,
    is_enabled TINYINT(1) DEFAULT 1,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    UNIQUE KEY uk_suggestion (institution_id, suggestion_text, suggestion_type),
    INDEX idx_text (suggestion_text),
    INDEX idx_type (suggestion_type),
    INDEX idx_search_count (search_count DESC),
    INDEX idx_success_rate (success_rate DESC),
    INDEX idx_enabled (is_enabled)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: heritage_ranking_config
-- Configurable ranking weights per institution
CREATE TABLE IF NOT EXISTS heritage_ranking_config (
    id INT AUTO_INCREMENT PRIMARY KEY,
    institution_id INT DEFAULT NULL,

    -- Relevance weights
    weight_title_match DECIMAL(4,3) DEFAULT 1.000,
    weight_content_match DECIMAL(4,3) DEFAULT 0.700,
    weight_identifier_match DECIMAL(4,3) DEFAULT 0.900,
    weight_subject_match DECIMAL(4,3) DEFAULT 0.800,
    weight_creator_match DECIMAL(4,3) DEFAULT 0.800,

    -- Quality weights
    weight_has_digital_object DECIMAL(4,3) DEFAULT 0.300,
    weight_description_length DECIMAL(4,3) DEFAULT 0.200,
    weight_has_dates DECIMAL(4,3) DEFAULT 0.150,
    weight_has_subjects DECIMAL(4,3) DEFAULT 0.150,

    -- Engagement weights
    weight_view_count DECIMAL(4,3) DEFAULT 0.100,
    weight_download_count DECIMAL(4,3) DEFAULT 0.150,
    weight_citation_count DECIMAL(4,3) DEFAULT 0.200,

    -- Boost/penalty
    boost_featured DECIMAL(4,3) DEFAULT 1.500,
    boost_recent DECIMAL(4,3) DEFAULT 1.100,
    penalty_incomplete DECIMAL(4,3) DEFAULT 0.800,

    -- Freshness decay
    freshness_decay_days INT DEFAULT 365,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    UNIQUE KEY uk_institution (institution_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: heritage_entity_cache
-- Cached extracted entities for faster filtering
CREATE TABLE IF NOT EXISTS heritage_entity_cache (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    object_id INT NOT NULL,

    entity_type ENUM('person', 'organization', 'place', 'date', 'event', 'work') NOT NULL,
    entity_value VARCHAR(500) NOT NULL,
    normalized_value VARCHAR(500) DEFAULT NULL,
    confidence_score DECIMAL(5,4) DEFAULT 1.0,

    source_field VARCHAR(100) DEFAULT NULL,
    extraction_method ENUM('taxonomy', 'ner', 'pattern', 'manual') DEFAULT 'taxonomy',

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_object (object_id),
    INDEX idx_entity_type (entity_type),
    INDEX idx_entity_value (entity_value(100)),
    INDEX idx_normalized (normalized_value(100))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- CONTRIBUTOR SYSTEM TABLES
-- =============================================================================

-- Table: heritage_contributor
-- Public user accounts (separate from AtoM users)
CREATE TABLE IF NOT EXISTS heritage_contributor (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    display_name VARCHAR(100) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    avatar_url VARCHAR(500) DEFAULT NULL,
    bio TEXT DEFAULT NULL,
    trust_level ENUM('new', 'contributor', 'trusted', 'expert') DEFAULT 'new',
    email_verified TINYINT(1) DEFAULT 0,
    email_verify_token VARCHAR(100) DEFAULT NULL,
    email_verify_expires TIMESTAMP NULL,
    password_reset_token VARCHAR(100) DEFAULT NULL,
    password_reset_expires TIMESTAMP NULL,
    total_contributions INT DEFAULT 0,
    approved_contributions INT DEFAULT 0,
    rejected_contributions INT DEFAULT 0,
    points INT DEFAULT 0,
    badges JSON DEFAULT NULL,
    preferences JSON DEFAULT NULL,
    is_active TINYINT(1) DEFAULT 1,
    last_login_at TIMESTAMP NULL,
    last_contribution_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_email (email),
    INDEX idx_trust_level (trust_level),
    INDEX idx_points (points DESC),
    INDEX idx_verified (email_verified),
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: heritage_contribution_type
-- Types of contributions users can make
CREATE TABLE IF NOT EXISTS heritage_contribution_type (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) NOT NULL UNIQUE,
    name VARCHAR(100) NOT NULL,
    description TEXT DEFAULT NULL,
    icon VARCHAR(50) DEFAULT 'bi-pencil',
    color VARCHAR(20) DEFAULT 'primary',
    requires_validation TINYINT(1) DEFAULT 1,
    points_value INT DEFAULT 10,
    min_trust_level ENUM('new', 'contributor', 'trusted', 'expert') DEFAULT 'new',
    display_order INT DEFAULT 100,
    is_active TINYINT(1) DEFAULT 1,
    config_json JSON DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_active (is_active),
    INDEX idx_order (display_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: heritage_contribution
-- Individual contributions from users
CREATE TABLE IF NOT EXISTS heritage_contribution (
    id INT AUTO_INCREMENT PRIMARY KEY,
    contributor_id INT NOT NULL,
    information_object_id INT NOT NULL,
    contribution_type_id INT NOT NULL,
    content JSON NOT NULL,
    status ENUM('pending', 'approved', 'rejected', 'superseded') DEFAULT 'pending',
    reviewed_by INT DEFAULT NULL,
    reviewed_at TIMESTAMP NULL,
    review_notes TEXT DEFAULT NULL,
    points_awarded INT DEFAULT 0,
    version_number INT DEFAULT 1,
    is_featured TINYINT(1) DEFAULT 0,
    view_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_contributor (contributor_id),
    INDEX idx_object (information_object_id),
    INDEX idx_type (contribution_type_id),
    INDEX idx_status (status),
    INDEX idx_reviewed_by (reviewed_by),
    INDEX idx_created (created_at),
    INDEX idx_featured (is_featured),

    CONSTRAINT fk_heritage_contribution_contributor
        FOREIGN KEY (contributor_id) REFERENCES heritage_contributor(id)
        ON DELETE CASCADE ON UPDATE CASCADE,

    CONSTRAINT fk_heritage_contribution_type
        FOREIGN KEY (contribution_type_id) REFERENCES heritage_contribution_type(id)
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: heritage_contribution_version
-- Version history for contribution edits
CREATE TABLE IF NOT EXISTS heritage_contribution_version (
    id INT AUTO_INCREMENT PRIMARY KEY,
    contribution_id INT NOT NULL,
    version_number INT NOT NULL,
    content JSON NOT NULL,
    created_by INT NOT NULL,
    change_summary VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_contribution (contribution_id),
    INDEX idx_version (contribution_id, version_number),

    CONSTRAINT fk_heritage_contribution_version_contribution
        FOREIGN KEY (contribution_id) REFERENCES heritage_contribution(id)
        ON DELETE CASCADE ON UPDATE CASCADE,

    CONSTRAINT fk_heritage_contribution_version_creator
        FOREIGN KEY (created_by) REFERENCES heritage_contributor(id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: heritage_contributor_session
-- Session tokens for contributor authentication
CREATE TABLE IF NOT EXISTS heritage_contributor_session (
    id INT AUTO_INCREMENT PRIMARY KEY,
    contributor_id INT NOT NULL,
    token VARCHAR(100) NOT NULL UNIQUE,
    ip_address VARCHAR(45) DEFAULT NULL,
    user_agent VARCHAR(500) DEFAULT NULL,
    expires_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_contributor (contributor_id),
    INDEX idx_token (token),
    INDEX idx_expires (expires_at),

    CONSTRAINT fk_heritage_contributor_session_contributor
        FOREIGN KEY (contributor_id) REFERENCES heritage_contributor(id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: heritage_contributor_badge
-- Badges that can be earned
CREATE TABLE IF NOT EXISTS heritage_contributor_badge (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) NOT NULL UNIQUE,
    name VARCHAR(100) NOT NULL,
    description TEXT DEFAULT NULL,
    icon VARCHAR(50) DEFAULT 'bi-award',
    color VARCHAR(20) DEFAULT 'primary',
    criteria_type ENUM('contribution_count', 'approval_rate', 'points', 'type_specific', 'manual') DEFAULT 'contribution_count',
    criteria_value INT DEFAULT 0,
    criteria_config JSON DEFAULT NULL,
    points_bonus INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    display_order INT DEFAULT 100,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: heritage_contributor_badge_award
-- Badges awarded to contributors
CREATE TABLE IF NOT EXISTS heritage_contributor_badge_award (
    id INT AUTO_INCREMENT PRIMARY KEY,
    contributor_id INT NOT NULL,
    badge_id INT NOT NULL,
    awarded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    UNIQUE KEY uk_contributor_badge (contributor_id, badge_id),

    CONSTRAINT fk_heritage_badge_award_contributor
        FOREIGN KEY (contributor_id) REFERENCES heritage_contributor(id)
        ON DELETE CASCADE ON UPDATE CASCADE,

    CONSTRAINT fk_heritage_badge_award_badge
        FOREIGN KEY (badge_id) REFERENCES heritage_contributor_badge(id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- ADMIN CONFIGURATION TABLES (Session 8)
-- =============================================================================

-- Table: heritage_feature_toggle
-- Feature flags per institution
CREATE TABLE IF NOT EXISTS heritage_feature_toggle (
    id INT AUTO_INCREMENT PRIMARY KEY,
    institution_id INT DEFAULT NULL,
    feature_code VARCHAR(100) NOT NULL,
    feature_name VARCHAR(255) NOT NULL,
    is_enabled TINYINT(1) DEFAULT 1,
    config_json JSON DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    UNIQUE KEY uk_institution_feature (institution_id, feature_code),
    INDEX idx_feature_code (feature_code),
    INDEX idx_enabled (is_enabled)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: heritage_branding_config
-- Institution branding configuration
CREATE TABLE IF NOT EXISTS heritage_branding_config (
    id INT AUTO_INCREMENT PRIMARY KEY,
    institution_id INT DEFAULT NULL,
    logo_path VARCHAR(500) DEFAULT NULL,
    favicon_path VARCHAR(500) DEFAULT NULL,
    primary_color VARCHAR(7) DEFAULT '#0d6efd',
    secondary_color VARCHAR(7) DEFAULT NULL,
    accent_color VARCHAR(7) DEFAULT NULL,
    banner_text VARCHAR(500) DEFAULT NULL,
    footer_text TEXT DEFAULT NULL,
    custom_css TEXT DEFAULT NULL,
    social_links JSON DEFAULT NULL,
    contact_info JSON DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    UNIQUE KEY uk_institution (institution_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- ACCESS MEDIATION TABLES (Session 6)
-- =============================================================================

-- Table: heritage_trust_level
-- User trust levels for access control
CREATE TABLE IF NOT EXISTS heritage_trust_level (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) NOT NULL UNIQUE,
    name VARCHAR(100) NOT NULL,
    level INT NOT NULL DEFAULT 0,
    can_view_restricted TINYINT(1) DEFAULT 0,
    can_download TINYINT(1) DEFAULT 0,
    can_bulk_download TINYINT(1) DEFAULT 0,
    is_system TINYINT(1) DEFAULT 0,
    description TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_level (level),
    INDEX idx_system (is_system)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: heritage_user_trust
-- User trust level assignments
CREATE TABLE IF NOT EXISTS heritage_user_trust (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    trust_level_id INT NOT NULL,
    institution_id INT DEFAULT NULL,
    granted_by INT DEFAULT NULL,
    granted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NULL,
    notes TEXT DEFAULT NULL,
    is_active TINYINT(1) DEFAULT 1,

    UNIQUE KEY uk_user_institution (user_id, institution_id),
    INDEX idx_trust_level (trust_level_id),
    INDEX idx_expires (expires_at),
    INDEX idx_active (is_active),

    CONSTRAINT fk_heritage_user_trust_level
        FOREIGN KEY (trust_level_id) REFERENCES heritage_trust_level(id)
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: heritage_purpose
-- Purposes for access requests
CREATE TABLE IF NOT EXISTS heritage_purpose (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) NOT NULL UNIQUE,
    name VARCHAR(100) NOT NULL,
    description TEXT DEFAULT NULL,
    requires_approval TINYINT(1) DEFAULT 0,
    min_trust_level INT DEFAULT 0,
    is_enabled TINYINT(1) DEFAULT 1,
    display_order INT DEFAULT 100,

    INDEX idx_enabled (is_enabled),
    INDEX idx_order (display_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: heritage_embargo
-- Embargoes on objects
CREATE TABLE IF NOT EXISTS heritage_embargo (
    id INT AUTO_INCREMENT PRIMARY KEY,
    object_id INT NOT NULL,
    embargo_type ENUM('full', 'digital_only', 'metadata_hidden') DEFAULT 'full',
    reason TEXT DEFAULT NULL,
    legal_basis VARCHAR(255) DEFAULT NULL,
    start_date DATE DEFAULT NULL,
    end_date DATE DEFAULT NULL,
    auto_release TINYINT(1) DEFAULT 1,
    notify_on_release TINYINT(1) DEFAULT 1,
    created_by INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_object (object_id),
    INDEX idx_end_date (end_date),
    INDEX idx_type (embargo_type),
    INDEX idx_auto_release (auto_release, end_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: heritage_access_request
-- Access requests from users
CREATE TABLE IF NOT EXISTS heritage_access_request (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    object_id INT NOT NULL,
    purpose_id INT DEFAULT NULL,
    purpose_text VARCHAR(255) DEFAULT NULL,
    justification TEXT DEFAULT NULL,
    research_description TEXT DEFAULT NULL,
    institution_affiliation VARCHAR(255) DEFAULT NULL,
    status ENUM('pending', 'approved', 'denied', 'expired', 'withdrawn') DEFAULT 'pending',
    decision_by INT DEFAULT NULL,
    decision_at TIMESTAMP NULL,
    decision_notes TEXT DEFAULT NULL,
    valid_from DATE DEFAULT NULL,
    valid_until DATE DEFAULT NULL,
    access_granted JSON DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_user (user_id),
    INDEX idx_object (object_id),
    INDEX idx_status (status),
    INDEX idx_created (created_at),

    CONSTRAINT fk_heritage_access_request_purpose
        FOREIGN KEY (purpose_id) REFERENCES heritage_purpose(id)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: heritage_access_rule
-- Access rules for objects/collections
CREATE TABLE IF NOT EXISTS heritage_access_rule (
    id INT AUTO_INCREMENT PRIMARY KEY,
    object_id INT DEFAULT NULL,
    collection_id INT DEFAULT NULL,
    repository_id INT DEFAULT NULL,
    rule_type ENUM('allow', 'deny', 'require_approval') DEFAULT 'deny',
    applies_to ENUM('all', 'anonymous', 'authenticated', 'trust_level') DEFAULT 'all',
    trust_level_id INT DEFAULT NULL,
    action ENUM('view', 'view_metadata', 'download', 'download_master', 'print', 'all') DEFAULT 'view',
    priority INT DEFAULT 100,
    is_enabled TINYINT(1) DEFAULT 1,
    notes TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_object (object_id),
    INDEX idx_collection (collection_id),
    INDEX idx_repository (repository_id),
    INDEX idx_enabled (is_enabled),
    INDEX idx_priority (priority)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: heritage_popia_flag
-- POPIA/GDPR privacy flags
CREATE TABLE IF NOT EXISTS heritage_popia_flag (
    id INT AUTO_INCREMENT PRIMARY KEY,
    object_id INT NOT NULL,
    flag_type ENUM('personal_info', 'sensitive', 'children', 'health', 'biometric', 'criminal', 'financial', 'political', 'religious', 'sexual') NOT NULL,
    severity ENUM('low', 'medium', 'high', 'critical') DEFAULT 'medium',
    description TEXT DEFAULT NULL,
    affected_fields JSON DEFAULT NULL,
    detected_by ENUM('automatic', 'manual', 'review') DEFAULT 'manual',
    is_resolved TINYINT(1) DEFAULT 0,
    resolution_notes TEXT DEFAULT NULL,
    resolved_by INT DEFAULT NULL,
    resolved_at TIMESTAMP NULL,
    created_by INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_object (object_id),
    INDEX idx_flag_type (flag_type),
    INDEX idx_severity (severity),
    INDEX idx_resolved (is_resolved)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- CUSTODIAN INTERFACE TABLES (Session 7)
-- =============================================================================

-- Table: heritage_audit_log
-- Detailed change tracking
CREATE TABLE IF NOT EXISTS heritage_audit_log (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    user_id INT DEFAULT NULL,
    username VARCHAR(255) DEFAULT NULL,
    object_id INT DEFAULT NULL,
    object_type VARCHAR(100) DEFAULT 'information_object',
    object_identifier VARCHAR(255) DEFAULT NULL,
    action VARCHAR(100) NOT NULL,
    action_category ENUM('create', 'update', 'delete', 'view', 'export', 'import', 'batch', 'access', 'system') DEFAULT 'update',
    field_name VARCHAR(100) DEFAULT NULL,
    old_value TEXT DEFAULT NULL,
    new_value TEXT DEFAULT NULL,
    changes_json JSON DEFAULT NULL,
    metadata JSON DEFAULT NULL,
    ip_address VARCHAR(45) DEFAULT NULL,
    user_agent VARCHAR(500) DEFAULT NULL,
    session_id VARCHAR(100) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_object (object_id, object_type),
    INDEX idx_user (user_id),
    INDEX idx_action (action),
    INDEX idx_category (action_category),
    INDEX idx_created (created_at),
    INDEX idx_field (field_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: heritage_batch_job
-- Batch job tracking
CREATE TABLE IF NOT EXISTS heritage_batch_job (
    id INT AUTO_INCREMENT PRIMARY KEY,
    job_type VARCHAR(100) NOT NULL,
    job_name VARCHAR(255) DEFAULT NULL,
    status ENUM('pending', 'queued', 'processing', 'completed', 'failed', 'cancelled', 'paused') DEFAULT 'pending',
    user_id INT NOT NULL,
    total_items INT DEFAULT 0,
    processed_items INT DEFAULT 0,
    successful_items INT DEFAULT 0,
    failed_items INT DEFAULT 0,
    skipped_items INT DEFAULT 0,
    parameters JSON DEFAULT NULL,
    results JSON DEFAULT NULL,
    error_log JSON DEFAULT NULL,
    error_message TEXT DEFAULT NULL,
    progress_message VARCHAR(500) DEFAULT NULL,
    started_at TIMESTAMP NULL,
    completed_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_status (status),
    INDEX idx_user (user_id),
    INDEX idx_type (job_type),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: heritage_batch_item
-- Individual items in a batch job
CREATE TABLE IF NOT EXISTS heritage_batch_item (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    job_id INT NOT NULL,
    object_id INT NOT NULL,
    status ENUM('pending', 'processing', 'success', 'failed', 'skipped') DEFAULT 'pending',
    old_values JSON DEFAULT NULL,
    new_values JSON DEFAULT NULL,
    error_message TEXT DEFAULT NULL,
    processed_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_job (job_id),
    INDEX idx_object (object_id),
    INDEX idx_status (status),

    CONSTRAINT fk_heritage_batch_item_job
        FOREIGN KEY (job_id) REFERENCES heritage_batch_job(id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- ANALYTICS & LEARNING TABLES (Session 9)
-- =============================================================================

-- Table: heritage_analytics_daily
-- Daily aggregate metrics
CREATE TABLE IF NOT EXISTS heritage_analytics_daily (
    id INT AUTO_INCREMENT PRIMARY KEY,
    institution_id INT DEFAULT NULL,
    date DATE NOT NULL,
    metric_type VARCHAR(100) NOT NULL,
    metric_value DECIMAL(15,2) DEFAULT 0,
    previous_value DECIMAL(15,2) DEFAULT NULL,
    change_percent DECIMAL(10,2) DEFAULT NULL,
    metadata JSON DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    UNIQUE KEY uk_date_metric (institution_id, date, metric_type),
    INDEX idx_date (date),
    INDEX idx_metric_type (metric_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: heritage_analytics_search
-- Search pattern tracking
CREATE TABLE IF NOT EXISTS heritage_analytics_search (
    id INT AUTO_INCREMENT PRIMARY KEY,
    institution_id INT DEFAULT NULL,
    date DATE NOT NULL,
    query_pattern VARCHAR(255) DEFAULT NULL,
    query_normalized VARCHAR(255) DEFAULT NULL,
    search_count INT DEFAULT 0,
    click_count INT DEFAULT 0,
    zero_result_count INT DEFAULT 0,
    avg_results DECIMAL(10,2) DEFAULT 0,
    avg_position_clicked DECIMAL(5,2) DEFAULT NULL,
    conversion_rate DECIMAL(5,4) DEFAULT 0,

    UNIQUE KEY uk_date_pattern (institution_id, date, query_pattern),
    INDEX idx_date (date),
    INDEX idx_search_count (search_count DESC),
    INDEX idx_zero_result (zero_result_count DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: heritage_analytics_content
-- Content performance tracking
CREATE TABLE IF NOT EXISTS heritage_analytics_content (
    id INT AUTO_INCREMENT PRIMARY KEY,
    object_id INT NOT NULL,
    period_start DATE NOT NULL,
    period_end DATE NOT NULL,
    view_count INT DEFAULT 0,
    unique_viewers INT DEFAULT 0,
    search_appearances INT DEFAULT 0,
    download_count INT DEFAULT 0,
    citation_count INT DEFAULT 0,
    share_count INT DEFAULT 0,
    avg_dwell_time_seconds INT DEFAULT NULL,
    click_through_rate DECIMAL(5,4) DEFAULT 0,
    bounce_rate DECIMAL(5,4) DEFAULT NULL,
    metadata JSON DEFAULT NULL,

    UNIQUE KEY uk_object_period (object_id, period_start, period_end),
    INDEX idx_period (period_start, period_end),
    INDEX idx_views (view_count DESC),
    INDEX idx_ctr (click_through_rate DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: heritage_analytics_alert
-- Actionable alerts and insights
CREATE TABLE IF NOT EXISTS heritage_analytics_alert (
    id INT AUTO_INCREMENT PRIMARY KEY,
    institution_id INT DEFAULT NULL,
    alert_type VARCHAR(100) NOT NULL,
    category ENUM('content', 'search', 'access', 'quality', 'system', 'opportunity') DEFAULT 'system',
    severity ENUM('info', 'warning', 'critical', 'success') DEFAULT 'info',
    title VARCHAR(255) NOT NULL,
    message TEXT DEFAULT NULL,
    action_url VARCHAR(500) DEFAULT NULL,
    action_label VARCHAR(100) DEFAULT NULL,
    related_data JSON DEFAULT NULL,
    is_read TINYINT(1) DEFAULT 0,
    is_dismissed TINYINT(1) DEFAULT 0,
    dismissed_by INT DEFAULT NULL,
    dismissed_at TIMESTAMP NULL,
    expires_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_institution (institution_id),
    INDEX idx_type (alert_type),
    INDEX idx_category (category),
    INDEX idx_severity (severity),
    INDEX idx_dismissed (is_dismissed),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: heritage_content_quality
-- Content quality scores
CREATE TABLE IF NOT EXISTS heritage_content_quality (
    id INT AUTO_INCREMENT PRIMARY KEY,
    object_id INT NOT NULL UNIQUE,
    overall_score DECIMAL(5,2) DEFAULT 0,
    completeness_score DECIMAL(5,2) DEFAULT 0,
    accessibility_score DECIMAL(5,2) DEFAULT 0,
    engagement_score DECIMAL(5,2) DEFAULT 0,
    discoverability_score DECIMAL(5,2) DEFAULT 0,
    issues JSON DEFAULT NULL,
    suggestions JSON DEFAULT NULL,
    last_calculated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_overall (overall_score DESC),
    INDEX idx_completeness (completeness_score)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- ENHANCED LANDING PAGE TABLES
-- =============================================================================

-- Table: heritage_featured_collection
-- Curated collections for showcase on landing page
CREATE TABLE IF NOT EXISTS heritage_featured_collection (
    id INT AUTO_INCREMENT PRIMARY KEY,
    institution_id INT DEFAULT NULL,

    -- Content
    title VARCHAR(255) NOT NULL,
    subtitle VARCHAR(500) DEFAULT NULL,
    description TEXT DEFAULT NULL,
    curator_note TEXT DEFAULT NULL,

    -- Visual
    cover_image VARCHAR(500) DEFAULT NULL,
    thumbnail_image VARCHAR(500) DEFAULT NULL,
    background_color VARCHAR(7) DEFAULT NULL,
    text_color VARCHAR(7) DEFAULT '#ffffff',

    -- Link
    link_type ENUM('collection', 'search', 'repository', 'external') DEFAULT 'search',
    link_reference VARCHAR(500) DEFAULT NULL,
    collection_id INT DEFAULT NULL,
    repository_id INT DEFAULT NULL,
    search_query JSON DEFAULT NULL,

    -- Stats (cached)
    item_count INT DEFAULT 0,
    image_count INT DEFAULT 0,

    -- Display
    display_size ENUM('small', 'medium', 'large', 'featured') DEFAULT 'medium',
    display_order INT DEFAULT 100,
    show_on_landing TINYINT(1) DEFAULT 1,
    is_featured TINYINT(1) DEFAULT 0,
    is_enabled TINYINT(1) DEFAULT 1,

    -- Scheduling
    start_date DATE DEFAULT NULL,
    end_date DATE DEFAULT NULL,

    created_by INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_institution (institution_id),
    INDEX idx_featured (is_featured),
    INDEX idx_enabled (is_enabled),
    INDEX idx_display_order (display_order),
    INDEX idx_dates (start_date, end_date),
    INDEX idx_link_type (link_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: heritage_hero_slide
-- Full-bleed hero carousel slides
CREATE TABLE IF NOT EXISTS heritage_hero_slide (
    id INT AUTO_INCREMENT PRIMARY KEY,
    institution_id INT DEFAULT NULL,

    -- Content
    title VARCHAR(255) DEFAULT NULL,
    subtitle VARCHAR(500) DEFAULT NULL,
    description TEXT DEFAULT NULL,

    -- Media
    image_path VARCHAR(500) NOT NULL,
    image_alt VARCHAR(255) DEFAULT NULL,
    video_url VARCHAR(500) DEFAULT NULL,
    media_type ENUM('image', 'video') DEFAULT 'image',

    -- Visual effects
    overlay_type ENUM('none', 'gradient', 'solid') DEFAULT 'gradient',
    overlay_color VARCHAR(7) DEFAULT '#000000',
    overlay_opacity DECIMAL(3,2) DEFAULT 0.50,
    text_position ENUM('left', 'center', 'right', 'bottom-left', 'bottom-right') DEFAULT 'left',
    ken_burns TINYINT(1) DEFAULT 1,

    -- Call to action
    cta_text VARCHAR(100) DEFAULT NULL,
    cta_url VARCHAR(500) DEFAULT NULL,
    cta_style ENUM('primary', 'secondary', 'outline', 'light') DEFAULT 'primary',

    -- Attribution
    source_item_id INT DEFAULT NULL,
    source_collection VARCHAR(255) DEFAULT NULL,
    photographer_credit VARCHAR(255) DEFAULT NULL,

    -- Display
    display_order INT DEFAULT 100,
    display_duration INT DEFAULT 8,
    is_enabled TINYINT(1) DEFAULT 1,

    -- Scheduling
    start_date DATE DEFAULT NULL,
    end_date DATE DEFAULT NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_institution (institution_id),
    INDEX idx_enabled (is_enabled),
    INDEX idx_display_order (display_order),
    INDEX idx_dates (start_date, end_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: heritage_featured_collection
-- Curated collections to display on landing page
CREATE TABLE IF NOT EXISTS heritage_featured_collection (
    id INT AUTO_INCREMENT PRIMARY KEY,
    institution_id INT DEFAULT NULL,
    source_type ENUM('iiif', 'archival') NOT NULL DEFAULT 'archival',
    source_id INT NOT NULL COMMENT 'iiif_collection.id or information_object.id',
    title VARCHAR(255) DEFAULT NULL COMMENT 'Override title',
    description TEXT DEFAULT NULL COMMENT 'Override description',
    thumbnail_path VARCHAR(500) DEFAULT NULL COMMENT 'Override thumbnail',
    display_order INT DEFAULT 100,
    is_enabled TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_enabled (is_enabled),
    INDEX idx_order (display_order),
    UNIQUE KEY uk_source (source_type, source_id, institution_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: heritage_explore_category
-- Visual browse categories (Time, Place, People, Theme, Format, Trending)
CREATE TABLE IF NOT EXISTS heritage_explore_category (
    id INT AUTO_INCREMENT PRIMARY KEY,
    institution_id INT DEFAULT NULL,

    -- Content
    code VARCHAR(50) NOT NULL,
    name VARCHAR(100) NOT NULL,
    description TEXT DEFAULT NULL,
    tagline VARCHAR(255) DEFAULT NULL,

    -- Visual
    icon VARCHAR(50) DEFAULT 'bi-grid',
    cover_image VARCHAR(500) DEFAULT NULL,
    background_color VARCHAR(7) DEFAULT '#0d6efd',
    text_color VARCHAR(7) DEFAULT '#ffffff',

    -- Data source
    source_type ENUM('taxonomy', 'authority', 'field', 'facet', 'custom') NOT NULL,
    source_reference VARCHAR(255) DEFAULT NULL,
    taxonomy_id INT DEFAULT NULL,

    -- Display configuration
    display_style ENUM('grid', 'list', 'timeline', 'map', 'carousel') DEFAULT 'grid',
    items_per_page INT DEFAULT 24,
    show_counts TINYINT(1) DEFAULT 1,
    show_thumbnails TINYINT(1) DEFAULT 1,

    -- Landing page display
    display_order INT DEFAULT 100,
    show_on_landing TINYINT(1) DEFAULT 1,
    landing_items INT DEFAULT 6,
    is_enabled TINYINT(1) DEFAULT 1,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    UNIQUE KEY uk_institution_code (institution_id, code),
    INDEX idx_enabled (is_enabled),
    INDEX idx_display_order (display_order),
    INDEX idx_source_type (source_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: heritage_timeline_period
-- Time periods for timeline navigation
CREATE TABLE IF NOT EXISTS heritage_timeline_period (
    id INT AUTO_INCREMENT PRIMARY KEY,
    institution_id INT DEFAULT NULL,

    -- Content
    name VARCHAR(100) NOT NULL,
    short_name VARCHAR(50) DEFAULT NULL,
    description TEXT DEFAULT NULL,

    -- Date range
    start_year INT NOT NULL,
    end_year INT DEFAULT NULL,
    circa TINYINT(1) DEFAULT 0,

    -- Visual
    cover_image VARCHAR(500) DEFAULT NULL,
    thumbnail_image VARCHAR(500) DEFAULT NULL,
    background_color VARCHAR(7) DEFAULT NULL,

    -- Search integration
    search_query JSON DEFAULT NULL,
    date_field VARCHAR(100) DEFAULT 'dates',

    -- Stats (cached)
    item_count INT DEFAULT 0,

    -- Display
    display_order INT DEFAULT 100,
    show_on_landing TINYINT(1) DEFAULT 1,
    is_enabled TINYINT(1) DEFAULT 1,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_institution (institution_id),
    INDEX idx_enabled (is_enabled),
    INDEX idx_display_order (display_order),
    INDEX idx_years (start_year, end_year)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS=1;

-- =============================================================================
-- SEED DATA
-- =============================================================================

-- Default filter types
INSERT IGNORE INTO heritage_filter_type (code, name, icon, source_type, source_reference, is_system) VALUES
('content_type', 'Format', 'bi-file-earmark', 'taxonomy', 'contentType', 1),
('time_period', 'Time Period', 'bi-calendar', 'field', 'date', 1),
('place', 'Place', 'bi-geo-alt', 'authority', 'place', 1),
('subject', 'Subject', 'bi-tag', 'taxonomy', 'subject', 1),
('creator', 'Creator', 'bi-person', 'authority', 'actor', 1),
('collection', 'Collection', 'bi-collection', 'field', 'repository', 1),
('language', 'Language', 'bi-translate', 'taxonomy', 'language', 1),
('glam_sector', 'Type', 'bi-building', 'taxonomy', 'glamSector', 1);

-- Default landing config
INSERT IGNORE INTO heritage_landing_config (id, institution_id, hero_tagline, hero_subtext, hero_search_placeholder, suggested_searches, stats_config) VALUES
(1, NULL, 'Discover Our Heritage', 'Explore collections spanning centuries of history, culture, and human achievement', 'Search photographs, documents, artifacts...', '["photographs", "maps", "letters", "newspapers"]', '{"show_items": true, "show_collections": true, "show_contributors": false}');

-- Default institution filters (only insert if not exists for global scope)
INSERT INTO heritage_institution_filter (institution_id, filter_type_id, is_enabled, display_order, show_on_landing, show_in_search, max_items_landing)
SELECT NULL, ft.id, 1,
    CASE ft.code
        WHEN 'content_type' THEN 10
        WHEN 'time_period' THEN 20
        WHEN 'place' THEN 30
        WHEN 'subject' THEN 40
        WHEN 'creator' THEN 50
        WHEN 'collection' THEN 60
        WHEN 'language' THEN 70
        WHEN 'glam_sector' THEN 80
    END,
    CASE WHEN ft.code IN ('content_type', 'time_period', 'place', 'subject', 'creator', 'collection') THEN 1 ELSE 0 END,
    1,
    6
FROM heritage_filter_type ft
WHERE ft.is_system = 1
AND NOT EXISTS (
    SELECT 1 FROM heritage_institution_filter hif
    WHERE hif.filter_type_id = ft.id
    AND hif.institution_id IS NULL
);

-- Default ranking config
INSERT IGNORE INTO heritage_ranking_config (institution_id) VALUES (NULL);

-- Default learned terms (common synonyms)
INSERT IGNORE INTO heritage_learned_term (institution_id, term, related_term, relationship_type, confidence_score, source, is_verified) VALUES
(NULL, 'photo', 'photograph', 'synonym', 0.95, 'admin', 1),
(NULL, 'photos', 'photographs', 'synonym', 0.95, 'admin', 1),
(NULL, 'picture', 'photograph', 'synonym', 0.90, 'admin', 1),
(NULL, 'image', 'photograph', 'related', 0.85, 'admin', 1),
(NULL, 'doc', 'document', 'synonym', 0.90, 'admin', 1),
(NULL, 'letter', 'correspondence', 'related', 0.85, 'admin', 1),
(NULL, 'memo', 'memorandum', 'synonym', 0.95, 'admin', 1),
(NULL, 'map', 'cartographic material', 'related', 0.80, 'admin', 1),
(NULL, 'chart', 'map', 'related', 0.75, 'admin', 1),
(NULL, 'old', 'historic', 'related', 0.70, 'admin', 1),
(NULL, 'ancient', 'historic', 'related', 0.75, 'admin', 1),
(NULL, 'vintage', 'historic', 'related', 0.80, 'admin', 1),
(NULL, 'antique', 'historic', 'related', 0.75, 'admin', 1),
(NULL, 'arcive', 'archive', 'spelling', 0.99, 'admin', 1),
(NULL, 'photgraph', 'photograph', 'spelling', 0.99, 'admin', 1),
(NULL, 'documnet', 'document', 'spelling', 0.99, 'admin', 1);

-- Default contribution types
INSERT IGNORE INTO heritage_contribution_type (code, name, description, icon, color, requires_validation, points_value, display_order) VALUES
('transcription', 'Transcription', 'Transcribe handwritten or typed documents into searchable text', 'bi-file-text', 'primary', 1, 25, 1),
('identification', 'Identification', 'Identify people, places, or objects in photographs and documents', 'bi-person-badge', 'success', 1, 15, 2),
('context', 'Historical Context', 'Add historical context, personal memories, or background information', 'bi-book', 'info', 1, 20, 3),
('correction', 'Correction', 'Suggest corrections to existing metadata or descriptions', 'bi-pencil-square', 'warning', 1, 10, 4),
('translation', 'Translation', 'Translate content into other languages', 'bi-translate', 'secondary', 1, 30, 5),
('tag', 'Tags/Keywords', 'Add relevant tags and keywords to improve discoverability', 'bi-tags', 'dark', 0, 5, 6);

-- Default badges
INSERT IGNORE INTO heritage_contributor_badge (code, name, description, icon, color, criteria_type, criteria_value, display_order) VALUES
('first_contribution', 'First Steps', 'Made your first contribution', 'bi-star', 'warning', 'contribution_count', 1, 1),
('contributor_10', 'Active Contributor', 'Made 10 approved contributions', 'bi-star-fill', 'warning', 'contribution_count', 10, 2),
('contributor_50', 'Dedicated Contributor', 'Made 50 approved contributions', 'bi-trophy', 'warning', 'contribution_count', 50, 3),
('contributor_100', 'Heritage Champion', 'Made 100 approved contributions', 'bi-trophy-fill', 'primary', 'contribution_count', 100, 4),
('transcriber', 'Transcription Expert', 'Completed 25 transcriptions', 'bi-file-text-fill', 'primary', 'type_specific', 25, 10),
('identifier', 'Sharp Eye', 'Identified people in 25 photographs', 'bi-eye', 'success', 'type_specific', 25, 11),
('historian', 'Local Historian', 'Added context to 25 records', 'bi-book-fill', 'info', 'type_specific', 25, 12),
('perfectionist', 'High Quality', 'Maintained 95% approval rate on 20+ contributions', 'bi-check-circle-fill', 'success', 'approval_rate', 95, 20);

-- Default trust levels
INSERT IGNORE INTO heritage_trust_level (code, name, level, can_view_restricted, can_download, can_bulk_download, is_system, description) VALUES
('anonymous', 'Anonymous', 0, 0, 0, 0, 1, 'Unauthenticated visitors'),
('registered', 'Registered User', 1, 0, 1, 0, 1, 'Basic registered account'),
('contributor', 'Contributor', 2, 0, 1, 0, 1, 'Users who contribute content'),
('trusted', 'Trusted User', 3, 1, 1, 0, 1, 'Verified trusted researchers'),
('moderator', 'Moderator', 4, 1, 1, 1, 1, 'Content moderators'),
('custodian', 'Custodian', 5, 1, 1, 1, 1, 'Full custodial access');

-- Default purposes
INSERT IGNORE INTO heritage_purpose (code, name, description, requires_approval, min_trust_level, display_order) VALUES
('personal', 'Personal/Family Research', 'Research into family history and genealogy', 0, 0, 1),
('academic', 'Academic Research', 'Scholarly research for educational institutions', 0, 0, 2),
('education', 'Educational Use', 'Use in teaching and educational materials', 0, 0, 3),
('commercial', 'Commercial Use', 'For-profit use requiring license agreement', 1, 1, 4),
('media', 'Media/Journalism', 'Publication in news or media outlets', 1, 1, 5),
('legal', 'Legal/Compliance', 'Legal proceedings or compliance requirements', 1, 1, 6),
('government', 'Government/Official', 'Official government use', 1, 1, 7),
('preservation', 'Preservation/Conservation', 'Digital preservation activities', 0, 2, 8);

-- Default feature toggles
INSERT IGNORE INTO heritage_feature_toggle (institution_id, feature_code, feature_name, is_enabled, config_json) VALUES
(NULL, 'community_contributions', 'Community Contributions', 1, '{"require_moderation": true}'),
(NULL, 'user_registration', 'User Registration', 1, '{"require_email_verification": true}'),
(NULL, 'social_sharing', 'Social Sharing', 1, '{"platforms": ["facebook", "twitter", "linkedin", "email"]}'),
(NULL, 'downloads', 'Downloads', 1, '{"require_login": false, "track_downloads": true}'),
(NULL, 'citations', 'Citation Generation', 1, '{"formats": ["apa", "mla", "chicago", "harvard"]}'),
(NULL, 'analytics', 'Analytics Dashboard', 1, '{"admin_only": true}'),
(NULL, 'access_requests', 'Access Requests', 1, '{"email_notifications": true}'),
(NULL, 'embargoes', 'Embargo Management', 1, '{}'),
(NULL, 'batch_operations', 'Batch Operations', 1, '{"max_items": 1000}'),
(NULL, 'audit_trail', 'Audit Trail', 1, '{"retention_days": 365}');

-- Default branding
INSERT IGNORE INTO heritage_branding_config (institution_id, primary_color, secondary_color, banner_text, footer_text) VALUES
(NULL, '#0d6efd', '#6c757d', NULL, 'Powered by AtoM Heritage Platform');

-- Default explore categories
INSERT IGNORE INTO heritage_explore_category (institution_id, code, name, description, tagline, icon, source_type, source_reference, display_style, display_order, show_on_landing) VALUES
(NULL, 'time', 'Time', 'Browse by historical period', 'Journey through time', 'bi-clock-history', 'field', 'dates', 'timeline', 1, 1),
(NULL, 'place', 'Place', 'Browse by location', 'Explore by geography', 'bi-geo-alt', 'authority', 'place', 'map', 2, 1),
(NULL, 'people', 'People', 'Browse by person or creator', 'Discover the people', 'bi-people', 'authority', 'actor', 'grid', 3, 1),
(NULL, 'theme', 'Theme', 'Browse by subject', 'Explore by topic', 'bi-tag', 'taxonomy', 'subject', 'grid', 4, 1),
(NULL, 'format', 'Format', 'Browse by format type', 'Filter by media', 'bi-collection', 'taxonomy', 'contentType', 'grid', 5, 1),
(NULL, 'trending', 'Trending', 'Popular items this week', 'What people are viewing', 'bi-graph-up', 'custom', 'trending', 'carousel', 6, 1);

-- Default timeline periods (South African focused with international context)
INSERT IGNORE INTO heritage_timeline_period (institution_id, name, short_name, start_year, end_year, description, display_order, show_on_landing) VALUES
(NULL, 'Pre-Colonial Era', 'Pre-1652', -10000, 1651, 'San and Khoi peoples, early Iron Age settlements, and African kingdoms before European contact', 1, 1),
(NULL, 'Dutch Colonial Period', '1652-1795', 1652, 1795, 'Dutch East India Company settlement at the Cape, expansion and conflicts', 2, 1),
(NULL, 'British Colonial Era', '1795-1910', 1795, 1910, 'British rule, the Great Trek, mineral discoveries, and Anglo-Boer Wars', 3, 1),
(NULL, 'Union of South Africa', '1910-1948', 1910, 1948, 'Formation of the Union, World Wars, and early segregation policies', 4, 1),
(NULL, 'Apartheid Era', '1948-1994', 1948, 1994, 'Formal apartheid, resistance movements, and the struggle for democracy', 5, 1),
(NULL, 'Democratic Era', '1994-Present', 1994, NULL, 'Post-apartheid South Africa, reconciliation, and nation building', 6, 1);

-- =============================================================================
-- VERIFICATION
-- =============================================================================
SELECT 'Heritage Plugin Installation Complete' as status;
SELECT
    (SELECT COUNT(*) FROM heritage_filter_type) as filter_types,
    (SELECT COUNT(*) FROM heritage_contribution_type) as contribution_types,
    (SELECT COUNT(*) FROM heritage_contributor_badge) as badges,
    (SELECT COUNT(*) FROM heritage_trust_level) as trust_levels,
    (SELECT COUNT(*) FROM heritage_purpose) as purposes,
    (SELECT COUNT(*) FROM heritage_feature_toggle) as feature_toggles,
    (SELECT COUNT(*) FROM heritage_explore_category) as explore_categories,
    (SELECT COUNT(*) FROM heritage_timeline_period) as timeline_periods;
