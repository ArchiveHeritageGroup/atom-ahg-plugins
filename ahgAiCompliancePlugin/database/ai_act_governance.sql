-- PSIS / AtoM-AHG - EU AI Act governance layer.
--
-- Complements the Article 12 inference receipt chain (ai_inference_log) with the
-- broader EU AI Act obligations: an AI system inventory + risk classification
-- (Art. 6/52), a model registry (Art. 11 technical documentation), a risk
-- register (Art. 9 risk management), and conformity / human-oversight
-- attestations (Art. 9/13/14/47/48).
--
-- Standalone tables (no FK to core AtoM tables, no ENUM columns per AHG rules).

CREATE TABLE IF NOT EXISTS `ai_act_system` (
    `id`                  BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name`                VARCHAR(255) NOT NULL,
    `description`         TEXT DEFAULT NULL,
    `purpose`             TEXT DEFAULT NULL COMMENT 'Intended purpose (Art. 6)',
    `provider`            VARCHAR(255) DEFAULT NULL COMMENT 'Provider / deployer',
    `role`                VARCHAR(20) NOT NULL DEFAULT 'deployer' COMMENT 'provider, deployer, importer, distributor',
    `risk_classification` VARCHAR(20) NOT NULL DEFAULT 'minimal' COMMENT 'prohibited, high, limited, minimal',
    `lifecycle_status`    VARCHAR(20) NOT NULL DEFAULT 'development' COMMENT 'development, deployed, suspended, retired',
    `deployment_context`  TEXT DEFAULT NULL,
    `human_oversight`     TEXT DEFAULT NULL COMMENT 'Human oversight measures (Art. 14)',
    `owner`               VARCHAR(255) DEFAULT NULL COMMENT 'Accountable person / unit',
    `last_review_date`    DATE DEFAULT NULL,
    `next_review_date`    DATE DEFAULT NULL,
    `is_active`           TINYINT(1) NOT NULL DEFAULT 1,
    `created_at`          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_risk` (`risk_classification`),
    KEY `idx_status` (`lifecycle_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `ai_act_model` (
    `id`                   BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `system_id`            BIGINT UNSIGNED DEFAULT NULL COMMENT 'Owning ai_act_system.id (logical FK)',
    `model_id`             VARCHAR(191) NOT NULL COMMENT 'e.g. nomic-embed-text, qwen3:14b',
    `version`              VARCHAR(64) DEFAULT NULL,
    `provider`             VARCHAR(255) DEFAULT NULL,
    `modality`             VARCHAR(40) NOT NULL DEFAULT 'text' COMMENT 'text, vision, audio, multimodal, embedding, other',
    `intended_purpose`     TEXT DEFAULT NULL,
    `training_data_summary` TEXT DEFAULT NULL COMMENT 'Data governance summary (Art. 10)',
    `limitations`          TEXT DEFAULT NULL COMMENT 'Known limitations / accuracy / bias',
    `evaluation_summary`   TEXT DEFAULT NULL,
    `license`              VARCHAR(128) DEFAULT NULL,
    `is_active`            TINYINT(1) NOT NULL DEFAULT 1,
    `created_at`           DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`           DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_system` (`system_id`),
    KEY `idx_model` (`model_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `ai_act_risk` (
    `id`               BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `system_id`        BIGINT UNSIGNED DEFAULT NULL COMMENT 'Related ai_act_system.id (logical FK)',
    `title`            VARCHAR(255) NOT NULL,
    `category`         VARCHAR(40) NOT NULL DEFAULT 'other' COMMENT 'safety, fundamental_rights, bias_discrimination, privacy, security, transparency, accuracy, other',
    `description`      TEXT DEFAULT NULL,
    `likelihood`       TINYINT UNSIGNED NOT NULL DEFAULT 3 COMMENT '1 rare .. 5 almost certain',
    `severity`         TINYINT UNSIGNED NOT NULL DEFAULT 3 COMMENT '1 negligible .. 5 catastrophic',
    `mitigation`       TEXT DEFAULT NULL COMMENT 'Risk mitigation measures (Art. 9)',
    `residual_likelihood` TINYINT UNSIGNED DEFAULT NULL,
    `residual_severity`   TINYINT UNSIGNED DEFAULT NULL,
    `status`           VARCHAR(20) NOT NULL DEFAULT 'open' COMMENT 'open, mitigating, accepted, closed',
    `owner`            VARCHAR(255) DEFAULT NULL,
    `review_date`      DATE DEFAULT NULL,
    `created_at`       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_system` (`system_id`),
    KEY `idx_status` (`status`),
    KEY `idx_category` (`category`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `ai_act_attestation` (
    `id`            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `system_id`     BIGINT UNSIGNED DEFAULT NULL COMMENT 'Related ai_act_system.id (logical FK)',
    `type`          VARCHAR(40) NOT NULL DEFAULT 'conformity_declaration' COMMENT 'conformity_declaration, human_oversight, risk_management, data_governance, technical_documentation, transparency, other',
    `statement`     TEXT DEFAULT NULL,
    `status`        VARCHAR(20) NOT NULL DEFAULT 'draft' COMMENT 'draft, attested, expired, revoked',
    `attested_by`   VARCHAR(255) DEFAULT NULL,
    `attested_at`   DATETIME DEFAULT NULL,
    `evidence_url`  VARCHAR(512) DEFAULT NULL,
    `next_review_date` DATE DEFAULT NULL,
    `created_at`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_system` (`system_id`),
    KEY `idx_type` (`type`),
    KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
