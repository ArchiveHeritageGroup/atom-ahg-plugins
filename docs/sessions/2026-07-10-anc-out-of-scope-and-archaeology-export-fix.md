# ANC out of scope + archaeology extendedRights export fix

**Date:** 2026-07-10

## Scope decision (standing)
**ANC (`atom.theahg.co.za`, `/usr/share/nginx/atom`, DB `atom`) is now a pure Laravel/Heratio instance** and Johan fixes it himself via the Heratio dev-first flow (heratio-dev → heratio). It is **out of scope for archive/PSIS + archaeology sessions** — no active edits/config/releases on ANC from here. Diagnostic reads are fine. If asked to work on ANC (or any Heratio-Laravel instance) from an archive/archaeology session, **flag it as a likely wrong-session before proceeding**. The only Symfony AtoM instances worked on here are **archive/PSIS**, **archaeology**, and (deploy-only) **WDB**.

## Fix shipped: archaeology `/extendedRights/export/id/:id` 500 (ahgExtendedRightsPlugin)
- **Symptom:** `System Error Log` #183 — "Cannot use object of type stdClass as array" at base `QubitRoute.class.php:121`, `GET /extendedRights/export/id/498`.
- **Cause:** the export template did `url_for([$record, 'module' => 'informationobject'])`. `$record = QubitInformationObject::getById()` hydrates a plain `stdClass` (framework `QubitModelTrait`), and `QubitRoute` line 121 does `$params[0][$key]` — array access on the object → PHP `Error` (not caught; only `sfException` is).
- **Fix (no base AtoM change):** resolve the slug explicitly in the action (`slug` table lookup) and pass `url_for(['module'=>'informationobject','slug'=>$recordSlug])` — never an object into the route class. Guard the link on `!empty($recordSlug)`. Touched `exportAction.class.php` + `exportSuccess.blade.php` + `exportSuccess.php`.
- **Verified:** authed GET now 200 (was 500), "Back to record" link renders (slug `sample-excavation-archive-wits-archaeology-pilot`). Live on archaeology + archive copy; **release pending**.

## ANC `/glam/browse` ~120s — diagnosed only (Johan fixes in Heratio)
Read-only investigation; **no changes left on ANC** (MySQL slow/general log toggles reverted). Root cause: a bare `/glam/browse` auto-applies the `default_sector='archive'` setting as a type filter → `$hasFilters` true → the **live-facet path** runs (`getLiveFacet`), whose scoped `COUNT(DISTINCT io.id)` place (42s) + subject (41s) facets are ~83s of the 117s. `?topLevel=1` (skips the injection → cached-facet path) runs in 15s. Two code bugs noted for the Heratio dev flow: (1) `useDenormFacets()` reads `SettingHelper` (base `setting` table) but the toggle is written to `ahg_settings` — so denorm is silently off; (2) the ~10s main browse query is the residual after the facet path. Handed to Johan for the Heratio-side fix.
