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
| `/storageLocation/create`, `/save`, `/edit`, `/update`, `/delete`, `/moveObjects`, `/placeObjects` | Administrators |

`modules/storageLocation/config/security.yml` is what makes that table true. The
application default is `is_secure: false`, so a module without its own security.yml
leaves save, update and delete open to anonymous visitors. The same mistake is
recorded at the top of `modules/storageManage/config/security.yml`.

A location with children refuses to be deleted, rather than letting the foreign
key quietly promote its children to the root.

The location types are a managed list: the Dropdown Manager taxonomy
`storage_location_type`, seeded with the nine above by `install.sql`. An archive
that keeps things in a plan cabinet adds "cabinet" there and it appears in the
forms. A type switched off is no longer offered; locations that already carry it
keep it. If the list is empty, or `ahg_dropdown` is not there, the nine shipped
types are used, because a form with no types in it cannot save anything.

### Containers inside containers

A box in a carton on a pallet in a bay is modelled with the tree, not beside it.
The carton and the pallet are **locations** (types `container` and
`storage_unit`); the box is the **physical object** placed in the carton. There
is no parent link between physical objects.

That choice is what makes moving a pallet one event. The pallet is a location,
so moving it is a location move, logged once, and everything on it goes with it
because it is still in the same carton on the same pallet. Had the carton been a
physical object holding other physical objects, relocating it would have meant
rewriting every box inside.

A location's page lists what is held in it directly and, separately, what is
held in every location beneath it, each with the location it is actually in.

### Placing objects

A location's page offers the physical objects that have no place yet, with a
search by name, to administrators. Selecting some and pressing **Place here**
is one batch in the movement log, each a first placement. Objects that already
have a place are moved from the page of the location they are in.

### Capacity

Each location may declare a capacity and a unit. A location's page shows its own
figure and, apart from it, the sum of what is declared beneath, **per unit**.
Units are never added to each other, and the two figures are never added
together: an archive may declare capacity on a room, on its shelves, or on both,
and adding them would count the same space twice.

What is held is counted in physical objects - here, beneath, and in all. The
plugin does not know how much of a shelf a box takes up, so it does not claim a
percentage full.

### Bringing the old location fields into the tree

Before the tree, where a box was kept was recorded in three places:
`physical_object_extended` (building, floor, room, aisle, bay, rack, shelf), a
strongroom assignment, and the free-text `location` on the physical object.

    php symfony storage:migrate-flat-locations            # report only
    php symfony storage:migrate-flat-locations --apply

For each physical object without a place it finds or creates the chain of
locations the structured fields name and places the object in the innermost one.
Failing those it uses the strongroom, which becomes a room. Every strongroom
becomes a room whether or not anything is in it, with its description and
capacity.

- Nothing is deleted or overwritten. The old fields stay as they were.
- It can be run again. Objects already placed are left alone and existing
  locations are reused, matched on parent, type and name without regard to case.
- It is one transaction. If it cannot finish, nothing is changed.
- **Free text is not guessed at.** An object with only free text is listed and
  left. If an archive's free text always names, say, a room, pass
  `--free-text-type=room` and each distinct text becomes a root location of that
  type.

Not carried over, because they are not locations of a physical object:
`information_object_physical_location` records where a description sits *inside*
its container (shelf, row, folder), and `spectrum_location` belongs to
`ahgSpectrumPlugin` and records where a described object is, not a container.

    php ahgStorageManagePlugin/testing/storage-contents-check.php /path/to/client.cnf

checks the type list, the roll-up, nested containers, placing and the migration
against a throwaway database.

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
