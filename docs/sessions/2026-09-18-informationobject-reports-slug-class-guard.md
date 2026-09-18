# Information object reports - a repository slug on an information-object action

**Date:** 2026-09-18
**Release:** plugins v3.106.101
**Calls:** CH-000066 (fixed), CH-000065 (duplicate of 66), CH-000044 + CH-000045 (already fixed, v3.106.97)

## How it surfaced

Four open CallHub calls handed over as the only genuine code cluster among 38 open
calls. Two distinct bugs, each logged twice.

## CH-000065 is CH-000066, seen as a status code

`ahg_error_log.request_id` is NULL on every row, so correlation by request id was not
available. Reproducing settled it: a single GET of
`/pieterse-fonds/informationobject/reports` wrote **two** rows at the same second,
carrying exactly the two signatures behind the two calls.

| id | exception_class | signature | call |
|---|---|---|---|
| 33548 | sfException | `180918a60e40` | CH-66 |
| 33549 | NULL, status 500 | `274cb71984e6` | CH-65 |

The logger records the exception and the response status separately. Any sfException
that reaches a 500 will raise this pair, so **expect duplicate calls of this shape as
a rule, not as a one-off**.

## The bug (CH-000066)

`pieterse-fonds` is a **QubitRepository**, not an information object.

The catch-all route `slug/default` (`/:slug/:module/:action`, `QubitResourceRoute`)
resolves a slug to whatever object owns it and never checks the class against the
module. A repository or actor slug therefore reaches an information-object action.
`reportsAction` then called `containsLevelOfDescription()` on it, which exists only on
`QubitInformationObject`, and `BaseObject::__call` raised the sfException.

⚠️ **`lib/model/om/BaseObject.php:1201` is where `__call` gives up, not where the bug
is.** An undefined method on a Qubit class is normally read as upgrade residue - this
host carries `atom.upgrade-backup-20260522` and `-20260608`, which makes that reading
tempting. It was wrong here. The method is present and healthy at
`lib/model/QubitInformationObject.php:788`; the *resource* was the wrong class. KM had
nothing on either upgrade directory.

**All three incidents came from Bingbot** (`40.77.167.x`), which crawls slug x module x
action combinations the catch-all route invents. These pages are not reachable by
navigation; a crawler found them anyway.

## Where the fix had to go

`apps/qubit/config/qubitConfiguration.class.php:58` overrides `getControllerDirs()` to
put **plugin dirs first and the application dir last**, the reverse of stock symfony
(the code labels itself `// HACK`). So the file executing is
`ahgCorePlugin/modules/informationobject/actions/reportsAction.class.php`, which was
byte-identical to base. Base stayed untouched.

Fix: guard the class before anything reads the resource, and move
`getExistingReports()` below the guard because it reads the resource too.

```php
$this->resource = $this->getRoute()->resource;

if (!$this->resource instanceof QubitInformationObject) {
    $this->forward404();
}

$this->getExistingReports();
```

`instanceof` subsumes the previous `isset` check - null is not an instance of anything.

### Verified after the fix

| URL | before | after |
|---|---|---|
| `/pieterse-fonds/informationobject/reports` (repository) | 500 | 404 |
| `/pieterse-family/informationobject/reports` (actor) | 500 | 404 |
| `/engelbrecht-family-fonds/informationobject/reports` | 200 | 200 |
| `/mobrey-family-archive/informationobject/reports` | 200 | 200 |

No new `ahg_error_log` rows from any of the four.

## CH-000044 / CH-000045 - already fixed

Duplicates of each other: identical hash `e36910be`, differing only by ISO week (W37
vs W38), which is how this log deduplicates.

`getLevels()` in `informationobject/inventoryAction` returned `null` on three paths and
`execute()` passed it straight to `Elastica\Query\Terms`, whose second argument is
typed `array`. The null, not the TypeError, was the defect: **`inventory_levels` has
never existed as a row in this database.** Fixed 2026-09-14 in `a160cef0`
(v3.106.97) by always returning an array. Confirmed live - `/flower-2/informationobject/inventory`
returns 200.

Separate from the bug: the inventory feature is inert on PSIS because no levels are
configured. Admin > Settings > Inventory would enable it. Config decision, not a defect.

## Still open

Every action in the `informationobject` module reads `getRoute()->resource` with no
class guard - 22 files, of which `reports` is now the only one fixed. There is no cheap
chokepoint: 21 of 25 extend `sfAction` directly, with no shared information-object
ancestor to hook. Most need auth or POST, so `reports` may be the only anonymously
reachable one, but that has not been mapped. Map it from the routes and ACL rather than
by crawling production, which would manufacture the very error rows being cleared.
