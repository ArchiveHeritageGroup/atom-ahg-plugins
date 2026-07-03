# Researcher Offline — collect + notes offline, sync back (Symfony)

**Date:** 2026-07-03
**Repo:** atom-ahg-plugins · **Plugins:** ahgResearchPlugin (user-unlocked) + ahgFavoritesPlugin
**Instances:** archive/PSIS + archaeology (synced, md5 MATCH) · **Status:** UNRELEASED (pending user test)

## Requirement
A researcher takes **their own collected content + notes** offline, browses/annotates
with no connection, then syncs their own work back. Distinct from Portable Export (do
not reuse ahgPortableExportPlugin).

## Approach — extend the existing offline PWA (not a new plugin)
ahgResearchPlugin already had a PWA: `/research/mobile` + service worker `/sw.js`
(network-first + cache-fallback) + localStorage queue `heratio_offline_queue_v1` →
`/research/sync/offline` → `OfflineSyncService::applyQueue()` (synced journal+annotation
only). We extended that engine.

### Offline scope (what goes offline)
- New `collectedOfflineIds()` gathers the researcher's IO ids across **3 sources**:
  Collections (`research_collection.researcher_id` → `research_collection_item.object_id`),
  Favourites (`favorites.user_id` → `archival_description_id`), Projects
  (`research_project.owner_id` → `research_project_resource`).
- ACL-scoped: subtract `SearchAccessFilterService::getRestrictedObjectIds($userId)`
  (fail-closed) + drop unpublished (status type 158/160).
- New `executeOfflineData` → `GET /research/offline-data` JSON (records + thumbnail +
  the researcher's own notes) for the PWA to cache. Verified johan: 24 → 23 (1 unpublished).

### Offline capture + sync-back
- `mobileHomeSuccess.php` reworked into a browse+detail offcanvas + sticky **Save for
  sync** bar. Per record: **Note / Source / Suggestion / File** tabs → queued.
- `OfflineSyncService::applyQueue()` gained 4 kinds (beyond journal_entry/annotation):
  - `source` → `research_annotation` (annotation_type=source)
  - `metadata_suggestion` → NEW `research_metadata_suggestion` (curator review queue — never a live edit)
  - `file` → NEW `research_offline_attachment` (5 MB cap, written to `uploads/research-offline/<rid>/`)
  - `collection_item_note` → UPDATE `research_collection_item.notes` (ownership-checked)
- Sync-back always uses the **server-resolved** `researcher_id`, never the payload.

### Entry points
- "Take offline" button on collection view, project view, and favourites folder view;
  "Work Offline" link in the research sidebar — all → `/research/mobile`.

### DB (additive, guarded, no FK/ENUM/atom_plugin insert)
`migration_researcher_offline.sql`: `research_metadata_suggestion` + `research_offline_attachment`.
Applied to archive + archeology. `sw.js` bumped v1→v2 + precache `/research/offline-data`.

## Verification
- All PHP + template lint clean; inline JS `node --check` OK.
- Sync-back applier test: applyQueue(5 mixed kinds) → applied=5, conflicts=0; 2 annotations,
  1 suggestion, item-note updated, file written to disk with correct bytes, sync_log row;
  all test rows cleaned up + item note restored.
- Scope pipeline: 24 collected → 23 after ACL + published; 23 records resolvable.
- Endpoints `/research/mobile` + `/research/offline-data` on archive AND archaeology → 301/302
  auth (no 500). 10 files md5 MATCH on both; cc + php-fpm restart.
- Not headless-testable: the browser PWA flow (offcanvas, SW caching, offline queue) — user to test.

## Files
- ahgResearchPlugin: config (route), modules/research/actions/actions.class.php
  (collectedOfflineIds + executeOfflineData), lib/Services/OfflineSyncService.php (4 kinds),
  templates (mobileHomeSuccess, viewCollectionSuccess, viewProjectSuccess, _researchSidebar),
  database/migration_researcher_offline.sql
- ahgFavoritesPlugin: modules/favorites/templates/browseSuccess.php
- /usr/share/nginx/archive/sw.js (root; synced to archaeology)
