# Extended Rights Management

Extended rights management with RightsStatements.org integration, embargo management, Traditional Knowledge labels, and batch rights assignment

| | |
|---|---|
| Machine name | `ahgExtendedRightsPlugin` |
| Version | 1.2.9 |
| Category | rights |
| Licence | AGPL-3.0-or-later |
| Author | The Archive and Heritage Group |

## Requirements

| Component | Version |
|---|---|
| atom framework | `>=1.0.0` |
| atom | `>=2.8` |
| php | `>=8.1` |

## Depends on

- `ahgCorePlugin`
- `ahgRightsPlugin`

## Database tables

Creates 14 table(s):

- `object_rights_holder`
- `object_rights_statement`
- `tk_label_category`
- `tk_label`
- `tk_label_category_i18n`
- `tk_label_i18n`
- `extended_rights`
- `extended_rights_batch_log`
- `extended_rights_i18n`
- `extended_rights_tk_label`
- `embargo`
- `embargo_audit`
- `embargo_exception`
- `embargo_i18n`

Reads or writes these tables owned by other plugins:

- `rights_embargo`
- `rights_embargo_i18n`

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
cd tmp-fetch && git sparse-checkout set ahgExtendedRightsPlugin && cd ..
mv tmp-fetch/ahgExtendedRightsPlugin ./ahgExtendedRightsPlugin && rm -rf tmp-fetch

# 2. Apply the schema.
mysql -u <user> -p <database> < ahgExtendedRightsPlugin/database/install.sql

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
