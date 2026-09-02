# Portable Export & Import

Full-system portable export and import for AtoM Heratio. Export complete instances with 15 entity types, SHA-256 checksummed manifests, and self-documenting packages. Import archives into fresh instances with ID remapping, merge/replace/dry-run modes. Also supports offline HTML viewer export for CD/USB/ZIP distribution.

| | |
|---|---|
| Machine name | `ahgPortableExportPlugin` |
| Version | 3.0.0 |
| Category | export |
| Licence | AGPL-3.0-or-later |
| Author | The Archive and Heritage Group (Pty) Ltd |

## Features

- Self-contained HTML/JS viewer for offline catalogue access
- Archive export: 15 entity types (descriptions, authorities, taxonomies, rights, accessions, physical objects, events, notes, relations, digital objects, repositories, object term relations, settings, users, menus)
- Archive import: merge, replace, or dry-run modes with full ID remapping
- Self-documenting packages: README.md + schema documentation in manifest
- SHA-256 checksummed manifest for all exported files
- FK-dependency-ordered import pipeline (taxonomies → repositories → descriptions → etc.)
- Dry-run estimate (record counts + estimated package size)
- Export and import verification CLI: portable:verify, portable:import --mode=dry_run
- Export full or partial catalogue (fonds, repository, custom scope)
- Client-side search with FlexSearch
- Hierarchical tree navigation (MPPT-ordered)
- Digital object inline viewing (images, PDFs)
- Edit mode with researcher exchange format
- CLI commands: portable:export, portable:import, portable:verify, portable:cleanup
- Admin UI with background processing and progress tracking (export + import)
- Secure download tokens for sharing
- Queue integration with nohup fallback

## Requirements

| Component | Version |
|---|---|
| atom framework | `>=2.0.0` |
| atom | `>=2.8` |
| php | `>=8.1` |

## Depends on

- `ahgCorePlugin`

## Database tables

Creates 3 table(s):

- `portable_export`
- `portable_export_token`
- `portable_import`

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
cd tmp-fetch && git sparse-checkout set ahgPortableExportPlugin && cd ..
mv tmp-fetch/ahgPortableExportPlugin ./ahgPortableExportPlugin && rm -rf tmp-fetch

# 2. Apply the schema.
mysql -u <user> -p <database> < ahgPortableExportPlugin/database/install.sql

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
