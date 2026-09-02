# AHG Authority Resolution Engine

Evidence-based authority resolution for persons, places, and organisations. Replaces name-only matching with an archivist-driven workflow that surfaces neighbourhood-context evidence, ranked candidates, and provenance-tracked decisions. Provenance writes to Fuseki as RDF-Star.

| | |
|---|---|
| Machine name | `ahgAuthorityResolutionPlugin` |
| Version | 0.1.0 |
| Category | authority |
| Licence | AGPL-3.0-or-later |
| Author | The Archive and Heritage Group |

## Requirements

| Component | Version |
|---|---|
| atom framework | `>=2.8.0` |
| atom | `>=2.8` |
| php | `>=8.1` |

## Depends on

- `ahgCorePlugin`
- `ahgActorManagePlugin`
- `ahgAIPlugin`

## Optional integrations

These are used when present and are not required:

- `ahgAuthorityPlugin`
- `ahgRicExplorerPlugin`
- `ahgTermTaxonomyPlugin`

## Database tables

Creates 7 table(s):

- `ahg_mention`
- `ahg_mention_context`
- `ahg_mention_candidate`
- `ahg_mention_decision`
- `ahg_mention_park`
- `ahg_ner_feedback`
- `ahg_authority_lookup_cache`

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
cd tmp-fetch && git sparse-checkout set ahgAuthorityResolutionPlugin && cd ..
mv tmp-fetch/ahgAuthorityResolutionPlugin ./ahgAuthorityResolutionPlugin && rm -rf tmp-fetch

# 2. Apply the schema.
mysql -u <user> -p <database> < ahgAuthorityResolutionPlugin/database/install.sql

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
