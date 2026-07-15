# Authority-record visibility (draft / embargo) - Phase 1

**Date:** 2026-07-14
**Plugins:** ahgActorManagePlugin, ahgThemeB5Plugin, ahgAPIPlugin
**Released:** atom-ahg-plugins v3.79.90 (PSIS + archaeology)

## Why

AtoM has no publish/draft state for **authority records** (unlike archival descriptions) - a long-standing gap raised repeatedly on the AtoM Users list (e.g. Mary Allen, Jul 2026: keeping records of *living individuals* out of public view for GDPR). The only stock lever is an ACL "deny anonymous read", which hides the detail page but still leaks the name in browse, search, autocomplete and exports. This feature adds a real draft/embargo visibility state for authority records, hidden from the public but always visible to staff.

## Design

- **Storage:** new table `ahg_actor_visibility` (`actor_id` PK, `status` published|draft, `embargo_until` DATE, `reason`, `set_by_user_id`, timestamps). A row exists **only** for non-public actors; absence = published. Draft = hidden indefinitely (living persons); embargo = hidden until a date, then auto-public.
- **Central service:** `AhgActorManage\Services\ActorVisibilityService` - `getHiddenActorIds()` (request-cached), `isHiddenFromPublic()`, `isVisibleToCurrentUser()`, `getStatus()`, `setStatus()`. Fail-open (a broken table/query never hides the whole catalogue).
- **Suppression is public-only:** authenticated staff always see every record.

## Surfaces covered (Phase 1 - all AHG-owned, no base-AtoM edits)

| Surface | File | Mechanism |
|---|---|---|
| Detail page + EAC-CPF export | `ahgActorManagePlugin/config/…Configuration` | `controller.change_action` event → `sfError404Exception` for anon on a hidden actor (covers sfIsaarPlugin/actor/sfEacPlugin index) |
| Browse list | `ActorBrowseService::browse` | anon-only `must_not` ids on the actor OpenSearch query |
| Autocomplete (ES + DB fallback) | `ActorBrowseService::autocomplete` / `autocompleteFromDb` | anon-only `must_not` ids / `whereNotIn` |
| Description-page name links | `ahgThemeB5Plugin/…/_nameAccessPoints.php`, `_creatorDetail.php` | filter `$allActors` / skip hidden creators for anon |
| REST v2 `/api/v2/authorities` | `ahgAPIPlugin/lib/repository/ApiRepository.php` | `whereNotIn` on browse; `null` on read-by-slug |
| Admin write | `sfIsaarPlugin editAction` + theme `editSuccess.php` | "Public access" accordion (status / embargo / reason) saved from the request in `processForm()` |
| Staff status badge | theme `indexSuccess.php` | Draft / Embargoed badge on the detail page |

## Verified on PSIS

Service unit test (10/10 pass): draft/embargo hide from anon browse, stay visible to staff, embargo-date auto-publish, clean revert. HTTP: draft actor detail → **404** for anon (200 published), EAC export → **404**, autocomplete by exact slug → **absent** for anon draft. All PHP lint-clean. Test actor reverted; `ahg_actor_visibility` left empty.

## Phase 2 (base-adjacent - not yet done)

Vectors that are base-AtoM and need the `atom-framework/patches/` mechanism or an actor-index status field: global-search actor autocomplete (`apps/qubit/modules/search/…/autocompleteAction`), `SitemapActorSet`, bulk CSV/XML/EAC export jobs, and search-result **card** creator names rendered from IO ES docs (`ahgDisplayPlugin` adapters / `_searchResult`). To be scoped and presented for approval.

## Forum answer given (base AtoM, for the list)

Stock AtoM: only the ACL deny-anonymous-read lever exists (partial - hides detail body but not the name in lists/search); keep living-person records name-only with sensitive detail in a linked access-restricted description. Deleting records is the worst option. This plugin closes the gap properly for the AHG stack.
