PSIS-parity epic from the 2026-05-31 two-way audit (see docs/HERATIO_PSIS_PARITY_AUDIT.md).

**Domain:** ai — current parity ≈ **55%**.

AI domain shows 55% parity. Heratio leads in governance (compliance, provenance, receipts), chatbot, DONUT, cost management. AtoM excels in condition assessment SaaS tiers, granular damage tracking, evidence evaluation. Core NER/HTR/authority resolution exist on both with architectural differences. Major PSIS gaps: no chatbot, no receipt library, minimal compliance, no DONUT, simplified HTR. Major Heratio gaps: no SaaS tiers, fewer evaluators, no training workflows. Both implement shared services (LLM, Guardrail, NER) differently.

### High-severity gaps (PSIS missing)
- **AI Chatbot (RAG-grounded chat engine)** — `ahg-ai-chatbot` → `None`. ChatbotService, QdrantRetriever, WhatsAppChannel; AtoM has no plugin
- **Inference Receipt Chain** — `ahg-inference-receipts` → `ahgAiCompliancePlugin`. Receipt, Signer, ReceiptChain, KeyPair with JCS+Ed25519; AtoM stores only
- **EU AI Act Compliance Framework** — `ahg-ai-compliance` → `ahgAiCompliancePlugin`. OversightService, AiRiskService, models; AtoM lacks services
- **AI Governance Dashboard** — `ahg-provenance-ai` → `ahgProvenancePlugin`. GovernanceController, /admin/governance routes; AtoM lacks these
- **DONUT (Document Understanding)** — `ahg-ai-services` → `None`. DonutService with extract/batch/training; AtoM absent

### Medium-severity gaps
- HTR Advanced Features — `ahg-ai-services` → `ahgAIPlugin`.
- Translation Memory Management — `ahg-ai-services` → `None`.
- Cost & Quota Management — `ahg-ai-services` → `None`.
- LLM Config Admin UI — `ahg-ai-services` → `ahgAIPlugin`.

### Remediation (per AHG twin convention)
- **A — port via patches/plugin extension** (preferred): mirror the Heratio package logic into the AtoM plugin.
- **B — new plugin** where no counterpart exists (e.g. MISSING).
- **C — skip/defer** if not required for PSIS market.

Mirror the Heratio implementation under `/usr/share/nginx/heratio/packages/`.