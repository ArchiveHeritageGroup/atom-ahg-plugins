# Wrong-class slug guards - phase 1, and two things the sweep got wrong

**Date:** 2026-09-19
**Releases:** plugins v3.106.106 (phase 1). Earlier in this arc: v3.106.101, v3.106.103, v3.106.105.
**Calls:** CH-000044, CH-000045, CH-000065, CH-000066 closed. CH-000091 open, base defect.

## The correction that matters most

`isset($this->resource->parent)` is **not** a safe probe. `BaseObject::__isset`
(lib/model/om/BaseObject.php) throws `Unknown record property` for a class without the
column, exactly as `__get` does. It does not return false.

Confirmed live before acting on it:

```
/2025-10-10-4/informationobject/inventory    (QubitAccession) -> HTTP 500
/nrze-23m7-cshy/informationobject/inventory  (QubitRights)    -> HTTP 500
/pieterse-fonds/informationobject/inventory  (QubitRepository) -> HTTP 404  (control)
```

The control passes because `actor` has `parent_id`. **Nine slug-reachable classes do
not**: accession, deaccession, rights, event, relation, static page, physical object,
user, function object.

An earlier note in this arc claimed eleven `informationobject` actions had no
wrong-class exposure because they only touched `id` and `parent`. That was wrong, and
this is the correction.

## Phase 1 - shipped in v3.106.106

Eight actions guarded, all in ahg plugins, no base AtoM file touched:

| plugin | actions | guard |
|---|---|---|
| ahgCorePlugin | `informationobject/` inventory, fullWidthTreeView, modifications | `QubitInformationObject` |
| ahgCorePlugin | `actor/index` | `QubitActor` |
| ahgCorePlugin | `repository/index` | `QubitRepository` |
| ahgCorePlugin | `right/index` | `QubitRights` |
| ahgMuseumPlugin | `museum/index` | `QubitInformationObject` |
| ahgRightsHolderManagePlugin | `rightsholder/index` | `QubitRightsHolder` |

All eight return 404 for a wrong-class slug.

## Phase 2 - cleared, no work needed

`addCart`, `addFavorites`, `removeCart`, `removeFavorites` exist in **both**
ahgThemeB5Plugin and ahgDisplayPlugin. The sweep flagged all eight for
`isset($resource->parent)`.

⚠️ **It is dead code.** Each subclass defines its own `execute($request)` and never
calls `parent::execute()`. `AhgEditController::execute()` is the only caller of
`earlyExecute()`, and it is overridden, so `earlyExecute()` never runs. The live path
touches only `$this->resource->id`, which every Qubit class has. No exposure.

**Lesson for the next sweep: scanning a whole file over-reports, because unreachable
methods look identical to reachable ones.**

### What those eight do have

No `QubitAcl::check`, no `AclService::check`, no CSRF, no POST requirement - and they
INSERT and DELETE on anonymous GET. `removeCart`'s SQL is malformed and always errors
on an unterminated string literal:

```php
$sql = 'DELETE FROM cart WHERE id = "'.$this->resource->id.';';
```

`removeFavorites` is well-formed and does delete, scoped to the session user. Left
alone - out of scope for a class-guard release and needs its own decision.

## Phase 3 - dropped by decision

`sfIsadPlugin/fileList` is exposed and is **not to be changed** (Johan, 2026-09-19).
That includes the technically-available route of shadowing it from
`ahgCorePlugin/modules/sfIsadPlugin/` - ahgCorePlugin loads at order 1 and sfIsadPlugin
at 60, so the override would win, but it changes base-plugin behaviour by the back door
and was rejected on that basis.

## Out of scope - base AtoM

`deaccession/index` (qtAccessionPlugin) and `staticpage/index` (apps/qubit) are exposed
and stay that way. `deaccession/index` has no override path at all: qtAccessionPlugin
is in the hardcoded core plugin list in `config/ProjectConfiguration.class.php`, ahead
of every database-loaded plugin.

## Pre-existing defects found while regression-testing, none fixed, none with a call

- **`actor/index` has no `indexSuccess.php` template anywhere on the system.** It 500s
  for *every* actor. The class guard narrows who gets the 500; the page is broken for
  everyone.
- **`right/index` reads `$resource->act`; the `rights` table only has `basis_id`.**
  It 500s for *every* QubitRights. Same caveat.
- `IndexWrapper::count()` undefined - logs while returning HTTP 200.
- `QubitTerm::getTreeViewChildren()` at lib/model/QubitTerm.php:801 dereferences null
  for any leaf term. Logs, returns 200. This is CH-000091, and the traffic that raised
  it was a diagnostic probe, not a user.

⚠️ Three of these four return HTTP 200 or affect a page nobody had reported. **They
will never generate a call**, which is the concrete case for not reading the call queue
as a measure of what is broken.

## Diagnostic rows

Error-log rows 33548-33561 on 18-19 September are diagnostics from this work, not user
traffic. Any call raised from them should be closed as such.
