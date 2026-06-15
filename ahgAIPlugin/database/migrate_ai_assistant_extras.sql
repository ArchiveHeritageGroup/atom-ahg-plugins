-- ahgAIPlugin — AI assistant extras (DB-audit archive build-order #4)
-- Two genuinely-missing tables ported from Heratio:
--   1. ahg_ai_chatbot_message — persist #121 collection-chatbot conversation turns
--   2. ahg_translation_memory  — translation-memory reuse for ai:translate
-- Run-once, additive. No INSERT INTO atom_plugin (ahgAIPlugin is already enabled).
-- NOTE: `role` is VARCHAR not ENUM per project rule #5 (no ENUM columns).

CREATE TABLE IF NOT EXISTS `ahg_ai_chatbot_message` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `session_id` VARCHAR(64) NOT NULL,
    `role` VARCHAR(20) NOT NULL DEFAULT 'user' COMMENT 'user, assistant, system',
    `content` TEXT NOT NULL,
    `sources` JSON DEFAULT NULL,
    `grounding_score` FLOAT(5,4) DEFAULT NULL,
    `model` VARCHAR(100) DEFAULT NULL,
    `tokens_in` INT UNSIGNED DEFAULT NULL,
    `tokens_out` INT UNSIGNED DEFAULT NULL,
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `ix_session` (`session_id`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `ahg_translation_memory` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `source_text_hash` CHAR(64) NOT NULL COMMENT 'sha256 hex of the source text',
    `source_lang` CHAR(8) NOT NULL DEFAULT '',
    `target_lang` CHAR(8) NOT NULL,
    `source_text` TEXT NOT NULL,
    `target_text` TEXT NOT NULL,
    `provenance` VARCHAR(32) NOT NULL DEFAULT 'machine' COMMENT 'machine, human, reviewed',
    `confidence` FLOAT DEFAULT NULL,
    `hit_count` INT UNSIGNED NOT NULL DEFAULT 0,
    `last_used_at` TIMESTAMP NULL DEFAULT NULL,
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_tm_hash_target` (`source_text_hash`, `target_lang`),
    KEY `idx_tm_target` (`target_lang`),
    KEY `idx_tm_provenance` (`provenance`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
