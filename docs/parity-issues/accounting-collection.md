PSIS-parity epic from the 2026-05-31 two-way audit (see docs/HERATIO_PSIS_PARITY_AUDIT.md).

**Domain:** accounting-collection — current parity ≈ **68%**.

Heratio accounting-collection achieves 68% parity with PSIS/AtoM. Major gaps: (1) IPSAS CLI reporting (medium), (2) Insurance/Impairment service methods (high), (3) Heritage Accounting plugin entirely missing (high), (4) AI provenance inference (medium), (5) Vendor CRUD service (medium). Database schemas identical. Spectrum and Loan near-parity.

### High-severity gaps (PSIS missing)
- **Create Insurance Policy** — `ahg-ipsas` → `ahgIPSASPlugin`. AtoM IPSASService.createInsurance(); Heratio method missing.
- **Create Impairment Assessment** — `ahg-ipsas` → `ahgIPSASPlugin`. AtoM IPSASService.createImpairment(); Heratio method missing.
- **Heritage Accounting Multi-Standard** — `N/A` → `ahgHeritageAccountingPlugin`. AtoM has entire plugin; no Heratio equivalent.

### Medium-severity gaps
- IPSAS Report Command — `ahg-ipsas` → `ahgIPSASPlugin`.
- Provenance AI Inference — `ahg-provenance` → `ahgProvenancePlugin`.
- Vendor CRUD Service — `ahg-vendor` → `ahgVendorPlugin`.

### Remediation (per AHG twin convention)
- **A — port via patches/plugin extension** (preferred): mirror the Heratio package logic into the AtoM plugin.
- **B — new plugin** where no counterpart exists (e.g. MISSING).
- **C — skip/defer** if not required for PSIS market.

Mirror the Heratio implementation under `/usr/share/nginx/heratio/packages/`.