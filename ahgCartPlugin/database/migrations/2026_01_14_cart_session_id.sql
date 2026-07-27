-- Migration: Add session_id to cart table for guest checkout
-- Date: 2026-01-14
--
-- NB: no "IF NOT EXISTS" on ADD COLUMN / CREATE INDEX - that is MariaDB-only
-- syntax and MySQL 8 rejects it with a syntax error. The migration runner
-- treats "duplicate column" (1060) and "duplicate key" (1061) as safe, so a
-- plain ADD COLUMN / CREATE INDEX is idempotent across re-runs.

ALTER TABLE cart ADD COLUMN session_id VARCHAR(255) DEFAULT NULL AFTER user_id;

CREATE INDEX idx_cart_session ON cart(session_id);
