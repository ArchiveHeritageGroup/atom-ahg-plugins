# Landing-filter outage (host-wide 502) + online help F1

Date: 2026-08-02
Instance: PSIS / archive
Releases: atom-framework v2.13.50, atom-ahg-plugins v3.88.15

## 1. Host-wide outage - OR-joined creator facet

### Symptom

Every PHP vhost on .112 (psis, callhub, keys, ...) returned 502 or timed out.
nginx logged `connect() to unix:/run/php/php8.3-fpm.sock failed
(11: Resource temporarily unavailable)`. php-fpm reported `Processes active: 100,
idle: 8, slow: 85951` against `pm.max_children = 100`.

### Diagnosis

`SHOW FULL PROCESSLIST` was full of concurrent copies of one statement, each
running 20-27 seconds:

```sql
select a.id as value, ai.authorized_form_of_name as label, COUNT(DISTINCT io.id) as count
from actor a
  inner join relation r on a.id = r.object_id  OR a.id = r.subject_id
  inner join information_object io on r.subject_id = io.id OR r.object_id = io.id
  inner join status st on io.id = st.object_id and st.type_id = 158
where st.status_id = 160 ...
```

Source: `atom-framework/src/Heritage/Filters/FilterValueResolver.php`,
`resolveActorValues()`. MySQL cannot use an index for an OR'd join predicate, so
both joins degraded to nested-loop scans over `relation` x `information_object`.

The caller is `Heritage/Config/LandingConfigService.php`, which resolves filter
values on **every landing-page request with no caching**. One visitor is slow;
concurrent visitors and crawlers saturate the pool and take the whole host down.
A php-fpm restart bought roughly five seconds before re-saturation.

### Fix (fw v2.13.50)

Resolve creators through `event.actor_id`, which is indexed (`event_FI_3`
object_id, `event_FI_4` actor_id), instead of the generic `relation` table. Same
approach already used by `ahgDisplayPlugin DynamicFacetService::getCreatorCounts()`
(line ~739), so the two now agree with each other. `HAVING COUNT(DISTINCT io.id) > 0`
dropped, since the inner join already guarantees it.

Measured on the live database: **25s -> 0.001s**. Pool recovered to `slow: 0`;
psis returns HTTP 200 in ~2.1s.

`resolveActorValues` was the only OR-joined resolver in the file (checked with
`grep -n orOn`).

### Behaviour change (accepted by Johan)

The old query counted actors linked through `relation` in either direction and of
any type. The new one counts actors linked as **event actors**, which is what a
creator filter means. Actors related to a description only by a plain `relation`
row no longer appear in the landing filter.

### LESSON

**Never OR two join predicates in a facet or landing query.** MySQL drops to a
nested-loop scan and the cost is invisible until concurrency arrives. If a
relationship is genuinely bidirectional, resolve it through an indexed
single-direction table (here `event`) or split into a UNION.

### Follow-up not done

`LandingConfigService` still resolves filter values on every request with no
caching. A short-TTL cache belongs there.

## 2. Online help - assets never loaded, F1 added (plugins v3.88.15)

### Root cause

`ahgHelpPlugin` (v1.0.0, enabled, 358 articles / 9223 sections) registered its
assets via `$response->addJavascript()` / `addStylesheet()` in
`ahgHelpPluginConfiguration::injectAssets()`. **ahgThemeB5Plugin never calls
`include_javascripts()` or `include_stylesheets()`**, so none of them were ever
emitted. Contextual help, the floating "?" button and help-page search had been
dead on every page since install. This was not an F1 gap; the whole client side
was missing.

### Changes

- `ahgThemeB5Plugin/templates/_layout_end.php`: emit `help.css`,
  `help-context.js` and `help-search.js` directly with CSP nonces, gated on the
  plugin being enabled, following the existing voice-commands pattern.
  `help-chatbot.js` deliberately NOT loaded - it injects a floating chat widget on
  every page, which is a separate decision.
- `ahgHelpPlugin/js/help-context.js`: bind **F1** to the contextual offcanvas
  panel; second press closes it. Modifier combos are ignored so the browser's own
  help still works. The context map is fetched asynchronously, so a press before
  it arrives is queued (`pendingOpen`) rather than dropped, and `mappings` is set
  to `[]` on error so F1 still reaches the help centre. Pages with no mapping fall
  back to `/help` in a new tab. Offcanvas footer link opens in a new tab.
- `ahgThemeB5Plugin/templates/_header.php`: Help Center icon gains
  `target="_blank" rel="noopener"`; tooltip now reads
  "Help Center (press F1 for help on this page)".

### Verified live

All three assets return HTTP 200; the tags appear in rendered HTML;
`target="_blank"` present on the header link; the F1 handler is in the served JS;
`/help/api/context-map` returns its mappings; `/help` loads in 0.9s. The actual
F1 keypress needs a browser and was not shell-testable.

### LESSON

**In this theme, `$response->addJavascript()` is a no-op.** Any plugin relying on
it ships dead assets. Load plugin JS/CSS with direct `<script src>` / `<link>` at
the end of `_layout_end.php`, with the CSP nonce.

## Related

Issue #258 (digital object masters served statically by nginx, bypassing all
access control) was filed this session and remains open and untouched.
