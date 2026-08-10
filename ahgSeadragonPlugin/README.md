# AHG Seadragon Viewer

OpenSeadragon as an independently installable AtoM plugin. Deep zoom on a single image, with the pan, rotate and page controls a reader expects.

**Current version: 1.0.5. Bundled library: OpenSeadragon 4.1.0.**

## How it plugs in

The plugin ships no routes, no PHP modules and no database tables. It contributes one renderer - `lib/Renderers/SeadragonRenderer.php` - which `ahgIiifPlugin`'s `RendererRegistry` discovers automatically from `lib/Renderers/*.php`. Priority 15, above the built-in image renderer.

Install this viewer, [Mirador](../ahgMiradorPlugin), both or neither. Removing one leaves the rest working, and nothing in the theme names either.

The renderer emits markup and `data-*` attributes only - no inline `<script>`, no inline `style` - so it satisfies AtoM's Content Security Policy, which has no `unsafe-inline`. `web/js/boot.js` turns the container into a viewer, and is loaded by the IIIF injector from `data-rendered-by`.

## What it does

**Opens a IIIF manifest.** Walks the canvases, takes each image service id and appends `/info.json`, for Presentation 2 and 3. Multi-page material becomes a sequence.

**Falls back without an image server.** Given a direct image URL it uses OpenSeadragon's simple-image mode, so a site with no Cantaloupe still gets a working viewer, flat rather than tiled.

**Sequence navigation.** `sequenceMode` and `showReferenceStrip` follow the page count, so a multi-page document is navigable rather than opening on page one with no way forward.

**Rotation and flip controls**, which scanned material needs more often than anyone would like.

**Tile retry** - three attempts by default. A dropped tile request otherwise stays a blank square.

**Cross-origin handling** - `crossOriginPolicy: 'Anonymous'`, without which canvas operations fail as soon as tiles come from a different host to the page. That is the usual arrangement when an image server has its own hostname.

**Height** applied through the CSSOM, because a `style` attribute is dropped by the CSP without reporting anything.

## Configuration

**Admin > Settings > Image viewers** sets the defaults: navigator and its position, rotation control, flip control, cross-origin policy, zoom per click, tile retries and height.

A caller may also pass options per record through `$config['options']` on the renderer. Those override the site settings, and are filtered through a 54-key allowlist covering navigation controls, the reference strip, zoom and pan limits, mouse, touch and pen gestures, networking including `ajaxHeaders`, rendering, sequence navigation and collection display.

## Requirements

    AtoM              2.9 or 2.10
    PHP               8.1 or later
    ahgIiifPlugin     carried in this bundle
    ahgCorePlugin     carried in this bundle
    ahgRuntimePlugin  2.14.1 or later, installed separately

OpenSeadragon is vendored, so there is no CDN to whitelist and no build step.

An IIIF image server is optional and stays yours - see the [image server guide](../docs/infrastructure/iiif-image-server.md). Whichever you run, it reads files straight off disk and knows nothing about AtoM's access control; the Cantaloupe delegate that closes that ships in this bundle at `ahgIiifPlugin/config/cantaloupe/delegates.rb`.

## Not implemented

OpenSeadragon exposes 174 options. This plugin sets 19 as defaults and accepts 54. Untouched:

- Fine navigator styling - size ratio, auto-fade, colours, explicit positioning
- Blend and compositing - `blendTime`, `alwaysBlend`, `compositeOperation`, `opacity`
- Image cache tuning - `imageLoaderLimit`, `maxImageCacheCount`, `minScrollDeltaTime`
- Debug options

Collection display, touch and pen gestures, `ajaxHeaders` and the sequence controls are now configurable per record through `$config['options']`, but have no field on the settings screen - they are set in code by whoever renders the viewer.

Verified against Cantaloupe 5.0.6: the viewer opens a manifest, extracts the tile source and renders. Verified with one record and one image - a real multi-page TIFF has not been through it.

Licence: AGPL-3.0-or-later.
