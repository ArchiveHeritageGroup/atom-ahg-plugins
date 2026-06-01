# ahgObservabilityPlugin

Prometheus-format `/metrics` exporter for AtoM, ported from the Heratio
`ahg-observability` package and adapted to AtoM (Symfony 1.4 + atom-framework)
conventions.

## What it exposes

A single lightweight, auth-gated endpoint:

```
GET /metrics
```

emitting the Prometheus text exposition format (version 0.0.4). Metrics are
namespaced `atom_`:

| Metric | Type | Labels | Source |
|--------|------|--------|--------|
| `atom_http_requests_total` | counter | method, route, status | every request (response.filter_content) |
| `atom_http_request_duration_seconds` | histogram | method, route, status | every request |
| `atom_queue_depth` | gauge | queue | `observability:record-queue-depth` (samples `ahg_queue_job`) |

`route` is the AtoM `module/action` pair (never the raw URL) to keep
cardinality bounded.

## Authentication (fail-closed)

OR semantics, evaluated in the action:

1. `Authorization: Bearer <obs_token>` matches the configured token, **or**
2. the client IP is in `obs_allowed_ips` (default `127.0.0.1,::1`).

Empty token **and** empty allow-list = deny all (401). This is intentional:
an unconfigured endpoint must not be scrapeable from the public internet.

The route is registered **without** the AtoM ACL/login filter so Prometheus
can scrape without a session cookie — the action does its own auth.

## Storage backends (auto-selected, fail-soft)

Resolved by `obs_storage_driver` (default `auto`):

- **redis** — phpredis loaded + reachable (multi-host, shared across php-fpm
  and queue workers)
- **apcu** — APCu loaded + enabled for the SAPI (single host, cross-worker)
- **inmemory** — process-local fallback (tests/CLI, or when nothing else is
  usable)

An unreachable Redis or disabled APCu silently degrades to in-memory; a
metrics-backend fault never 500s a request.

## CLI

```bash
php bin/atom observability:record-queue-depth      # sample ahg_queue_job backlog -> atom_queue_depth gauge
php bin/atom observability:emit-textfile           # write registry snapshot to node_exporter textfile
php bin/atom observability:emit-textfile --dry-run
```

Recommended cron: `record-queue-depth` every minute. `emit-textfile` only if
Prometheus cannot reach `/metrics` directly but can scrape node_exporter.

## Settings (ahg_settings, group `observability`)

Seeded by `database/install.sql` (idempotent):
`obs_token`, `obs_allowed_ips`, `obs_storage_driver`, `obs_textfile_dir`,
`obs_redis_host`, `obs_redis_port`, `obs_redis_database`.

## External dependencies (all optional)

- `ext-apcu` / `ext-redis` — shared counter storage (in-memory fallback otherwise)
- Prometheus server — scrapes `/metrics`
- node_exporter textfile collector — for `emit-textfile`

The plugin has **zero composer dependency**; the Prometheus client + text
renderer are self-contained (the locked AtoM `vendor/` tree has no PromPHP).
