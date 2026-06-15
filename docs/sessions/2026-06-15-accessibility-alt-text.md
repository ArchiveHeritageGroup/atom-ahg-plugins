# Accessibility — image alt text (WCAG 1.1.1) — 2026-06-15

**Source:** DB-audit archive plan §15 / build-order #3 (accessibility fields). **New plugin:** `ahgAccessibilityPlugin`. **Status:** built + CLI-verified live, table created, symlinked. NOT enabled (needs `atom_plugin` INSERT) and unreleased.

## Why a new plugin
`image_alt_text` is genuinely absent on PSIS (verified: PSIS has `media_transcription` with vtt/srt paths + `media_audio_description`, so captions/AD are covered — but **nothing** for image alternative text). Every natural home (theme, ahgIiifPlugin) is on the locked/stable DO-NOT-MODIFY list, so the lock-compliant path is a new focused plugin — also an extensible home for future WCAG tooling.

## Delivered
- `database/install.sql` — `image_alt_text` (soft ref to digital_object.id, no FK; unique (digital_object_id, lang)). Mirrors Heratio's schema. **Table created on PSIS** (0 rows). No `INSERT INTO atom_plugin` (plugins enabled manually).
- `lib/Service/AltTextService.php` (ns AhgAccessibility\Service) — operates on image **masters** (media_type_id=136, usage_id=140): `map/get/set` (set with empty string deletes), `counts` (coverage %), `imageList` (paginated, missing-only + q filters, IO title/slug join), `forInformationObject`.
- `accessibilityActions` (requireAuth): `index` (coverage dashboard + authoring list), `edit` (per-DO, per-culture textareas), `save`, `apiObject` (JSON alt map), `apiSlug` (JSON alt for every image master on a record). Routes via RouteLoader('accessibility') → `/accessibility/alt-text[...]`.
- `indexSuccess.php` (cards + ARIA progress bar + filter + table), `editSuccess.php` (guidance + per-language textareas).
- `web/js/alt-text.js` — CSP-safe vanilla enhancer loaded globally (config addJavaScript); resolves the record slug from the URL, calls `apiSlug`, and applies authored alt to matching `<img>` (base-name match on master→derivative) that lack a meaningful alt — **without touching the locked theme**.

## Verified
- All `php -l` clean; `node --check` JS OK.
- CLI (AltTextService against live DB): counts {total:90 images, with_alt:0}; set DO 832 → get returns it → counts with_alt:1 (1.1%); imageList total 90; forInformationObject(829) returns alt map; empty-string set deletes → counts back to 0. **Table shipped clean (0 rows).**

## Activation (hand to Johan)
```sql
-- enable the plugin (DB write — Johan runs)
INSERT INTO atom_plugin (name, class_name, version, description, category, is_enabled, is_core, is_locked, load_order)
VALUES ('ahgAccessibilityPlugin', 'ahgAccessibilityPluginConfiguration', '1.0.0',
        'WCAG accessibility tooling (image alternative text)', 'reporting', 1, 0, 0, 100)
ON DUPLICATE KEY UPDATE is_enabled=1;
```
Then: `sudo rm -rf cache/qubit/prod/* && sudo systemctl restart php8.3-fpm`. Table + symlink already done. Then smoke `/accessibility/alt-text` (expect 302 login, not 500).

## Deferred / honest scope
- The enhancer auto-applies alt only where a derivative `<img>` base-name matches its master; IIIF/Mirador canvases and multi-render layouts may not match — full per-canvas alt needs an ahgIiifPlugin manifest hook (stable plugin, needs unlock). Authoring + coverage + API are complete and IIIF-ready.
