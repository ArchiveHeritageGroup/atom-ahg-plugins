# 2026-07-01 — Spectrum workflow per-record step checklist (v3.79.30)

## Context
The 6 procedure "steps" (cataloguing: identification, description, measurements,
photography, research, review) were config-only metadata with no functional backing — no
per-step tracking, no completion, just labels rendered for display. User asked to make them
a real, tickable checklist.

## What was built (checklist model — tick off in any order, independent of the approval
## state machine)
- **Table** `spectrum_workflow_step_state` (procedure_type, record_id, step_key, is_done,
  completed_by, completed_at, timestamps; UNIQUE(procedure_type, record_id, step_key)). In
  `database/migration_workflow_step_state.sql` + appended to install.sql. Applied on
  archeology + archive.
- **Route** `spectrum_workflow_steps` → POST `/spectrum/:slug/workflow/steps` → action
  `workflowSteps`.
- **Action** `executeWorkflowSteps` (POST, admin/editor-gated): resolves all step keys from
  the config, upserts is_done for each (ticked → 1, unticked → 0), preserves the original
  completed_at/by when a step was already done, redirects back.
- **`executeWorkflow`** now loads `$this->stepStates` (step_key => row) for the record +
  procedure, guarded so the page still renders pre-migration.
- **Template** (`workflowSuccess.php`): the "Procedure steps" section is now a checkbox
  form with an X/N-done badge + "Save steps" button for editors; read-only check/uncheck
  icons for viewers. Completion date shown per ticked step.

Deliberately NOT gating the state transitions on step completion (checklist-first, per the
agreed design); linear-gating can be layered on later if wanted.

## Deploy
Lint clean; migration applied on both DBs; files mirrored archive→archaeology; cache
cleared + php-fpm restarted (route change). Verified: workflow page 200, steps route
POST-only (GET 404). Released v3.79.30 (pushed origin/main + tag). Applies to every
procedure that defines steps.
