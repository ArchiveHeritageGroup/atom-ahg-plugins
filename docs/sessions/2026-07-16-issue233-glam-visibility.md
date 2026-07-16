# #233 - Apply authority-record visibility to the live GLAM display

**Date:** 2026-07-16
**Plugin:** ahgDisplayPlugin
**Issue:** ArchiveHeritageGroup/atom-extensions-catalog#233
**Released:** atom-ahg-plugins v3.79.93 (PSIS + archaeology)
**Builds on:** authority visibility Phase 1/2 (v3.79.90/91) + #232 (v3.79.92)

## Key discovery

The **live** `/glam/browse` is the `display` module (`modules/display`, DB-driven via `DynamicFacetService`), **not** the `displaySearch`/ES module #232 fixed. So the actual anon leaks of a draft creator's name were the GLAM **creator facet** and the **repository dropdown** - both DB-driven and keyed on `actor_id`. The live browse cards carry no per-card creator name, so there was no card surface to filter there.

## Fixes (all ahgDisplayPlugin, guest-only; staff unaffected)

- **Creator facet + repository facet (uncached path):** `DynamicFacetService::getCreatorCounts()` / `getRepositoryCounts()` now `whereNotIn` the hidden actor ids for guests, via new `hiddenActorIdsForGuest()` (uses `ActorVisibilityService::getHiddenActorIds()`; empty for staff or when the plugin is absent). The service already received `isAuthenticated`.
- **Creator facet + repository facet (cached path):** the landing page reads a precomputed `display_facet_cache` (guest key `creator`/`repository`, staff key `*_all`), refreshed by `ahg:refresh-facet-cache` with raw SQL. Added `hiddenActorClause()` (table-existence guarded) to the **guest** variants of `refreshCreatorFacet`/`refreshRepositoryFacet`. Applies on the next cache refresh (cron).
- **Repository filter dropdown:** `displaySearch/templates/_glamAdvancedSearch.php` (included cross-module by the live browse) `whereNotIn` hidden repo ids for guests.

## Verified (PSIS)

Uncached path (`/glam/browse?type=museum`, actor 982 both creator+repository): `North West Univirsity` text published=2 (facet + dropdown) → draft=0; `?creator=982` facet link published=1 → draft=0. Cached path: `ahg:refresh-facet-cache` entry count 788 (982 published) → 786 (982 draft) = its creator+repository entries dropped. Clean state after (`ahg_actor_visibility` empty). Lint clean. Mirrored to archaeology.

## ⚠️ Operational hazard hit (recovered)

Running `sudo -u www-data php symfony ahg:refresh-facet-cache` ad-hoc during testing **corrupted the prod config cache** (`arOpenSearchConfigHandler not found`, sfConfigCache) and 500'd the site instance-wide. Recovery: `rm -rf cache/qubit/prod/* && systemctl restart php8.3-fpm`. This is the known CLI-pollutes-prod-cache hazard, not caused by the code. Lesson: avoid ad-hoc `php symfony` runs on prod; if unavoidable, be ready to clear the prod cache + restart fpm. Cron-driven refresh (forked, not under php-fpm) is the sanctioned path.

## Status

Authority-record visibility feature now COMPLETE across all reachable public surfaces (Phase 1 + 2a + 2b-list + #232 subsystem + #233 GLAM facets/dropdown). See [[authority_record_visibility]].
