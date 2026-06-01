-- ============================================================================
-- ahgObservabilityPlugin - Prometheus /metrics exporter
-- ============================================================================
--
-- This plugin stores no metric data in MySQL (counters live in APCu/Redis or
-- process memory). It only seeds default configuration into the shared
-- ahg_settings table (group "observability"), so there are NO new tables.
--
-- Keys are prefixed "obs_" to stay collision-free in the flat setting_key
-- space. INSERT ... ON DUPLICATE KEY keeps re-running install.sql idempotent
-- and never clobbers an operator's tuned value.
--
-- Settings:
--   obs_token         Bearer token required to scrape /metrics (empty = none).
--   obs_allowed_ips   Comma-separated IP allow-list for /metrics
--                     (default loopback only). Empty token + empty list =
--                     deny-all (fail-closed).
--   obs_storage_driver  auto | redis | apcu | inmemory
--   obs_textfile_dir  node_exporter textfile collector directory used by the
--                     observability:emit-textfile command.
--   obs_redis_host / obs_redis_port / obs_redis_database  Redis backend (only
--                     used when the driver resolves to redis).
-- ============================================================================

INSERT INTO ahg_settings (setting_key, setting_value, setting_type, setting_group, description)
VALUES
  ('obs_token', '', 'string', 'observability', 'Bearer token required to scrape /metrics (empty disables token auth)'),
  ('obs_allowed_ips', '127.0.0.1,::1', 'string', 'observability', 'Comma-separated IP allow-list permitted to scrape /metrics without a token'),
  ('obs_storage_driver', 'auto', 'string', 'observability', 'Metric storage backend: auto, redis, apcu, or inmemory'),
  ('obs_textfile_dir', '/var/lib/node_exporter/textfile_collector', 'string', 'observability', 'node_exporter textfile collector directory for observability:emit-textfile'),
  ('obs_redis_host', '127.0.0.1', 'string', 'observability', 'Redis host (only used when storage driver resolves to redis)'),
  ('obs_redis_port', '6379', 'integer', 'observability', 'Redis port (only used when storage driver resolves to redis)'),
  ('obs_redis_database', '0', 'integer', 'observability', 'Redis database index (only used when storage driver resolves to redis)')
ON DUPLICATE KEY UPDATE setting_key = setting_key;
