# 2026-07-03 — Portable export #1389 disclosure gate (Heratio → PSIS)

Release: atom-ahg-plugins v3.79.46. Both instances (archive + archaeology).

## What
Ported Heratio's #1389 disclosure gate to the PSIS ahgPortableExportPlugin — a
confidentiality safeguard for OFFLINE portable packages (over-inclusion into an
ungated bundle is unrecoverable). Before anything is built, it excludes + counts:
- unpublished (status type 158 / status 160) unless portable_export_include_unpublished on
- ICIP/TK restricted (icip_access_restriction, incl. applies_to_descendants subtrees)
- ODRL use-prohibited (research_rights_policy action_type=use, policy_type=prohibition)

Records what was withheld in a new `disclosure_summary` column + a
`data/disclosure-summary.json` inside the bundle, and shows a "N withheld" shield
badge in the export list.

## Files
- NEW lib/Services/DisclosureGate.php (filter/getExcluded/icipRestrictedIds/odrlRestrictedIds; fail-closed; Capsule DB; ported from AhgCore\Services\DisclosureGate)
- CatalogueExtractor.php — applies gate right after scope resolution in extractDescriptions(), exposes getDisclosureExcluded()
- ExportPipelineService.php — writes disclosure-summary.json + stampDisclosureSummary() (try/catch tolerant of pre-migration schema)
- indexSuccess.php — withheld shield badge
- database/migration_disclosure_summary.sql — ADD COLUMN disclosure_summary (guarded) + portable_export_include_unpublished setting (default false)

## Verified
DisclosureGate::filter on all 738 PSIS IOs: 691 kept, 47 unpublished withheld;
0 ICIP and 0 ODRL restricted ids leaked into kept; counts reconcile exactly.
Migration applied on both DBs (column present, setting=false).

## Notes
- Gate wired into the CatalogueExtractor (viewer read_only/editable modes). Archive
  mode uses ArchiveExtractor — not gated in this pass (re-importable data export,
  different risk profile); a follow-up could gate it too.
- Code is safe before the migration (gate still filters, json still written; only
  the operator-view column stays empty).
