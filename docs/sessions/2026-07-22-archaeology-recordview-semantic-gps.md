# 2026-07-22 - Archaeology: record-view fixes, semantic search, GPS, advanced-search

**Instance:** archaeology.theahg.co.za (`/usr/share/nginx/archeology`, Symfony AtoM), mirrored from `/usr/share/nginx/archive` (PSIS). Code released on `atom-ahg-plugins`.
**Releases:** v3.79.135, v3.79.136, v3.79.137.

## Summary

A long working session on the Wits Archaeology demo instance covering record-view UX,
a critical authenticated-user white screen, semantic search enablement, masked GPS
site locations, custom-field advanced search, and a facet-cache outage fix. Two
regressions were introduced and fixed within the session (facet task site-down;
`isPluginActive()` fatal flood).

## Releases

### v3.79.135 - record-view + landing batch
- **Facet-cache safety:** extracted the facet SQL into `FacetCacheRefresher` + a
  standalone Illuminate-only runner (`ahgDisplayPlugin/bin/refresh-facet-cache.php`).
  The Symfony task `ahg:refresh-facet-cache` boots the full prod app from CLI and, with
  `opcache.validate_timestamps=0`, pins the web runtime to a broken compiled config
  cache -> site-wide 500. The runner boots only the DB layer and is safe for cron/seeds.
- **Provenance:** moved "Provenance & Chain of Custody" above digital-object metadata;
  repointed stale `cco/provenance` (retired museum route) links to the unified
  `/provenance/:slug` route in the context menu, gallery, and GLAM/DAM menu; hide the
  whole section when no provenance is recorded (Add stays in the left context menu).
- **ICIP panel:** restyled to match "Additional Fields" (render_b5_section_heading, no
  icon); `restriction_type` values shown human-readable (e.g. community_permission_required
  -> "Community permission required"); left sidebar block restyled to "Research Tools" look.
- **Condition:** AI Scan gated on `AiGatewayClient::isConfigured()` (hidden when the
  gateway key is absent); the "Edit" button repointed off the dead spectrum route to
  `/:slug/condition`.
- **Preservation:** "Back to record" button on the packages page; menu item relabelled
  "Digital Preservation (OAIS)" -> "Preservation Packages".
- **Version panel** hidden entirely when no versions captured.
- **Context menu:** Collections-Management + Privacy items given Font Awesome icons and
  `list-unstyled` - Bootstrap Icons (`bi-*`) have NO webfont loaded on this theme so they
  render blank; use `fas fa-*`.
- **Creator link:** `_creatorDetail.php` `link_to(...,[$item])` needed `'module'=>'actor'`
  (without it url_for resolved against the informationobject module -> /informationobject/add).
- **Voice commands:** disable now fully silences via a single `speak()` gate + immediate
  flag + cancel of in-flight speech.
- **IIIF** viewer label trimmed to just "IIIF"; **landing** copy de-scoped to archaeology
  (no rock art / fossils) + chip contrast (white text, dark fill, cache-busted CSS).
- **Spectrum -> "Collections Procedures"** settings-section label.

### v3.79.136 - white screen + sector gating + chips
- **Non-admin white screen** ("top nav then blank"): `mainMenuComponent::userCanCreate()`
  calls `QubitRepository::getById(ROOT_ID=6)`, which is **null on archeology** (the
  repository-root sentinel object id 6 is absent). `QubitAcl::check(null)` -> `get_class(null)`
  PHP 8 TypeError thrown mid-header-render -> body blanks. Admins short-circuit (hasGroup),
  anon denied earlier; only authenticated non-admins hit it. Fixed by guarding null roots.
- **Advanced-search sector buttons** gated on their sector plugins - but the first fix used
  `isPluginActive()`, which is NOT loaded in the displaySearch module context, flooding the
  log and white-screening `/glam/browse`. Corrected to
  `in_array('ahgXxxPlugin', sfProjectConfiguration::getActive()->getPlugins())`.
