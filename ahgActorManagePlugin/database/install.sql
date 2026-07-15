-- ahgActorManagePlugin
-- Overrides actor browse/autocomplete and adds authority-record visibility control.

-- ---------------------------------------------------------------------------
-- Authority-record (actor) visibility / publication status.
--
-- AtoM has no publish/draft concept for authority records (unlike archival
-- descriptions). This table adds one, primarily to keep authority records for
-- living individuals out of public view (GDPR / POPIA), without deleting them.
--
-- Design: a row exists ONLY for actors that are NOT publicly visible
-- (status='draft' or an active embargo). Absence of a row = published/public.
-- That keeps the table small and makes the "hidden set" a cheap lookup.
--   status='draft'        -> hidden from public indefinitely (e.g. living person)
--   embargo_until = DATE  -> hidden from public until that date, then auto-public
-- Authenticated staff always see the record (suppression is public-only).
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `ahg_actor_visibility` (
    `actor_id`        BIGINT UNSIGNED NOT NULL COMMENT 'FK-by-convention to actor.id (QubitActor)',
    `status`          VARCHAR(20)  NOT NULL DEFAULT 'published' COMMENT 'published, draft',
    `embargo_until`   DATE         NULL COMMENT 'if set and in the future, hidden from public until this date',
    `reason`          VARCHAR(255) NULL COMMENT 'optional note, e.g. "living individual - GDPR"',
    `set_by_user_id`  BIGINT UNSIGNED NULL COMMENT 'user.id who last changed the status',
    `created_at`      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`actor_id`),
    KEY `idx_aav_status` (`status`),
    KEY `idx_aav_embargo` (`embargo_until`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
