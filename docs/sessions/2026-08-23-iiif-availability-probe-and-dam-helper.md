# Why a healthy IIIF viewer showed a flat image: the availability probe, and the DAM helper

**Date:** 2026-08-23
**Instances:** PSIS (HTTPS, enforcing-less report-only CSP) and archaeology
**Plugins touched:** ahgIiifPlugin, ahgDAMPlugin (template only)

Reported as "I do not see IIIF/Seadragon/Mirador on psis.theahg.co.za/photo-3". Four things
in a chain, each hiding the next. The first three were misdiagnosed at least once.

## The red herring: `iiif_server_url`

The `iiif` settings group has an `iiif_server_url` key and it was empty, which looked like the
answer. **Nothing reads it except the settings UI.** The viewer resolves its image server from
`sfConfig::get('app_iiif_cantaloupe_url')`, defaulting to `/iiif/2`. Setting the DB value would
have changed nothing.

**Cantaloupe was running on 112 the whole time**, on :8182, correctly proxied - `/iiif/2`
returns 200.

## 1. The DAM template never loaded its helper

`ahgDAMPlugin/modules/dam/templates/indexSuccess.php` does
`if (function_exists('render_digital_object_viewer'))` and falls back to a bare `<a><img>`
otherwise. **Symfony 1.4 does not autoload helpers**, so that check was always false and every
DAM record showed a flat image with nothing logged.

`render_digital_object_viewer()` lives in
`ahgUiOverridesPlugin/lib/helper/informationobjectHelper.php`, so the helper name is
`informationobject`. The template now calls `use_helper('informationobject')` first, wrapped so
an instance without ahgUiOverridesPlugin keeps the fallback rather than fataling.

## 2. `is_iiif_available()` was false under FPM and true under CLI

Same host, same user, same code. The probe requests the site's own base URL. **In a web request
that URL is https; resolved from the CLI it came out http.** This host cannot reach its own
public `:443` - measured, every variant returned status 0:

```
HEAD  http2     status=0 errno=92  HTTP/2 stream 1 was not closed cleanly: PROTOCOL_ERROR
HEAD  http1.1   status=0 errno=52  Empty reply from server
GET   http2     status=0 errno=92  ...
GET   http1.1   status=0 errno=52  ...
GET   ranged    status=0 errno=92  ...
```

while `:80` answers 301 in 8ms. So a healthy image server read as absent **only on HTTPS**,
which is exactly why it never showed up on a plain-HTTP instance or from a shell.

The function's own comment already said only a connection failure or timeout should count as
absent. A bare `$status > 0` check did not implement that. It now treats **errno 6
(COULDNT_RESOLVE_HOST), 7 (COULDNT_CONNECT) and 28 (OPERATION_TIMEDOUT)** as absent, and
anything else - including an HTTP/2 stream reset or an empty reply - as "something is there
and replying".

⚠️ **Numeric errnos, deliberately.** `CURLE_HTTP2` and `CURLE_HTTP2_STREAM` are **not defined
in this PHP build**, and referencing an undefined constant is fatal in PHP 8. A first attempt
at this fix used them and introduced a latent crash.

## 3. ViewerInjector could never inject anything, for two independent reasons

- **`renderViewers()` discards any renderer whose output lacks `data-rendered-by`**, and
  neither `ImageRenderer` nor `MiradorRenderer` emitted it. The viewer list was always empty.
  Long-standing; `ImageRenderer` had never been modified.
- **`themeProvidesViewer()` stood the injector down on any install carrying
  ahgThemeB5Plugin**, presuming the theme renders a viewer. The DAM sector display does not.

Replaced the presumption with a **marker test** on the response
(`ahg-iiif-viewer` / `osd-viewer` / `mirador-wrapper`) - the same correction
ahgSiteRecordPlugin's SiteRecordPanelInjector already documents. Added `dam` to `VIEW_MODULES`
and an anchor for the DAM card body. Also gave the injector the same `is_iiif_available()`
check the helper path uses, so it cannot inject a viewer pointing at an absent image server.

## 4. A missed file in the earlier CSP sweep

`ImageRenderer` still carried an inline `style="width:100%;height:600px;..."`. The earlier
sweep grepped for `osd-iiif-viewer-` and this file emits `osd-`, so it was missed.
**Grepping one id shape misses renderers that build the same widget differently.**

## Result

`/photo-3` renders exactly one OSD container and one Mirador wrapper inside the DAM card, no
plain-image fallback, no duplication, `ahg-iiif-viewer: 0` confirming the injector correctly
stood down because the helper had already rendered. Archaeology unchanged. Both instances
carry identical ahgIiifPlugin trees.

## Process note

This took several wrong turns - an empty setting mistaken for the cause, a HEAD-versus-HTTP/2
theory that was wrong, and an undefined-constant regression. What ended it was tracing the
actual branch taken in a real web request to a file, rather than reasoning about the code or
reading a pool log that turned out to be the wrong pool (PSIS runs on the **www** pool, not
`atom`, which is already recorded and which I failed to apply).
