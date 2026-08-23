# Getting the IIIF viewer working under a real CSP, and the 403 that looks like permissions

**Date:** 2026-08-23
**Instances:** archaeology (enforcing CSP), PSIS (report-only)
**Plugin:** ahgIiifPlugin (locked; unlocked explicitly for this work)

Reported as "the IIIF viewer shows nothing" and "3 black blocks". It was **five** separate
faults. Only the first was visible, and fixing any one alone left it still broken.

**Revision, same day:** an earlier version of this note listed four faults and presented the
Mirador workspace change as a fix. That was wrong on both counts. Fault 5 below is what
actually produced the black body, and the workspace change was a regression I introduced.
Both are corrected here.

## The instance difference that hid it

**archaeology serves `Content-Security-Policy`. PSIS serves
`Content-Security-Policy-Report-Only`.** PSIS had every one of these faults all along,
reported and never enforced, so its viewer worked by configuration luck rather than
correctness. Anyone comparing the two would conclude the code differs. It does not.

## 1. Inline style ATTRIBUTES

The viewer containers carried `style="width:100%;height:500px;background:..."`. **A nonce
covers `<style>` and `<script>` ELEMENTS and never `style=""` ATTRIBUTES.** The containers
got no dimensions, collapsed to zero height, and rendered nothing - with no console error
pointing at the cause.

Static geometry moved to `web/css/viewer-switch.css`. Values only known at runtime (the
configured height, the background colour) are emitted per viewer id in a `<style>` element
carrying the nonce, because a class cannot express "whatever height the administrator set".

Three render paths were affected, not one: `IiifViewerHelper`, `MiradorRenderer`,
`DigitalObjectViewerHelper`.

**A trap for the next person:** adding `'unsafe-inline'` to `style-src` does nothing while a
nonce is present. Per CSP Level 3 a directive containing a nonce ignores `'unsafe-inline'`
entirely, so that "fix" changes nothing and invites the conclusion that CSP is not the cause.

## 2. A `<style>` element with no nonce at all

`IiifViewerHelper` hardcoded `'<style>'` around line 690, carrying `.viewer-area { position:
relative }` and the thumbnail rules. Blocked outright. Fixing only the attributes would have
left the viewer broken and made the diagnosis look wrong.

## 3. Mirador styles itself at runtime - and JSS wants a meta tag

Mirador 3 styles itself through Material-UI's JSS, which builds `<style>` elements from
JavaScript after load. Those are subject to `style-src` like any other style element, so
every one was dropped. Tiles loaded, layout did not exist. That is the black blocks.

This cannot be fixed with a stylesheet, because the rules do not exist until runtime. JSS
reads **`<meta property="csp-nonce">`** and stamps that value onto every sheet it creates.
The selector is JSS's, not ours - it must be exactly that. Emitting the tag with the
request's nonce fixed it.

**Ruled out along the way**, so nobody repeats it: the manifest resolves (IIIF Presentation
3); `info.json`, the full image and a 256px tile all return 200 with valid JPEG; CORS
returns `Access-Control-Allow-Origin: *` on all three, which mattered because Mirador's
internal OSD runs with `crossOriginPolicy: 'Anonymous'` while the plugin's own OSD does not.
The 47-byte `mirador.min.css` is **correct**, not a broken asset - Mirador 3 bundles its
styles in JS and the file says so.

## 4. Sizing, once it was styled

The frame was a flat 700px, taller than a phone viewport, so even a correct render sat mostly
below the fold. Now `clamp(320px, 70vh, 700px)`. `workspaceControlPanel` is disabled: it is
for juggling several windows and with one manifest only costs width.

### A regression I introduced here, and the lesson

I also set `workspace.type: 'single'`, having grepped the bundle and found `"single"` 27
times. **`'single'` is not a workspace type.** It is `window.defaultView` - single page versus
book. Mirador's workspace switch has exactly two cases:

```
case"mosaic": ...
case"elastic": ...
```

An unmatched value renders **no workspace component at all**, leaving the window's title bar
over a zero-height body. That was worse than the fault it replaced. Reverted to `'mosaic'`.

**Counting a string in a minified bundle proves it exists, not what it means. Find the switch
that consumes it.** One grep would have settled it, and did, later.

## 5. The fault that actually caused the black body: mounted while hidden

`IiifViewerManager.showViewer()` hides every wrapper, then ran:

```js
case 'mirador':
    await this.initMirador();                    // mounts into a display:none element
    this.showElement(`mirador-wrapper-${vid}`);  // shown only afterwards
```

