# 2026-07-01 — Spectrum workflow step indicator never completes (v3.79.28)

## Symptom
Cataloguing workflow "does not complete — step 2/3 does not go to 4."

## Root cause — steps vs states conflation
Each spectrum procedure config has TWO independent lists: `steps` (procedure sub-tasks) and
`states` (the approval state machine). They are different lengths and unrelated:
- cataloguing: 6 steps / 4 states (pending, in_progress, review, completed)
- acquisition: 6 steps / 7 states
- object_entry / conservation: 5 steps / 6 states

`workflowSuccess.php` drove the 6-step visual stepper straight off the current STATE's index
in the `states` array:
```php
$stateIndex = array_search($currentStateName, $states);   // 0..3 for cataloguing
if ($index < $stateIndex) completed; elseif ($index == $stateIndex) current;
```
So the terminal state `completed` (index 3) only ever lit step 4 — steps 5-6 stayed
`pending` forever, and the workflow never appeared to finish; at `review` (index 2) it was
stuck showing step 3 as current, never reaching step 4.

## Fix
Map the state's PROGRESS fraction across the steps, and mark ALL steps done at the terminal
state:
```php
$stateIndex = array_search($currentStateName, $states) ?: 0;
$isFinalState = ($stateIndex >= count($states) - 1);
$progress = count($states) > 1 ? $stateIndex / (count($states) - 1) : 0;
$doneSteps = $isFinalState ? count($steps) : (int) floor($progress * count($steps));
// step completed if index < doneSteps, current if == doneSteps
```
Cataloguing now: pending→step1 current; in_progress→steps1-2 done/3 current;
review→steps1-4 done/5 current (passes step 4); completed→all 6 done.

Reaching `completed` requires clicking the state transitions (start→submit→approve) — those
no longer 500 after v3.79.27 (the `user_role_relation` fix).

## Deploy
Lint clean; mirrored archive→archaeology; php-fpm restarted. Released v3.79.28 (pushed
origin/main + tag). Template-only change; applies to all procedures.
