# AHG Exhibition

Exhibition management for GLAM/DAM sectors - standalone plugin for managing exhibitions, objects, storylines, and events

| | |
|---|---|
| Machine name | `ahgExhibitionPlugin` |
| Version | 1.0.1 |
| Category | glam |
| Licence | AGPL-3.0-or-later |
| Author | The Archive and Heritage Group |

## Features

- Exhibition lifecycle management
- Object selection and placement
- Storylines and narratives
- Venue and space management
- Installation tracking
- Event scheduling
- Checklists and tasks

## Requirements

| Component | Version |
|---|---|
| atom | `>=2.6.0` |

## Database tables

Creates 15 table(s):

- `exhibition_venue`
- `exhibition_gallery`
- `exhibition`
- `exhibition_status_history`
- `exhibition_section`
- `exhibition_object`
- `exhibition_event`
- `exhibition_checklist_template`
- `exhibition_checklist`
- `exhibition_checklist_item`
- `exhibition_storyline`
- `exhibition_storyline_stop`
- `exhibition_media`
- `ahg_exhibition_space`
- `ahg_exhibition_placement`

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
cd tmp-fetch && git sparse-checkout set ahgExhibitionPlugin && cd ..
mv tmp-fetch/ahgExhibitionPlugin ./ahgExhibitionPlugin && rm -rf tmp-fetch

# 2. Apply the schema.
mysql -u <user> -p <database> < ahgExhibitionPlugin/database/install.sql

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
