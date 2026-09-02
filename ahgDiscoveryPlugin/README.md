# Discovery

Topic discovery and related content — natural language search with NER, synonym expansion, hierarchical context, and PageIndex LLM-driven retrieval over EAD, PDF, and RiC-O records

| | |
|---|---|
| Machine name | `ahgDiscoveryPlugin` |
| Version | 1.0.0 |
| Category | search |
| Licence | AGPL-3.0-or-later |
| Author | The Archive and Heritage Group |

## Features

- Natural language query expansion with synonym lookup
- Three-strategy search: keyword, NER entity, hierarchical
- Result grouping by collection/fonds
- Related content sidebar for record views
- Search analytics and caching
- PageIndex LLM-driven retrieval: vectorless, reasoning-based search over records
- Tree index builder for EAD finding aids, uploaded PDFs, and RiC-O metadata
- Ollama-powered tree construction and retrieval reasoning (llama3.1:8b)
- Explainable search results with tree node breadcrumb paths
- Admin trigger to index individual information objects

## Requirements

| Component | Version |
|---|---|
| atom framework | `>=2.8.0` |
| atom | `>=2.8` |
| php | `>=8.1` |

## Depends on

- `ahgCorePlugin`

## Database tables

Creates 4 table(s):

- `ahg_discovery_cache`
- `ahg_discovery_log`
- `ahg_pageindex_tree`
- `ahg_pageindex_query_log`

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
cd tmp-fetch && git sparse-checkout set ahgDiscoveryPlugin && cd ..
mv tmp-fetch/ahgDiscoveryPlugin ./ahgDiscoveryPlugin && rm -rf tmp-fetch

# 2. Apply the schema.
mysql -u <user> -p <database> < ahgDiscoveryPlugin/database/install.sql

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
