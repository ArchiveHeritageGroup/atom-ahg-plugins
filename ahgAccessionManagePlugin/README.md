# AHG Accession Manage

First-class accession management with intake queue, appraisal workflow, container tracking, rights inheritance, and multi-tenant isolation

| | |
|---|---|
| Machine name | `ahgAccessionManagePlugin` |
| Version | 2.0.1 |
| Category | browse |
| Licence | AGPL-3.0-or-later |
| Author | The Archive and Heritage Group |

## Features

- Accession browse via direct ES HTTP queries
- Intake queue with status workflow (draft/submitted/under_review/accepted/rejected/returned)
- Configurable intake checklists with templates
- Accession timeline (chain-of-custody audit trail)
- File attachments (deed of gift, photos, correspondence)
- Formal appraisal with weighted scoring criteria and templates
- Heritage asset valuation history (GRAP 103/IPSAS 45)
- Portfolio valuation reporting
- Physical container tracking with barcode support
- Container item management with IO linking
- PREMIS-aligned rights management with inheritance to child IOs
- Per-repository accession numbering sequences
- Multi-tenant isolation (tenant_id on all tables)
- CLI tasks: accession:intake, accession:report

## Requirements

| Component | Version |
|---|---|
| atom framework | `>=2.8.0` |
| atom | `>=2.8` |
| php | `>=8.1` |

## Depends on

- `ahgCorePlugin`

## Database tables

Creates 15 table(s):

- `accession_v2`
- `accession_intake_checklist`
- `accession_intake_template`
- `accession_timeline`
- `accession_attachment`
- `accession_appraisal`
- `accession_appraisal_criterion`
- `accession_appraisal_template`
- `accession_valuation_history`
- `accession_container`
- `accession_container_item`
- `accession_rights`
- `accession_rights_inherited`
- `accession_numbering_sequence`
- `accession_config`

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
cd tmp-fetch && git sparse-checkout set ahgAccessionManagePlugin && cd ..
mv tmp-fetch/ahgAccessionManagePlugin ./ahgAccessionManagePlugin && rm -rf tmp-fetch

# 2. Apply the schema.
mysql -u <user> -p <database> < ahgAccessionManagePlugin/database/install.sql

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
