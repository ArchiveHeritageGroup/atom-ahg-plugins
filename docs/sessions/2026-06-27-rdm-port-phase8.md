# RDM port — Phase 8 (atom-ahg-plugins#175): Feature 3 — dashboard + filters

**Date:** 2026-06-27
**Issue:** ArchiveHeritageGroup/atom-ahg-plugins#175 (epic #167 — FINAL child)
**Source:** Heratio `packages/ahg-rdm` `DashboardService` + dashboard view (heratio#1337 F3 + #1345 filters)
**Builds on:** Phase 7 (#174)

## What this delivers
The roll-up RDM dashboard — the RDM unit's at-a-glance operational + strategic
view above the per-dataset compliance scoreboard. **Completes the reverse port.**

## Files
- `lib/Services/DashboardService.php` (`AhgRdm\Services`): `overview(filters)` — 9 KPIs + verdict/disposition/method/type breakdowns + 12-month deposit trend + per-faculty posture + human-gate backlog (top 10) + recent (8). Filters (from/to/institution) resolve to ONE dataset-id set scoping every aggregate (`filteredDatasetIds`); trend honours institution only. All guarded for the DMP slice.
- `modules/rdm/actions` — `executeDashboard()` (auth; overview + institutions).
- `config` — route `/research/datasets/dashboard` (before `/:id`).
- `modules/rdm/templates/dashboardSuccess.php` — defensibility banner, 8 KPI cards, 5 Chart.js 4.4 charts (verdict/disposition/method doughnuts, PII-type bar, 12-month trend line), per-faculty + backlog + recent tables. Chart.js via jsDelivr (already CSP-whitelisted in config/app.yml for the ahgAI dashboards); inline init script carries the CSP nonce.
- `indexSuccess.php` / `complianceSuccess.php` — Dashboard cross-links.

## Port deltas (Laravel → Symfony/AtoM)
- Carbon `now()->subMonths(11)->startOfMonth()` / `->format('Y-m')` → `date('Y-m-01', strtotime('first day of -11 months'))` / `date('Y-m', strtotime("first day of -{$i} months"))`.
- `Schema::hasColumn` → `information_schema`. Closures/scopers + `when()`/`whereIn`/`whereDate` work unchanged on Capsule.
- Blade `@json` → `json_encode`; `route('rdm.datasets.show',$id)` → `url_for('@rdm_datasets_show?id=')`; `Str::limit` → `mb_strimwidth`; `@push('js')` → inline `<script>` with `sfConfig::get('csp_nonce')`.

## Parity note (#1344 reports-menu link) — DEFERRED
Heratio added a direct "RDM Dashboard" entry to the reports menu (heratio#1344).
On AtoM that menu lives in **`ahgReportsPlugin`, which is LOCKED** — adding the
`<li>` needs an explicit unlock (same blocker class as heratio#1344). Deferred:
the dashboard is reachable via the RDM index + compliance cross-links; the locked
reports-menu link is a follow-up needing an ahgReports unlock.

## Activation + verification (LIVE on PSIS)
- No new DDL. Cache cleared + php-fpm restarted.
- `php -l` clean (15 PHP files).
- `/research/datasets/dashboard` renders with the demo corpus; charts + KPIs + tables populate (see run notes).

## Epic #167 — COMPLETE
All 8 phases (#168–#175) of the sovereign RDM + POPIA-scan reverse port are built,
released, and live on PSIS. Open follow-ups: raw-binary ODRL gate (parity #1347 —
needs nginx/base change) and the locked reports-menu link (parity #1344).
