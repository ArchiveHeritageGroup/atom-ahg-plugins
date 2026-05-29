# Heratio → PSIS port — atom-ahg-plugins v3.45.0

- **Date:** 2026-05-29
- **Repo / release:** `ArchiveHeritageGroup/atom-ahg-plugins` v3.44.8 → **v3.45.0** (commit `d31a3e67`, pushed to `origin/main`)
- **Goal:** Reflect the Heratio (Laravel) 3-day development window onto the PSIS Symfony AtoM plugins.
- **Approach:** **Ported, not copied.** Heratio is Laravel; PSIS is Symfony 1.x AtoM — a byte-for-byte clone is impossible and against the standing "never copy Heratio Laravel into archive" rule. Each feature was reimplemented as Symfony plugin code, **reuse-first** (extend existing services/tables; never duplicate). Verified per slice (lint + functional tests against the live `archive` DB / real data).

## What shipped (5 plugins)

### ahgLibraryPlugin
- **MARC21 binary decode** — new `Marc21DecoderService` (pure ISO 2709) + `MarcService::importMarc21()`/`parseDecodedRecord()` reusing the existing MARCXML import pipeline + the new static `MarcService::parseMarc21()` bridge. The bridge also **fixed a latent bug**: `Z3950Service::importResults()` called `MarcService::parseMarc21()`, which never existed — the Z39.50 import path was silently broken.
- **Subject Authority Control** — new `AuthorityControlService` + `authorityControl` module (CRUD/link/unlink/search) + routes. (Fixed the Heratio `linkToItem` swapped-where bug in the port.)
- **EDI/EANCOM trading partners** — `EdiAdapter` (EANCOM S93/S94, X12 850, CUSTOM; SFTP/AS2/HTTP/EMAIL/MANUAL) + `tradingPartner` CRUD module. Guzzle present (AS2/HTTP live); phpseclib absent (SFTP degrades gracefully).
- **Copy cataloguing** — `copyCataloguing` workflow module reusing the **existing** `AtomExtensions\Services\Z3950Service` (search + importResults) — no new Z39.50 engine.
- **COUNTER R5 email** — `library:email-usage-reports` task reusing the **existing** `LibraryCounterService` (PR/TR_J1/TR_J3/DR/IR + JSON/TSV). Heratio's parallel `LibraryUsageService`/tables were deliberately NOT ported (PSIS already had COUNTER R5).
- **OPAC ES relevance** — `OpacService` now uses AtoM's **existing** `archive_qubitinformationobject` ES index for free-text relevance (+ `FIELD()` ordering, MySQL fallback) and extends `getFacets()` to 6 dimensions. No new ES index/pipeline.

### ahgResearchPlugin
- **ORCID self-service** (resolves twin **atom-ahg-plugins#102**) — extended the existing `OrcidService` with per-researcher OAuth credentials (`researcher_orcid_credential`, secret encrypted via existing `encryptToken`), tokenless public-record fetch + `pullProfile`, hub + credentials UI, rate-guarded fetch action. Global-config fallback keeps the existing flow intact.

### ahgPrivacyPlugin
- **Embedded-metadata PII + GPS gate (#751)** — `EmbeddedMetadataPiiService` (scans `digital_object_metadata`/`dam_iptc_metadata` for GPS/persons/contact → `ahg_pii_finding_embedded`) + **`EmbeddedMetadataPiiGate`** (GPS redaction, fail-open). The gate is the shared dependency reused by #750 and C2PA. `privacy:scan-embedded` task + review UI.

### ahgMetadataExportPlugin
- **IPTC fallback (#752)** — `IptcFallbackResolver` (ISAD-wins, falls back to `dam_iptc_metadata`, audited to `ahg_error_log`), wired into `SchemaOrgExporter`.
- **C2PA content credentials (#749/#753)** — greenfield `lib/C2pa/` bundle: `JcsEncoder` (RFC 8785), `CborEncoder`, `C2paKeyPair`/`C2paSigner` (Ed25519 via ext-sodium), `Assertion`/`Claim`/`ManifestBuilder`, `StandardMetadataLoader` (stds.exif/iptc/xmp, GPS-gated by #751), `C2paManifestService` + `c2pa:sign`/`c2pa:generate-key` tasks. Verified: deterministic JCS, sign→verify VALID, tamper/wrong-key→INVALID.

### ahgAIPlugin
- **AI embedded context (#750)** — `AhgAiContextHints` + `AhgEmbeddedMetadataContextService` (capture-date/place/creator/subjects from EXIF/IPTC, **fail-safe GPS gating** via the #751 gate), wired into `ahgNerService::extractWithContext()` (non-breaking).

## Migrations (applied 2026-05-29)
- `ahgLibraryPlugin/database/migration_library_rda_authority_edi_20260529.sql`
- `ahgResearchPlugin/database/migrations/2026_05_29_orcid_self_service.sql`
- `ahgPrivacyPlugin/database/migrations/2026_05_29_pii_embedded.sql`
- `ahgMetadataExportPlugin/database/migrations/2026_05_29_c2pa_manifest.sql`

C2PA signing additionally requires `php symfony c2pa:generate-key` → store the secret in `ahg_settings` (`c2pa_secret_key`).

## Deliberately NOT ported
- **Spectrum** — PSIS `ahgSpectrumPlugin` already exceeds the Heratio controller (48 actions, 38 routes, GRAP/privacy/annotations modules Heratio lacks). The Heratio 3-day Spectrum work was a Laravel-only repair of regression #91; no Symfony counterpart gap.
- **MFA login fix** — Heratio-only regression (webauthn/totp tables); PSIS has no such tables, nothing to port.

## Notes / gotchas
- The `atom-ahg-plugins` checkout was `root:root`; `./bin/release` (SSH remote) must run as `johanpiet`. Fix applied: `sudo chown -R johanpiet:www-data /usr/share/nginx/archive/atom-ahg-plugins` before release (world/group read preserved → php-fpm still reads).
- Two pre-existing library WIP files were parked via `git stash push -- <paths>` so the release didn't sweep them, then restored.
