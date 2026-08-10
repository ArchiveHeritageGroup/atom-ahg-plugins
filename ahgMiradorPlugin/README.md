# AHG Mirador Viewer

Mirador as an independently installable AtoM plugin. A full IIIF workspace - deep zoom, thumbnails, metadata panel and the comparison model Mirador is chosen for.

**Current version: 1.2.0. Bundled library: Mirador 3.4.3.**

## How it plugs in

The plugin ships no routes, no PHP modules and no database tables. It contributes one renderer - `lib/Renderers/MiradorRendererPlugin.php` - which `ahgIiifPlugin`'s `RendererRegistry` discovers automatically. Priority 18, so where both viewers are installed Mirador wins.

Install this viewer, [Seadragon](../ahgSeadragonPlugin), both or neither.

**Mounted in an iframe, deliberately.** Mirador is a full React and Material-UI application that expects to own its viewport and positions panels absolutely. Dropped straight into an AtoM page it collides with the Bootstrap bundle and its panels scatter as dark blocks. The iframe target is a static page in this plugin's own `web/` directory, so the plugin still ships no routes and no PHP. That is also why viewer plugins declare *how* they need to be mounted rather than being assumed interchangeable.

The renderer emits markup and `data-*` only - no inline `<script>`, no inline `style` - so it satisfies AtoM's Content Security Policy.

## What it does

**Opens a IIIF manifest** in a Mirador window, with thumbnail navigation along the bottom.

**Renders ranges as a table of contents.** Where a manifest carries `structures` - which `ahgIiifPlugin` now emits - Mirador turns them into a navigable contents list in its sidebar. That is the clearest reason to prefer it for multi-page material.

**Follows the interface language.** The viewer opens in AtoM's current culture, so a reader on an Afrikaans or isiZulu interface does not get an English viewer.

**Same-origin manifests only.** The viewer page refuses a manifest from another origin, so it cannot be used to frame arbitrary remote content.

**Sensible window defaults** - the window cannot be closed by the reader, because a closed window leaves an empty workspace with no way back except a reload. Fullscreen and maximize are on, the sidebar starts closed, the workspace control panel is hidden.

**Height** applied through the CSSOM, because a `style` attribute is dropped by the CSP without reporting anything.

## Configuration

**Admin > Settings > Image viewers** sets the defaults: whether a reader may close the window, maximize, fullscreen, zoom controls, sidebar state, thumbnail navigation position, the workspace control panel, and height.

A caller may pass options per record through `$config['options']` on the renderer. Those override the site settings and are filtered through an allowlist - `window`, `workspace`, `workspaceControlPanel`, `thumbnailNavigation`, `osdConfig`, `theme`, `galleryView`, `export` - applied on both the PHP side and in the viewer page, because that page is reachable by URL and its query string is therefore attacker-controlled.

## Requirements

    AtoM              2.9 or 2.10
    PHP               8.1 or later
    ahgIiifPlugin     carried in this bundle
    ahgCorePlugin     carried in this bundle
    ahgRuntimePlugin  2.14.1 or later, installed separately

The bundled build is pinned to the published **Mirador 3.4.3** release
(`https://unpkg.com/mirador@3.4.3/dist/mirador.min.js`, sha256 recorded in
`extension.json`), so it can be checked against upstream advisories. Until v1.2.0
the bundle matched no release between 3.0.0 and 3.4.3 and its provenance could not
be established, which left any security question about it unanswerable.

Mirador is vendored, so there is no CDN to whitelist and no build step. Mirador 3 injects its own styles from JavaScript; the `mirador.min.css` beside it is a placeholder and carries no rules.

An IIIF image server is optional and stays yours - see the [image server guide](../docs/infrastructure/iiif-image-server.md).

## Not implemented

- **Side-by-side comparison.** `compare.html` exists in `ahgIiifPlugin` and was never ported to this plugin, so comparison across manifests is not reachable from here. This is the capability Mirador is best known for and the gap worth closing first.
- **Annotations.** Mirador's annotation support is exposed nowhere, while `ahgIiifPlugin` runs its own annotation feature that knows nothing about Mirador's. Two systems, no connection.
- **`osdConfig`** - Mirador embeds OpenSeadragon and passes options through to it, so everything in the Seadragon plugin's option list is reachable this way and is not.
- **`theme`** - the viewer does not match the AtoM site around it on any instance.
- **`galleryView`, `export`, `requests`** - grid browsing, sharing a view, and headers for manifest fetches.
- **Annotations are not wired to a store.** Mirador can display annotations from a manifest, but nothing here writes them back. `ahgIiifPlugin` has an annotation store and the two are not connected, so annotations made in the viewer are not kept.

Verified on AtoM 2.10 against Cantaloupe 5.0.6: mounts on a record page, refuses a cross-origin manifest, reports a missing one, and drops a non-allowlisted override.

Licence: AGPL-3.0-or-later.
