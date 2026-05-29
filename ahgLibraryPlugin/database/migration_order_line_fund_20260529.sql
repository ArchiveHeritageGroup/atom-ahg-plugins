-- ============================================================================
-- Acquisitions fund-split (#104) — 2026-05-29
-- ============================================================================
-- Allocate a single order line across multiple funds. library_order_line keeps
-- its primary fund_code; this table records the split when one is supplied.
-- Idempotent.
-- ============================================================================

CREATE TABLE IF NOT EXISTS `library_order_line_fund` (
  `id`            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_line_id` BIGINT UNSIGNED NOT NULL,
  `fund_code`     VARCHAR(50) NOT NULL,
  `amount`        DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `created_at`    DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_olf_line` (`order_line_id`),
  KEY `idx_olf_fund` (`fund_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
