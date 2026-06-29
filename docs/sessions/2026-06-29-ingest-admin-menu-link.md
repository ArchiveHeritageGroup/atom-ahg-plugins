# 2026-06-29 — Ingest admin-menu link + ingest/preservation reporting (v3.79.17)

## Context
Status review against KM. Found 20 PSIS session logs (2026-06-27/28) missing from the
KM ingest queue and republished them. Then inspected the **provenance** and **data
ingest** subsystems on PSIS/archive, and wired a navigation entry for ingest.

## KM republish
Dropped 20 missing session logs (RDM port phases 1–8 + #176/#177, security-audit issue
series #178–#186, #187 route-500 sweep, route-500 accessions/forms/workflow/IIIF) into
`/var/spool/km-ingest/`. Watcher swept all 20 into `archive/2026-06-29/`, none failed.

## Provenance findings (ahgProvenancePlugin) — no code change
- PSIS has caught up to Heratio's sector-neutral consolidation (PSIS v3.79.13–15 ↔
  Heratio v1.154.182/183): museum provenance soft-retired behind a non-destructive
  read-bridge; CSV export + chain-gap detection added; summary-panel render fixed.
- **Ed25519 inference signing keypair is un-minted on PSIS.** `data/ahg-ai-signing/`
  does not exist (only the audit plugin's separate `ahg-audit-signing/` is present);
  `ai_inference_key` = 0 rows. Consequence: authenticity verdicts can only ever resolve
  to `unsigned` — `verified` is unreachable until `sudo -u www-data php symfony
  ai-provenance:keygen` is run. (Matches open thread from #152.)
- All provenance tables empty (`provenance_entry`, `_record`, `_event`, `_agent`,
  `ahg_c2pa_*` = 0 rows). Plumbing live, no data captured yet.

## Ingest findings (ahgIngestPlugin)
- Exercised: 13 sessions, 16 files, 14 mappings, 6 jobs, 30 validations.
- Jobs #1 and #3 stuck in `running` since 2026-02-17 (orphaned; harmless but noisy).
- Server-directory ingest is admin-only since v3.71.7.
- Entry points: `/ingest` (dashboard, requireAuth, admins see all) and `/ingest/new`
  (configure). No menu link existed — URL-only.

## Change shipped (v3.79.17)
1. **ahgThemeB5Plugin/templates/_ahgAdminMenu.php** — new "Data Ingest" section in the
   AHG Plugins admin dropdown, gated by `ahgIsPluginEnabled('ahgIngestPlugin')`:
   - Ingest Dashboard → `/ingest`
   - New Ingest → `/ingest/configure` (no-id renders the new-session form; verified safe)
   Placed after the Data Entry (Forms) block; admin-only (dropdown already `$isAdmin`).
   Note: ahgThemeB5Plugin is locked-core, but the admin menu is hardcoded in this one
   template with no DB-driven hook — only place the link could live. Purely additive.
2. **ahgIngestPlugin/lib/Services/IngestCommitService.php** (pre-existing uncommitted
   work, shipped with consent) — Archivematica-style preservation baseline on every
   ingest commit: fixity checksum + format-id + virus scan (when ClamAV present) +
   PREMIS "ingestion" event. Idempotent, non-fatal per object, no-op without
   ahgPreservationPlugin.
3. **ahgReportsPlugin (actions + indexSuccess.php)** (pre-existing uncommitted work) —
   ingest stats (jobs/records/objects, last-ingest time) + preservation stats (PREMIS
   events, checksummed objects) on the Reports dashboard, guarded for missing tables.

## Activation
`sudo -u www-data php symfony cc` + `sudo systemctl restart php8.3-fpm` (host runs
opcache.validate_timestamps=0, so php-fpm restart is mandatory for template changes).
Verified `/ingest` → 302 (login redirect = route alive).

## Open follow-ups
- Mint the inference signing keypair (`ai-provenance:keygen` as www-data) to make
  authenticity verdicts meaningful.
- Optionally clear the two stuck `ingest_job` rows (#1, #3) — DB write, not yet done.
