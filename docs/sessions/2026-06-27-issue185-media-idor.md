# #185 — Class-2 by-id IDOR / draft leak: 3d-model / spectrum / marketplace

**Date:** 2026-06-27 · From the #178 audit. Closes the last #178 gap.

## ahg3DModelPlugin 1.2.1
- `executeAddHotspot` / `executeDeleteHotspot` — were login-only (any authed user
  could add/delete hotspots on any model by id). Added `hasCredential(['editor',
  'administrator'])` → 403 after the existing 401 auth check.

## ahgSpectrumPlugin 1.1.11
- `executeConditionPhotos` — had NO auth (GET created a `spectrum_condition_check`,
  POST uploaded a `spectrum_condition_photo`). Gated to staff (auth + editor/admin)
  → `forward('admin','secure')`.
- `spectrumApi` `eventApi` POST `createEvent` — was login-only; added editor/admin
  credential (403 otherwise).

## ahgMarketplacePlugin 1.0.1
- Public listing detail (`marketplaceListingAction`) — `getBySlug` had no status
  filter, so an anon could view draft/pending/withdrawn/suspended/expired listings
  by guessing the deterministic slug. Now non-`active` listings are visible only to
  the owning seller (`getSellerByUserId`) or an admin; else `forward404`.
- `sellerListingImagesAction` — the parent-listing ownership was checked but the
  posted `image_id` was passed straight to `setPrimaryImage`/`deleteListingImage`
  with no listing-membership check (cross-seller image IDOR). Now the image_id must
  be in `getListingImages($listingId)` before acting.

## Not fixed (lowest severity — noted)
- `ahg3DModelPlugin` `apiHotspots`/`apiBookmarks`/`apiModels` read JSON by guessable
  `model_id` (hotspot coordinates for the public viewer; low) and `addBookmark`/
  `deleteBookmark` login-only (user viewer state; low).
- `ahgSpectrumPlugin` latent photo routes (`annotationSave`/`photoDelete`/…) — route
  names don't match the `execute*` methods, so currently 404/unreachable.

## Verified
php -l clean (5 files); cache + php-fpm restart; `/marketplace/browse` → 200, no 500s.
Versions: ahg3DModelPlugin 1.2.1, ahgSpectrumPlugin 1.1.11, ahgMarketplacePlugin 1.0.1.

**#178 backlog now fully discharged** (#179–#185 all resolved).
