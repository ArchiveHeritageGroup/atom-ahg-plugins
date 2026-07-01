# 2026-07-01 — Spectrum workflow linear-gated step option (v3.79.31)

Follow-up to v3.79.30 (step checklist). Adds a per-procedure LINEAR mode alongside the
default CHECKLIST mode.

## Config flag
`steps_linear` (bool) in `spectrum_workflow_config.config_json` (default false = checklist).
No schema change.

## Linear mode behaviour
- **Ordered ticking:** the save action (`executeWorkflowSteps`) forces the done set to a
  contiguous prefix — step N can only be done if every earlier step is done; unticking a
  step cascades to untick all later ones.
- **Locked UI:** in the checklist form, steps beyond the first not-done step render disabled
  (lock icon); done steps + the next actionable step stay enabled (done ones must stay
  enabled so they still POST).
- **Finalisation gate:** `executeWorkflowTransition` blocks any transition whose target is
  the procedure's FINAL state until all steps are done (flash error + redirect, shows
  X/N done).
- **Admin toggle:** new `workflowStepsMode` action + route (POST /spectrum/:slug/workflow/
  steps-mode) flips `steps_linear`; a "Switch to linear/checklist mode" button on the
  workflow page (admin/editor). Mode badge (Linear/Checklist) shown by the steps heading.

## Deploy
lint clean; mirrored archive→archaeology; cache cleared + php-fpm restarted (route change).
Verified page 200, steps-mode POST-only (GET 404). Released v3.79.31.
