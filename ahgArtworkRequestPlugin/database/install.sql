-- ahgArtworkRequestPlugin
--
-- Staff requests to place artworks in offices and shared spaces.
--
-- Deliberately separate from ahg_loan. That table models a loan to another
-- institution: partner_institution is NOT NULL, and there are couriers, customs
-- states, facility reports and loan fees beside it. A colleague hanging a
-- painting in their office is none of those things, and filling a NOT NULL
-- partner column with something untrue on every internal booking is how a
-- collection database rots. Approval hands off to ahgLoanPlugin instead, where
-- ahg_loan_object already carries the lifecycle and the condition reports.
--
-- No ENUM columns anywhere: VARCHAR with a COMMENT listing the valid values, so
-- adding a state later is not a migration.

CREATE TABLE IF NOT EXISTS `artwork_request` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `request_number` VARCHAR(50) NOT NULL,

    -- Who is asking. Name and email are cached so a request still reads
    -- correctly after someone leaves and their user row is deactivated.
    `requester_user_id` INT UNSIGNED NULL,
    `requester_name` VARCHAR(255) NULL,
    `requester_email` VARCHAR(255) NULL,
    `department` VARCHAR(255) NULL,

    `status` VARCHAR(30) NOT NULL DEFAULT 'draft'
        COMMENT 'draft, submitted, approved, declined, withdrawn, fulfilled, returned, cancelled',
    `purpose` VARCHAR(100) NULL COMMENT 'office, boardroom, shared workspace, event, other',
    `justification` TEXT NULL,

    `requested_from` DATE NULL,
    `requested_to` DATE NULL,

    -- Where it will hang. Free text rather than a foreign key: an office is not
    -- a storage location and should not pollute the location hierarchy.
    `placement_building` VARCHAR(255) NULL,
    `placement_floor` VARCHAR(50) NULL,
    `placement_room` VARCHAR(100) NULL,
    `placement_occupant` VARCHAR(255) NULL,
    `placement_notes` TEXT NULL,

    `reviewed_by` INT UNSIGNED NULL,
    `reviewed_at` DATETIME NULL,
    `review_notes` TEXT NULL,

    -- Whether the decision was actually taken here.
    --
    -- Deciding which work hangs in whose office is a conversation between
    -- people, and software that insists on owning that decision gets worked
    -- around within a term. A request settled in a corridor is recorded as
    -- 'offline' with a note rather than the system pretending it ran the
    -- conversation.
    `decision_channel` VARCHAR(20) NOT NULL DEFAULT 'system' COMMENT 'system, offline',

    -- Set when a loan record is created from this request. Nullable forever:
    -- ahgLoanPlugin is an optional dependency and many sites will not have it.
    `loan_id` BIGINT UNSIGNED NULL,

    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_artwork_request_number` (`request_number`),
    KEY `idx_artwork_request_status` (`status`),
    KEY `idx_artwork_request_requester` (`requester_user_id`),
    KEY `idx_artwork_request_dates` (`requested_from`, `requested_to`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- One row per work on a request.
--
-- Status is per work, not per request: a curator commonly approves two of the
-- three works asked for, and a single request-level status cannot express that.
CREATE TABLE IF NOT EXISTS `artwork_request_object` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `request_id` BIGINT UNSIGNED NOT NULL,
    `information_object_id` INT UNSIGNED NOT NULL,

    -- Cached for the list views, so showing fifty requests is not fifty joins
    -- into the i18n tables.
    `object_title` VARCHAR(500) NULL,
    `object_identifier` VARCHAR(255) NULL,

    `status` VARCHAR(30) NOT NULL DEFAULT 'requested'
        COMMENT 'requested, approved, declined, issued, returned',

    -- What the availability check found when the request was made. Recorded
    -- rather than enforced: a clash is a warning, because the curator may still
    -- say yes and needs to see why they are being asked twice.
    `conflict_note` TEXT NULL,

    `issued_at` DATETIME NULL,
    `returned_at` DATETIME NULL,
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (`id`),
    KEY `idx_aro_request` (`request_id`),
    KEY `idx_aro_object` (`information_object_id`),
    KEY `idx_aro_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Who is notified.
--
-- department NULL means the general queue. A site that does not want per
-- department routing simply never sets one, and every approver sees everything.
CREATE TABLE IF NOT EXISTS `artwork_request_approver` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` INT UNSIGNED NOT NULL,
    `department` VARCHAR(255) NULL,
    `email_notifications` TINYINT(1) NOT NULL DEFAULT 1,
    `active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_ara_user_dept` (`user_id`, `department`),
    KEY `idx_ara_active` (`active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `artwork_request_log` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `request_id` BIGINT UNSIGNED NOT NULL,
    `event` VARCHAR(50) NOT NULL
        COMMENT 'created, submitted, approved, declined, withdrawn, issued, returned, reminded, note',
    `actor_user_id` INT UNSIGNED NULL,
    `actor_name` VARCHAR(255) NULL,
    `detail` TEXT NULL,
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (`id`),
    KEY `idx_arl_request` (`request_id`),
    KEY `idx_arl_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
