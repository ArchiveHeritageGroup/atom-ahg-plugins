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

## Follow-up fixes (v3.79.154)

Two bugs surfaced on first live use of the new scopes:

1. **Object-scope requests failed** ("Failed to create request. You may already have a
   pending request."). Root cause: `access_request.requested_classification_id` was
   `NOT NULL`, but item/collection/repository/all requests carry no classification, so the
   insert violated the constraint and `createRequest()` returned null - falling through to
   the generic error. Fix: `ALTER TABLE access_request MODIFY requested_classification_id
   INT UNSIGNED NULL` on both DBs (archive + archeology); FK left intact; `install.sql`
   updated for fresh installs. Rollback probe confirms an object/all request now inserts
   cleanly. Clearance requests still set the column.

2. **Double flash notification.** Every theme layout renders flashes globally via
   `get_partial('alerts')` (ahgThemeB5Plugin `_alerts.php`), but three accessRequest
   templates (myRequests, new, requestObject) also rendered their own flash blocks - so
   every message showed twice. Removed the per-template blocks.

## Follow-up fix 2 (v3.79.155) - white screen on Approve

Approving an object-scope request white-screened. Real error (php-fpm log):
`grantObjectAccess(): Argument #4 ($includeDescendants) must be of type bool, int given,
called ... AccessRequestService.php:337`. The file declares `strict_types=1`, and
`approveRequest()` passed `$scope->include_descendants` (int 0/1 from the DB) straight into
the `bool` parameter → **TypeError**. Latent forever, but only reachable now that object
requests can be created (previous NOT NULL fix). The TypeError is an `\Error`, not caught by
the method's `catch (\Exception)`, so it propagated as an uncaught fatal (white screen);
the transaction was undone by MySQL's connection-close rollback, so request #8 stayed
`pending` (no half-approve, no stray grant).

Fix: cast the DB values at the call site - `(int) user_id`, `(string) object_type`,
`(int) object_id`, `(bool) include_descendants`. Also broadened the `createRequest()` and
`approveRequest()` transaction catches from `\Exception` to `\Throwable` so any future
`\Error` rolls back cleanly and returns a graceful failure instead of a fatal. Verified with
a rollback probe that the grant loop now accepts the int include_descendants.

**Lesson:** in a `strict_types=1` file, always cast DB row values (which come back as
strings/ints) to the callee's declared scalar types, and catch `\Throwable` (not just
`\Exception`) around DB transactions so TypeErrors don't escape as fatals.

## Follow-up fix 3 (v3.79.156) - requester cannot view/cancel own request

A requester was denied viewing their own submitted request ("You are not authorized to view
this request"). `executeView` used `$this->accessRequest->user_id === $userId` and
`cancelRequest` used `$request->user_id !== $userId`. In a live web session
`getAttribute('user_id')` is a **string**, but the DB `user_id` is an **int**, so the strict
comparison failed the owner check and redirected them away. The my-requests *list* kept
working (masking it) because `getUserRequests()` filters via a SQL `WHERE` (loose match).

Fix: compare as ints on both sides in `executeView` and `cancelRequest`. The owner is not an
approver, so `canApprove` stays false and they get a proper read-only view (no approve/deny
buttons).

**Lesson:** never strict-compare a session attribute (string) against a DB column value
(int) - cast both to int. A working SQL-based list filter can hide a broken PHP `===` owner
check on the detail page.

## Follow-up fix 4 (v3.79.158) - approver scope visibility + Afrikaans email header

1. **Approvers couldn't see the request scope.** The pending list's "Current -> Requested"
   column only rendered classification badges (null for object requests), so object/
   collection/repository/all requests showed nothing. `getPendingRequests()` already loaded
   `$req->scopes`; the template just didn't render them. Fixed: pending list now shows a
   scope label (Specific item / Collection / All holdings of repository / Entire archive)
   plus the target title; the view page shows a clear scope summary with a dedicated
   "entire archive" callout instead of dumping the raw tree root (object_id 1).

2. **Notification email sender name was Afrikaans.** `sendEmail()` selected `siteTitle`
   with no culture filter and grabbed the `af` row ("Wits Argeologiese Versameling").
   `siteTitle` exists per-culture (af + en). Fixed to select the site's default culture
   (`sf_default_culture`, effectively `en`) -> "Wits Archaeological Collection", falling
   back to any culture then "Archive".

**Lesson:** any `setting_i18n` lookup (siteTitle etc.) MUST filter by culture - an unfiltered
`->value()` returns an arbitrary culture row and can surface the wrong language.
