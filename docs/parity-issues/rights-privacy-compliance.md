PSIS-parity epic from the 2026-05-31 two-way audit (see docs/HERATIO_PSIS_PARITY_AUDIT.md).

**Domain:** rights-privacy-compliance — current parity ≈ **72%**.

Heratio achieves 72% functional parity with PSIS/AtoM rights-privacy-compliance domain. Core databases and service layers map well (rights, embargo, privacy, security_clearance, audit_trail all present in both). However, Heratio introduces significant UX and security improvements: WebAuthn/FIDO2 passpkeys (high), visual redaction editor for image/PDF privacy (high), cryptographic audit trail chaining (high), and structured GDPR Article 30 export (medium). Enhanced PII scanning (embedded metadata backfill), retention schedule + multi-stage disposal workflow, and per-role MFA policy enforcement are also Heratio-only. AtoM maintains some legacy backward-compat (privacy_breach_incident table) and tighter AI integration (NER-based PII detection via ahgAIPlugin) which Heratio does not yet expose. Nine packages align 1:1 with plugins; NARSSA transfer tracking identical at schema level. Estimated effort to close gaps: WebAuthn ~2-3 weeks, visual redaction ~3-4 weeks, chained audit verification ~2 weeks, disposal workflow UI ~3 weeks.

### High-severity gaps (PSIS missing)
- **WebAuthn/FIDO2 passkey MFA (issue #721)** — `ahg-security-clearance` → `ahgSecurityClearancePlugin`. Heratio has WebAuthnService with full FIDO2/passkey support; AtoM plugin only has TOTP and basic 2FA - ahg_webauthn_credential table and WebAuthnService.php only exist in Heratio
- **Visual redaction editor for privacy (images/PDF)** — `ahg-privacy` → `ahgPrivacyPlugin`. Heratio has visual-redaction-editor.blade.php and VisualRedactionService; AtoM has only PDF/text redaction - no pixel-level image redaction UI
- **Embedded metadata PII scanning** — `ahg-privacy` → `ahgPrivacyPlugin`. Heratio has ScanEmbeddedBackfillCommand, ScanIoCommand, EmbeddedMetadataPiiService; AtoM has privacyScanEmbeddedTask but weaker implementation without backfill
- **Audit trail chaining (cryptographic verification)** — `ahg-audit-trail` → `ahgAuditTrailPlugin`. Heratio has ChainedAuditWriter with install-chain.sql, VerifyChainCommand; AtoM plugin lacks blockchain/chain verification - no cryptographic integrity
- **Retention schedule + disposal workflow (records management)** — `ahg-extended-rights` → `ahgExtendedRightsPlugin`. Heratio has retention_schedule, retention_assignment, disposal_action tables (2026-05-17 migration) with multi-stage signoff; AtoM plugin migration file exists but lacks full workflow UI

### Medium-severity gaps
- Article 30 ROPA export (GDPR compliance) — `ahg-privacy` → `ahgPrivacyPlugin`.
- DPIA with risk scoring — `ahg-privacy` → `ahgPrivacyPlugin`.
- MFA policy enforcement per role (issue #738) — `ahg-security-clearance` → `ahgSecurityClearancePlugin`.
- Watermark application to derivatives (security classification) — `ahg-security-clearance` → `ahgSecurityClearancePlugin`.
- PII scan & redaction review workflow — `ahg-privacy` → `ahgPrivacyPlugin`.

### Remediation (per AHG twin convention)
- **A — port via patches/plugin extension** (preferred): mirror the Heratio package logic into the AtoM plugin.
- **B — new plugin** where no counterpart exists (e.g. MISSING).
- **C — skip/defer** if not required for PSIS market.

Mirror the Heratio implementation under `/usr/share/nginx/heratio/packages/`.