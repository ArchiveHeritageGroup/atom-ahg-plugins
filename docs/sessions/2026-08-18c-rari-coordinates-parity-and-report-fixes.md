# RARI coordinate recovery, plugin parity, and the spatial report

**Date:** 18 August 2026
**Releases:** atom-ahg-plugins v3.103.22, v3.103.23, v3.103.24
**Instances:** RARI dev (192.168.0.133, rari.theahg.co.za), atom210 (192.168.0.131)

## 3,062 coordinates were in the original dump and never reached AtoM

RARI's pre-AtoM export (`fulldump-orig.xml`, 519MB RMXML) carries
`site_coordinates_latitude` and `site_coordinates_longtitude` on its 7,633 site
records. 3,062 sites have both. None of them had been migrated - the migration
carried the map sheet reference across but not the position.

Two reasons this stayed hidden, both worth noting because both produce a
confident wrong answer:

- The field is misspelt **longtitude** in the source, so a search on the correct
  spelling matches nothing.
- `grep` treats the file as binary (it is iso-8859-1), so a plain search returns
  **no matches and exit 0** rather than an error. The first pass over this file
  reported "no coordinate fields" for exactly that reason. `grep -a` is required.

### Validated rather than trusted

Most records also carry a 1:50,000 map sheet reference, which encodes its own
position, so the sheet is an independent witness to the coordinate:

| Verdict | Count |
|---|---|
| Confirmed - inside its own sheet cell | 2,515 |
| Transposed latitude/longitude, corrected | 41 |
| No sheet to check against | 416 |
| Near-miss (outside by under 0.3 deg) - held | 64 |
| Outright conflict - held | 24 |
| Ambiguous name - held | 2 |

**2,972 written; 90 held back for RARI to decide.** Nothing was guessed.
Verified against the database afterwards rather than from the task's own count:
2,973 rows carry coordinates (the extra is a pre-existing test record), 157
correctly northern-hemisphere, zero out of range, `locality_sensitive` untouched
at 1 on every one of the 7,586. Rollback snapshot at `/root/coord-rollback.csv`.

### Three ways this data is silently wrong if parsed naively

- **Decimal commas.** `25 12' 13,64''` is European notation; split on the comma
  and the fractional seconds vanish (~20m). 119 records.
- **Northern hemisphere.** 122 sites are in Libya and Ethiopia and mostly carry
  no N/S letter. Defaulting to south puts them in the Kalahari, ~5,500km out.
  The country field is the only thing that saves them.
- **No hemisphere letter at all** on 721 values.

### Matching

3,060 of 3,062 resolve to exactly one authority record on normalised name; zero
unmatched. Every target already had a site record, so this is an update, not a
create. The 2 ambiguous names both collide with duplicate authority records from
the bad import - **the duplicate merge should run first**, or coordinates attach
to a record due to be merged away.

Tooling: `siterecord:import-coordinates`, dry-run by default, plus
`bin/import-coordinates` - see "CLI tasks do not exist" below.

## Plugin parity with archaeology

RARI had 24 plugins, archaeology 66. All 43 missing plugins were copied,
schema-installed and enabled; RARI now runs 67 (the 66 plus ahgCartPlugin).

**A stale manifest was the real blocker.** ahgSecurityClearancePlugin's
`extension.json` on RARI declared 29 tables including four watermark ones owned
by ahgDAMPlugin. The installer refuses when a dependency's tables are absent, so
that false claim blocked security clearance, which blocked workflow, which
blocked authority resolution. The repo manifest was already correct at 21 tables;
syncing it cleared all three. Forcing past it would have overridden a correct
refusal driven by wrong metadata.

**Three of four dependency declarations are wrong, one was right.**
ahgAuthorityResolution / ExtendedRights / Rdm / ThemeB5 declare dependencies on
plugins archaeology does not have. For three, `--force` was correct. For
ahgAuthorityResolutionPlugin it was genuine: `ahg_mention` has a foreign key to
`ahg_ner_entity`, owned by ahgAIPlugin. Archaeology has that table without the
plugin enabled, so its schema was installed and the plugin left disabled.

### The install produced a white screen, from two causes

- **Fatal.** Several plugins build `sf_root_dir . '/atom-framework/bootstrap.php'`.
  RARI ships the framework as ahgRuntimePlugin, so the path did not exist and the
  require was fatal - a blank page, not an error page. ahgRuntimePlugin does
  provide `bootstrap.php`; a symlink at the AtoM root resolves it. The durable fix
  is for those plugins to resolve the framework relative to themselves. This is
  the constructed-path problem again.
- **Missing theme assets.** ahgThemeB5Plugin looks for bundles in the web root's
  `dist/`, but they ship in the plugin's `web/dist/`. Nothing publishes them on
  enable.

### CLI tasks do not exist on an instance with stock ProjectConfiguration

