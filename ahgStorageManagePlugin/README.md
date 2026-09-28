# AHG Storage Manage

Physical storage browse and management using Laravel Query Builder

| | |
|---|---|
| Machine name | `ahgStorageManagePlugin` |
| Version | 1.0.0 |
| Category | browse |
| Licence | AGPL-3.0-or-later |
| Author | The Archive and Heritage Group |

## Features

- Physical storage browse via Laravel Query Builder
- Sort by name and location with direction toggle
- Inline search across name, location, and type
- Export storage report link
- Theme-compatible templates (SimplePager)
- Hierarchical storage locations, with parent, capacity and type

## Requirements

| Component | Version |
|---|---|
| atom framework | `>=1.0.0` |
| atom | `>=2.8` |
| php | `>=8.1` |

## Depends on

- `ahgCorePlugin`

## Database tables

Creates 7 table(s):

- `physical_object_extended`
- `ahg_strongroom`
- `ahg_physical_object_storage`
- `ahg_storage_location`
- `ahg_storage_location_closure`
- `ahg_physical_object_location`
- `ahg_storage_movement`

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
cd tmp-fetch && git sparse-checkout set ahgStorageManagePlugin && cd ..
mv tmp-fetch/ahgStorageManagePlugin ./ahgStorageManagePlugin && rm -rf tmp-fetch

# 2. Apply the schema.
mysql -u <user> -p <database> < ahgStorageManagePlugin/database/install.sql

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

## Storage locations

A hierarchy of places - building, floor, room, aisle, bay, rack, shelf, container,
storage unit - held in `ahg_storage_location` as an adjacency list (`parent_id`),
with `level` carried alongside for ordering and recomputed whenever a location moves.

| URL | Who |
|---|---|
| `/storageLocation/browse`, `/view?id=`, `/apiLocations`, `/apiTree`, `/apiSearch` | Anyone |
| `/storageLocation/create`, `/save`, `/edit`, `/update`, `/delete` | Administrators |

`modules/storageLocation/config/security.yml` is what makes that table true. The
application default is `is_secure: false`, so a module without its own security.yml
leaves save, update and delete open to anonymous visitors. The same mistake is
recorded at the top of `modules/storageManage/config/security.yml`.

A location with children refuses to be deleted, rather than letting the foreign
key quietly promote its children to the root.

## Movement log

`ahg_storage_movement` records every move, append-only: what moved, where from,
where to, when, by whom, and why. A move entered wrongly is corrected by a
further move, never by editing or deleting a row - a history that can be quietly
rewritten proves nothing about the custody of the holdings.

Two kinds of subject. A **physical object** moving between locations, and a
**storage location** moving within the hierarchy. A location move is logged once,
for the location that moved; what sat under it is a closure query, so fanning the
event out over every object underneath would only duplicate the hierarchy and go
stale the moment the tree changed.

A null on one side is meaningful, and reads differently for the two subjects.
For an object, no `from` is a first placement and no `to` is a removal from
storage. For a location, the two columns hold its old and new parent, and null
is the root of the hierarchy. That is why the foreign keys are RESTRICT rather than SET
NULL - nulling a deleted location's id would silently turn "moved out of Room A"
into "taken out of storage". Each row also snapshots the subject and location
names and the username, so history still reads after a rename or a deleted
account.

`ahg_physical_object_location` is the current-state index beside the log, one row
per object, written in the same transaction. Browse reads it instead of working
out the latest movement per object - the same split as `parent_id` and its
closure table.

A bulk move writes one row per object sharing a `batch_id`, so per-object history
stays a plain lookup and "what moved together" is a lookup by batch. Objects
already in the destination are skipped rather than logged as moves that did not
happen.

A location cannot be deleted while it holds objects or appears in the movement
log; the log outlives the shelf.

    php ahgStorageManagePlugin/testing/storage-movement-check.php /path/to/client.cnf

builds a throwaway database and checks, after every step, that the log and the
current-location index agree.

## Licence

AGPL-3.0-or-later. Copyright The Archive and Heritage Digital Commons Group (Pty) Ltd.
