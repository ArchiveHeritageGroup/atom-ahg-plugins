# 2026-07-01 — Preservation double-flash + redaction draw-drag (v3.79.32)

## Task 1 — Preservation package/schedule save shows "created successfully" + empty "Error:"
`executePackageEdit` / schedule save wrapped `$this->redirect()` inside a `try { ... }
catch (Exception $e)`. Symfony's `redirect()` throws `sfStopException` to halt the action;
the catch swallowed it, cancelled the redirect, and set `setFlash('error', 'Error: ' .
$e->getMessage())` with an empty message — so the edit page re-rendered showing BOTH the
success notice and a phantom empty "Error:". Fixed both catches (package @925/941, schedule
@687/692) to rethrow `sfStopException` before treating anything as an error.

## Task 4 — Visual redaction editor: image drags instead of drawing a box
`/admin/privacy/redaction/:id` uses OpenSeadragon + openseadragon-annotorious. Entering draw
mode called `annotorious.setDrawingEnabled(true)` but left OSD's drag-to-pan on, so a
click-drag panned the image instead of drawing. Fixed `setTool()` to toggle
`osdViewer.gestureSettingsMouse.dragToPan = false` in rect/draw mode (restored in select
mode); scroll-to-zoom stays available. (User's "Heratio uses another viewer" — kept the OSD
viewer, just fixed the pan-vs-draw gesture conflict.)

## Deploy
Lint clean; mirrored archive→archaeology; php-fpm restarted. Released v3.79.32.

## Still open (need the screen URL)
"Scan for PII" should hide when AI (ahgAIPlugin) is off; "Visual Redaction" + "Privacy
Dashboard" should appear — couldn't locate the exact screen showing those three together;
awaiting URL.
