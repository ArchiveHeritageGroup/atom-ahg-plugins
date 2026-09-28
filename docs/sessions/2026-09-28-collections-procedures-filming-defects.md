# 2026-09-28 - Filming Museum Collections found ten defects (v3.112.11, v3.112.12, fw v2.18.42)

Recording the Museum Collections flagship video end to end exposed ten defects in
`ahgSpectrumPlugin` and two unguarded plugins. None were found by reading code;
every one surfaced by trying to use the feature the way the video narrates it.

## The chain, in the order each one hid the next

1. **The Collections Procedures dashboard was dead.** `dashboardSuccess.php:45`
   called `ahgSpectrumWorkflowService::countOpenTasks()`; the method is
   `countOpenTasksForUser()`. The page returned 1,505 bytes of unstyled fragments
   with no theme and no layout. The comment directly above the call says the
   count is shared with the admin-menu badge and My Tasks "so they always agree" -
   the helper was renamed, the badge caller in the config class was updated, and
   this template was missed.

2. **Two tables shipped with no install.** `SpectrumEvidenceService` and
   `SpectrumOutcomeService` query `spectrum_evidence` and
   `spectrum_outcome_proposal`. `database/install.sql` creates 33 other
   `spectrum_` tables and neither of those. `/spectrum/outcomes` returned 500 on
   every fresh install, and every per-record workflow page logged a failed
   evidence lookup while still rendering, which is why nobody noticed.

3. **Eight routes named actions that do not exist.** Six had a real target under a
   different name (`saveAnnotation` -> `annotationSave`, `getAnnotation` ->
   `annotationGet`, `ropa` -> `privacyRopa`, and the three API routes). Two named
   actions that exist nowhere - `spectrum_condition_check` and
   `spectrum_template_config` - and were removed rather than stubbed.

4. **Both API action classes were misnamed.** `statisticsApiAction` instead of
   `spectrumApiStatisticsApiAction`. Symfony resolves a single-action file as
   `<module><Action>Action`, so these endpoints had never once executed. Repointing
   the routes in step 3 is what made this visible.

5. **Both redeclared `renderJson($data)`** incompatibly with
   `AhgController::renderJson(array $data, int $status = 200)` - a fatal at class
   load. The overrides also did less than the parent (no content type, no
   standalone mode), so they were deleted rather than patched.

6. **A SQLSTATE used as an HTTP status.** `$code = $e->getCode() ?: 500` then
   `setStatusCode($code)`. A PDOException carries its SQLSTATE there, so `'42S22'`
   became the status and nginx answered 502 - hiding the very error the catch
   block existed to report.

7. **`COUNT(DISTINCT e.object_id)`** in the outer query of
   `getProcedureStatistics`, where the only table in scope is the derived `latest`.

8. **`io.slug` in three places.** `information_object` has no slug column; AtoM
   keeps slugs in the `slug` table keyed by `object_id`. Same defect and same fix
   as `ahgMetadataExtractionPlugin` earlier the same day.

9. **The evidence store is never created.** `SpectrumEvidenceService` writes to
   `/var/lib/ahg-evidence/<instance>`, deliberately outside the document root so
   the files are not reachable over the web. Nothing in the install creates it and
   `/var/lib` is root-owned, so the web user cannot. Every upload failed with
   "Could not create the evidence directory" - a message naming no path and no
   cause. **The directory does not exist on PSIS either**, so evidence upload is
   broken in production until this is run:

   ```
   sudo mkdir -p /var/lib/ahg-evidence/archive
   sudo chown -R www-data:www-data /var/lib/ahg-evidence
   sudo chmod 770 /var/lib/ahg-evidence /var/lib/ahg-evidence/archive
   ```

   The error now names the directory and gives the command, and
   `INSTALLATION_GUIDE.md` carries the step.

10. **`/spectrum/ropa` still fails**, but environmentally: `privacy_processing_activity`
    belongs to `ahgPrivacyPlugin`, which is not installed on the demo VM. Left
    alone, but it does mean `ahgSpectrumPlugin` ships nine `privacy*` actions
    querying another plugin's tables, against the "each plugin autonomous" rule.

## Security

Fixing the class names in step 4 made two endpoints reachable that never had
been. `modules/spectrumApi` had **no `security.yml`**, so the app default
`is_secure: false` applied and an anonymous GET returned object identifiers and
slugs, assignee usernames, due dates and procedure notes. `eventApi` guarded only
its write path; `statisticsApi` guarded nothing.

