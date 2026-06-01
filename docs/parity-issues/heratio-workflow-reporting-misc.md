Heratio-side parity gaps from the 2026-05-31 two-way audit (PSIS/AtoM has these; Heratio does not).

**Domain:** workflow-reporting-misc

### Features to add to Heratio (present in PSIS/AtoM)
- **[medium]** WorkflowBulkService with bulk transition/assign/note/priority operations — _PSIS plugin: ahgWorkflowPlugin_: AtoM ahgWorkflowPlugin/lib/Services/WorkflowBulkService.php with bulkTransition/bulkAssign methods; Heratio WorkflowService lacks dedicated bulk operations layer

Mirror the PSIS/AtoM implementation under `/usr/share/nginx/archive/atom-ahg-plugins/`.