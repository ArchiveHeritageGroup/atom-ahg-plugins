# Ingestion Manager

OAIS-aligned multi-stage ingestion pipeline: configure, upload, map, validate, preview, commit with rollback support

| | |
|---|---|
| Machine name | `ahgIngestPlugin` |
| Version | 1.1.0 |
| Category | ingestion |
| Licence | AGPL-3.0-or-later |
| Author | The Archive and Heritage Group (Pty) Ltd |

## Features

- 6-step wizard: configure, upload, map & enrich, validate, preview, commit
- CSV/ZIP/EAD and Excel (.xlsx/.xls) upload with auto-detection
- Auto field mapping with confidence indicators
- Embedded metadata extraction (EXIF/IPTC/XMP)
- Hierarchical tree preview with approval workflow
- OAIS-aligned SIP/DIP packaging
- Real-time commit progress with rollback
- SHA-256 checksum generation
- Duplicate detection
- Manifest download

## Hierarchical CSV (legacyId / parentId)

Video: [Hierarchical CSV import](https://youtu.be/pySvqADG4dQ).

Choose **Use hierarchy from CSV** on the Configure step. Each row names itself in
`legacyId`; `parentId` names its parent: a `legacyId` elsewhere in the same file, or
the slug of a description already in AtoM. A row with no `parentId` goes at the top.

- **Any row order.** Rows are committed parents-first, so a child may come before its
  parent in the file. The preview tree is built the same way.
- **Checked before anything is written.** A parent that is neither a `legacyId` in the
  file nor an existing slug, a row that is its own parent, and a chain of parents that
  loops back are errors; those rows are not committed.
- **Dry-run report.** The Preview step's *Dry-run report* button downloads a CSV of
  what the commit will do, row by row and in commit order: create or update (and
  which record), and where each record will go. Nothing is written.
- **Updating an existing hierarchy.** Every created record's `legacyId` is recorded in
  AtoM's `keymap`, under a source name (the uploaded file name unless one is given,
  as base AtoM's CSV import does). With **Update records imported before from this
  source** ticked, a row whose `legacyId` is already in the keymap for that source
  updates that record instead of creating a copy:
  - non-empty fields overwrite; empty fields leave the record alone;
  - dates and creators, when the row has any, replace the record's creation events;
  - access points are added if missing, never duplicated;
  - a changed `parentId` moves the record, and its descendants with it;
  - publication status changes only when the row gives one.

Check: `php ahgIngestPlugin/testing/hierarchy-planner-check.php` (ordering and loop
detection, no database needed).

## Grid entry

Admin > Data Ingest > Grid entry adds records under a chosen description in a
spreadsheet-style grid: type into the cells, or paste a block of cells from Excel
(with or without its heading row). Every row has **Duplicate** (a copy directly
below) and **Delete**. *Validate & save* hands the rows to the wizard's own commit.
Video: [Excel import and grid entry](https://youtu.be/IZqd7jo5ry4).

## Requirements

| Component | Version |
|---|---|
| atom framework | `>=1.0.0` |
| atom | `>=2.8` |
| php | `>=8.1` |

## Depends on

- `ahgCorePlugin`
- `ahgSecurityClearancePlugin`

## Database tables

Creates 7 table(s):

- `ingest_session`
- `ingest_file`
- `ingest_mapping`
- `ingest_validation`
- `ingest_row`
- `ingest_job`
- `ingest_watch_folder`

## Installation

This plugin requires **atom-framework**. It is not optional: the framework
provides `AhgController`, `AtomFramework\*` and the routing and settings
services that this plugin builds on.

```bash
# 1. Fetch into the AtoM plugins directory as a REAL DIRECTORY.
#    A symlink fails the prefix test in pluginsAction.class.php and the
#    plugin is then invisible in the stock admin UI with no error shown.
cd <atom-root>/plugins
git clone --depth 1 --filter=blob:none --sparse \
    https://github.com/ArchiveHeritageGroup/atom-ahg-plugins.git tmp-fetch
cd tmp-fetch && git sparse-checkout set ahgIngestPlugin && cd ..
mv tmp-fetch/ahgIngestPlugin ./ahgIngestPlugin && rm -rf tmp-fetch

# 2. Apply the schema.
mysql -u <user> -p <database> < ahgIngestPlugin/database/install.sql

# 3. Enable it, then clear the cache and reload PHP-FPM.
cd <atom-root>
php symfony cc
sudo systemctl reload php8.3-fpm
```

**Enabling differs by instance shape.** Check which list governs:

```bash
grep -c 'loadPluginsFromDatabase' <atom-root>/config/ProjectConfiguration.class.php
```

- `0` (stock AtoM): plugins load from the serialised `plugins` row in
  `setting_i18n`. The `atom_plugin` table is inert and the admin screen can
  show a plugin as enabled that does not load.
- `1` or more (AHG): `atom_plugin` is the source of truth.

Verify against whichever list governs, not against the admin screen.

## Licence

AGPL-3.0-or-later. Copyright The Archive and Heritage Digital Commons Group (Pty) Ltd.