- Chip text forced white `!important` + heritage-landing.css cache-buster.

### v3.79.137 - custom-field advanced search + CSRF token
- **Custom-field ("Additional Fields") search** in the GLAM advanced search: searchable
  fields appended to the "Search specific field" dropdown (prefixed `cf_`); browse action
  applies a `whereExists` on `custom_field_value` per selected field, and forces
  `topLevel=0` when a custom-field filter is active (fields live on nested find records).
  Verified: `cf_lithic_type=grindstone` -> 5/5 grindstone finds, `flake` -> 0, no regression.
- **User-edit form** now carries the M12 CSRF token `_ahg_csrf_token` (the AHG CsrfService
  field name - deliberately distinct from base AtoM's `_csrf_token`). CSRF is in `log` mode
  on archeology so this was a Phase-2 tokenization, not a blocker.

## Data changes (archeology DB - not code)

- **Actor tree repair:** the demo seed created all 9 non-root actors with `parent_id=NULL`;
  `RepositoryIndexAction` treats a null-`->parent` actor as the root and forward404s -> the
  repository and every person's detail page 404'd. Fixed with
  `UPDATE actor SET parent_id=3 WHERE parent_id IS NULL AND id!=3` (actor root=3; the actor
  table here is adjacency-only, no lft/rgt). Seed scripts 01/05 patched to set parent_id=root.
- **Semantic search enabled** (`ahgSemanticSearchPlugin`). Runs without AI: saved-search is
  pure DB; vector embeddings (Ollama) are an optional dependency that degrades gracefully;
  thesaurus sync uses public web APIs (Datamuse/WordNet, Wikidata SPARQL), not the AHG AI
  gateway. Restores the saved-search endpoint (`/searchEnhancement/saveSearch`, was 404).
- **Thesaurus seeded** for archaeology: 20 general terms via Datamuse (95 web synonyms) +
  curated local synonyms for SA-specific vocab (lithic, potsherd, kraal, LSA/MSA, ostrich
  eggshell, ...) -> 38 terms / 145 synonyms in `ahg_thesaurus_term`/`ahg_thesaurus_synonym`.
- **GPS site locations:** 4 Location custom fields (`site_latitude`, `site_longitude`,
  `coordinate_datum`, `location_sensitive`; all `is_visible_public=0`) + coordinates on the
  3 demo sites. The Leaflet map panel (local Leaflet + OSM tiles, CSP-whitelisted) shows
  **exact** coordinates + pin to authenticated staff, and a **generalised** location
  (rounded ~0.1deg, circle not pin) + restriction note to guests. `location_sensitive`
  defaults ON. Verified no coordinate leakage to anonymous users - protects un-gazetted
  sites from looting.

## Ops

- **Facet-cache crons:** `/etc/cron.d/archeology-facet-cache` (:05) and
  `/etc/cron.d/archive-facet-cache` (:10) both run the standalone Illuminate runner as
  www-data. archive/PSIS was removed from the shared `ahg-facet-cache` cron (which drives
  DBs with the Heratio Laravel artisan - a schema mismatch that dumped ~558k junk rows into
  archeology's facet cache when archeology was briefly added).

## Non-obvious learnings

- `bi-*` (Bootstrap Icons) has no webfont on this theme -> renders blank; use `fas fa-*`.
- `isPluginActive()` is only loaded in some template contexts; the portable check is
  `in_array('plugin', sfProjectConfiguration::getActive()->getPlugins())`.
- A null-parent actor 404s its own detail page (RepositoryIndexAction root check).
- `QubitRepository::getById(ROOT_ID)` can be null on sparse instances -> guard before
  `QubitAcl::check`.
- The Symfony `ahg:refresh-facet-cache` task takes the site down from CLI; use the
  standalone Illuminate runner for anything automated.
- CSRF (M12) is in `log` mode on archeology - logs violations, never blocks; the AHG token
  field is `_ahg_csrf_token`, not base AtoM's `_csrf_token`.
