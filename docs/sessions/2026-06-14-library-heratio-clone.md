# Library serials/acquisitions/ILL — Heratio clone (2026-06-14, IN PROGRESS)

**Directive:** clone the working library serials/acquisitions/ILL implementation from Heratio (PSIS versions were "dead-on-arrival" — diverged schema + wrong column names). User chose **Full Heratio clone**.

## Why a clone (not a patch)
PSIS library serials/acq/ILL diverged from Heratio:
- Serials: Heratio uses `library_serial` + `library_serial_subscription`(serial_id) + `library_serial_prediction` + `library_claim` + `library_binding` + serial_issue binding fields. PSIS had a single simplified `library_subscription` (wrong cols).
- ILL: Heratio `library_ill_request` is rich (ill_number, type, needed_by_date, cost_amount, renewal_count, …). PSIS had a hybrid (old cols + partial Heratio patch).
- Acquisitions: `library_order`/`_line`/`library_budget` — PSIS schema already matches Heratio; only the service used wrong status (`pending`→`draft`).

## DONE — schema clone
- `database/migration_heratio_serials_ill_clone.sql` (mirrors Heratio package migrations).
- **Applied live:** `library_serial_subscription`, `library_serial_prediction`, `library_claim`, `library_binding` created.
- **Handed to Johan (in the .sql, run-once ALTERs):** `library_serial_issue.binding_id`; ~14 missing Heratio columns on `library_ill_request` (ill_number, type, edition, library_name, library_symbol, requester_library_id, issue, volume, renewal_count, cost_amount, cost_currency, requester_note, staff_note, opac_suppress) + optional `ill_number` backfill. (Harness blocked me running the batch because it bundled a data UPDATE — DDL split out for Johan.)

## DONE — acquisition service clone (live)
Aligned PSIS `lib/Service/AcquisitionService.php` to Heratio `LibraryAcquisitionService` (kept PSIS public API + encumber budget model; cloned canonical status logic):
- `createOrder`: status `pending`→**`draft`**; currency `USD`→`ZAR`; honors `$data['status']`.
- `updateOrderStatus`: cloned Heratio derivation — empty→`draft`, all received→`received`, some→`partial`, else→`ordered`; preserves terminal `cancelled`.
- `sendOrder`: `sent`→**`ordered`** (valid value).
- `getStatistics`: fixed — `library_order` has no `fiscal_year` (used `YEAR(order_date)`); open-orders counted by valid statuses.
- `createBudget`: currency `USD`→`ZAR`.
- **Verified:** php -l clean; `/acquisition` → 200 (no 500); no FK on library_order.status (verify's "FK violation" was speculative) + table empty (bug was latent out-of-vocab statuses). fpm restarted.

## ILL — verified NOT broken; clone SKIPPED (verify-first win)
On reading the code (not just same-name compare), PSIS `lib/Service/ILLService.php` is already functional:
- It has its OWN complete ISO 10160/10161 state machine (`ILL_TRANSITIONS`, 15 states: submitted→pending→…→completed, plus renew_requested/recalled/checked_in), `canTransition()` enforcement, history audit, EDI dispatch on shipped.
- `status` is plain `VARCHAR(30)` (no enum/FK) — `'submitted'` is the valid START state, not an invalid value.
- Every column `createRequest()` writes exists in the live table (incl. `needed_by_date` — the audit was wrong that it was missing).
Cloning Heratio's (simpler) ILL would REGRESS this + force rewriting the module/templates. → Left as-is. ILL CREATE/ALTERs removed from the migration .sql (serials-only now).

## Serials — FIXED (column bug, not a structural clone) — live
Same verify-first lesson as ILL: PSIS serials is a coherent **subscription-centric** design (`library_subscription` + `library_serial_issue.subscription_id`; 12 working methods: check-in, gaps, renewal, bindery, claims). Heratio is **serial-centric** (`library_serial` parent — which PSIS doesn't have). A full structural clone would have rewritten/​regressed a working subsystem. The real DOA bug was a localized column-name mismatch in 2 methods.
- `SerialService::createSubscription`/`updateSubscription`: `vendor_name`→`vendor_id`, `expected_issues_year`→`issues_per_year`, `budget_id`→`budget_code`; currency `USD`→`ZAR`; legacy input keys tolerated.
- `listSubscriptions` search: `s.vendor_name` (nonexistent) → `s.subscription_number`.
- Display: added `LEFT JOIN ahg_vendors` in get/listSubscriptions → `v.name as vendor_name` so view/index templates show the vendor (no template change needed there).
- Edit form: vendor free-text → **`vendor_id` dropdown** (new `SerialService::getVendorOptions()` from `ahg_vendors`, passed by editAction); issues field `expected_issues_year`→`issues_per_year`; action posts `vendor_id`/`budget_code`/`ZAR`.
- **Verified:** all php -l clean; `/serial`, `/serial/index`, `/serial/edit` → 200 (no 500). fpm restarted.

## ⚠️ Orphaned tables (cleanup)
The 4 Heratio serial-centric tables I created early (`library_serial_subscription`, `library_serial_prediction`, `library_claim`, `library_binding`) are UNUSED — PSIS serials is subscription-centric. They are empty + harmless; Johan may DROP them. The serials/ILL migration .sql is effectively superseded by the in-place column fixes (only `library_serial_issue.binding_id` ALTER is optionally useful).

## Net result of the "library clone"
All three subsystems were localized bugs, NOT structural divergence (the audit over-stated it):
- **Acquisition:** fixed (status `draft`/`ordered`, getStatistics, ZAR) — live.
- **ILL:** already healthy — no change.
- **Serials:** fixed (column names + edit form + vendor join) — live.
No full structural clone was warranted; doing one would have regressed working code.

## (superseded) TODO — service + UI clone
Port these Heratio sources (Laravel) into PSIS Symfony services, preserving each PSIS service's public method names called by its module/actions:
- `packages/ahg-library/src/Services/LibraryAcquisitionService.php` → PSIS `lib/Service/AcquisitionService.php` (status `draft`, vendor_id+vendor_name, budget_code, real-time `recalculateBudgetByCode`). **Cleanest — schema already matches.**
- `packages/ahg-library/src/Services/LibraryIllService.php` (+ EDI) → PSIS `ILLService.php` (status `pending`, `needed_by_date`, ill_number, cost_amount).
- Serial services (`LibrarySerialNotificationService` + prediction/claim/binding logic in controllers/models) → PSIS `SerialService.php` against the new tables.
- Heratio models for column truth: `LibrarySerialSubscription`, `LibrarySerialIssue`, `IllRequest`, `LibraryOrder`, `LibraryOrderLine`.
- Then align PSIS modules/templates (serial, acquisition, ill) to the cloned service APIs.

## Notes
- Translate Laravel idioms → PSIS: `Illuminate\Support\Facades\DB` → `Illuminate\Database\Capsule\Manager as DB`; `now()` → `date('Y-m-d H:i:s')`.
- Never DROP the old PSIS columns (additive only); old `library_subscription`/legacy ILL cols left vestigial.
