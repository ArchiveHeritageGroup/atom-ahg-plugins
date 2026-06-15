# #152 — Provenance & authenticity layer: authenticity report — 2026-06-15

**Issue:** #152 (twin: C2PA-signed digitisation + inference provenance). **Plugin:** ahgProvenancePlugin. **Status:** authenticity-report slice built + verified live, unreleased. Issue STAYS OPEN (C2PA digital-object DO-signing wiring deferred).

## Context — most of #152 was already built
- `ahgC2paPlugin` exists (C2paSigner / Claim / ManifestBuilder / CborEncoder); `c2patool` at `/usr/local/bin/c2patool`; `ahg_c2pa_manifest` table present (information_object_id col, 0 rows).
- `ahg_ai_inference` table + `ahgProvenancePlugin\Service\InferenceService` already record + Ed25519-sign every AI inference (6 rows; target_entity_type='information_object').
- The genuine clean/verifiable gap was the issue's own words: **surface that provenance as an authenticity report**.

## Delivered
- `InferenceService::authenticityForObject(int $ioId)` — read-only dossier: every AI inference touching the record's fields (model name@version, confidence, occurred_at) each with an Ed25519 **verdict** (verified / signed / tampered / unsigned, verified against the current signing key), plus any `ahg_c2pa_manifest` content credentials bound to the record's digital objects. Self-contained: missing table / absent key → that section reports empty/unverifiable, never throws.
- `provenanceActions::executeAuthenticity` (requireAuth) → route `provenance_authenticity` → `/provenance/authenticity/:id`.
- `authenticitySuccess.php` — summary cards (inferences / signed / verified / C2PA), AI-inference table with verdict badges + signer-key id, C2PA content-credentials table, legend explaining the verdicts.
- Discoverability: "Authenticity Report" button on the provenance `viewSuccess.php` page.

## Verified
- All `php -l` clean.
- CLI on IO **902722**: summary `{inferences:3, signed:0, verified:0, c2pa:0}` — 3 inferences surfaced correctly (spaCy en_core_web_sm@3.7.1 conf 94% / bart-large-cnn@1.0 / qwen3:8b on scope_and_content + archival_history); all `unsigned` because the Ed25519 keypair is not yet minted on PSIS (`ai-provenance:keygen` unrun) — correct behaviour.
- HTTP `/provenance/authenticity/902722` → **302** (auth gate, route resolves, no 500).

## Deferred (keep #152 open)
- **C2PA digital-object signing wiring** — actually invoking `c2patool` to sign derivatives at digitisation/ingest time and populating `ahg_c2pa_manifest`. The plumbing (C2paSigner, c2patool, table) is present; the ingest/derivative hook that signs + records is not wired. The authenticity report already renders those rows the moment they exist.
- To light up the `verified` verdict on PSIS: run `php symfony ai-provenance:keygen` once (mints the Ed25519 keypair); subsequent inferences sign + verify.
