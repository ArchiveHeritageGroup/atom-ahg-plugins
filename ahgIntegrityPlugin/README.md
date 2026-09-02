# Integrity Assurance

Enterprise-grade automated integrity assurance: scheduled fixity verification, scoped validation, concurrency controls, append-only ledger, dead-letter queue, retention policies, legal holds, disposition review, threshold alerting

| | |
|---|---|
| Machine name | `ahgIntegrityPlugin` |
| Version | 1.1.0 |
| Category | preservation |
| Licence | AGPL-3.0-or-later |
| Author | The Archive and Heritage Group (Pty) Ltd |

## Requirements

| Component | Version |
|---|---|
| atom framework | `>=2.8.0` |
| atom | `>=2.8` |
| php | `>=8.1` |

## Depends on

- `ahgCorePlugin`
- `ahgPreservationPlugin`

## Database tables

Creates 12 table(s):

- `integrity_schedule`
- `integrity_run`
- `integrity_ledger`
- `integrity_dead_letter`
- `integrity_retention_policy`
- `integrity_legal_hold`
- `integrity_disposition_queue`
- `integrity_alert_config`
- `vital_record`
- `record_declaration`
- `destruction_certificate`
- `retention_trigger_event`

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
cd tmp-fetch && git sparse-checkout set ahgIntegrityPlugin && cd ..
mv tmp-fetch/ahgIntegrityPlugin ./ahgIntegrityPlugin && rm -rf tmp-fetch

# 2. Apply the schema.
mysql -u <user> -p <database> < ahgIntegrityPlugin/database/install.sql

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
