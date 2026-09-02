# AHG Federation

OAI-PMH Federation plugin for Heritage Platform metadata exchange

| | |
|---|---|
| Machine name | `ahgFederationPlugin` |
| Version | 1.0.0 |
| Category | integration |
| Licence | AGPL-3.0-or-later |
| Author | AHG Development Team |

## Features

- oai_heritage_format
- harvest_client
- provenance_tracking

## Database tables

Creates 10 table(s):

- `federation_peer`
- `federation_harvest_log`
- `federation_harvest_session`
- `federation_peer_search`
- `federation_search_cache`
- `federation_search_log`
- `federation_vocab_sync`
- `federation_term_mapping`
- `federation_vocab_change`
- `federation_vocab_sync_log`

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
cd tmp-fetch && git sparse-checkout set ahgFederationPlugin && cd ..
mv tmp-fetch/ahgFederationPlugin ./ahgFederationPlugin && rm -rf tmp-fetch

# 2. Apply the schema.
mysql -u <user> -p <database> < ahgFederationPlugin/database/install.sql

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
