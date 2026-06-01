# ahgObservabilityPlugin — Prometheus /metrics exporter (PSIS port of Heratio ahg-observability)

Date: 2026-06-01

## Summary

New plugin `ahgObservabilityPlugin` ports the metrics core of Heratio's
`ahg-observability` package to PSIS/AtoM (Symfony 1.4 + atom-framework).
Exposes an auth-gated Prometheus-format `/metrics` endpoint, a metrics
registry, and auto-selecting APCu/Redis/in-memory counter storage.

## What shipped

- `/metrics` route (AtoM RouteLoader, module `observability`, action `metrics`),
  NOT behind the ACL filter; action does its own bearer-token OR IP-allow-list
  auth (fail-closed when both unset). Mirrors Heratio MetricsController.
- `MetricsRegistry` facade (namespace `AtomExtensions\Observability`) with
  counter/gauge/histogram helpers and `auto` storage resolution
  (redis -> apcu -> inmemory) with fail-soft fallback.
- Self-contained storage adapters (no composer/PromPHP dep, since AtoM vendor/
  is locked and has no PromPHP): `InMemoryStorage`, `ApcuStorage`,
  `RedisStorage` (phpredis), plus `PrometheusTextRenderer` (text format 0.0.4).
- HTTP request instrumentation via `response.filter_content` event in the
  plugin Configuration → `atom_http_requests_total` +
  `atom_http_request_duration_seconds` labelled method/route(module/action)/status.
- CLI: `observability:record-queue-depth` (samples `ahg_queue_job` pending
  backlog → `atom_queue_depth` gauge) and `observability:emit-textfile`
  (atomic node_exporter textfile write).
- Settings seeded into `ahg_settings` group `observability` (`obs_*` keys);
  no new tables.

## Notes / decisions

- DB-query histogram (Heratio's RecordDbQuery on Laravel QueryExecuted) was
  intentionally NOT ported: Symfony 1.x has no equivalent global query event
  and a degraded version would violate the no-half-baked rule. HTTP + queue
  metrics are the core surface.
- Fixed a histogram cumulative double-count during build: buckets are stored
  cumulatively at observe time, so collect() emits them as-is rather than
  re-accumulating.
- APCu is enabled on this host (web SAPI); Redis ext is not installed → auto
  resolves to APCu on web requests, InMemory on CLI when apc.enable_cli is off.

## Integration required (human)

- `php bin/atom extension:enable ahgObservabilityPlugin` (never INSERT INTO
  atom_plugin in install.sql).
- Run `database/install.sql` to seed default settings.
- Set `obs_token` and/or `obs_allowed_ips` before exposing /metrics.
- Optional: cron `observability:record-queue-depth` every minute.
