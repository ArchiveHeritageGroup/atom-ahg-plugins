-- #131: DPIA (GDPR Article 35 Data Protection Impact Assessment) — PSIS twin of heratio ahg_dpia.
-- Workflow: draft -> review -> completed -> archived. Linked to privacy_processing_activity (ROPA).
CREATE TABLE IF NOT EXISTS `privacy_dpia` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NOT NULL,
  `processing_activity_id` INT UNSIGNED DEFAULT NULL COMMENT 'privacy_processing_activity.id (nullable until linked)',
  `description` TEXT DEFAULT NULL,
  `necessity_proportionality` TEXT DEFAULT NULL COMMENT 'Step 1: necessity and proportionality assessment',
  `risks_to_subjects` TEXT DEFAULT NULL COMMENT 'Step 2: identified risks to data subjects',
  `measures_to_mitigate` TEXT DEFAULT NULL COMMENT 'Step 3: mitigation measures',
  `residual_risks` TEXT DEFAULT NULL COMMENT 'Step 3: residual risk after mitigation',
  `dpo_opinion` TEXT DEFAULT NULL COMMENT 'Step 4: DPO consultation opinion',
  `dpo_consulted_at` DATE DEFAULT NULL,
  `completed_at` DATE DEFAULT NULL,
  `high_risk` TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'auto-flag: special category / large-scale / biometric / cross-border',
  `signed_off_by_user_id` INT DEFAULT NULL,
  `signed_off_at` DATETIME DEFAULT NULL,
  `status` VARCHAR(16) NOT NULL DEFAULT 'draft' COMMENT 'draft, review, completed, archived',
  `created_by_user_id` INT DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_dpia_status` (`status`),
  KEY `idx_dpia_activity` (`processing_activity_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
