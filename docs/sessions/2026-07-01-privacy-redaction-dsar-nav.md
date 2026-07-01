# 2026-07-01 — Privacy plugin fixes (v3.79.33)

Instances: archive/PSIS + archaeology (both synced, cc + php-fpm restart).

## Fixes
1. **Visual Redaction editor drag-vs-draw** — OpenSeadragon ignored the
   `gestureSettingsMouse.dragToPan` tweak, so click-drag panned the image
   instead of drawing the redaction box. Replaced with
   `osdViewer.setMouseNavEnabled(false)` in Draw mode (re-enabled in Select).
   Applied to both `visualRedactionEditorSuccess.php` (Symfony) and
   `.blade.php` (Heratio).
2. **DSAR confirmation double message** — `/privacy/dsarConfirmation` showed
   both "Failed to submit request:" and the success + reference. `redirect()`
   sets the Location header then throws `sfStopException`, which the
   `catch (\Exception)` swallowed into a phantom error flash while the redirect
   still fired. Now rethrows `sfStopException` in the DSAR-request and
   complaint-submit catches. Same class as the preservation package/schedule fix.
3. **"Scan for PII" nav item** — runs `ahgNerService` (AI). Now gated on
   `isPluginActive('ahgAIPlugin')` in `_contextMenu.php`. Visual Redaction
   (digital-object-gated) + Privacy Dashboard already render in the block.

Files: ahgPrivacyPlugin/modules/privacy/actions/actions.class.php,
ahgPrivacyPlugin/modules/privacyAdmin/templates/visualRedactionEditorSuccess.{php,blade.php},
ahgThemeB5Plugin/modules/informationobject/templates/_contextMenu.php
