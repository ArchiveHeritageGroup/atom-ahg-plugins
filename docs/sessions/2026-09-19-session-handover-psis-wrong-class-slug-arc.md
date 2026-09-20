# Session handover - PSIS wrong-class slug arc (archive-1f)

**Session:** archive-1f
**Dates:** 2026-09-17 evening to 2026-09-19
**Releases:** plugins v3.106.101 through v3.106.107
**Base AtoM files changed:** none. Verified at every step across `apps/`, `lib/`,
`vendor/` and every real directory under `plugins/`.

## What this was

Four CallHub calls arrived as "genuine code bugs". They turned out to be two bugs, one
of which was already fixed. Chasing the second one surfaced a whole class of defect
nobody had catalogued, and twelve more actions were guarded as a result.

## The root cause, in one paragraph

`QubitResourceRoute::bind()` (lib/routing/QubitResourceRoute.class.php) resolves a slug
with a bare `QubitSlug::SLUG` lookup joined to `QubitObject` and **no class filtering**.
The catch-all route `/:slug/:module/:action` therefore hands an arbitrary class to any
module's action. **23 classes are slug-reachable on PSIS** - including
QubitDigitalObject (1321 slugs), RicInstantiation (1280), QubitRelation (682),
QubitEvent (293) and QubitUser (40). Every incident was Bingbot (40.77.167.x) walking
combinations the route invents. The cause is untouched and lives in locked base
routing; only the symptoms were guarded.

## Shipped

| version | change |
|---|---|
| v3.106.101 | `informationobject/reports` - CH-000066 |
| v3.106.103 | `informationobject` index, treeView, generateFindingAid |
| v3.106.105 | `term/treeView`, `treeview/view` |
| v3.106.106 | informationobject inventory/fullWidthTreeView/modifications, actor/index, repository/index, right/index, museum/index, rightsholder/index |

v3.106.102, .104 and .107 are session logs. The guard is always the same three lines:
`instanceof` the class(es) the code can actually handle, else `forward404()`.

## Calls

- **CH-000044 + CH-000045** - duplicates of each other (same hash, different ISO week).
  Already fixed 14 September in v3.106.97, four days before triage reached them. Root
  cause was the null, not the TypeError: `inventory_levels` has never existed as a row
  in this database.
- **CH-000065 + CH-000066** - the same single request, proven by reproduction: one GET
  wrote both rows with both signatures.
- **CH-000091** - assigned to this session. Real pre-existing base defect.

## Open, and why

| item | why it is open |
|---|---|
| `deaccession/index` | base AtoM, and **no override path exists** - qtAccessionPlugin is in the hardcoded core list in `config/ProjectConfiguration.class.php`, ahead of every database plugin |
| `staticpage/index` | base AtoM; only copy is in `apps/qubit/` |
| `sfIsadPlugin/fileList` | off-limits by decision (Johan, 2026-09-19), including via an ahgCorePlugin override |
| `actor/index` | **broken for every actor** - no `indexSuccess.php` template exists anywhere |
| `right/index` | **broken for every QubitRights** - reads `$resource->act`; `rights` only has `basis_id` |
| cart/favorites x8 | INSERT/DELETE on anonymous GET, no ACL, no CSRF, no POST. `removeCart` inert only because its SQL is malformed |
| CH-000091 | `QubitTerm::getTreeViewChildren()` at lib/model/QubitTerm.php:801 dereferences null for any leaf term; logs while returning 200 |

## Three method lessons, each learned by being wrong

1. **`isset($resource->parent)` is not a safe probe.** `BaseObject::__isset` throws
   `Unknown record property` just as `__get` does. Nine slug-reachable classes lack
   `parent_id`. An earlier claim in this arc that eleven actions were "not exposed"
   rested on the opposite assumption and was wrong.
2. **Line order does not imply execution order.** In `edit` and
   `updatePublicationStatus` the unsafe members sit in helpers *above* `execute()` but
   run *after* `earlyExecute()`'s ACL check. `term/index` looked exposed until
   `setAndCheckResource()` turned out to be its first statement.
3. **A whole-file scan over-reports.** The eight cart/favorites actions were flagged for
   an `isset($resource->parent)` inside `earlyExecute()` - dead code, because each
   subclass overrides `execute()` without calling `parent::execute()`.

⚠️ **An ACL check is not a class check.** `informationobject/index` had
`QubitAcl::check(..., 'read')` and was still exposed, because `read` passes for any
publicly readable repository. Do not treat "has an ACL call" as "safe".

## On the call queue

Asked why calls accumulate. The honest answer: the queue is fed from `ahg_error_log`
rows, so it measures which broken URLs received traffic, not what is broken. Calls are
tombstones for events and nothing reconciles them against releases. An sfException
reaching a 500 always writes **two** rows with different signatures, and the weekly
dedup bucket splits a hash across ISO weeks, so one fault on one day can produce four
calls. Meanwhile four defects found here return HTTP 200 or affect pages nobody
browses, and will never raise a call at all. What would have to change: raise calls
from a *fault* with its own identity and current state, with log rows as occurrences of
it.

## Diagnostic noise to discount

Error-log rows **33548-33561** (18-19 September) are probes from this session, not user
traffic. CH-000091 was raised by one of them. Any call sourced from those rows should
be closed as diagnostic.

## If picking this up

The guard work is done to the agreed scope. The next genuinely useful things are not
more guards - they are `actor/index` and `right/index`, which are total failures of two
live pages, and the unauthenticated writes in the cart/favorites actions. None of the
three will ever appear in the call queue.
