# 2026-07-22 - CDN vendoring: stack now fully self-hosted (no external JS/CSS)

**Repo:** atom-ahg-plugins. **Instances:** archive (PSIS) + archaeology (Symfony).
**Releases:** v3.79.141 - v3.79.144 (four batches).

## Goal / why

Natasha (clean-install) hit CSP-blocked CDN scripts and asked how CDNs + inline event
handlers are meant to be handled. The proper answer: **host the JS locally and serve from
`'self'`**, rather than keep whitelisting CDNs. Leaflet/three/etc. were already partly
vendored but templates still pulled from CDN, so the fix was to finish vendoring and repoint.

## Outcome

**All four external CDN hosts removed from BOTH the code and the CSP:**
`unpkg.com`, `d3js.org`, `cdnjs.cloudflare.com`, `cdn.jsdelivr.net`. ~25 libraries vendored,
~60 template `<script>`/`<link>` references repointed to `'self'`. No external JS/CSS at
runtime; the CSP `script-src`/`style-src`/`img-src`/`connect-src`/`font-src` no longer list
any CDN host.

## Batches

- **v3.79.141 (batch 1)** - Leaflet (4 map templates -> theme local) + RiC Explorer graph
  deps (cytoscape 3.28.1, three r128, three-spritetext 1.8.2, 3d-force-graph 1.73.3, exact
  versions downloaded into ahgRicExplorerPlugin/web/js/). **Cleared `unpkg.com`.**
- **v3.79.142 (batch 2)** - d3 v7.9.0 (7 refs incl. the site-wide `_layout_end.php` load) and
  **bootstrap-icons 1.11.0 loaded GLOBALLY** in `_layout_start.php` (fixes blank `bi-*` icons
  everywhere - they were CDN-only before). **Cleared `d3js.org`.**
- **v3.79.143 (batch 3)** - chart.js (consolidated 4/4.4.0/4.4.1 -> 4.4.1, incl. bare
  unpinned refs), tom-select, konva, quill, bootstrap (5.3.0/5.3.3 -> 5.3.3), vis-timeline,
  choices.js, mermaid. 23 templates + 13 libs.
- **v3.79.144 (batch 4, finale)** - pdf.js (repointed to the exact-version copy already in
  ahgIiifPlugin), font-awesome (css into web/dist/css/ reusing the theme's existing webfonts),
  swagger-ui, graphiql + react/react-dom, and the **three.js ecosystem**: three r137 build +
  8 loader/control addons (exhibition walkthrough) and three r160 module (imageAr viewer),
  vendored at matching versions under `web/vendor/npm/three@<rev>/` (path structure preserved
  so the repoint was a clean host-prefix swap). **Cleared `cdnjs` + `jsdelivr`.**

## Where libs live

Shared libs in `ahgThemeB5Plugin/web/{js,css}/`; three ecosystems kept per-plugin at matching
revisions (`ahgRicExplorerPlugin` r128, `ahgExhibitionPlugin` r137, `ahgImageArPlugin` r160,
`ahg3DModelPlugin`/`ahgIiifPlugin` r128). bootstrap-icons + font-awesome webfonts resolve via
the css's relative `../webfonts/` / `fonts/` paths - placement matters.

## Non-obvious learnings / gotchas

- **CSP is Report-Only** on these instances (`Content-Security-Policy-Report-Only`), so CDN
  removal is safe hygiene - nothing is blocked either way; the point is a clean allowlist +
  no runtime external dependency for an enforcing deployment.
- **`bi-*` had no webfont loaded** except on the one CDN page - vendoring bootstrap-icons +
  loading it globally fixes it site-wide (supersedes the earlier fa-* workarounds).
- **Font Awesome** loads via the theme's webpack bundle (webfonts in `web/dist/webfonts/`);
  only 2 standalone pages used the cdnjs FA - put the vendored `all.min.css` in `web/dist/css/`
  so its `../webfonts/` resolves to the existing fonts.
- **`grep -ho ... | grep -v blade` is a trap** - `-h` strips filenames, so the blade filter
  matches nothing; use `grep -rl ... | grep -v '\.blade\.'` to exclude Heratio templates.
- **Three revisions must match their addons** - r137 addons need r137 build; never mix.
- **VRButton (WebXR)** at `three@0.137.5/examples/js/webxr/VRButton.js` is **404 on the CDN
  itself** (only exists under `jsm/`); the walkthrough's VR button was already broken - not a
  regression.
- **`config/app.yml` (CSP) is per-server, NOT git** - CSP edits applied directly on archeology
  + archive; a config-cache clear + fpm reload is required for app.yml changes to take effect.

## Follow-ups

- Visual spot-check the two 3D pages (exhibition walkthrough, imageAr viewer) - JS-rendered,
  not curl-verifiable.
- `.blade.php` templates still reference CDNs - those serve the Heratio/Laravel instances
  (out of Symfony scope); a parallel pass finishes it fleet-wide.
- Clean installs (e.g. Natasha's) no longer need the CDN hosts whitelisted at all.
