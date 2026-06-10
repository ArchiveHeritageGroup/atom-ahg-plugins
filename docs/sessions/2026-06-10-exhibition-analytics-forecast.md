# 2026-06-10 — Exhibition analytics + forecast (+ output-escaping fix)

## Summary
Ported Heratio's exhibition **analytics** and **conservation forecast** dashboards to PSIS `ahgExhibitionPlugin`, and fixed a cross-cutting **output-escaping bug** that was leaving embedded JSON empty on the ported pages (including the already-released walkthrough + plan editor).

## Analytics + forecast (new)
- Migration `004_exhibition_readings.sql` — 4 tables: `ahg_exhibition_reading`, `ahg_exhibition_visit`, `ahg_exhibition_visit_event`, `ahg_exhibition_alert` (idempotent, run on PSIS).
- ~18 service methods ported off Laravel facades (Schema::hasTable → `tableExists`, Carbon `now()` → `date()`): buildingAnalytics, visitorAnalytics, visitorHeatmap, conservationForecast/Status/Timeline, buildingForecast, lightBudget, latestReadings, liveState, recordReading, recentAlerts, simulateReadings, getOrCreateSensorToken, regenerateSensorToken, ingestSensor, recordVisitBeat/Event.
- Actions: `executeAnalytics`, `executeForecast` (public pages) + `recordReadings` / `simulateReadings` / `sensorRegen` (auth) ingest + routes.
- Templates `analyticsSuccess.php` (Chart.js per-metric charts, visitor stats, heatmap, sensor token/alerts) + `forecastSuccess.php` (light-dose/risk per room, conservation timeline, what-if simulator). Chart.js from jsdelivr (nonce'd).
- Demo: gave `demo-gallery-builder` a `building_id` + 128 seeded readings so the charts/forecast populate (forecast risk = **alert** at the 200-lux target).

## Output-escaping fix (important)
AtoM runs `escaping_strategy: true`, so action vars reach templates wrapped in `sfOutputEscaperArrayDecorator`. `json_encode($var)` on that yields `{}` — so every embedded data blob (`var DATA`, the walkthrough `exh-wt-config`, the plan `plan`, forecast `var T`) was **empty**, i.e. the released walkthrough rendered an empty 3D scene and the plan editor showed no rooms. Fix: prepend each ported template with a block that `sfOutputEscaper::unescape()`s its action vars before encoding. Applied to analyticsSuccess, forecastSuccess, walkthroughSuccess, planSuccess, builderSuccess. Verified: analytics 28 buckets, forecast 1 room, walkthrough scene 1 room + 4 object stops, plan rooms present.

## Status
Released. Per-instance deploy (ANC/WDB): run migrations `003` + `004`, cache clear, fpm restart.
Deferred (under #149): the `generate` (AI) page + the visitor-presence layer that feeds visitor analytics; sensor data flows via the readings/simulate endpoints.
