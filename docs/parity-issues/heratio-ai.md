Heratio-side parity gaps from the 2026-05-31 two-way audit (PSIS/AtoM has these; Heratio does not).

**Domain:** ai

### Features to add to Heratio (present in PSIS/AtoM)
- **[medium]** SaaS API Tier Management — _PSIS plugin: ahgAiConditionPlugin_: ahg_ai_service_client table with tier, monthly_limit
- **[medium]** Training Data Upload Workflows — _PSIS plugin: ahgAiConditionPlugin_: executeApiTrainingUpload, consent docs, approval flows
- **[low]** Specialized Evidence Evaluators — _PSIS plugin: ahgAuthorityResolutionPlugin_: 12+ evaluators: EvidenceDateUtil, RelationalEvaluator, TemporalEvaluator

Mirror the PSIS/AtoM implementation under `/usr/share/nginx/archive/atom-ahg-plugins/`.