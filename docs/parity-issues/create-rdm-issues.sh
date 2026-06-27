#!/usr/bin/env bash
# File the ahgRdmPlugin reverse-port epic + 8 per-phase issues.
# Reverse port: net-new Heratio ahg-rdm (heratio#1337 + heratio#1345) -> AtoM-AHG (PSIS).
# Body-of-record: rdm-port.md (beside this script). Run from anywhere.
set -euo pipefail
cd "$(dirname "$0")"

REPO="ArchiveHeritageGroup/atom-ahg-plugins"

# --- Epic ---------------------------------------------------------------------
EPIC_URL=$(gh issue create --repo "$REPO" \
  --title "EPIC: ahgRdmPlugin — sovereign RDM + POPIA scan (reverse port of Heratio ahg-rdm)" \
  --body-file "rdm-port.md" \
  --label type:enhancement --label category:research --label status:future --label priority:P2)
echo "Epic: $EPIC_URL"
EPIC_NUM="${EPIC_URL##*/}"

mk() { # title, body
  gh issue create --repo "$REPO" \
    --title "$1" \
    --body "$2"$'\n\n'"Parent: #${EPIC_NUM}. Spec: docs/parity-issues/rdm-port.md. Heratio source: heratio#1337 (Features 1-3) + heratio#1345 (dashboard filters)." \
    --label type:enhancement --label category:research --label status:future --label priority:P3
}

# --- Per-phase issues (mirror the Heratio epic order; lowest risk first) ------
mk "[ahgRdmPlugin] Phase 1 — scaffold plugin + Dataset model + deposit (AtoM IngestService)" \
"Scaffold \`ahgRdmPlugin\` (Symfony 1.4 modules/actions/templates + lib/Services, ref ahgIngestPlugin/ahgResearchPlugin shape). Sidecar tables via idempotent install SQL (CREATE TABLE IF NOT EXISTS): rdm_dataset, rdm_dataset_file, rdm_scan_finding + dropdowns (dataset_status, rdm_disposition) — NEVER ALTER a Qubit base table, NEVER MySQL ENUM. DatasetService: create container IO + per-file deposit via AtoM IngestService. Gotcha: force-commit the Propel PDO transaction in CLI/cron or digital_object inserts roll back at process exit."

mk "[ahgRdmPlugin] Phase 2 — PopiaScanService (deterministic + lexicon + gateway NER, async task)" \
"Deterministic-first: SA ID (Luhn+date), email, SA phone, passport — samples MASKED. Special-category lexicon. AI-suggested NER via ai.theahg.co.za gateway ONLY (never a direct GPU node port), quota-guarded. Async via an AtoM job/symfony task (NER exceeds request limits). Verdict roll-up CLEAR/PERSONAL/SPECIAL_CATEGORY."

mk "[ahgRdmPlugin] Phase 3 — human gate + provenance (release blocked until resolved)" \
"PopiaGateService: open 'release' BLOCKED while any PERSONAL/SPECIAL finding is pending or confirmed. Finding resolve actions (confirm/dismiss + decision_note, reviewed_by/_at). Provenance via AiDisclosure (ahgAIPlugin/ahgAiCompliancePlugin)."

mk "[ahgRdmPlugin] Phase 4 — access/embargo (ODRL) + DOI + public landing" \
"DatasetReleaseService: disposition as ODRL prohibition (restrict/de-identify) / embargo (date_to) on container + child IOs (ahgResearchPlugin/ahgRightsPlugin/ahgExtendedRightsPlugin). DOI minted for ANY disposition via ahgDoiPlugin (dry-run off-prod). Public no-auth landing /research/datasets/{id}/landing: DataCite-style citation, DOI, access badge; binaries STAY gated."

mk "[ahgRdmPlugin] Phase 5 — compliance scoreboard (ComplianceReportService)" \
"Per-dataset compliance scoreboard, filterable by institution/verdict/disposition. Mirrors Heratio ComplianceReportService (Feature 2)."

mk "[ahgRdmPlugin] Phase 6 — synthetic demo task (php symfony rdm:demo --fresh)" \
"Port ahg:rdm-demo to an AtoM symfony task on 100%-synthetic assets (Luhn-valid fake SA IDs, health transcript, consent PDF, clean climate set). Acceptance: ~17 findings, clean files CLEAR, open release blocked -> restrict -> DOI minted -> landing + scoreboard + dashboard + DMP linked."

mk "[ahgRdmPlugin] Phase 7 — Feature 1: DMP link (wire AtoM DmpService)" \
"DmpLinkService: context/link/createAndLink/unlink over the existing ahgResearchPlugin DmpService — DMP builder ALREADY exists on AtoM, never rebuild. Writes only rdm_dataset.dmp_id; never touches research_dmp* except through DmpService. DMP is project-scoped + advisory (not a hard release gate). Show/landing/compliance surfaces gain the DMP card."

mk "[ahgRdmPlugin] Phase 8 — Feature 3: dashboard + filters (Chart.js)" \
"DashboardService: KPI roll-up + verdict/disposition/method/type breakdowns + 12-month deposit trend + per-faculty posture + human-gate backlog + recent. from/to/institution filters resolved to ONE dataset-id set scoping every aggregate; trend honours institution only. Chart.js 4.4 via jsDelivr (same CDN as ahgAIPlugin donut dashboards, CSP-consistent). Add an 'RDM' entry to the AtoM reports/research menu."

echo "Done. Epic #${EPIC_NUM} + 8 phase issues filed."
