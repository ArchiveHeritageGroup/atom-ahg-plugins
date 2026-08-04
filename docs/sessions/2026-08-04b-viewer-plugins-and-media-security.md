# Autonomous viewer plugins, IIIF POC, and three access-control fixes

**Date:** 2026-08-04
**Releases:** plugins v3.89.0 → v3.91.2, framework v2.13.58/59
**Issues:** #266, #267, #268, #269, **#270 (security)**; #264 retitled

## Why

Evaluating a second distribution model: AHG plugins installable individually into
vanilla AtoM, with no Laravel framework and no AHG theme. IIIF was chosen as the
proof-of-concept because it is already an API standard, its viewer is client-side,
and tiling is genuine remote compute.

## The POC (VM `atom210`)

Rebuilt the VM to a minimal install: base AtoM 2.10 + framework + **2 enabled
plugins** (`ahgCorePlugin`, `ahgIiifPlugin`), stock `arDominionB5Plugin` theme.

- **Sparse checkout works**: `git clone --filter=blob:none --sparse` +
  `git sparse-checkout set` → **17 MB / 2 plugins** vs 194 MB / 113. A fair stand-in
  for per-plugin artifacts.
- ✅ **`ahgIiifPlugin` runs with NO AHG theme** - 0 theme references on the page.
- ⚠️ **The framework remains mandatory**: `ahgIiifPlugin` declares only
  `ahgCorePlugin` as a dependency but makes **161 `DB::table()` calls**, so Illuminate
  (51 MB vendor) comes along regardless. This is the gap `ahgRuntimePlugin` exists to
  close, now measured on a clean box.

## Viewers as autonomous plugins

`ahgSeadragonPlugin` and `ahgMiradorPlugin`: three files each plus their own vendored
library (424 KB / 2.3 MB) and boot script. No modules, no routes, no tables.
**Installing one is how you choose a viewer.**

`RendererRegistry` gained cross-plugin discovery - any enabled plugin may ship
`lib/Renderers/*.php`; classes are found by diffing `get_declared_classes()` around the
require, so contributors use any namespace.

`ViewerInjector` places a viewer on stock description pages via
`response.filter_content` - no theme, no template override.

## ⚠️ Lessons that cost real time

- **A description page is NOT the `informationobject` module.** AtoM forwards `/{slug}`
  to the descriptive-standard module (`sfIsadPlugin` here), so a listener checking only
  for `informationobject` **silently never fires**. The MODULE_MAP in
  `ahgVersionControlPlugin/lib/Listeners/ViewLinkInjector.php` solved this first.
- **Discovery must respect ENABLEMENT, not the filesystem.** Globbing
  `plugins/*/lib/Renderers/*.php` kept using renderers from plugins the administrator
  had disabled - the directory is still on disk. My "handover works" demo passed only
  because I moved directories, which nobody does. Now reads `getPlugins()`.
- **Never inject a viewer with no boot script.** With no viewer plugin enabled the
  registry falls back to a built-in renderer that emits no `data-rendered-by`, so no
  boot script was added - a container nothing can initialise, i.e. a permanent black
  rectangle. Now injects nothing and leaves AtoM's own image display alone.
- **Mirador needs `position:relative` on its container** - it positions panels
  absolutely against the nearest positioned ancestor; without one they scatter across
  the page as dark blocks.
- **Mirador REQUIRES a IIIF image service; OpenSeadragon does not.** Without
  Cantaloupe the `/iiif/2/*` URLs return AtoM's **HTML** 404 and Mirador dies on
  `Unexpected token '<'`. Fixed by `imageServerAvailable()` (setting
  `app_iiif_image_server`: on|off|**auto**) + `buildCanvasWithoutImageService()`,
  which paints the derivative and omits the service - valid IIIF, graceful.
- **Canvas width/height are load-bearing with no image service**; `digital_object`
  dims are often empty for derivatives (declared 1000x1000 for a 480x360 file → visibly
  distorted). Now measured with `getimagesize()`.
- **Manifests are cached in `iiif_manifest_cache`** - clear it after changing
  generation or you test stale output.
- ⚠️ **The injector must NOT run on a themed install** - the theme dispatches viewers
  itself. It briefly double-injected on PSIS, duplicating a "No 3D model data
  available" placeholder on a live page. Now guarded three ways: once per request,
  never when `ahgThemeB5Plugin` is present, never into content already carrying a viewer.

## Three access-control defects

1. **#270 (critical, fixed v3.91.1)** - `/media/download/:id` and `/media/stream/:id`
   served **any** master to anonymous users, including **unpublished records**
   (verified: a draft master downloaded at 421,397 bytes). No ACL, no PREMIS, no
   allowlist, no `security.yml`. Fixed via `MediaFileServer::authorise()`.
2. **3D models broken by #258** - `.glb` files are always masters with no meaningful
   derivative, so gating masters left `<model-viewer>` hanging forever. Fixed with an
   access-controlled `/3d/file/:id` route (v3.91.0) that grants access from the
   **description** rather than `readMaster`, keeping the master gate shut.
3. **#268 / #269** - `bin/install` enables a hardcoded plugin set without checking the
   plugins exist (→ HTTP 500 on any partial install), and the framework's `install.sql`
   creates the entire estate's schema (273 tables) regardless of what is installed.

All three share one root cause: **absence of a declared security posture is invisible
in review and fails open.**

## Sweep: masters with no usable derivative

| Class | Count | Detail |
|---|---|---|
| No reference derivative at all | 45 | text/plain 10, text/csv 8, Office, AV formats |
| Reference is a poster, not a substitute | 45 | **PDF 33**, glTF 8 (fixed), x-tgif 4 |

⚠️ Derivatives link by **`parent_id`**, not a shared `object_id` (they carry
`object_id = NULL`). A first query using `object_id` reported 197 false positives.

The 33 PDFs now serve again through `MediaFileServer`, with PREMIS enforced for the
first time - base AtoM's old bypass returned true for `readMaster` on any text object
*before* both the ACL check and `checkPremis()`.

## Still open

Mirador rendering (three black blocks) pending retest after the `position:relative`
fix; if it persists the cause is a CSS collision with AtoM's Bootstrap and the honest
answer is isolating Mirador in an iframe. Class A's 45 masters and the 4 `x-tgif` files
remain inaccessible. `archeology` still carries the #270 vulnerability (non-production).
PREMIS **denial** remains unexercised - no test data restricts `readMaster`.
