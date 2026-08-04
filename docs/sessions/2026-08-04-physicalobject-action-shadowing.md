# physicalobject action shadowing - a partial actions.class.php hides a whole module

**Date:** 2026-08-04
**Releases:** plugins v3.88.39 (boxList guard), v3.88.40 (shadowing fix)
**Issues:** #266 (shadowing), #267 (missing UI link)

## How it surfaced

A reported 500 on `/physicalobject/boxList`: `Call to a member function getLabel()
on null`. In `ahg_error_log` since **2026-06-27** - the URL had never worked.

## Two separate defects

### 1. A route that can never supply a resource (v3.88.39)

`ahgStorageManagePlugin` registers `/physicalobject/boxList` via
`RouteLoader::any()`, which builds a plain `sfRoute`. Only `QubitResourceRoute`
populates `getRoute()->resource`, so `$this->resource` was always null and the
template fatalled. It sits alongside three siblings (`browse`, `autocomplete`,
`holdingsReportExport`) that genuinely need no resource - boxList is mis-grouped.

Fixed with the guard idiom this plugin already uses in
`PhysicalObjectIndexAction`: `getRoute()->resource ?? null` (the `??` matters -
the property is undefined on a plain `sfRoute` and PHP 8 warns) then
`forward404()`.

⚠️ **Deleting that route would have been worse, not better.** `/physicalobject/boxList`
would fall through to the catch-all `default: /:module/:action`, which has **no
`class:`** and is therefore also a plain `sfRoute` - the same fatal, but now
inside `apps/qubit/.../boxListSuccess.php`, which is locked and unfixable.

### 2. A partial actions.class.php shadowing the whole module (v3.88.40, #266)

Even the canonical URL 404'd. Three actions were dead while their siblings on the
identical route worked:

| action | before | why |
|---|---|---|
| `index`, `edit`, `delete` | 200 | defined in the theme's partial class |
| `browse` | 200 | `ahgCorePlugin` ships a per-action `browseAction.class.php` |
| `boxList`, `holdingsReportExport`, `autocomplete` | **404** | defined nowhere the lookup reached |

Two behaviours combine:

- `apps/qubit/config/qubitConfiguration.class.php:58` overrides
  `getControllerDirs()` to put **plugin dirs first, application dir last** - the
  reverse of stock symfony. The code labels itself `// HACK`.
- `sfController::controllerExists()` (`vendor/symfony/lib/controller/sfController.class.php:136-144`)
  **`return false`s immediately** when a directory holds an `actions.class.php`
  whose class lacks `execute<Action>` - it does not continue to the next directory.

⚠️ **So a partial `actions.class.php` in a plugin is an all-or-nothing claim on
the entire module.** Every action it omits is shadowed - base AtoM's included -
and 404s. Nothing errors; the actions simply vanish.

`ahgThemeB5Plugin` is loaded as a **core** plugin
(`config/ProjectConfiguration.class.php:126`), merged ahead of every database
plugin, and ships a `physicalobjectActions` with only Index/Edit/Browse/Delete.
`ahgDisplayPlugin` ships a near-identical copy behind it. Both needed fixing;
fixing one would have left the other shadowing.

**Fix:** added the three missing `execute*` methods to both classes, delegating
to the base action and copying its public vars - the pattern
`executeBrowse()`/`executeDelete()` in those same files already used.

⚠️ **A per-action-file split was tried first and rejected**: the new files would
declare `PhysicalObjectBoxListAction`, which base AtoM already declares and which
those same files `require_once` - risking a fatal redeclare.

## What was ruled out, and how

- **Routing** - reconstructed all 420 routes from
  `cache/qubit/prod/config/config_routing.yml.php` (unserialize with
  `allowed_classes => false`, then rebuild each pattern from its tokens) and
  simulated matching: all four URLs resolve to the same route, `slug/default`
  (`/:slug/:module/:action`, position 411).
- **Permissions** - every action file readable by `www-data`.
- **Module disabled** - `mod_physicalobject_enabled => true`.
- **Action-name case** - `boxlist`, `BoxList`, `boxList` behaved identically.
- **storageManage worked throughout** (`/main-vault/storageManage/boxList` = 200),
  proving the code was fine and the module resolution was not.

## Blast radius

Scanned every plugin module against base and against every other plugin:
`physicalobject` is the **only** module in the 80-plugin set with this collision.

## Verified after the fix

All seven actions resolve (login gate, base ACL applied); `nonexistentAction`
still 404s, so the module did not become permissive; no new fatals. Mirrored to
archeology - its copies were byte-identical pre-fix, md5 verified after.

## Still open (#267)

The theme's `physicalobject/indexSuccess.php` overrides base's and **dropped the
boxList link**, so the feature remains unreachable by navigation even now that it
works. Either restore the link or retire the feature and its leftovers - the
current state, where code, route and template all exist and nothing reaches them,
is the one option that is not defensible. The same question applies to
`holdingsReportExport`.
