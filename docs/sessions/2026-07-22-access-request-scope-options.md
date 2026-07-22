# 2026-07-22 - Access requests: per-item / collection / repository / entire-archive scope options

**Repo:** atom-ahg-plugins. **Release:** v3.79.152. **Plugin:** ahgAccessRequestPlugin.

## Request

On `/security/request-access`, let a requester choose the *scope* of what they are
requesting access to: a specific item, a whole collection, all holdings of a repository,
or the entire archive - not just a blanket clearance-level bump.

## Starting point

The scope **backend already existed and was complete**:
- `access_request.scope_type` (single / with_children / collection / repository_all)
- `access_request_scope` (object_type, object_id, include_descendants)
- `object_access_grant` + `AccessRequestService::grantObjectAccess()` on approval
- `hasObjectAccess()` already honours direct grants, ancestor-with-descendants, and
  whole-repository grants.

The **gap was purely UI**: `/security/request-access` only offered a clearance-level
request; `/security/request-object?type=&id=` only handled a single pre-selected object.

## What was built (unified page)

`newSuccess.php` is now a single page with a scope chooser (radio), each revealing its own
panel via CSP-nonce'd vanilla JS:

| Option | object_type / include_descendants | scope_type |
|---|---|---|
| A higher clearance level | (clearance path, unchanged) | - |
| A specific item | information_object / no | single |
| A whole collection | information_object / yes | with_children |
| All holdings of a repository | repository / yes | repository_all |
| The entire archive | information_object #1 (tree root) / yes | all |

- **Entire archive** = grant on the tree root (id 1) with descendants; the root
  (lft 1, rgt max) is an ancestor of every record, so `hasObjectAccess()` returns true
  everywhere. No new access-check code needed.
- Item/collection use a **type-ahead picker**: new JSON route
  `/security/request-access/search-objects` → `AccessRequestService::searchInformationObjects()`
  (title LIKE, current culture, child count, min 2 chars, auth-gated).
- Repository scope uses a dropdown from `getRepositoriesList()`.

### Service changes (additive)
- `createObjectAccessRequest()` gained optional `$scopeTypeOverride`, `$requestTypeOverride`
  (existing callers unaffected) - used to store the "all" request as scope_type=all.
- New `searchInformationObjects()` and `getRepositoriesList()`.

### Action changes
- `executeCreate()` now branches on a `scope` param (clearance | item | collection |
  repository | all).
- New `executeSearchObjects()` JSON endpoint (uses AhgController `renderJson`).

## Latent bug fixed en route

`getObjectTitle('repository', $id)` read `repository_i18n.authorized_form_of_name` - that
column does not exist (repository_i18n is effectively empty). Repository extends **actor**,
so the name lives in `actor_i18n`. The old code silently returned null (`?? null`), so
repository scopes showed no title in My Requests / approver emails. Fixed to read
`actor_i18n` with culture fallback. The nameless phantom root repository (#6) is now
filtered out of the dropdown (12 named repos remain).

## Verification

- Routes respond; JSON endpoint returns `[]` unauth (auth-gated) and real hits authed.
- `getRepositoriesList()` = 12 named repos, phantom #6 dropped, sorted.
- Rollback-wrapped probe (net-zero writes, no emails - only object_access_grant):
  - ALL (root+desc) grants an arbitrary leaf - PASS
  - REPOSITORY (+all) grants a leaf in that repo - PASS
  - ITEM grants that item and does NOT leak to its parent - PASS
  - COLLECTION (parent+desc) grants a child - PASS

## Notes

- ahgAccessRequestPlugin is a locked plugin; the feature request necessarily targets it, so
  it was in scope (don't-over-gate rule).
- Related: [[full_embargo_detail_view_enforcement]] (embargo hides records; access requests
  are how a user asks for them back).
