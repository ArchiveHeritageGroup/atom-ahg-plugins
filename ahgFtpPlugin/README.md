# FTP / SFTP Upload

Browser-based FTP/SFTP upload for CSV import digital objects. Provides drag-and-drop upload interface under Import > FTP Upload so users can place files on the server without external FTP client software. Prominently shows the path to use in CSV digitalObjectPath column.

| | |
|---|---|
| Machine name | `ahgFtpPlugin` |
| Version | 1.0.0 |
| Category | import |
| Licence | AGPL-3.0-or-later |
| Author | The Archive and Heritage Group (Pty) Ltd |

## Features

- Browser-based drag-and-drop file upload to FTP/SFTP server
- Multi-file upload with per-file progress bars
- Remote file listing with size, date, and delete
- Prominent CSV path warning showing exact digitalObjectPath prefix
- Admin-configurable FTP/SFTP connection settings
- Test Connection button in settings
- Supports FTP (active/passive) and SFTP protocols

## Requirements

| Component | Version |
|---|---|
| atom framework | `>=2.0.0` |
| atom | `>=2.8` |
| php | `>=8.1` |

## Depends on

- `ahgCorePlugin`
- `ahgSettingsPlugin`

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
cd tmp-fetch && git sparse-checkout set ahgFtpPlugin && cd ..
mv tmp-fetch/ahgFtpPlugin ./ahgFtpPlugin && rm -rf tmp-fetch

# 2. Apply the schema.
mysql -u <user> -p <database> < ahgFtpPlugin/database/install.sql

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
