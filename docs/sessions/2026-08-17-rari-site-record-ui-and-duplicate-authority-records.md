# RARI: site record navigation, coordinate datum label, and duplicate authority records

**Date:** 2026-08-17
**Release:** atom-ahg-plugins v3.103.3
**Instance:** rari-dev (192.168.0.133, serving rari.theahg.co.za)

## Site record navigation

A site record was a dead end. The only way in is the panel on its authority record, and
neither the view nor the edit screen offered a way back to it - "Back to site records"
goes to the browse list, which is not where the reader came from. Both screens now carry
a **Back to record** button.

Guarded on `$actor->slug`. An actor that has been deleted would otherwise render
`href="/"` and silently send the reader to the home page, which is worse than no button.
`SiteRecordRepository::actor()` selects `sl.slug` explicitly, and `executeEdit` sets
`$this->actor` on both the by-id and by-actor paths, forwarding to 404 if it is missing,
so the guard is belt and braces rather than the only thing holding it up.

## "Datum" is not a typo for "Date"

Queried during review. It is the geodetic datum the coordinates are measured against -
WGS84, Cape Datum, Hartebeesthoek94 - and without it a latitude and longitude pair is
ambiguous by a few hundred metres. The column is `coordinate_datum`, defaulting to
WGS84.

It was still worth changing. The field sat directly beneath "Date visited", and *datum*
is Afrikaans for date, so in a South African archive the misreading is close to
inevitable. The label is now **Coordinate datum** with a hint line reading "Reference
frame for the coordinates, e.g. WGS84." Stored values are untouched.

## Duplicate authority records

Reported by RARI: the import created many duplicate authority records. Confirmed, and
the cause is an import that created rather than matched.

Measured on the development copy:

| | |
|---|---|
| Authority records | 8,443 |
| Surplus by exact name | 125, across 63 repeated names |
| Surplus allowing for case, spacing and punctuation | 143 |
| Loaded in the original pass, 30 April 2025 | 8,243 |
| Created after it | 186 |
| Of those, exact duplicates of an existing record | 58 |
| **Duplicates carrying descriptions** | **52** |

The clearest case is Chentcherere II: the real record (id 17107, April) holds 84
descriptions, and nineteen copies created in a single run on 21 August 2025 hold 43 more
between them, slugged `chentcherere-ii-2` through `-20`. The copies have
`entity_type_id` NULL where the original has 1996, which is the fingerprint of the
importer creating a fresh actor instead of matching an existing one - and then attaching
that run's descriptions to what it had just made.

Two findings shape the remedy:

- **It is a merge, not a delete.** An initial usage check said the duplicates were
  almost all orphaned. That check was wrong: it counted the `relation` table, but AtoM
  links a creator to a description through `event.actor_id`. Rechecked properly, 52 of
  the 58 duplicates carry descriptions. Deleting them would take those descriptions with
  them.
- **Name is the only key.** `description_identifier` is empty for every actor, so there
  is no site code to match on. Site codes exist only as parallel names, the same field
  covered by the outstanding locality decision.

Two fingerprints were tested and rejected. Slugs ending `-<number>` match 2,622 actors,
because RARI site names legitimately contain numerals. `entity_type_id` NULL matches 302,
but 140 of those come from the original load, so it is not specific to the import either.
Exact and normalised name matching remains the only sound basis, and the 120 later
records with no exact twin still need checking for near-duplicates.

No data was modified. The sequence - dry-run report, RARI review, apply on development,
repeat at cutover, re-run detection - is now a section in
`docs/RARI_Production_Migration_Plan.md`, together with the point that the importer must
be corrected first or the next load recreates the problem.

The existing deduplication plugin does not help here: it scans `information_object` and
`digital_object` and has no actor handling, so this is new work rather than repointing an
existing tool.

## Also

The legacy `rock_forms` application has been removed from RARI production at Wits and its
credentials rotated, confirmed by Johan. That closes the unauthenticated create, edit and
delete surface and the published coordinates described in the plugin's planning notes.

The decision on the 877 parallel names that encode map sheet references is still with
RARI management.