So Mirador mounted inside a hidden element, **measured zero in every dimension**, laid its
workspace out at that size, and never re-measured when the wrapper was shown. Title bar over
a zero-height body, black underneath.

**OpenSeadragon survived the identical ordering only because it re-measures on resize.** That
is exactly why the symptom was "Seadragon works, Mirador does not", and why it pointed at
Mirador configuration when the fault was in the switching order shared by both.

Fix: show the container, then initialise into it. Applied to all five branches.

Recognise the shape generally: **any measuring widget - OpenSeadragon, Mirador, charts, maps -
initialised inside a hidden container computes zero and does not recover.**

### How the diagnosis went wrong

When it still failed after the deploy, I said it was browser cache. It was not; a check in
incognito ruled that out and sent me back to the code, where fault 5 was. The screenshot had
already shown a rendered window title bar - meaning the workspace *was* rendering - and I
anchored on my own most recent change instead of on that evidence.

## The 403 that looks like a permissions fault

`/plugins/ahgIiifPlugin/web/public/mirador/viewer.html` returned 403. So did `compare.html`.
`mirador.min.js` and `mirador.min.css` **in the same directory, same owner, same permissions**
returned 200.

Stock AtoM's nginx serves only a fixed extension list under `/plugins` (css, png, jpg, js,
svg, ico, gif, pdf, woff, woff2, otf, ttf). `.html` is not on it. **Every static `.html` in
any plugin tree is 403 on every standard AtoM install.**

No nginx change was needed. The plugin already had `/iiif/viewer/:id` - an action and a
template that already handled CSP nonces. The "open in new window" button was simply
pointing at the static file. Widening the nginx allowlist was rejected deliberately: it
would permit `.html` under `/vendor` and `/plugins` everywhere, a far broader surface, and
the framework is documented as working with stock AtoM nginx.

## Compare had three faults

It embedded `compare.html` in an iframe, so it hit the 403 above. The **reason** for the
iframe was also wrong: the template's own comment said a nonce cannot cover a stylesheet the
page writes after load, and therefore a header-free static file was needed. True as far as it
goes, but Mirador does not need a nonce attached from outside - it needs the meta tag. Compare
now renders inline with the nonce meta, no iframe.

And **only one manifest ever arrived.** `getParameter('manifest')` cannot see repeated
parameters; PHP collapses `?manifest=A&manifest=B` to `B`. The static page used
`URLSearchParams.getAll()`, which does see both, so repeated parameters appeared to work
there and never could in the PHP action. A comparison of one image is not a comparison. It
now parses the raw query string, accepts `manifest[]=`, and rejects anything that is not an
absolute http(s) URL, since those values are handed to Mirador to fetch.

## Environment

**`php-imagick` was not installed on atom210.** No package at all; only `gd`. Any image
uploaded there produced a master with no reference or thumbnail derivative, silently. It is
listed as a prerequisite in the project's own INSTALLATION.md. Installed, and derivatives now
generate.

## The rule

**Never build UI with inline `style=""` attributes on an instance with a CSP.** Use classes.
Where a value is only known at runtime, emit a nonce-carrying `<style>` element scoped by id.
SVG presentation attributes (`fill`, `stroke`, `x`, `y`) are attributes of the element, not
CSS, and are unaffected by `style-src` - which is why the archaeology plugin's own drawings
use them exclusively.


## Addendum: ViewerInjector could never inject, and the CSP was never the cause

**`renderViewers()` discards any renderer whose output lacks `data-rendered-by`, and neither
`ImageRenderer` nor `MiradorRenderer` emitted it.** The viewer list was therefore always
empty and the injector bailed on every page. Long-standing, and unrelated to this session's
edits - `ImageRenderer` had never been modified.

**`themeProvidesViewer()` also stood the injector down on any install carrying
`ahgThemeB5Plugin`**, presuming the theme renders a viewer. The DAM sector display does not:
it calls `render_digital_object_viewer()` and falls back to a bare `<a><img>` when that
helper is not loaded. Replaced with a marker test on the response - the same correction
`SiteRecordPanelInjector` already documents. `dam` added to `VIEW_MODULES`.

**The CSP change was reverted.** With the nonce restored, Mirador still renders at 630px, so
inline-style blocking was never the cause of the collapse. The real cause was
`viewer-switch.css` being linked only by ViewerInjector: on helper-rendered pages
`.ahg-mirador-frame` had no stylesheet behind it and computed `height: 0`, while OpenSeadragon
escaped because its height comes from a per-id `<style>` block. Net security change: none.

Both instances now carry identical `ahgIiifPlugin` trees, verified by checksum.
