# Storage locations: the rest of the list in #193

**2026-10-07**

Issue #193 carried five open storage items after the tree and the movement log
shipped. One of them, the bulk relocation screen, had already gone out as
v3.112.0 two days after the issue was last updated. This is the other four, on
the branch `storage-193-remaining`. It is not merged and not released.

## What was found on the way in

Nothing had ever been put in the tree. The relocation screen moves objects out
of the location they are in, and there was no screen that put one in a location
to begin with. On PSIS `ahg_storage_location` was empty, and so were the
movement log and the current-location index. The four items below all assumed
objects in locations, so placing had to come first.

The location types were hardcoded in three templates and once more as a
constant, as well as in the column comment the issue mentions. PSIS already had
the nine types in `ahg_dropdown` under `storage_location_type`, inserted on 28
September. Nothing read them, and `install.sql` did not seed them, so a fresh
install would not have had them.

## What changed

**Location types are a managed list.** `StorageLocationService::types()` reads
the Dropdown Manager taxonomy and the forms, the filter and the validation use
it. `install.sql` seeds the nine. A type that is switched off stops being
offered; a location that already has it keeps it, including through an edit. An
empty list falls back to the shipped nine.

**Placing objects.** A location's page lists physical objects with no place,
searchable by name and capped at a hundred, for administrators. Placing a
selection is one batch of first placements in the log.

**Containers inside containers.** The model is now written down: a carton or a
pallet is a location, a box is a physical object placed in it, and physical
objects have no parent link to each other. That is what keeps moving a pallet
to one log row. A location's page lists what is in every location beneath it,
with the location each object is actually in.

**Capacity roll-up.** `capacityRollup()` reports a location's own declared
capacity and, apart from it, the sum declared beneath, per unit. Units are not
added to each other and the two figures are not added together. Occupancy is a
count of objects here, beneath and in all. There is no percentage full, because
nothing records how much space a box takes.

**Migration of the flat fields.** `StorageFlatMigrationService`, run by
`php symfony storage:migrate-flat-locations`, dry unless `--apply`. It reads
the structured fields on `physical_object_extended` first, then the strongroom
assignment, and places each unplaced object in the innermost location named,
finding or creating the chain. Every strongroom becomes a room. Free text is
reported and left unless `--free-text-type` says what it means. Nothing is
deleted, it can be run again, and it is one transaction.

Left out on purpose: `information_object_physical_location`, which records
where a description sits inside its container, and `spectrum_location`, which
belongs to `ahgSpectrumPlugin`. Neither is the location of a physical object.

## How far it was tested

`testing/storage-contents-check.php`, 63 checks, against a throwaway MySQL 8.0
in a container: the type list and its fallbacks, nested containers, the
roll-up, placing, and the migration dry, applied, re-run, with free text, and
failing part way. The two existing checks still pass with the seeded install
file. Both were given `host=` and `port=` from the client file so they can run
somewhere other than the local socket.

**Not tested: anything in a browser.** The work was done in a separate working
copy so that it could not ride along in another session's release, and PSIS
loads plugins from the main one. The templates lint and nothing more. The
symfony task has never been run; the service it calls has. PHP CS Fixer is not
installed on this host, so the style rule is unchecked.

The migration has not been run on PSIS, dry or otherwise. That is a database
write and needs asking for.

## For the Heratio side

The schema is unchanged apart from the wording of one column comment, and nine
seed rows in `ahg_dropdown`. Heratio already reads location types from the
Dropdown Manager. The roll-up, the contents-beneath list, placing and the
migration have no twin there yet.

## 8 October: merged, and looked at on PSIS

The branch was squash-merged into the main tree, the Symfony cache cleared, and
the pages loaded from psis.theahg.co.za.

What was seen: the storage locations browse page renders with no error, and its
type filter lists the nine types from the Dropdown Manager, by their labels.
The tree API answers with an empty list, which is right: PSIS has no locations
yet. Signed out, the create page and the new place-objects action both show the
login page and not the form.

What was not seen, because it needs an administrator's login and at least one
location to exist: the create and edit forms, a location's own page with the
capacity card, the contents-beneath list, and "Place objects here". Creating a
location is a write to the PSIS database and was left for a person.

The migration task is found by Symfony and loads. Run as an ordinary user it
stops before doing anything, because the command-line log is writable only by
the web server's user. It wants running as www-data:

    sudo -u www-data php symfony storage:migrate-flat-locations

That is the dry run. It has still not been run on PSIS.

One thing to know about clearing the cache as an ordinary user: it leaves
command-line cache files owned by that user, which the web server's user cannot
then replace. The ones created here were removed. Clear the cache as www-data.
