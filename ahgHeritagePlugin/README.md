# Heritage Plugin

Heritage discovery platform with contributor system, custodian management, and analytics

| | |
|---|---|
| Machine name | `ahgHeritagePlugin` |
| Version | 1.1.0 |
| Category | - |
| Licence | AGPL-3.0-or-later |
| Author | The Archive and Heritage Group |

## Requirements

| Component | Version |
|---|---|
| atom framework | `>=1.0.0` |
| atom | `>=2.8` |
| php | `>=8.1` |

## Depends on

- `ahgThemeB5Plugin`

## Database tables

Creates 45 table(s):

- `heritage_era`
- `heritage_landing_config`
- `heritage_filter_type`
- `heritage_institution_filter`
- `heritage_filter_value`
- `heritage_curated_story`
- `heritage_hero_image`
- `heritage_discovery_log`
- `heritage_discovery_click`
- `heritage_learned_term`
- `heritage_search_suggestion`
- `heritage_ranking_config`
- `heritage_entity_cache`
- `heritage_contributor`
- `heritage_contribution_type`
- `heritage_contribution`
- `heritage_contribution_version`
- `heritage_contributor_session`
- `heritage_contributor_badge`
- `heritage_contributor_badge_award`
- `heritage_feature_toggle`
- `heritage_branding_config`
- `heritage_trust_level`
- `heritage_user_trust`
- `heritage_purpose`
- `heritage_embargo`
- `heritage_access_request`
- `heritage_access_rule`
- `heritage_popia_flag`
- `heritage_audit_log`
- `heritage_batch_job`
- `heritage_batch_item`
- `heritage_analytics_daily`
- `heritage_analytics_search`
- `heritage_analytics_content`
- `heritage_analytics_alert`
- `heritage_content_quality`
- `heritage_featured_collection`
- `heritage_hero_slide`
- `heritage_explore_category`
- `heritage_timeline_period`
- `heritage_entity_graph_node`
- `heritage_entity_graph_edge`
- `heritage_entity_graph_object`
- `heritage_graph_build_log`

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
cd tmp-fetch && git sparse-checkout set ahgHeritagePlugin && cd ..
mv tmp-fetch/ahgHeritagePlugin ./ahgHeritagePlugin && rm -rf tmp-fetch

# 2. Apply the schema.
mysql -u <user> -p <database> < ahgHeritagePlugin/database/install.sql

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
