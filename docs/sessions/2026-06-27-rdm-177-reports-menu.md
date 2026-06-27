# RDM #177 — direct "RDM Dashboard" link in the reports menu

**Date:** 2026-06-27
**Issue:** ArchiveHeritageGroup/atom-ahg-plugins#177 (parity heratio#1344)
**Builds on:** the completed RDM port (epic #167)

## What this delivers
A direct **RDM Dashboard** + **RDM Compliance** entry in the AHG reports/research
menu, so the dashboard (the natural RDM entry point) is reachable top-level, not
only via the RDM index/compliance cross-links.

## Authorisation note
This required editing `ahgReportsPlugin` (locked-by-default per CLAUDE.md). Done
under the explicit #177 request, which names ahgReports. Change is minimal +
guarded; no other ahgReports behaviour touched.

## Files (ahgReportsPlugin — minimal, guarded)
- `modules/reports/templates/indexSuccess.php` (the live-rendered template) — two `<li>` links in the "Research Services" card, after "Research Dashboard", wrapped in `if (isPluginActive('ahgRdmPlugin'))` so they vanish cleanly when RDM is absent.
- `modules/reports/templates/indexSuccess.blade.php` (the source twin) — same two links in the Research section, `@if (isPluginActive('ahgRdmPlugin'))`.
- `extension.json` — 1.0.0 → 1.1.0.

Links target the literal working routes `/research/datasets/dashboard` and `/research/datasets/compliance` (same convention as the other absolute-path links in the menu).

## Verification (LIVE on PSIS)
- `php -l` clean on indexSuccess.php; `isPluginActive` is the established menu-guard helper (already used for `$hasResearch`); `ahgRdmPlugin` is enabled → links render for admins.
- Cache cleared + php-fpm restarted; `/reports` returns 200, no 500.

## Result
Both RDM follow-ups (#176, #177) are now closed; the RDM port + hardening is
fully complete and live on PSIS.