Checking the neighbours found the same gap already live and unrelated to this
work: **`ahgExhibitionPlugin` and `ahgMuseumPlugin` had no `security.yml` on any
module**. Confirmed in a browser, not inferred - an anonymous request to
`/index.php/exhibition` rendered the management screen with its New Exhibition
button and statistics panel, and `exhibitionSpace` exposed `destroy`,
`saveLayout` and `removePlacement` the same way.

Ten `security.yml` files added, all following the precedent in
`modules/spectrum/config/security.yml`: `is_secure` only, no module-wide
`credentials` key, because a module-wide `credentials: [editor]` refuses
administrators, who hold the administrator credential rather than editor.

## The audit has a false negative, and it let this through

`atom-framework/bin/audit-security-yml` exempts an entire action file when the
guard regex matches **anywhere** in it:

```
grep -qE "$GUARD_RE" "$f" 2>/dev/null && continue
```

`ahgExhibitionPlugin/modules/exhibition/actions/actions.class.php` holds 31
actions, one `isAuthenticated`, and a `preExecute` that does not block. It passed
the audit and was never once in the baseline while being anonymously reachable.
Verified directly: with its new `security.yml` moved aside, the audit still does
not report it.

The script's own comment warns against exactly this reasoning for parent classes
- "useless in both directions ... a silent audit is worse than a noisy one" - and
then applies it to the action file itself.

**Do not repeat the scary number.** A per-action heuristic suggested 55 modules
and 863 hidden actions, but spot-checking the six worst against live PSIS showed
four are guarded in practice (`/privacyAdmin`, `/reportBuilder` and `/workflow`
return the login form, `/integrity` returns 403). The file-level exemption is a
hole in the **detector**, not evidence of 863 live exposures. A real figure needs
per-module verification. Two observations from that check, neither acted on:
`/registry` serves a public "AtoM Community Hub" page and `/heritage` serves
"Discover Our Heritage", both of which may be entirely intentional.

Baseline ratcheted 232 -> 211 (all removals, no additions) and released as
framework v2.18.42.

### The audit was then fixed, and the baseline number goes UP - read this first

Framework v2.18.43 splits a multi-action `actions.class.php` and judges each
`execute*` method on its own body. A `preExecute` excuses the class only if it
refuses UNCONDITIONALLY, because ahgExhibitionPlugin's refuses for a hardcoded
list of write actions and lets every read through.

**The baseline went 211 -> 978. Nothing became less secure.** Of the 774 new
entries, ZERO are newly-exposed modules: all 774 are action-level refinements of
files already counted, and 7 entries moved from whole-file to per-action (each
confirmed replaced - `ahgThemeB5Plugin/physicalobject/actions.class.php` became 7
action entries, and so on). The detector got finer, so the count grew. Without
re-recording the baseline, CI fails with 774 phantom regressions.

A future session reading "baseline 978" with no context will reasonably conclude
something went badly wrong. It did not. The number to compare against is 978.

**A bug was introduced and caught in testing, and it is the same failure mode the
script exists to prevent.** The first version passed `REFUSE_RE` through
`awk -v`, which runs its own escape processing, so the escaped parentheses were
consumed twice and awk died with "invalid regexp" on every class containing a
`preExecute` - and `|| true` swallowed it. The audit got QUIETER, and exhibition
still reported zero. The regex is now parenthesis-free and a parse failure fails
CLOSED: the file is reported whole rather than skipped.

What it now surfaces is a backlog to triage, not a fire - 146 actions in
`ahgRegistryPlugin/registry`, 75 in `ahgPrivacyPlugin/privacyAdmin`, 41 in
`ahgIntegrityPlugin`. Several of those are guarded in practice on PSIS despite
having no security.yml, per the spot-check above.

## Recording note for whoever films next

The first take looked clean - no errors, no failed selectors - and saved no
evidence at all. Two causes stacked: the scene script matched
`button:has-text("Add")` outside the evidence form so nothing posted, and
underneath it defect 9. **Check the database after a recording that performs
writes.** Steps, evidence and state were all verified in SQL before the video was
published.

The second take used a different object (`the-great-wave-off-kanagawa`, id 469)
rather than resetting the first one's procedure state with SQL, because a clean
object was available and no data-modifying statement was needed.

## Released

- `atom-ahg-plugins` v3.112.11 - defects 1-8 plus the ten `security.yml`
- `atom-ahg-plugins` v3.112.12 - defect 9, error message and install step
- `atom-framework` v2.18.42 - baseline ratchet
- Video: `access-to-memory-museum-collections.mp4`, 3:57, in
  `atom-extensions-catalog/docs/videos`
