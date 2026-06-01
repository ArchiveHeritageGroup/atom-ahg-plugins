Heratio-side parity gaps from the 2026-05-31 two-way audit (PSIS/AtoM has these; Heratio does not).

**Domain:** descriptive-manage

### Features to add to Heratio (present in PSIS/AtoM)
- **[low]** Accession Intake Queue View (with Dashboard stats) — _PSIS plugin: ahgAccessionManagePlugin_: AtoM accessionManage has executeDashboard() and getQueueStats() in dashboard action. Heratio has a standalone intakeQueue() and dashboard() methods but AtoM integrates them.

Mirror the PSIS/AtoM implementation under `/usr/share/nginx/archive/atom-ahg-plugins/`.