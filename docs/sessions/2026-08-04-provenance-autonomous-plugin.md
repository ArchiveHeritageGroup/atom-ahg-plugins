# Provenance as an autonomous plugin - v3.94.0

**Date:** 2026-08-04
**Repo:** atom-ahg-plugins
**Release:** v3.94.0
**Instance:** POC VM `atom210` (192.168.0.131), stock AtoM, no AHG theme, no framework

## What was tested

"Add Provenance to an Archival Description" on a stock AtoM install, as the second
plugin in the one-plugin-at-a-time delivery POC (after the IIIF viewer plugins).

Installed by sparse checkout as a **real directory** (not a symlink), ran its own
`database/install.sql` (8 `provenance*` tables), enabled via the stock `plugins`
setting. Six AHG plugins now enabled on the VM: Core, Iiif, Seadragon, Mirador,
Backup, Provenance.

Data captured through the plugin's own edit form: a provenance record
(`in_custody` / `owned` / `donation` / 1998-04-15 / `documented`) plus two custody
events - a donation from "Estate of M. van der Merwe" to AHG and an accessioning -
with both agents auto-created by `findOrCreateAgent`. Verified in the database:
`provenance_record` 1, `provenance_event` 2, `provenance_agent` 2, no orphans.

## Three defects the test exposed, all fixed

### 1. Provenance was invisible without an AHG theme
`provenanceDisplay` is only ever invoked by theme templates (ahgThemeB5Plugin,
ahgMuseumPlugin, ahgLibraryPlugin). A stock install could capture provenance and
never display it.

**Fix:** new `lib/Listeners/ProvenanceInjector.php` renders that same component via
`response.filter_content`. Same pattern as ahgIiifPlugin's `ViewerInjector` - and the
same guards: skip when `ahgThemeB5Plugin` is present (else the panel renders twice),
once per request, HTML GET only, and the descriptive-standard module list rather than
just `informationobject`.

Placed directly above the `<div class="digitalObjectMetadata">` block, with the viewer
markers as fallback anchors. Heading uses AtoM's own section-header markup
(`h5 mb-0 atom-section-header` + `d-flex p-3 border-bottom text-primary`) so it renders
in the native orange style and reads as part of the page.

### 2. `is_public` was written but never enforced on read
The edit form wrote the flag and the export reported it, but no read path checked it -
so a record flagged non-public still published **donor and prior-owner names to
anonymous visitors**. The theme path leaks identically; it gates only on "is the plugin
enabled".

**Fix:** enforced in `executeProvenanceDisplay`, which every caller goes through, so
theme templates and the injector are both covered by one check. Non-public provenance
stays visible to users who can edit the description (`QubitAcl::check($resource,
'update')`). Legacy museum bridge rows have no `is_public` column and default to
visible, preserving existing behaviour.

Verified: `is_public=0` -> anonymous 0 occurrences, admin 1. `is_public=1` -> anonymous 1.

### 3. `addEvent` returned an uncaught 500 on a missing `record_id`
`ProvenanceService::addEvent()` declares `int $recordId`, so a missing parameter raised
a **TypeError - an `\Error`, which `catch (\Exception)` does not catch** - and escaped
as an uncaught 500. Agents were also created *before* the parameter was ever checked,
leaving orphan rows.

**Fix:** validate first (`renderJsonError('record_id is required')`, HTTP 400 JSON), and
widen the catch to `\Throwable`.

Also added the CSRF token to the edit form **and** its `fetch()` call - the `fetch()`
half is the one repeatedly missed, and `CsrfService` runs in `enforce` mode.

## Lessons

- **A column name guess cost a wrong conclusion.** `provenance_record` keys on
  `information_object_id`, not `object_id`; my query silently returned nothing and I
  briefly reported the save as failed when it had worked. Same failure family as the
  earlier `ahg_backup*` table-name guess. **Confirm schema before concluding from an
  empty result.**
- **`catch (\Exception)` does not catch a TypeError.** Any AJAX endpoint whose service
  declares scalar parameter types needs `\Throwable` or up-front validation.
- **Provenance routes are slug-scoped.** `/provenance/addEvent` 404s;
  `/{slug}/provenance/addEvent` works.
- **Storing a visibility flag is not enforcing it.** `is_public` existed, was written,
  was exported - and was never checked on any read path. Worth sweeping for other
  flags in the same state.

## Estate-wide findings from the install.sql audit (NOT fixed - plugins locked)

101 of 115 plugins ship their own `database/install.sql`, so per-plugin schema already
works. But:

- **13 plugins destroy existing data on re-run** via `DROP TABLE IF EXISTS x;` followed
  by `CREATE TABLE IF NOT EXISTS x` - the `IF NOT EXISTS` is decorative because the DROP
  already removed the table. Includes **ahgAuditTrailPlugin** (`ahg_audit_access`,
  i.e. POPIA/NARSSA compliance evidence), ahgAccessRequestPlugin,
  ahgSecurityClearancePlugin, ahgExtendedRightsPlugin, ahgRightsPlugin,
  ahgSpectrumPlugin (32 drops), ahgConditionPlugin, ahgDisplayPlugin, ahgGalleryPlugin,
  ahgIiifPlugin, ahg3DModelPlugin, ahgRicExplorerPlugin, ahgBackupPlugin.
- **25 plugins carry `INSERT INTO atom_plugin`**, which CLAUDE.md explicitly forbids.
  For community delivery it is worse than a style violation: self-registering marks the
  plugin *statically enabled*, and the stock admin UI deliberately hides those - so a
  self-enabling plugin makes itself unmanageable in the UI the community would use.
- The other 88 use `CREATE TABLE IF NOT EXISTS` only - safe, but it **silently skips a
  table that exists with an older schema**, so upgrades never gain new columns. MySQL
  has no `ADD COLUMN IF NOT EXISTS`; that has to go through the migration runner fixed
  in fw v2.13.46.

**Proposed rule:** `install.sql` is create-if-absent only - never DROP, never ALTER,
never touch `atom_plugin`. Every post-v1 schema change goes in `database/migrations/`.
Worth a `bin/audit-install-sql` ratchet so it cannot regress.

These matter more under one-plugin-at-a-time delivery, because that model re-runs
installs against live client databases. Every plugin deployed in this POC went onto
empty tables, where a DROP is invisible.

## Deployment recipe proven so far

Real directory (a symlink fails the prefix test at `pluginsAction.class.php:51` and is
invisible in the stock admin UI), own `install.sql`, enable via the stock `plugins`
setting, `php symfony cc` + reload php-fpm. Plugin requirements: `public static
$summary` not containing "theme"; absent from `atom_plugin`; theme-free templates
(`__()` only); integration via `response.filter_content`, never template overrides;
assets as `<link>`/`<script src>` with the CSP nonce and **never** inline `style=`;
CSRF on every form *and* every `fetch()`; a `security.yml` present, because its absence
fails open.

Dependency enforcement remains the honest gap - nothing stops a plugin being enabled
without its dependency (issue #268).
