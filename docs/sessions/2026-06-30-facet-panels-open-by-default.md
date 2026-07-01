# 2026-06-30 — Facet (collapsible) panels open by default (v3.79.24)

## Request
"All collapsible panels open by default except GLAM/DAM browse pages." (Clarified: the
collapsible facet/aggregation panels, not image carousels.)

## Change
`ahgThemeB5Plugin/modules/search/templates/_aggregation.php` — the shared facet panel
partial used by every standard AtoM browse/search page (actor, repository,
informationobject, term, etc. via `get_partial('search/aggregation')`).

Before: `$openned = (isset($sf_request->{$name}) || (isset($open) && $open && count>0))`
— collapsed unless the facet was filtered or the caller passed `$open=true`.

After: open by default —
`$openned = (isset($sf_request->{$name}) || !isset($open) || $open) && count($aggs[$name])>0`
Still honours an explicit `$open=false` override and always opens an active filter.

## Why the GLAM/DAM exception holds automatically
GLAM browse (`ahgDisplayPlugin/modules/display/templates/browseSuccess.php`) and DAM browse
render their OWN facet markup with their own collapse states — they do NOT use
`search/aggregation` (verified: `grep search/aggregation` in ahgDisplayPlugin/ahgDAMPlugin
is empty). So changing the shared partial opens standard browse/search facets while leaving
GLAM/DAM browse exactly as they were. No page-type condition needed.

Also verified no caller passes `'open' => false`, so there are no intentional collapses to
override.

## Deploy
Lint clean; applied on PSIS/archive (theme is symlinked from atom-ahg-plugins) and mirrored
to archaeology; cache cleared + php-fpm restarted on both. Smoke: /actor/browse healthy on
both (no 500). Released v3.79.24 (pushed origin/main + tag).
