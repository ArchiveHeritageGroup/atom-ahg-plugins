# Security scan + remediation - 2026-07-12

**Scope:** PSIS / archive (atom-framework + atom-ahg-plugins). Base AtoM files (`apps/`, `lib/`, `plugins/`, `vendor/`) are locked and were NOT modified - base findings were compiled for coordinated disclosure to Artefactual instead.

## Outcome

Full security scan completed. Our-code findings triaged into CRITICAL / HIGH / MEDIUM and remediated across a series of gated releases. All fixes lint-clean, mirrored to the archaeology instance, caches cleared, php-fpm restarted, and verified live where feasible.

| Tier | Count | Releases |
|------|-------|----------|
| CRITICAL | 4 | plugins v3.79.70 |
| HIGH | 11 | plugins v3.79.71, v3.79.72 |
| MEDIUM | 15 | plugins v3.79.73-75, framework v2.13.18 |
| Base AtoM (disclosure only) | 9 | Artefactual DOCX (not code) |

## Themes addressed

- **Authorization gaps:** several AHG plugin modules lacked a `config/security.yml` (or used `is_secure: false`) while relying on partial inline checks. Fixed via module `security.yml` + `AhgController` boot() gates + `AclService::check()` on object-scoped actions (settings, loan, gallery, reports, feedback, reportBuilder API, apiv2 descriptions, accessRequest approve/deny).
- **IDOR / cross-tenant:** owner/researcher-scoped checks added to research ODRL/bibliography/hypothesis/report actions, favorites send-to-collection/project/bibliography, custom-field save, ingest manifest download.
- **Stored/reflected XSS:** output escaping on exhibition search, request-to-publish submission fields, annotations.
- **Crypto & secrets:** Argon2id in StandaloneUserWriteService, CSPRNG for generated passwords, hardened key derivation (LlmService, OrcidService fail-closed).
- **Open redirect:** local-path-only guard on login + security-clearance return URLs.
- **Privilege escalation:** data-migration admin gating; report/export draft + PII gating.
- **File-upload validation (M3):** routed 6 upload sites through `AtomExtensions\Services\FileValidationService` (extension allowlist + magic-byte MIME check + size cap + filename sanitization) - condition photos (API multipart + base64), provenance documents, accession attachments, donor-agreement logo and documents, condition annotations. Reference implementation: `ahgAPIPlugin` apiv2 `fileUploadAction`.

## Deferred (own follow-up task)

- **M12 - framework CSRF in the Symfony request path:** enforcing CSRF globally is a per-form behavioural change (many existing POST actions carry no token) and must be rolled out deliberately with per-form testing, not bundled into the scan cleanup.
- ~10 LOW-severity our-code findings.

## Base-AtoM disclosure

9 findings (XXE, missing REST ACLs, finding-aid auth gaps, CSRF, draft leaks, unsafe unserialize/shell) compiled into a coordinated-disclosure report for Artefactual. Base AtoM remains unmodified per the hard-lock rule.
