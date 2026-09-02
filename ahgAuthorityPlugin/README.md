# AHG Authority Records Enhancement

Comprehensive authority record enhancements: external linking (Wikidata, VIAF, ULAN, LCNAF), completeness dashboard, NER-to-authority pipeline, relationship graph, merge/split workflow, bulk deduplication, structured occupations, ISDF functions, EAC-CPF export enrichment, and contact panel surfacing.

| | |
|---|---|
| Machine name | `ahgAuthorityPlugin` |
| Version | 1.0.0 |
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

## Optional integrations

These are used when present and are not required:

- `ahgDedupePlugin`
- `ahgAIPlugin`
- `ahgRicExplorerPlugin`
- `ahgWorkflowPlugin`
- `ahgContactPlugin`
- `ahgExportPlugin`
- `ahgFunctionManagePlugin`

## Database tables

Creates 7 table(s):

- `ahg_actor_identifier`
- `ahg_actor_completeness`
- `ahg_actor_occupation`
- `ahg_actor_merge`
- `ahg_ner_authority_stub`
- `ahg_actor_function_link`
- `ahg_authority_config`

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
cd tmp-fetch && git sparse-checkout set ahgAuthorityPlugin && cd ..
mv tmp-fetch/ahgAuthorityPlugin ./ahgAuthorityPlugin && rm -rf tmp-fetch

# 2. Apply the schema.
mysql -u <user> -p <database> < ahgAuthorityPlugin/database/install.sql

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
