# 2026-07-01 — Archaeology worker + rights/embargo/export fixes

Instances: archive/PSIS + archaeology (synced). Releases v3.79.34–v3.79.36.

## Infrastructure
- **Archaeology had NO AtoM job worker** → every Gearman job failed ("No Gearman
  worker available that can handle the job arObjectMoveJob"); the record **Move**
  action 500'd on POST, and DIP/derivative/ingest jobs never ran. Created
  `/etc/systemd/system/archeology-worker.service` (clone of archive-worker,
  WorkingDirectory=/usr/share/nginx/archeology, User=www-data) with MySQL-order
  + StartLimitIntervalSec=0 hardening; enabled+started. Ability name is keyed to
  the project's absolute path, so no cross-DB job bleed with archive-worker.

## Fixes (v3.79.34)
- **Embargo/extended-rights block on ISAD(G) view**: `extendedRightsArea` partial
  was commented out for ISAD (DC/ISDF already had it). Enabled it. Rewrote
  `_embargoStatus.{php,blade.php}` to read the canonical **rights_embargo** table
  (+ rights_embargo_i18n) instead of the dead legacy `embargo` table — that
  mismatch meant an active embargo never displayed.
- **External DO link never persisted**: `addDigitalObjectAction` URL path called
  `importFromURI()`, which synchronously downloads the remote bitstream for a
  thumbnail; slow/unreachable URLs blew past php-fpm's 90s request_terminate_timeout,
  killed the request, and rolled back the save (success flash but no digital_object
  row). Set `createDerivatives = false` so the external URI reference stores
  instantly without the blocking download.

## Robustness (v3.79.35)
- `_extendedRightsArea.php` gated on `checkPluginEnabled()` — a template-scoped
  function only defined inside `_mainMenu.php`, so the whole block could silently
  blank depending on render order. Switched to
  `in_array('ahgExtendedRightsPlugin', sfProjectConfiguration::getActive()->getPlugins())`.

## UX (v3.79.36)
- Added a **"Back to record"** button on the extended-rights export page
  (`/extendedRights/export/id/:id`) for single-object exports.

## Non-issue clarified
- `/test-2/informationobject/rename` 404 was NOT an ES problem — the slug had been
  renamed (test-2 → test-223 → test12323); DB and ES were in sync (ES holds the
  slug on the doc, updated in place on rename). Old slugs 404 by design.
