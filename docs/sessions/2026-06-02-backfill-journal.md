# research:backfill-journal — seed a researcher journal from real activity

**Date:** 2026-06-02
**Released:** atom-ahg-plugins **v3.49.19** (commit `3fc11536`, also tagged v3.49.20 on the same commit) — on origin/main.
**Plugin:** ahgResearchPlugin (#115 journal subsystem).
**Scope:** New CLI command + a one-off live data seed of Johan's PSIS journal.

## Trigger
"Seed from my journals" against https://psis.theahg.co.za/research/journal/. Clarified that the
target is the per-researcher **logbook** (`research_journal_entry`), not the academic journal
*builder* (`research_journal`/`_issue`/`_article`, which is empty), and that the source is the
researcher's **real research footprint** ("backfill from my activity").

## What shipped
New file `ahgResearchPlugin/lib/Commands/BackfillJournalCommand.php`
(namespace `AtomFramework\Console\Commands\Research`, extends `BaseCommand`). Auto-discovered by
`CommandRegistry` scanning `atom-ahg-plugins/ahg*/lib/Commands/` — no registration step.

Generates `research_journal_entry` rows for a researcher from:
- `research_collection` → "Collection survey: \<name\>" (with `research_collection_item` count)
- `research_booking` (status confirmed/checked_in/completed) → "Reading-room visit — \<date\>"
- `research_bibliography` → "Bibliography started: \<name\>"
- optional "Research background & objectives" overview entry

**Idempotency (key design choice):** every entry stores source linkage in
`(related_entity_type, related_entity_id)` with `entry_type='backfill'`. Re-running skips any
source that already has an entry — and because the system's existing `auto_*` entries also carry
that linkage, the backfill correctly skips sources already journaled by auto-logging. Existing
`auto_*` / `manual` entries are never touched.

**Usage / flags:**
```
php bin/atom research:backfill-journal 25            # by research_researcher.id
php bin/atom research:backfill-journal --email=johan@theahg.co.za
php bin/atom research:backfill-journal --all         # every researcher with source rows
php bin/atom research:backfill-journal 25 --dry-run --skip-background --public
```

## Verification
- `php -l` clean; appears in `bin/atom list`; `--help` renders; `--dry-run` correct.
- Ran (as `www-data`) on PSIS for researcher 25 (Johan).

## Data seeded on PSIS (researcher 25 = Johan Pieterse)
Two-part seed of the `archive` DB (no schema change):
1. **5 manual narrative entries** inserted earlier by hand (entry_type `manual`, ids #9–13):
   background + WDB survey + RARI sources + reading-room visit + Engelbrecht fonds. These lack
   `related_entity_*` linkage (hand-inserted), so the command does not dedup against them.
2. **3 `backfill` entries** via the command with `--skip-background` (ids #14–16):
   - #14 collection/14 — Collection survey: WDB Archival Collection
   - #15 bibliography/2 — Bibliography started: Engelbrecht Family Fonds
   - #16 bibliography/3 — Bibliography started: test

   Skipped 3 (RARI collection/15, Engelbrecht collection/16, booking/11 — already auto-logged).

Journal now holds **12 entries** for researcher 25 (4 auto + 5 manual + 3 backfill).

## Notes
- Command file committed root-owned (chown johanpiet:www-data is the repo convention before
  `./bin/release`; it committed fine regardless).
- Second `./bin/release` run on a clean tree added tag v3.49.20 to the same commit — duplicate
  tag, no new code.
- DB write was explicitly authorised by the user ("run it live for me with --skip-background").
