# ahgLibraryPlugin - fold full library schema into install.sql (v3.79.97)

**Date:** 2026-07-17
**Repo:** atom-ahg-plugins
**Release:** v3.79.97
**Trigger:** fresh AtoM 2.10 + AHG git install on VM `atom210` surfaced `/library` HTTP 500.

## Problem

`bin/install` runs each plugin's `database/install.sql` but **never** its
`database/migration_*.sql` files. ahgLibraryPlugin's real schema was fragmented
across ~13 migrations, so a clean-DB install was missing dozens of columns and
tables and `/library` 500'd. First blocker was `library_item.frbr_work_key`;
behind it, `library_item_creator.is_primary`, `library_serial_issue.binding_id`,
`library_subject_authority.*`, and the entire #214 circulation system
(`library_copy`, `library_patron`, `library_checkout`, `library_hold`,
`library_fine`, `library_order`, ...).

## Fix

Folded every library migration into `ahgLibraryPlugin/database/install.sql`
(idempotent, appended after the base content), so the installer builds the
complete schema:

- FRBR clustering: `frbr_work_key`, `frbr_override_type` (VARCHAR not ENUM),
  `library_item_frbr_override` table, indexes, `library_usage_event.frbr_work_key`.
- #214 full circulation system + heritage-accounting columns.
- reconcile (`is_primary`), RDA/authority/EDI, MARC control fields,
  serial bindery, order-line/fund, ILL status history, serials/ILL clone,
  COUNTER/SUSHI, SUSHI access log, Z39.50 server, Z39.50/SRU, ONIX ingest.

All statements idempotent via `INFORMATION_SCHEMA` guards + `PREPARE/EXECUTE`
or `CREATE TABLE IF NOT EXISTS`.

Two corrections during folding:
- **Omitted** the trailing cross-plugin `INSERT ... ahg_dropdown_column_map`
  rows - that table has no creator anywhere in the tree (dangling reference).
  `ahg_dropdown` itself is fine (created by ahgCorePlugin, present on every
  real install).
- **Guarded** the two raw non-idempotent `binding_id` ALTERs (were
  `ALTER TABLE library_serial_issue ADD COLUMN binding_id ...` with no guard).

## Validation

- **Fresh scratch DB** (framework `library_*` base tables + `ahg_dropdown`,
  then the complete plugin install.sql): builds 40 library tables and every
  target column (frbr_work_key, frbr_override_type, heritage_asset_id,
  is_primary, binding_id, library_subject_authority.lc_label,
  library_item_frbr_override); **2-pass idempotent** (no duplicate-column /
  table-exists errors on re-run). Residual errors were only references to core
  AtoM tables (`term`, `object`, `slug`, `level_of_description_sector`) absent
  from the minimal scratch seed - present on any real install.
- **Real atom DB**: complete install.sql runs clean (zero errors, no-op).
- `/library` = **200** on VM atom210.

## Notes

- ahgLibraryPlugin was explicitly unlocked by the user for this change.
- Synced to `/usr/share/nginx/archeology` (file mirror, md5 verified). No DB
  action there: plugin disabled and `library_item.frbr_work_key` already present.
- The `migration_*.sql` files are left in place as history; they are no longer
  needed for a fresh install.
