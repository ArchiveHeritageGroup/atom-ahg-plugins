# Rights and embargo on a stock AtoM: a baked-in dev path and three open modules

**Date:** 2026-08-11
**Instance:** clean AtoM 2.10 (131), which runs the generated `ahgRuntimePlugin`
**Follows:** the migration-runner work in
`2026-08-11-collections-procedures-evidence-and-outcomes.md`

## What was asked

Install rights and embargo on the clean instance, the way Spectrum and Heritage
Accounting were.

## What was actually wrong

**They had never been installed.** All 39 `rights_*`, `embargo*`,
`extended_rights*`, `tk_label*` and `creative_commons*` tables were absent - not
drifted, absent. A different fault from the heritage one despite the identical
symptom shape, and worth separating: heritage had a table one design behind the
code, this had no table at all.

**A development path was baked into shipped code.** With the plugins deployed and
enabled, `/ahg/rights/embargo` returned 500:

    Cannot declare class ahgExtendedRightsPlugin\Services\EmbargoService,
    because the name is already in use in
    /usr/share/nginx/atom/atom-ahg-plugins/ahgExtendedRightsPlugin/lib/Services/EmbargoService.php

Thirteen `require_once` statements across ten files hardcoded
`sf_root_dir . '/atom-ahg-plugins/ahgExtendedRightsPlugin/lib/...'`. On PSIS that
path *is* where the plugin lives, so it worked. Anywhere the plugin runs from
`plugins/` - which is every packaged install - the configuration had already
loaded the class from `plugins/`, and the action then loaded the same class from
a second absolute path. `require_once` de-duplicates by resolved path, not by
class, so both files declared it and PHP fatalled.

Now resolved relative to the file: `dirname(__FILE__) . '/../../../lib/...'` in
the actions, `dirname(__DIR__, 2)` in the two commands. Both layouts resolve to
one real path, so `require_once` de-duplicates as intended.

**Three modules had no `security.yml`.** `extendedRights`, `rights` and
`rightsAdmin` inherited `apps/qubit/config/security.yml`, whose
`default: is_secure: false` makes every action public. Verified anonymously with
no session: `/admin/rights` returned 200 and rendered "Extended Rights
Management" with its batch and edit controls - **on PSIS as well as on the clean
instance**. Added, using the double-bracket OR form so a single-item list cannot
403 administrators: staff modules `[[editor, administrator]]`, `rightsAdmin` and
the batch action `[[administrator]]`.

## Verified

    tables created                      39, no errors
    migrations                          49 applied, 0 failed; third run a no-op
    /ahg/rights/embargo                 500 -> 200
    /admin/rights anonymous             rendered admin page -> login page
    /admin/rights/batch anonymous       login page
    home + heritage after enabling      200, no regression

## Then the home page broke

Enabling the plugins put their routes at the front of the collection, and every
link in "Popular this week" started generating as

    /ahg/rights/embargo/edit/?0%5BdisableNestedSetUpdating%5D=0&0%5BindexOnSave%5D=1

Two independent faults, and it took both to produce that.

**One: a prepended variable-free route matches any unnamed url_for().**
RouteLoader prepends, which it must - a plugin path has to be matched before
AtoM's catch-all `/:slug`. But `sfRoute::matchesParameters()` merges the route's
own defaults over the supplied parameters before comparing them, so a caller that
omits module and action has both filled in from the route being tested, where
they trivially equal themselves. A route with no variables has nothing else to
fail on and extra parameters are allowed as a query string, so it matches
essentially anything. The four routes ahead of it were spared only because they
carry an `:id`.

Nothing about this was specific to embargo. Whichever variable-free plugin route
sits at position 0 captures the site's links, so the failure moves as the enabled
set changes - which is exactly what happened when the fix was applied in stages
and the links jumped to `physicalobject/holdingsReportExport`.

Fixed with `ExplicitRoute` / `ExplicitRequestRoute` (framework): plugin routes
decline to match parameter-based generation unless the caller named the module
and action. Request matching goes through `matchesUrl` and is untouched;
generation by route name short-circuits before this and is untouched; a
deliberate `url_for(['module' => 'embargo', ...])` still works. RouteLoader
requires the classes from its own directory rather than relying on the
`require_once` in `config/ProjectConfiguration.class.php`, which names
`atom-framework/src/Routing` explicitly and therefore misses on any instance
running the packaged `ahgRuntimePlugin`.

**Two: the caller named no module.** `ahgCorePlugin`'s `_popular.php` called
`url_for([$object])`. `ahgThemeB5Plugin`'s copy of the same widget has always
passed `'module' => 'informationobject'` / `'repository'` / `'actor'` per type -
so the correct pattern was already in the codebase, in the template that was not
running on this instance. Now matched, with Repository tested before Actor since
it extends it, and unrecognised types skipped rather than linked somewhere
arbitrary.

Verified: the eight bad links are gone, the list renders proper slugs, and
`/survey-plan-farm-rietfontein-no-412` returns 200 as
"Item AHG-DEMO-1 - Survey plan, Farm Rietfontein No. 412". Twelve screens swept,
all 200, nothing logged.

## Left open, deliberately

`modules/embargo/config/security.yml` declares `all: is_secure: false`. That is an
explicit decision rather than an omission, so it was left alone - but a public
embargo index does disclose which records are restricted and until when, and it
deserves a second look.

The clipboard's "Clear all selections" item still renders as
`/physicalobject/holdingsReportExport`. Its `menu` row stores `path = '#'` - a
JS-driven control with no real target - and something between the menu row and
the anchor hands that to URL generation. `MenuService::resolvePath()` is not the
culprit; it maps `#` to `/#`. Small, cosmetic, and a different caller, so it was
left rather than chased.

`ahgWorkflowPlugin` is not in the enabled list yet its migrations ran: migrations
are discovered from disk, independent of enablement. Defensible - schema should be
ready before a plugin is switched on - but it does mean the runner touches
plugins the site is not using.

## Lesson

**A path that happens to be true on the development machine is not a path.** The
plugin worked on PSIS for as long as it has existed, because PSIS is the machine
whose layout was written into the code. The failure needs a *different* layout to
appear, which is exactly what a clean instance is for. Same family as the earlier
finding where a developer environment was baked in as a fallback.
