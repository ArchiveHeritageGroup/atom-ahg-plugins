# 2026-06-10 — Exhibition Space: Heratio digital-twin parity port

## Summary
Ported the **Heratio** exhibition-space experience (Konva 2D builder + three.js 3D walkthrough) into the PSIS Symfony plugin `ahgExhibitionPlugin` for look-and-feel + functional parity. Triggered by a 404 on the exhibition space URL — root cause was a **missing `config/routing.yml`** (all slug URLs fell through to AtoM's object resolver). Built with a 3-agent multi-agent workflow; integrated, lint-clean, and verified on PSIS.

## What shipped
- **Routing** — added `config/routing.yml` mirroring Heratio's `/exhibition-space/...` URLs (browse, add, `:slug` show, edit, builder, walkthrough, plan, delete/destroy, place, save-room) + 16 builder/plan AJAX routes. Bare `/:slug` show route kept LAST.
- **Schema** — 44 columns added to `ahg_exhibition_placement` (rotation_deg, scale, label_visible, wall_or_zone, wall_u/v, model_tilt_x/z, spotlight, display_case, on_floor, view_x/y) and `ahg_exhibition_space` (room_w/d/h, building grouping, walls/doors/windows/shape/wall_colors/wall_images JSON, floor/ceiling/wall images, scan shell, furniture, stairs, sensor token, floor_level, is_outdoor) — `database/migrations/003_exhibition_heratio_parity.sql` (idempotent, INFORMATION_SCHEMA-guarded).
- **Service** — `ExhibitionSpaceService` grew from ~13 to ~40 methods: `getPlacementsForBuilder` (richer shape), placement controls (tilt/spotlight/display-case/on-floor/z-order/view/wall/size), walls/doors/windows/shape get+save, room dims, `getWalkthroughBuilding`, media helpers. Defensive INFORMATION_SCHEMA guards for not-yet-present tables/columns (readings, furniture, guided_tour_json). Old `saveLayout` renamed `saveLayout_legacy`.
- **Actions** — 16 builder/plan AJAX endpoints (JSON body in, `{ok:...}` HTTP-200 out; no 4xx status, no quoted content-type, per the AtoM response rules); `executeBuilder`/`executeWalkthrough` updated to feed the new contract vars.
- **Builder UI** — `builderSuccess.php` (1070 lines): faithful Konva floor-plan builder — drag/resize/select, room size, interior walls/doors/windows editors, and the full selected-object control panel (rotate, size, spotlight 3-state, display case, on-floor, bring-to-front/back, tilt x/z, tour view-spot, wall hang, remove), object search via tom-select.
- **Walkthrough UI** — `walkthroughSuccess.php` + `web/js/exhibition-walkthrough-3d.js` (2688-line three.js engine): first-person navigation, 3D rooms/walls/doors/windows, framed images + 3D models, spotlights, display cases, guided tour with camera sequencing.

## Verified
Cache rebuilt, site healthy; show/builder/walkthrough all 200; `builderPlacements` AJAX returns the correct richer JSON; builder renders 102 KB of Konva UI and walkthrough 255 KB of three.js scene (verified via a brief, reverted auth-bypass — the pages are login-gated). The original 404 is gone; the corrected URLs are `/exhibition-space/<slug>/builder` and `/exhibition-space/<slug>/walkthrough`.

## Deferred (phase-2, guarded no-ops)
AI docent / describe / TTS (browser speechSynthesis fallback), multi-user presence avatars, persisted wall annotations, live IoT sensor overlay. PDF-on-wall uses pdf.js from cdnjs.cloudflare.com — whitelist in `config/app.yml` script-src if wanted (degrades gracefully otherwise).

## Per-instance deploy (ANC/WDB)
Run `database/migrations/003_exhibition_heratio_parity.sql`, then cache clear + php-fpm restart. ahgExhibitionPlugin already enabled.
