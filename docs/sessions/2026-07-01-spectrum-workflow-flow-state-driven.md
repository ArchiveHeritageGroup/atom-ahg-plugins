# 2026-07-01 — Spectrum workflow progress now follows the state machine (v3.79.29)

## Symptom (3rd report on this workflow)
Cataloguing workflow "does not flow — jumps back to 3 then 5." The step marker skipped
(1→3→5) as the user clicked transition actions.

## Root cause
Each procedure config is a STATE MACHINE (`initial_state` / `states` / `transitions`); the
`steps` array (6 named cataloguing sub-tasks) is reference metadata. The template rendered
`steps` as a live progress bar driven by the current STATE's index. With 6 steps but only 4
states, the proportional mapping (v3.79.28) advanced the "current" marker ~2 steps per
transition → visible jumping and never a clean per-step flow. There is no per-step state in
the data model — the transition dropdown only moves the state.

## Fix (workflowSuccess.php)
Progress bar now iterates the STATES themselves (Pending → In Progress → Review →
Completed), which is exactly what the transition actions advance — monotonic, one stage per
transition, and the terminal state renders green (completed). Uses `state_labels` from the
config when present. The 6 named procedure steps are shown separately as a static reference
checklist (not a synced progress bar). Applies to every procedure, not just cataloguing.

## Also this turn
Removed the stale `/etc/php/8.3/fpm/conf.d/20-imagick.ini.disabled` symlink (imagick 3.8.1
now active on php8.3 — see host_imagick_php83_manual_build memory).

## Deploy
Lint clean; mirrored archive→archaeology; php-fpm restarted. Released v3.79.29 (pushed
origin/main + tag). Template-only.
