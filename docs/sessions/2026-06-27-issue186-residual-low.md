# #186 — residual LOW security items (3D read-by-id / bookmarks + spectrum latent routes)

**Date:** 2026-06-27 · Closes the #178 audit's residual LOW items.

## ahg3DModelPlugin 1.2.2
- New helpers `ioPublishedFor3d($ioId)` (status type 158 / status_id 160) and `modelVisibleToViewer($modelId)` (staff always; guest only when the model `is_public` AND its IO is published).
- `apiModels` — guests now get an empty list unless the backing IO is published.
- `apiHotspots` / `apiBookmarks` — return empty for a model not visible to the viewer (closes the by-id JSON leak of a draft model's hotspots/bookmarks).
- `addBookmark` / `deleteBookmark` — bookmarks are shared per-model curation (no `user_id`), so now require `editor`/`administrator` (was login-only IDOR), matching the hotspot gate.

## ahgSpectrumPlugin 1.1.12
- Added `spectrumStaffGate()` (auth + editor/admin → 403 JSON) and called it at the top of the latent JSON photo endpoints `annotationSave` / `annotationGet` / `photoDelete` / `photoSetPrimary` / `photoRotate`. These are currently unreachable (action-name/route mismatch → 404), but the gate ensures they can never become unauthenticated mutations + file unlink if a route is ever pointed at them.

## Verified
php -l clean (2 files); cache + php-fpm restart; no 500s. The #178 backlog is now fully closed (#179–#186).
