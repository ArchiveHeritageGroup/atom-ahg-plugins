# #187 — fix the route 500s found by the Playwright smoke test

**Date:** 2026-06-27 · All 7 flagged routes now resolve (checklist FAIL count 7 → 0).

## Group A — real parameterless 500s
- **ahgSpectrumPlugin 1.1.13** — `executeInstall` rendered a non-existent `installSuccess.php`. Created the template (shows install status from `checkTablesExist()`).
- **ahgIiifPlugin 1.0.1** (stable-listed; fixed under explicit "fix everything") — `iiifCollection` actions used `redirect('@login')` (no such route → 500 for anon) and `url_for/redirect(['module'=>'iiifCollection','action'=>…])`/`@login` array forms. Replaced `@login` → `['module'=>'user','action'=>'login']` (7×), iiifCollection array redirects → named routes `@iiif_collection_view?id=` / `@iiif_collection_index` (6×), and the `newSuccess.php` create/index `url_for` → named routes.

## Group B — real bugs (the 4th was a test artifact)
- **ahgC2paPlugin 0.1.1** — `executeManifests` selected columns that don't exist (`action`/`model_id`/`model_version`/`sidecar_path`/`claim_signature`) → SQL 500 on every call. Aligned the select + JSON mapping to the real `ahg_c2pa_manifest` schema (id/digital_object_id/manifest_label/asset_hash/kid/signature_hex/created_at).
- **ahgSpectrumPlugin** — `executeConditionReport` rendered a missing `conditionReportSuccess.php`. Created the template (resource + latest `spectrum_condition_check`).
- **ahgReportBuilderPlugin 2.0.1** — `executeSharedView` called `setTemplate('shareSuccess')` (3×), which Symfony resolved to `shareSuccess`**Success**`.php` (double suffix) → missing-template 500. Fixed to `setTemplate('share')` → renders the existing `shareSuccess.php`.
- **accession `/accession/:slug`** — NOT a bug: 500 only occurred with a substituted IO slug; `/accession/<real-accession-slug>` → 200. Marked N/A in the checklist (the action could 404 more gracefully on a wrong-type slug, but normal use is fine; plugin is locked).

## Verified
php -l clean on all touched files; cache + php-fpm restart. All 7 routes re-tested authed:
- `/spectrum/install` `/manifest-collection/new` `/manifest-collection/create` `/c2pa/manifests/553` `/title-of-object/spectrum/condition-report` `/reports/shared/1` → **200**.
Checklist (`AHG_Master_Manual_Test_Checklist.{md,docx}`) updated: **PASS 1094 / FAIL 0 / N/A 442**.
