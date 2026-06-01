Heratio-side parity gaps from the 2026-05-31 two-way audit (PSIS/AtoM has these; Heratio does not).

**Domain:** rights-privacy-compliance

### Features to add to Heratio (present in PSIS/AtoM)
- **[low]** Dual embargo/rights_embargo table consolidation option — _PSIS plugin: ahgExtendedRightsPlugin_: AtoM maintains separate embargo and rights_embargo tables with fallback logic in lib/Services/EmbargoService.php; Heratio unified to embargo table only
- **[low]** Legacy privacy_breach_incident table (backward compat) — _PSIS plugin: ahgPrivacyPlugin_: AtoM keeps privacy_breach_incident table in install.sql for legacy intake; Heratio moved fully to privacy_breach table

Mirror the PSIS/AtoM implementation under `/usr/share/nginx/archive/atom-ahg-plugins/`.