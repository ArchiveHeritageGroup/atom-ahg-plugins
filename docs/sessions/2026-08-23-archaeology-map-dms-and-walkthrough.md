# Archaeology map fatal, DMS double-escape, and the narrated walkthrough

Date: 2026-08-23
Instance: archaeology.theahg.co.za
Release: atom-ahg-plugins v3.106.4

## Two template bugs, one cause

Symfony escapes template variables before the template sees them. That single fact
produced both faults, and they look nothing alike:

- `mapSuccess.php` called `array_map()` on `$sites`, which is an
  `sfOutputEscaperArrayDecorator`, not an array. On PHP 8 that is a **fatal**, so
  `/archaeology/map` died outright and wrote to `ahg_error_log` - which is what put
  the red "open system errors" banner on the demo site.
- `planSuccess.php` called `esc_specialchars()` on an already-escaped value, so the
  dig plan printed `25&deg;48&#039;21.1&quot;S` as literal text.

Fix: unwrap once at the top (`getRawValue()`) and let the existing
`esc_specialchars()` calls do the single escape; for the scalar, decode with
`sfOutputEscaper::unescape()` before escaping. Now reads `25°48'21.1"S 28°16'41.9"E`.

## Demo data completed

`archaeology_site` id 2 had `site_type_id` and `period_id` NULL and `region` set to
"Eastern Cape" while the coordinate and the record's own description both say
Pretoria. Set to Settlement / Late Iron Age / Gauteng, so the sites browse no longer
shows blank Type and Period columns.

## Narrated walkthrough

`/opt/parity/e2e/harris-narrated.cjs` records a 5:15 narrated walkthrough: three
intro slides (what a Harris Matrix is, why a hierarchy cannot hold stratigraphy, how
AtoM keeps both) then fourteen screens, closing on the matrix image full frame.

Three traps, all paid for:

- **Never wait on `pgrep -f "<script name>"`** - the waiting shell's own command line
  contains that string, so pgrep matches itself and the loop never exits. Wait on a
  marker written to the log instead.
- **This instance ENFORCES CSP**, so `style=""` attributes are dropped. A closing card
  built with inline styles rendered completely unstyled, the image at its natural
  2550x1445. Element `.style` assignment (CSSOM) is NOT governed by CSP and works -
  which is why the caption bar was fine throughout.
- **`page.setContent()` writes into the CURRENT document**, so the site's CSP still
  applies to a `<style>` element. Navigate to `about:blank` first. Images that are
  only served to a logged-in session must then be inlined as data URIs, read with
  `fetch(..., {credentials:'same-origin'})` while still on the authenticated origin.

Narration: `johan-final` on the F5-TTS sidecar, `POST /tts` with the BARE voice id
(the `f5:` prefix the Workbench uses returns 404). Render at speed 0.38, measure each
line's wpm from its WAV duration, and re-render anything above ~135 wpm at 0.33.
Audio is synthesised first; each scene is held for its line's duration and every
narration start is logged as an offset, so picture and voice stay locked.
