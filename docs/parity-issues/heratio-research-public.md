Heratio-side parity gaps from the 2026-05-31 two-way audit (PSIS/AtoM has these; Heratio does not).

**Domain:** research-public

### Features to add to Heratio (present in PSIS/AtoM)
- **[high]** Password Reset & Account Recovery Workflow — _PSIS plugin: ahgResearchPlugin_: PasswordResetRequest, PasswordReset, AdminResetPassword actions in AtoM. These methods exist in AtoM ResearchActions but are NOT in Heratio ResearchController; likely delegated to app-wide auth instead.

Mirror the PSIS/AtoM implementation under `/usr/share/nginx/archive/atom-ahg-plugins/`.