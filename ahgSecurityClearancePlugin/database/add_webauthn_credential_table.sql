-- #126 / #721: WebAuthn / FIDO2 passkey credentials (MFA second factor).
CREATE TABLE IF NOT EXISTS `ahg_webauthn_credential` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `credential_id` VARBINARY(512) NOT NULL,
  `public_key` MEDIUMBLOB NOT NULL COMMENT 'serialized PublicKeyCredentialSource (JSON)',
  `attestation_type` VARCHAR(32) NOT NULL DEFAULT 'none',
  `aaguid` CHAR(36) DEFAULT NULL,
  `sign_count` BIGINT UNSIGNED NOT NULL DEFAULT 0,
  `transports` JSON DEFAULT NULL,
  `label` VARCHAR(255) NOT NULL DEFAULT '',
  `last_used_at` DATETIME DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_webauthn_credential_id` (`credential_id`),
  KEY `idx_webauthn_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
