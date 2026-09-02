# Semantic Search

Semantic search and thesaurus management with WordNet/Datamuse API sync, Wikidata SPARQL integration, and vector embeddings via Ollama

| | |
|---|---|
| Machine name | `ahgSemanticSearchPlugin` |
| Version | 1.0.0 |
| Category | search |
| Licence | AGPL-3.0-or-later |
| Author | The Archive and Heritage Group |

## Features

- Thesaurus management with domain-specific synonyms
- WordNet sync via Datamuse API
- Wikidata SPARQL integration
- Local JSON synonym import
- Elasticsearch synonym export
- Query expansion for enhanced search
- Vector embeddings via Ollama
- Scheduled sync via cron jobs

## Requirements

| Component | Version |
|---|---|
| atom framework | `>=1.0.0` |
| atom | `>=2.8` |
| php | `>=8.1` |

## Depends on

- `ahgCorePlugin`

## Database tables

Creates 8 table(s):

- `search_history`
- `search_template`
- `ahg_thesaurus_term`
- `ahg_thesaurus_synonym`
- `ahg_thesaurus_sync_log`
- `ahg_thesaurus_embedding`
- `ahg_semantic_search_settings`
- `ahg_semantic_search_log`

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
cd tmp-fetch && git sparse-checkout set ahgSemanticSearchPlugin && cd ..
mv tmp-fetch/ahgSemanticSearchPlugin ./ahgSemanticSearchPlugin && rm -rf tmp-fetch

# 2. Apply the schema.
mysql -u <user> -p <database> < ahgSemanticSearchPlugin/database/install.sql

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
