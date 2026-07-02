# 2026-07-02 — /accession/add 500 + donor dropdown (route fixes)

Releases: atom-framework v2.13.13, atom-ahg-plugins v3.79.42. Both instances.

## /accession/add "Unknown record property sf_method"
Not archaeology-specific (both instances; browse works, add/edit form calls
generate(null,$accession)). Base sfRequestRoute::matchesParameters runs
isset($params['sf_method']); for a model-object param that hits Qubit __isset which
THROWS. RouteLoader creates ~363 sfRequestRoutes → object-generate crashes.
Fix: SafeRequestRoute (atom-framework/src/Routing) — sf_method check only for array
params, else sfRoute::matchesParameters. RouteLoader uses it; ProjectConfiguration
require_once's it (template hard-stop still needs the 2 lines). + null-term generate
guards in accession editAction.

## Donor dropdown "does not work"
TomSelect remote-loads /donor/autocomplete → was 404 because /donor/:slug
(donorManage/view) shadowed it (slug="autocomplete"). Added /donor/autocomplete
route (module 'donor') registered after the catch-all (RouteLoader prepends last =
checked first). Now 403 unauth / JSON for admin.

## Also
Cleared archaeology ahg_error_log (180 rows; 144 were transient "SafeRequestRoute
not found" from the deploy window, now loads). archaeology 500 outage earlier was a
stale config-handler cache from repeated `symfony cc` — fixed by full
cache/qubit/prod/config clear + rebuild.
