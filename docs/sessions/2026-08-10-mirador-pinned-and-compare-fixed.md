# Mirador pinned to an identifiable release; IIIF comparison renders

**Date:** 2026-08-10
**Releases:** plugins v3.95.0, v3.95.1, v3.95.2
**Bundles:** ahgIiifPlugin 1.2.3, ahgMiradorPlugin 1.2.0, ahgSeadragonPlugin 1.1.3
**Issue:** [#286](https://github.com/ArchiveHeritageGroup/atom-extensions-catalog/issues/286)

## The bundled Mirador matched no published release

The issue recorded that `mirador.min.js` embeds no version string, and an earlier
fix responded by recording it by sha256 - described at the time as
"identification rather than a pin". That was not enough, and the reason matters.

Checked against every candidate release:

    ours            2,364,840 bytes
    mirador@3.3.0   2,089,341
    mirador@3.4.0   2,330,997
    mirador@3.4.2   2,336,379
    mirador@3.4.3   2,338,717

Nothing matched. The bundle was not a stock dist, so a sha256 of it identifies
the file and says nothing about its contents - which means it could not be
mapped to upstream advisories at all. A security question about the viewer was
not merely unanswered, it was unanswerable.

Replaced with upstream 3.4.3 in both places that carried it,
`ahgMiradorPlugin/web/mirador/` and `ahgIiifPlugin/web/public/mirador/`. Both now
checksum identically to `https://unpkg.com/mirador@3.4.3/dist/mirador.min.js`
(`43caefc0eb119c81...`), recorded in `extension.json` and stated in the README.

An attempt to identify the build by grepping for plugin markers
(`mirador-annotations`, `imageTools`, `textOverlay`) returned 0 in ours *and* in
stock 3.4.3, so that test proved nothing either way. File size is what settled
it.

## Comparison had three causes, not two

`/iiif/compare` had never worked. Two causes were found earlier - an output
escaper fatal truncating the response mid-script, and the CSP stripping
Mirador's runtime-injected styles. The third was mundane: `compare.html` loaded
`../mirador.min.js` while the file sits *inside* `public/mirador/`.

Verified on the clean 2.10 VM: 8 window elements, 0 console errors, sidebar,
window chrome, zoom controls and page strip all drawn.

## Settings

`requests` added to the allowlist. The other four the issue listed as unexposed -
`osdConfig`, `theme`, `galleryView`, `export` - were already there; the issue was
out of date. Confirmed a `requests` override is accepted while a non-allowlisted
key alongside it is still dropped.

## A bundle's version does not move when its contents do

Seadragon's own code did not change, but it ships `ahgIiifPlugin` as a
dependency, so its zip contents did. Rebuilding produced `ahgSeadragonPlugin-1.1.2.zip`
again - a different file under a version already published, which nobody
downloading it could detect. Bumped to 1.1.3 instead.

This is structural, not a one-off: only the dependency graph says which bundles
are affected by a change to a shared plugin, and nothing bumps them
automatically.

## Builds are not byte-reproducible

Rebuilding Seadragon after the commit produced a different zip checksum
(`939cf5b9` vs `e6d7bf18`) because zip entries carry mtimes. The published asset
is verified correct by content, but two builds of the same tree cannot be shown
to be equivalent. Worth making the builder deterministic if bundle checksums are
to mean anything to someone verifying a download.

## Verification

All three assets downloaded back from the releases page and checksummed against
the built zips:

    ahgIiifPlugin-1.2.3.zip       1ca223b267d76d31
    ahgMiradorPlugin-1.2.0.zip    66be3c59e00814b7
    ahgSeadragonPlugin-1.1.3.zip  939cf5b9a7e00b59

Three Mirador surfaces re-verified after the swap: record page viewer, standalone
viewer (12 windows, missing-manifest and cross-origin guards intact), and the
comparison workspace.

## Still open

Annotations remain unconnected to `ahgIiifPlugin`'s own annotation store. Mirador
can display annotations carried in a manifest but writes nothing back, and the
two systems know nothing about each other. Connecting them means deciding which
is authoritative and what becomes of annotations already held in either - a
design decision, not a missing call.
