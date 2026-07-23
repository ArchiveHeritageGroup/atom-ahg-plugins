# 2026-07-23 - IIIF manifest stale-image fix (ahgIiifPlugin v3.81.7 + v3.81.8)

**Instances:** archaeology (Wits) + PSIS (archive). Plugin: `ahgIiifPlugin` (stable/locked - edited at owner's request).

## Symptom

After replacing a digital-object image, the record page kept showing the **old** image, and
in incognito the image was **blank/empty**. Thumbnail list and the edit-digital-object page
showed the new image correctly - only the main record page was stale.

## Two distinct causes (this is why it was confusing)

### 1. Server-side manifest cache (the real bug) - v3.81.8

The record page renders images via the IIIF Mirador viewer, which loads a manifest from
`/iiif/manifest/<slug>`. That manifest is cached **server-side** in the `iiif_manifest_cache`
table (keyed `object_id` + `culture`, where culture is `en` or `en:v3`; 24h `expires_at`).
`modules/iiif/actions/actions.class.php:445` returns the cached `manifest_json` verbatim and
**ignores the query string**. So after an image replace the server kept serving the OLD
manifest, which pointed Cantaloupe at the now-**deleted** old file path -> Cantaloupe returned
a blank image. It only self-healed after the 24h cache expiry.

`IiifViewerService::setCachedManifest()` already stored a `cache_key` signature, but
`getCachedManifest()` **never checked it on read**, and the signature only hashed digital-object
*ids*, not checksums.

**Fix (`lib/Services/IiifViewerService.php`):**
- `getCachedManifest()` recomputes `buildCacheSignature()` and `hash_equals()` it against the
  stored `cache_key`; a mismatch is treated as a cache MISS -> the manifest regenerates against
  the current master image.
- `buildCacheSignature()` now hashes each digital object's **id + checksum** (was id only), so
  a replace-in-place (same row id, new file) also flips the signature and invalidates.
- `invalidateManifestCache($objectId)` (delete-by-object) already exists for a hard purge.

**Verified:** sf214 (object 849) manifest flipped from the old `5ae3c…/KRK-SF214.jpg` (deleted)
to the new `860dbc…/archaeological_bead_image.png`; Cantaloupe HTTP 200, image/jpeg, 104 KB.

### 2. Browser manifest cache - v3.81.7 (earlier, complementary)

The manifest URL `/iiif/manifest/<slug>` was stable, so the browser could serve a cached
manifest. `IiifViewerHelper.php` (~line 293) now appends `?v=<masterDOid>-<checksum12>`, so a
changed image changes the URL and the browser re-fetches. This is the browser-side half; v3.81.8
is the server-side half. Both are needed for an end-to-end fresh image.

> The same unversioned `/iiif/manifest/<slug>` pattern also exists in
> `DigitalObjectViewerHelper.php:69` (alternate Mirador embed). NOT patched - the record page
> uses `IiifViewerHelper`. Apply the same bust there if a stale-image report surfaces on a page
> using that helper.

## Not-a-bug: full embargo blanks the whole public detail view

Record sf238 (object 869) showed an **empty record** in incognito. Cause: an active
`rights_embargo` row (type `full`) on object 869. The full-embargo detail-view enforcement
(v3.79.149-151) intentionally blanks the **entire** detail body for non-staff (staff bypass) -
so it looked like a broken image but was the embargo working. Left in place for demo/testing.

**Lesson:** before diagnosing a "blank record/image" for anonymous users, check
`rights_embargo WHERE object_id = ? AND status = 'active'`. `EmbargoService` reads
`rights_embargo` (not the legacy `embargo` table).

## Also closed this thread
- CSRF "violation" log noise on `object/addDigitalObject`: the M12 shim logged base AtoM forms
  as token-missing. `CsrfService::isExempt()` now exempts any POST carrying base's own
  `_csrf_token` (atom-framework v2.13.43).
- "Image won't delete": was deleting a *derivative* (regenerated); the main Delete removes the
  master + derivatives. No code change.

## Releases
- `ahgIiifPlugin` v3.81.7 - browser-side manifest cache-bust (`?v=`).
- `ahgIiifPlugin` v3.81.8 - server-side content-aware manifest cache invalidation.
- `atom-framework` v2.13.43 - base-form CSRF exemption.
