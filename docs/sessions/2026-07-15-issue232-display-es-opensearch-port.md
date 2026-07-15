# #232 - ahgDisplayPlugin display-ES subsystem: port to OpenSearch/curl

**Date:** 2026-07-15
**Plugin:** ahgDisplayPlugin
**Issue:** ArchiveHeritageGroup/atom-extensions-catalog#232
**Released:** atom-ahg-plugins v3.79.92 (PSIS + archaeology)

## Problem

The display-ES field integration was **non-functional** on this OpenSearch stack: every ES-touching method used the ES7 PHP client (`\Elasticsearch\ClientBuilder`, not installed), the service targeted a single `atom` index (this deployment is multi-index `{prefix}_qubitinformationobject`), the live IO mapping had no `display` object under a `dynamic:strict` root, and the indexing data-assembly called `DisplayService` methods that no longer exist. Net: 0 docs carried display fields; `/glam/browse` rendered nothing; the authority-visibility card filter (v3.79.91) was inert.

## Fix (all in ahgDisplayPlugin, no base edits)

- **Transport → curl/OpenSearch.** Added `esRequest()`/`esBulk()`/`updateDocument()` (mirroring `ActorBrowseService`); ported all 6 ES7-client call sites: `updateMapping` (PUT `_mapping`), `hasDisplayMapping` (GET `_mapping`), `reindexDisplayData` (`_bulk` ndjson), `search`, `autocomplete`, and listener `onObjectTypeChange` (`_update`). ES7 client dependency removed.
- **Index topology.** Constructor now targets `{app_opensearch_index_name | db}_qubitinformationobject` with `app_opensearch_host/port`, matching the rest of the stack.
- **Mapping.** `displayMapping.php` gains a top-level `autocomplete` field (the copy_to target the strict root requires) + `display.creator_id` (`long`). Applied live via curl PUT.
- **Data-assembly bugs (pre-existing).** `getIndexData` used `DisplayService::getObjectType/getProfile` (gone) → `\DisplayTypeDetector::detect()/getProfile()`; `buildDisplayData` used `getAncestors` (gone) → new DB-based `getAncestorsData()` (parent_id walk, root-first, loop-capped).
- **creator_id passthrough.** `formatSearchResults` now emits `creator_id` so the adapter's card filter has the field.

## Verified (PSIS)

Mapping applied; `display:reindex` (now curl) processed **738 docs**. `exists:display` = 707, `exists:display_object_type` = 707, `display.creator_id` on 22. Base catalogue untouched (707 docs unchanged, `/informationobject/browse` 200 - display fields are additive partial-updates). `/glam/browse` renders real display data. Archaeology: mapping + 11 docs. Lint clean.

## Follow-up (filed separately)

Now that GLAM display is live, a draft creator's name still appears in two GLAM surfaces (creator **facet** - agg on `display.creator_keyword`; creator **filter dropdown** - from a DB list), and the card-level creator suppression (2b) is wired but not yet demonstrated end-to-end (card layouts don't surface creator in the tested spot). Tracked as a focused "apply authority visibility to the live GLAM display" follow-up. A draft creator is usually an institution, so this is lower-risk. See [[authority_record_visibility]].

## Durability note

Base arOpenSearch full rebuilds (`search:populate`) recreate the IO index from the base template (no `display` object), so the display mapping/data must be reapplied after such a rebuild - the listener re-adds per-IO on save; a `display:reindex --update-mapping` restores it in bulk. Pre-existing design constraint, not introduced here.
