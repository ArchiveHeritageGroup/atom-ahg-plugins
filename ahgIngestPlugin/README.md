# Ingestion Manager

OAIS-aligned multi-stage ingestion pipeline: configure, upload, map, validate, preview, commit with rollback support

| | |
|---|---|
| Machine name | `ahgIngestPlugin` |
| Version | 1.0.0 |
| Category | ingestion |
| Licence | AGPL-3.0-or-later |
| Author | The Archive and Heritage Group (Pty) Ltd |

## Features

- 6-step wizard: configure, upload, map & enrich, validate, preview, commit
- CSV/ZIP/EAD upload with auto-detection
- Auto field mapping with confidence indicators
- Embedded metadata extraction (EXIF/IPTC/XMP)
- Hierarchical tree preview with approval workflow
- OAIS-aligned SIP/DIP packaging
- Real-time commit progress with rollback
- SHA-256 checksum generation
- Duplicate detection
- Manifest download

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
