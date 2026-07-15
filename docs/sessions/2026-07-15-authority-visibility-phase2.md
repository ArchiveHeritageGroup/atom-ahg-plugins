# Authority-record visibility - Phase 2

**Date:** 2026-07-15
**Plugins:** ahgSearchPlugin, ahgThemeB5Plugin, ahgDisplayPlugin
**Released:** atom-ahg-plugins v3.79.91 (PSIS + archaeology)
**Builds on:** Phase 1 (v3.79.90, `ahg_actor_visibility` + `ActorVisibilityService`)

Phase 2 closes the remaining public actor-name surfaces. Confirmed up front: **no base-AtoM edits needed** - every *reachable public* surface is AHG-owned or AHG-route-overridden.

## 2a - Global-search (header) autocomplete

`/search/autocomplete` is already route-overridden to `ahgSearch/autocomplete`, backed by `AhgSearch\Services\SearchService::autocomplete()`. Added `hiddenActorIdsForPublic()` and an anon-only `must_not` `ids` clause on the `actors` sub-query of the `_msearch`. Mirrors the existing description draft-filter. **Verified at the ES layer:** actor query returns `['613','900011']` unfiltered → `['900011']` with the mustNot.

## 2b - Search-result creator names

- **`ahgThemeB5Plugin/modules/search/_searchResult.php`** (standard results list): reads raw `$doc['creators']` (carry actor ids), and for anon suppresses the whole creation line if any listed creator is a hidden actor (fail-safe). Works immediately; regression-safe (browse pages 200).
- **GLAM cards** (`ahgDisplayPlugin` `_card/_catalog/_gallery`): the display index bakes `creator` as a name string. Added `creator_id` to the display doc in `DisplayElasticsearchService::buildDisplayData` (from `event.actor_id`) and a read-time filter `DisplaySearchResultAdapter::creatorHiddenForPublic()`. **Graceful:** un-reindexed docs lack `creator_id` → filter is a no-op (no regression); fully activates after a `display:reindex` / `search:populate`. Not auto-run (heavy; creators are near-always published institutions, so this is an edge case).

## 2c - Sitemap: accepted + documented (option A)

`lib/sitemap/SitemapActorSet.class.php` (base) emits every actor slug. **But:** no sitemap is generated on PSIS (no `web/sitemap*.xml`, no route, no cron) → **no live leak**, and a draft URL would 404 anyway (Phase 1). It's a CLI **static-file** generator with no route/hook to override, so the only clean fix is a base patch - which we deliberately avoid while it's inactive. A ready-to-apply patch is staged at `stuff/authority-visibility-sitemap-patch/` (README + `SitemapActorSet.class.php.patch`); apply via `atom-framework/patches/` only if the sitemap is ever enabled.

## Deliberately no-op (no public leak)

Base `/actor/autocomplete` & `/actor/browse` (AHG routes already shadow them; Phase 1 covers). Bulk CSV/XML/EAC export jobs (`lib/job/*`, `lib/flatfile/*`) are admin-initiated - staff legitimately see drafts.

## Verified

2a ES-layer exclusion confirmed; 2b search pages render 200 for anon (no regression); clean state (`ahg_actor_visibility` empty); all files lint-clean; mirrored to archaeology. CLI test harness hit the known `arOpenSearchConfigHandler` cache quirk after `cc` - verified via ES/HTTP instead.
