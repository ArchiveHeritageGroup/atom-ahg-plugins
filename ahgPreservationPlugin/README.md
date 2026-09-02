# Digital Preservation

Digital preservation: checksums, fixity verification, PREMIS events, format registry

| | |
|---|---|
| Machine name | `ahgPreservationPlugin` |
| Version | 1.0.6 |
| Category | preservation |
| Licence | AGPL-3.0-or-later |
| Author | The Archive and Heritage Group (Pty) Ltd |

## Requirements

| Component | Version |
|---|---|
| atom framework | `>=1.0.0` |
| atom | `>=2.8` |
| php | `>=8.1` |

## Depends on

- `ahgCorePlugin`

## Database tables

Creates 30 table(s):

- `oais_information_package`
- `oais_fixity_check`
- `oais_package_content`
- `oais_premis_event`
- `oais_preservation_policy`
- `oais_pronom_format`
- `preservation_checksum`
- `preservation_fixity_check`
- `preservation_event`
- `preservation_format`
- `preservation_object_format`
- `preservation_policy`
- `preservation_stats`
- `preservation_virus_scan`
- `preservation_format_conversion`
- `preservation_backup_verification`
- `preservation_replication_target`
- `preservation_replication_log`
- `preservation_workflow_schedule`
- `preservation_workflow_run`
- `preservation_package`
- `preservation_package_object`
- `preservation_package_event`
- `preservation_migration_pathway`
- `preservation_format_obsolescence`
- `preservation_migration_plan`
- `preservation_migration_plan_object`
- `tiff_pdf_merge_job`
- `tiff_pdf_merge_file`
- `tiff_pdf_settings`

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
cd tmp-fetch && git sparse-checkout set ahgPreservationPlugin && cd ..
mv tmp-fetch/ahgPreservationPlugin ./ahgPreservationPlugin && rm -rf tmp-fetch

# 2. Apply the schema.
mysql -u <user> -p <database> < ahgPreservationPlugin/database/install.sql

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
