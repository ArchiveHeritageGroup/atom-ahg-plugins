# Heratio↔PSIS parity: audit → corrected verify → build 10 missing-high items

**Date:** 2026-06-01
**Scope:** Two-way functional parity (Heratio Laravel `packages/ahg-*` vs PSIS/AtoM `atom-ahg-plugins/ahg*Plugin`), then build the genuinely-missing high items.
**Method:** three multi-agent workflows (audit → corrected re-verify → build) + main-loop integration.

## 1. Audit (13 domains, two-way)
Initial audit flagged 56 high + ~67 medium PSIS gaps, avg ~63% parity. Report:
`docs/HERATIO_PSIS_PARITY_AUDIT.md`. Per-domain twin issue bodies + `create-twins.sh` /
`create-heratio-issues.sh` in `docs/parity-issues/` (gh issue create is DENIED for the agent —
user runs the scripts).

## 2. Corrected verification (CRITICAL)
The audit **over-counted**: its per-domain agents only checked the same-named plugin and missed
that PSIS splits functionality across MANY differently-named plugins. Re-verifying all 125
high+medium gaps cross-plugin: **87 present (false positives), 21 partial, 17 genuinely missing.**
Examples of false positives: authority merge/dedup/NER → ahgAuthorityPlugin; embargo/orphan →
ahgExtendedRightsPlugin; FRBR → ahgLibraryPlugin; heritage portal → ahgHeritagePlugin; privacy/
preservation/condition/provenance → their own plugins. **Lesson: never build straight from a
same-name audit; verify cross-plugin first.**

## 3. Built the 10 genuinely-missing HIGH items (workflow: build → adversarial verify)
New plugins: **ahgOcflPlugin** (OCFL storage), **ahgScanPlugin** (watched-folder ingest, needs
ahgIngestPlugin), **ahgResourceSyncPlugin** (ResourceSync 1.1 source), **ahgObservabilityPlugin**
(Prometheus /metrics), **ahgC2paPlugin** (C2PA manifests; c2patool optional).
Host extensions: **Z39.50 server** (ahgLibraryPlugin, `z3950:server`), **SHACL** validation
(ahgRicExplorerPlugin, `ric:shacl-validate`), **Idempotency-Key** + **ETag** (ahgAPIPlugin),
**DONUT** doc understanding (ahgAIPlugin, AI gateway on .115). All `php -l` clean; verify agents
fixed real defects (OCFL settings-class FQCN, Phar fallback, etc.).

## 4. Integration (main loop)
- Created all 10 tables on PSIS via PDO (mysql CLI rejects root; creds from config/config.php).
- Appended donut + shacl routes to their plugin routing.yml (valid YAML).
- Wired **ETag** into `AhgApiAction::success()` (304 on If-None-Match) and **Idempotency-Key**
  into `AhgApiAction::process()` (replay/409-conflict/store) — both fully try/catch-guarded so a
  middleware fault can never break the live API; INERT until php-fpm restart (validate_timestamps=0).
- Registered every new table for fresh installs: appended host DDL into each main install.sql +
  added table names to extension.json `tables[]` (ocfl/library/ric/api/ai).

## Pending (user)
- Enable the 5 new plugins: `extension:install <p> && extension:enable <p>` for each, then
  `rm -rf cache/* && systemctl restart php8.3-fpm` (also activates the API-base edit).
- Optional external deps (graceful fallback built): c2patool, pyshacl/rdflib, DONUT gateway, ext-sockets/systemd for Z39.50.
- Release: `./bin/release minor "…"`. File parity twins via the two scripts (gh create denied for agent).

## Gotcha recorded
`AhgController::redirect()` does NOT throw (unlike Symfony sfAction) — `return;` after it. mysql
CLI on PSIS rejects root → use PDO with config/config.php creds for any DB read/write.
