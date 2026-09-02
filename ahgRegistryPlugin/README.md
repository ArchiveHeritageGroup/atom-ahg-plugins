# Registry Plugin

AtoM/Heratio Community Hub & Registry - Directory of institutions, vendors, software, user groups, discussions, blog, and sync API for the GLAM community.

| | |
|---|---|
| Machine name | `ahgRegistryPlugin` |
| Version | 1.0.0 |
| Category | - |
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

## Database tables

Creates 36 table(s):

- `registry_institution`
- `registry_vendor`
- `registry_contact`
- `registry_instance`
- `registry_software`
- `registry_software_release`
- `registry_vendor_institution`
- `registry_institution_software`
- `registry_review`
- `registry_sync_log`
- `registry_tag`
- `registry_user_group`
- `registry_user_group_member`
- `registry_discussion`
- `registry_discussion_reply`
- `registry_attachment`
- `registry_blog_post`
- `registry_settings`
- `registry_oauth_account`
- `registry_instance_feature`
- `registry_software_component`
- `registry_favorite`
- `registry_newsletter`
- `registry_newsletter_subscriber`
- `registry_newsletter_send_log`
- `registry_user_institution`
- `registry_dropdown`
- `registry_notification`
- `registry_entity_url`
- `registry_erd`
- `registry_password_reset`
- `registry_note`
- `registry_standard`
- `registry_standard_extension`
- `registry_software_standard`
- `registry_setup_guide`

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
cd tmp-fetch && git sparse-checkout set ahgRegistryPlugin && cd ..
mv tmp-fetch/ahgRegistryPlugin ./ahgRegistryPlugin && rm -rf tmp-fetch

# 2. Apply the schema.
mysql -u <user> -p <database> < ahgRegistryPlugin/database/install.sql

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
