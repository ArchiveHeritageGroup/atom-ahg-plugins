# Data Migration Tool

Migrate records from external systems (ArchivesSpace, Vernon CMS, Preservica OPEX/PAX) with field mapping, Gearman background jobs, sector-specific CSV export, rights and provenance import

| | |
|---|---|
| Machine name | `ahgDataMigrationPlugin` |
| Version | 1.2.11 |
| Category | ahg |
| Licence | AGPL-3.0-or-later |
| Author | The Archive and Heritage Group |

## Features

- Web UI import with visual field mapping
- Preservica OPEX/PAX import and export
- OPEX rights extraction (SecurityDescriptor, dc:rights, MODS, EAD)
- Provenance/history import from OPEX
- Gearman background job processing
- Sector-specific CSV exporters (Archives, Museum, Library, Gallery, DAM)
- CLI import with mapping support
- Automatic slug, publication status, and nested set calculation

## Requirements

| Component | Version |
|---|---|
| atom framework | `>=1.0.0` |
| atom | `>=2.8` |
| php | `>=8.1` |

## Database tables

Creates 5 table(s):

- `atom_data_mapping`
- `atom_migration_job`
- `atom_migration_log`
- `atom_validation_rule`
- `atom_validation_log`

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
cd tmp-fetch && git sparse-checkout set ahgDataMigrationPlugin && cd ..
mv tmp-fetch/ahgDataMigrationPlugin ./ahgDataMigrationPlugin && rm -rf tmp-fetch

# 2. Apply the schema.
mysql -u <user> -p <database> < ahgDataMigrationPlugin/database/install.sql

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
