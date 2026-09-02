# Library & Bibliographic

Library cataloging with MARC-inspired fields, ISBN lookup, and bibliographic management

| | |
|---|---|
| Machine name | `ahgLibraryPlugin` |
| Version | 1.9.15 |
| Category | sector |
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

## Database tables

Creates 44 table(s):

- `atom_isbn_cache`
- `atom_isbn_lookup_audit`
- `atom_isbn_provider`
- `atom_library_cover_queue`
- `library_item`
- `library_item_creator`
- `library_subject_authority`
- `library_item_subject`
- `library_settings`
- `library_entity_subject_map`
- `library_kbart_vendor`
- `library_kbart_import_log`
- `library_z3950_server_config`
- `library_z3950_server_request`
- `library_item_frbr_override`
- `library_copy`
- `library_patron`
- `library_checkout`
- `library_hold`
- `library_fine`
- `library_subscription`
- `library_serial_issue`
- `library_order`
- `library_order_line`
- `library_budget`
- `library_ill_request`
- `library_loan_rule`
- `library_item_authority_link`
- `library_trading_partner`
- `library_bindery_batch`
- `library_order_line_fund`
- `library_ill_status_history`
- `library_serial_subscription`
- `library_serial_prediction`
- `library_claim`
- `library_binding`
- `library_usage_event`
- `library_counter_settings`
- `library_sushi_access_log`
- `library_z3950_target`
- `library_sru_log`
- `library_z3950_import_log`
- `library_onix_ingest`
- `library_onix_ingest_line`

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
cd tmp-fetch && git sparse-checkout set ahgLibraryPlugin && cd ..
mv tmp-fetch/ahgLibraryPlugin ./ahgLibraryPlugin && rm -rf tmp-fetch

# 2. Apply the schema.
mysql -u <user> -p <database> < ahgLibraryPlugin/database/install.sql

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
