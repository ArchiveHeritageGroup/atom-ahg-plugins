# 2026-07-22 - Fix: full embargo not enforced on the record detail view

**Repo:** atom-ahg-plugins. **Releases:** v3.79.149 (ISAD), v3.79.150 (RAD/MODS/DC/DACS).

## Symptom

A record with an **active "full" embargo** (`rights_embargo.embargo_type = 'full'`,
`status = 'active'`) was still fully visible to users who had the direct link - the
metadata rendered as normal.

## Root cause

Embargo enforcement was only partially wired:
- **Browse/search listings** - already filtered: `SearchAccessFilterService` excludes
  full embargoes ("only full embargoes hide from search; other types allow metadata").
- **Digital objects / media** - already filtered: `DigitalObjectEmbargoFilter` (wired in
  the ExtendedRights plugin config).
- **Record DETAIL view** - NOT enforced. `EmbargoService::canAccessRecord()` returns the
  correct answer (false for a full embargo unless the user has edit permission), and
  `EmbargoAccessFilter::checkAccess()` exists to act on it - but nothing ever called it on
  the information-object view. So the detail page leaked the full record. This was the
  parked "full embargo enforcement" item.

## Fix

Invoke `EmbargoService::canAccessRecord($resource->id, $sf_user)` at the top of each
descriptive-standard view template. If the user cannot access the record, set the title to
"Access restricted", empty the sidebar, render an "under embargo" notice, and `return`
before any record content is output. Wrapped in a `try/catch` so an embargo-check error
never breaks the page, and guarded on `isset($resource)`.

Templates gated (ahgThemeB5Plugin/modules/.../templates/indexSuccess.php):
- v3.79.149: **sfIsadPlugin** (ISAD - archaeology's standard)
- v3.79.150: **sfRadPlugin, sfModsPlugin, sfDcPlugin, arDacsPlugin** (RAD, MODS, Dublin
  Core, DACS)

## Behaviour after fix

- Guests/researchers under a full embargo: see the notice, zero metadata leaked
  (verified on object 869 - `krk-sf238`; content grep = 0).
- **Staff with edit permission bypass** - they still see the record (correct). If you test
  while logged in as admin you will still see it; the block is for non-staff.
- Non-embargoed records unaffected (control verified).
- Partial (non-full) embargoes still allow metadata viewing by design; only digital
  objects are hidden for those.

## Notes

- The gate lives in the theme view templates (not base AtoM), so it is upgrade-safe.
- `rights_embargo` links via `object_id` (not `information_object_id`).
- Actor/repository/other module views are not information-object detail pages and are out
  of scope for this fix.
