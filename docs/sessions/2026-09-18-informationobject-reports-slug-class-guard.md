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

## The sibling map - 3 open, 7 gated, 11 not exposed

Mapped statically from routes and ACL, on 2026-09-18. Production was not crawled.

### The mechanism is wider than one error message

`BaseObject::__call` routes **any** `get*`/`set*` call into `__get`/`__set` and only
throws for other names. `BaseObject::__get` (line 561) then throws
`Unknown record property "x" on "QubitRepository"`.

⚠️ **A wrong-class resource therefore produces two different messages.** A non-get/set
member gives `Call to undefined method` - CH-66's signature. A get/set member gives
`Unknown record property`. Searching the log for only the first misses most of the
family.

Discriminating columns, from `information_schema`: `lft`, `rgt`, `title`,
`scope_and_content`, `level_of_description_id`, `repository_id` and
`display_standard_id` exist **only** on the information_object tables. `parent_id`,
`source_standard` and `identifier` also exist on actor/repository - which is why
`reportsAction`'s `$this->resource->sourceStandard` on line 62 survived and
`containsLevelOfDescription()` on line 65 did not.

### Open to an anonymous crawler - fixed in v3.106.103

| action | gate | first unsafe member |
|---|---|---|
| `treeView` | none at all | `getTreeViewSiblings` / `getTreeViewChildren` |
| `generateFindingAid` | none at all | `getTitle` |
| `index` | `QubitAcl::check(..., 'read')` only | `getScopeAndContent` |

`index` is the instructive one. It **has** a gate, but `read` passes for a publicly
readable repository, so the gate checks permission and never class. That is exactly
how `reports` failed: its only gates were `isMethod('post')` and `isAuthenticated()`,
neither of which stops an anonymous GET. **An ACL check is not a class check.**

`generateFindingAid` queues `arFindingAidJob`, but `getTitle` threw first, so a crawler
got a 500 rather than a way to queue jobs.

Verified after the fix: `pieterse-fonds` (repository) and `pieterse-family` (actor)
return 404 on all three; `engelbrecht-family-fonds` returns 200 on index and treeView;
`mobrey-family-archive` returns 403 on index, which is `forwardToSecureAction()` on an
unpublished record - proof the guard passes real information objects through to ACL
rather than swallowing them. No new `ahg_error_log` rows from twelve requests.

### Gated - left unchanged

Reachable only by a signed-in user following a malformed URL: `calculateDates` (ACL
update), `delete` (ACL delete), `deleteFindingAid` (isAuthenticated), `edit` (ACL
update/translate in `earlyExecute`), `treeViewSort` (ACL update),
`updatePublicationStatus` (ACL publish in `earlyExecute`), `uploadFindingAid` (ACL
update). A guard here is defensible tidiness but protects nobody who is not already
authenticated.

### No wrong-class exposure

They touch only `id`, `parent` and members present on every Qubit class: `boxLabel`,
`fullWidthTreeView`, `fullWidthTreeViewMove`, `fullWidthTreeViewSync`, `inventory`,
`itemOrFileList`, `modifications`, `multiFileUpdate`, `physicalObjects`, `slugPreview`,
`storageLocations`.

⚠️ **Line order alone gives the wrong answer.** In `edit` and `updatePublicationStatus`
the unsafe members sit in helper methods *above* `execute()` in the file but run
*after* the ACL check in `earlyExecute()`. A first pass flagged both as open; they are
not.

## Still open

Nothing in this module. Two things reasoned rather than measured, recorded so the next
session does not mistake them for verified facts:

- `QubitAcl::check($repository, 'read')` returning true for anonymous. Confirming it
  would have meant firing the failing request at production.
- The pre-fix 500 on `index`, `treeView` and `generateFindingAid` was predicted from
  the code, not observed. Only `reports` was reproduced.

The underlying cause - a catch-all route that pairs any slug with any module - is
untouched and lives in locked base routing. Every other module in AtoM has the same
shape of exposure; only `informationobject` has been examined.
