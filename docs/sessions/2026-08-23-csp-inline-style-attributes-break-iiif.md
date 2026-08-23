# CSP: inline style ATTRIBUTES break the IIIF viewer, and a nonce cannot fix it

**Date:** 2026-08-23
**Instance:** archaeology (atom210). Applies to any instance with a CSP.

## Symptom

Browser console reports CSP blocking inline execution; the IIIF viewer shows nothing.

## Diagnosis

Every inline `<script>` on the page **does** carry the nonce - scripts are fine. The
blocked content is inline **style attributes**:

```html
<div id="osd-iiif-viewer-5667-..." style="width:100%;height:500px;background:#b1aaaa;...">
<div id="mirador-wrapper-..."      style="display:none;position:relative;...">
<div id="mirador-..."              style="width:100%;height:700px;">
```

The served policy is `style-src 'self' 'nonce-...' https://fonts.googleapis.com`.

**A nonce covers `<style>` and `<script>` ELEMENTS. It never covers `style=""`
ATTRIBUTES.** So the viewer containers receive no width or height, collapse to zero
height, and render nothing. There is no error in the page - the viewer is simply
invisible, which is why this reads as "IIIF does not work" rather than as a CSP problem.

## Why the obvious fix does not work

Adding `'unsafe-inline'` to `style-src` has **no effect** while a nonce is present:
per CSP Level 3, when a directive contains a nonce-source or hash-source, `'unsafe-inline'`
is ignored for that directive. Anyone "fixing" this by appending `'unsafe-inline'` will
see no change and conclude the policy is not the cause.

## The options, in order of preference

1. **Move the styles into a stylesheet** in the viewer plugin and reference them by class.
   Correct, and the only one that leaves the policy intact. Requires editing
   ahgIiifPlugin / ahgMiradorPlugin, both currently locked.
2. **`'unsafe-hashes'` plus the SHA-256 of each style attribute value** in `style-src`.
   Precise and keeps the policy meaningful, but brittle: every edit to a style string
   needs a new hash.
3. **Drop the nonce from `style-src` and use `'unsafe-inline'`.** Works immediately,
   costs the style-src protection. Lower risk than the same move on script-src, but it is
   still a real reduction and should be a deliberate decision, not a side effect.

`config/app.yml` holds the policy and is per-server, not in git.

## The rule to carry forward

**Never build UI with inline `style=""` attributes on an instance with a CSP.** Use
classes. This is why the archaeology plugin's own panel and its dig-plan SVG use Bootstrap
classes and SVG presentation attributes (`fill`, `stroke`, `x`, `y`) exclusively - SVG
presentation attributes are attributes of the element, not CSS, and are unaffected by
`style-src`.
