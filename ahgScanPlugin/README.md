# Watched Folder Scanner

Watched-folder streaming ingest: configurable watched folders, a scan/watch CLI that detects new files and feeds the ingest pipeline, processed/failed disposition dirs, and dedupe by SHA-256 checksum.

| | |
|---|---|
| Machine name | `ahgScanPlugin` |
| Version | 1.0.0 |
| Category | ingestion |
| Licence | AGPL-3.0-or-later |
| Author | The Archive and Heritage Group (Pty) Ltd |

## Features

- Configurable watched folders (path, layout, disposition, quiet period)
- Each folder bound 1:1 to an ahgIngestPlugin session for processing config
- scan:watch CLI detects new files and feeds the ingest commit pipeline
- SHA-256 dedupe — files already ingested in the session are skipped
- Quiet-period guard (skip files still being written)
- Processed (archive) and failed (quarantine) disposition directories
- Per-pass scan_event audit log with counts and errors
- Admin UI at /admin/scan to manage folders and review history

## Requirements

| Component | Version |
|---|---|
| atom framework | `>=1.0.0` |
| atom | `>=2.8` |
| php | `>=8.1` |

## Depends on

- `ahgCorePlugin`
- `ahgIngestPlugin`

## Database tables

Creates 2 table(s):

- `scan_folder`
- `scan_event`

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
cd tmp-fetch && git sparse-checkout set ahgScanPlugin && cd ..
mv tmp-fetch/ahgScanPlugin ./ahgScanPlugin && rm -rf tmp-fetch

# 2. Apply the schema.
mysql -u <user> -p <database> < ahgScanPlugin/database/install.sql

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
