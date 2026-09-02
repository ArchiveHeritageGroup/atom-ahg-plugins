# AHG Actor Manage

High-performance actor browse and autocomplete using Laravel Query Builder and direct ES queries. Replaces base AtoM actor browse that causes N+1 query hangs.

| | |
|---|---|
| Machine name | `ahgActorManagePlugin` |
| Version | 1.0.0 |
| Category | browse |
| Licence | AGPL-3.0-or-later |
| Author | The Archive and Heritage Group |

## Features

- Actor browse via direct ES HTTP queries (no Elastica)
- Batch facet population (2 queries instead of N+1)
- Actor autocomplete via ES prefix search with DB fallback
- Advanced search with boolean criteria
- Multiple display modes (list, grid, card)

## Requirements

| Component | Version |
|---|---|
| atom framework | `>=1.0.0` |
| atom | `>=2.8` |
| php | `>=8.1` |

## Depends on

- `ahgCorePlugin`

## Database tables

Creates 1 table(s):

- `ahg_actor_visibility`

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
cd tmp-fetch && git sparse-checkout set ahgActorManagePlugin && cd ..
mv tmp-fetch/ahgActorManagePlugin ./ahgActorManagePlugin && rm -rf tmp-fetch

# 2. Apply the schema.
mysql -u <user> -p <database> < ahgActorManagePlugin/database/install.sql

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
