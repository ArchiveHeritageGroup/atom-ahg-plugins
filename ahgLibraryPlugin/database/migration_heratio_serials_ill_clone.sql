-- ahgLibraryPlugin — clone of Heratio's serials / ILL schema (parity).
--
-- Mirrors Heratio packages/ahg-library migrations:
--   2026_06_01_000100 serial_subscription, _000101 prediction,
--   _000102 claim, _000103 binding, _000104 serial_issue binding fields,
--   2026_06_02_000104 library_ill_request (rich), 2026_05_30_000004 EDI fields.
--
-- New tables use CREATE TABLE IF NOT EXISTS (idempotent). The ALTER blocks at
-- the bottom are RUN-ONCE (MySQL has no ADD COLUMN IF NOT EXISTS) — skip a
-- statement if the column already exists. Indexes (not hard FKs) match Heratio.

-- ── Serials ────────────────────────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS `library_serial_subscription` (
    `id`                 BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `serial_id`          BIGINT UNSIGNED NOT NULL,
    `subscription_start` DATE NULL,
    `subscription_end`   DATE NULL,
    `subscription_cost`  DECIMAL(10,2) NULL,
    `notification_email` VARCHAR(255) NULL,
    `auto_claim_max`     TINYINT UNSIGNED NOT NULL DEFAULT 3,
    `notes`              TEXT NULL,
    `created_at`         TIMESTAMP NULL,
    `updated_at`         TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `serial_id_unique` (`serial_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `library_serial_prediction` (
    `id`            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `serial_id`     BIGINT UNSIGNED NOT NULL,
    `volume`        VARCHAR(32) NOT NULL DEFAULT '',
    `issue_number`  VARCHAR(32) NOT NULL DEFAULT '',
    `expected_date` DATE NULL,
    `days_until`    INT NOT NULL DEFAULT 0,
    `created_at`    TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    KEY `idx_library_serial_prediction_serial` (`serial_id`),
    KEY `idx_library_serial_prediction_expected` (`expected_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `library_claim` (
    `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `serial_id`  BIGINT UNSIGNED NOT NULL,
    `issue_id`   BIGINT UNSIGNED NULL,
    `claimed_at` TIMESTAMP NULL,
    `claimed_by` VARCHAR(255) NULL,
    `reason`     TEXT NULL,
    `status`     VARCHAR(32) NOT NULL DEFAULT 'open' COMMENT 'open, sent, resolved, cancelled',
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    KEY `idx_library_claim_serial` (`serial_id`),
    KEY `idx_library_claim_issue` (`issue_id`),
    KEY `idx_library_claim_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `library_binding` (
    `id`           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `serial_id`    BIGINT UNSIGNED NOT NULL,
    `volume_range` VARCHAR(120) NOT NULL DEFAULT '',
    `status`       VARCHAR(32) NOT NULL DEFAULT 'pending' COMMENT 'pending, at_bindery, bound, shelved',
    `bound_at`     DATE NULL,
    `location`     VARCHAR(255) NULL,
    `created_at`   TIMESTAMP NULL,
    `updated_at`   TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    KEY `idx_library_binding_serial` (`serial_id`),
    KEY `idx_library_binding_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── ILL ──────────────────────────────────────────────────────────────────────
-- NOTE: NOT cloned. On verification the PSIS ILLService is already functional —
-- it has its own complete ISO 10160/10161 state machine (start state
-- 'submitted'), status is plain VARCHAR(30) (no enum/FK), and every column it
-- writes (incl. needed_by_date) already exists. Cloning Heratio's ILL would
-- regress the richer PSIS implementation, so the ILL CREATE/ALTERs are dropped.
-- (The earlier audit over-flagged ILL by comparing to Heratio's vocabulary.)
-- The block below is retained only as documentation of Heratio's schema.
/*
CREATE TABLE IF NOT EXISTS `library_ill_request` (
    `id`                   BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `ill_number`           VARCHAR(50) NOT NULL,
    `type`                 VARCHAR(20) NOT NULL DEFAULT 'borrow' COMMENT 'borrow, lend',
    `request_type`         VARCHAR(20) NOT NULL DEFAULT 'BORROW',
    `borrowing_protocol`   VARCHAR(20) NOT NULL DEFAULT 'AARC',
    `material_type`        VARCHAR(30) NOT NULL DEFAULT 'BOOK',
    `title`                VARCHAR(500) NOT NULL DEFAULT '',
    `author`               VARCHAR(255) NOT NULL DEFAULT '',
    `isbn`                 VARCHAR(32) NULL,
    `issn`                 VARCHAR(32) NULL,
    `volume`               VARCHAR(64) NULL,
    `issue`                VARCHAR(64) NULL,
    `pages`                VARCHAR(64) NULL,
    `citation`             VARCHAR(500) NULL,
    `lender_string`        TEXT NULL,
    `edition`              VARCHAR(100) NULL,
    `publication_year`     VARCHAR(10) NULL,
    `library_name`         VARCHAR(255) NOT NULL DEFAULT '' COMMENT 'Counterparty library',
    `library_symbol`       VARCHAR(50) NULL,
    `requester_library_id` BIGINT UNSIGNED NULL,
    `responder_library_id` BIGINT UNSIGNED NULL,
    `trading_partner_id`   BIGINT UNSIGNED NULL,
    `patron_id`            BIGINT UNSIGNED NULL COMMENT 'FK library_patron (borrow direction)',
    `request_date`         DATE NULL,
    `needed_by_date`       DATE NULL,
    `due_date`             DATE NULL,
    `status`               VARCHAR(32) NOT NULL DEFAULT 'pending',
    `edi_message_id`       VARCHAR(50) NULL,
    `closed_at`            TIMESTAMP NULL,
    `closed_reason`        VARCHAR(200) NULL,
    `renewal_count`        INT UNSIGNED NOT NULL DEFAULT 0,
    `max_renewals`         TINYINT UNSIGNED NOT NULL DEFAULT 2,
    `cost_amount`          DECIMAL(10,2) NULL,
    `cost_currency`        VARCHAR(3) NULL,
    `shipping_method`      VARCHAR(50) NULL,
    `tracking_number`      VARCHAR(100) NULL,
    `requester_note`       TEXT NULL,
    `responder_note`       TEXT NULL,
    `staff_note`           TEXT NULL,
    `notes`                TEXT NULL,
    `opac_suppress`        TINYINT(1) NOT NULL DEFAULT 0,
    `created_at`           TIMESTAMP NULL,
    `updated_at`           TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_library_ill_number` (`ill_number`),
    KEY `idx_ill_status` (`status`),
    KEY `idx_ill_patron` (`patron_id`),
    KEY `idx_ill_partner` (`trading_partner_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
*/

-- ── RUN-ONCE ALTERs (serials only) ───────────────────────────────────────────
-- library_serial_issue binding fields (Heratio _000104). On PSIS shelf_location
-- and bound_at already exist; only binding_id may be missing. Run individually;
-- ignore "Duplicate column" errors.
ALTER TABLE `library_serial_issue` ADD COLUMN `binding_id` BIGINT UNSIGNED NULL;
ALTER TABLE `library_serial_issue` ADD INDEX `idx_library_serial_issue_binding` (`binding_id`);

-- ILL ALTERs intentionally removed — PSIS ILL is not cloned (already functional).