RARI's `config/ProjectConfiguration.class.php` is stock: a hardcoded ten-plugin
array that never reads the `plugins` setting. AHG plugins are enabled by the
*application* configuration, but AtoM discovers CLI tasks at *project* level - so
`php symfony siterecord:*` does not exist there, while the task file is present
and correct. Base AtoM is locked, so the plugin ships `bin/import-coordinates`,
which boots the application configuration and drives the task directly.

Two traps in doing that: `sfBaseTask` needs `setConfiguration()` or it dies on
`sfContext::createInstance(null)`, and `sfTask::log()` publishes to `command.log`
with no listener outside `sfCommandApplication` - so the task runs to completion
and prints **nothing**, which reads exactly like a task that found no data.

## The heritage landing page does not scale

| | Descriptions | Heritage page |
|---|---|---|
| archaeology | 133 | 1.2s |
| RARI | 292,278 | **112s** |

ahgHeritagePlugin redirects unauthenticated visitors from the homepage to that
page, so an instance large enough for it to be slow is exactly an instance where
every anonymous visitor is sent to it. Invisible in development, total in
production. Now gated on `heritage_homepage_redirect`, default on so existing
instances are unaffected, set off on RARI. The gate is fully qualified
(`AtomExtensions\Services\AhgSettingsService`) and guarded - it runs on
`controller.change_action` for every request, so an unresolvable class there
would be a fatal on every page.

`/reports` at 24s and browse at 6s are the same scale problem, unaddressed.

## Landing page differences against rari.wits.ac.za

ahgCorePlugin overrode AtoM's quick links component and set it to an empty array,
commented "template is fully hardcoded" - true only of ahgThemeB5Plugin, which
ships its own override *and* a template with the links baked in. On any other
theme the override still applied while no hardcoded template existed, so Home,
About, Privacy Policy, Help and General Feedback vanished. The menu rows were in
the database the whole time. It now reads them and applies base AtoM's own
`actionExistsForUrl` filter, which immediately caught a genuinely dead link.

Four menu rows were also wrong in the data: General Feedback pointed at a removed
action, Reports opened the picker rather than the dashboard, and Service Provider
and Registers were retired plugins rendering as dead links. Corrected through
`QubitMenu` rather than raw SQL - the table is a nested set and raw deletes leave
holes. Verified afterwards: 87 rows, no `lft >= rgt`, no duplicate boundaries.
Snapshot at `/root/menu-rollback.csv`.

## The spatial report

**The CSV export was never broken.** The form pre-ticked four countries as place
filters, hardcoded in the template, contradicting the field's own help text
("Leave empty for all places"). On RARI those can never be satisfied alongside a
coordinate source:

| | Descriptions |
|---|---|
| With a site record and map sheet | 126,973 |
| With a matching country place term | 5,199 |
| **Both** | **0** |

The populations do not overlap. The report ran correctly, matched nothing, and
returned a header row - which reads as "no spatial data" rather than "your
filters excluded everything". Places no longer default to selected (127,330 rows,
18MB, ~150s), and an empty result now redirects back naming the filters that
caused it.

Also fixed: neither checkbox on that form could be switched off, because an
unchecked box submits nothing and the action defaulted to true.

### Other report fixes

- `property` coordinate source selected `prop_lat.value`, which does not exist -
  values live in `property_i18n`. The report could never run in its own default
  mode.
- New `site_record` source, gated through `LocalityVisibilityService` so an export
  cannot become a way around the locality rule.
- New `map_sheet` source: a sheet is a ~25km cell, coarser than the ~11km the
  rule already permits, so it can be shown where an exact position cannot. Staff
  get the 15-minute cell, everyone else the one-degree square, malformed
  references are withheld with the reference blanked.
- Back to Reports pointed at `reportSelect` across ten templates.
- Central Dashboard was hardcoded in ahgThemeB5Plugin's menus, so on a stock theme
  the plugin routed and worked with nothing to click. It now contributes its own
  AhgNav entry (issue #292 shape).

## Mistakes made in this session

- **v3.103.23 shipped an incomplete feature.** Two of four edits silently no-oped:
  `.replace()` without asserting the pattern matched, then success printed
  unconditionally. The release defined `applyMapSheetCell` with nothing calling
  it and no join, filter or columns. Fixed in v3.103.24 with line-anchored
  insertion verified by grep. This is the same silent-failure pattern the rest of
  this work exists to remove.
- **A guard that failed towards disclosure.** The map sheet gate granted the exact
  cell whenever `LocalityVisibilityService` could not be consulted. Caught by
  testing the deployed class rather than a copy. An unasked question is not a yes.
- **A gate that would have been a site-wide fatal.** The first heritage gate
  called `\AhgSettingsService`, which does not exist. Caught before deploy.
- **Reported the CSV export as broken** before capturing the POST body. It was the
  form's defaults.

## Open

- Duplicate authority record merge (26 groups) - should precede any further import.
- 90 held coordinate rows need RARI's decision.
- `/reports` 24s, browse 6s, heritage page 112s on RARI.
- Plugins that construct `sf_root_dir . '/atom-framework/...'` should resolve
  relative to themselves.
- ahgThemeB5Plugin assets are not published on enable.
