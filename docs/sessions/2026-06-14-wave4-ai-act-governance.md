# Wave 4 — EU AI Act governance layer (2026-06-14)

**Repo:** ArchiveHeritageGroup/atom-ahg-plugins · **Plugin:** ahgAiCompliancePlugin v0.1.0 → **v0.2.0** · **Status:** built + lint-clean, NOT released, needs DDL.

## Context
Wave-4 verify-first sweep (read-only, vs live PSIS) found: **preservation / heritage-accounting / IPSAS = PRESENT** (nothing to build); **backup / observability / pdf-tools = PARTIAL** (specific gaps); **EU AI Act compliance = PARTIAL** — ahgAiCompliancePlugin had only the Article 12 inference receipt chain (`ai_inference_log`), missing the broader governance obligations. Built that gap.

## Built — EU AI Act governance registers
New `aiActGovernance` module in ahgAiCompliancePlugin, complementing the Art. 12 receipts:
- **4 tables** (`database/ai_act_governance.sql`, no ENUMs, no core-table FKs):
  - `ai_act_system` — AI system inventory + risk classification (prohibited/high/limited/minimal), lifecycle, role, human-oversight (Art. 6/14), review dates.
  - `ai_act_model` — model registry: model_id/version/modality/provider, intended purpose, training-data summary (Art. 10), limitations, evaluation, license; logical FK to system.
  - `ai_act_risk` — Article 9 risk register: category, likelihood×severity (1-5), mitigation, residual scores, status, owner, review date.
  - `ai_act_attestation` — conformity/oversight records (Art. 9/13/14/47/48): type, statement, status, attested_by/at (auto-stamped), evidence_url, next review.
- **`lib/Services/AiActGovernanceService.php`** — CRUD for all 4 registers + `dashboardSummary()` rollups (systems-by-risk, open/high risks, overdue attestations & system reviews) + controlled vocabularies + `riskBand()`.
- **`modules/aiActGovernance/actions/actions.class.php`** (`AhgController`, admin-gated) — index dashboard + list/edit/delete for each register (9 actions, POST `form_action`, `return;` after redirect per known gotcha).
- **9 templates** — dashboard + list + edit per register (Bootstrap 5, CSP-safe, `$sf_data->getRaw()`).
- **Routes** under `/admin/ai-act/...`.
- **Config fix:** `ahgAiCompliancePluginConfiguration` now actually connects `routing.load_configuration` and enables its modules (`aiCompliance` + `aiActGovernance`). The prior build defined `routingLoadConfiguration()` but never connected it — so even the existing `/.well-known/ai-inference-pubkey` route never registered. Now fixed.

## Verification
- All 12 files `php -l` clean; 4 CREATE TABLEs; plugin is enabled (`atom_plugin`) + symlinked; `ai_inference_log` sibling present; `ai_act_*` not yet created. `AhgController extends sfActions` confirmed → `sfView::SUCCESS` renders templates.
- No base-AtoM changes. No runtime click-through yet (needs DDL + cache clear).

## Deploy (Johan)
1. DDL: `mysql archive < ahgAiCompliancePlugin/database/ai_act_governance.sql`
2. `sudo rm -rf cache/qubit/prod/* && sudo systemctl restart php8.3-fpm`
3. Visit `/admin/ai-act`. (Optional seed: register models `nomic-embed-text`, `qwen3:14b` + the PSIS AI system.)

## Wave-4 remaining genuine gaps (not yet built)
- Backup: off-site S3/rsync replication, GPG encryption, PITR.
- Observability: DB/query metrics, full OTel exporter.
- PDF tools: standalone `pdftotext` extraction service.
- AI Act follow-up: admin-menu link for `/admin/ai-act` (currently direct-URL + dashboard cross-links only).
