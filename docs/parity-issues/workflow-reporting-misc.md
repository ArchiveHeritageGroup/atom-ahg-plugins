PSIS-parity epic from the 2026-05-31 two-way audit (see docs/HERATIO_PSIS_PARITY_AUDIT.md).

**Domain:** workflow-reporting-misc — current parity ≈ **72%**.

12 Heratio <-> AtoM plugin pairs audited across workflow-reporting-misc domain. Observability is entirely missing from PSIS (whole ahg-observability package absent). Workflow domain critically lacks SLA policy enforcement, mailing infrastructure, and scheduled notifications. Reports/statistics have similar coverage but ReportBuilder less comprehensive on AtoM. Version control, multi-tenant, custom fields, forms, GIS, and dedupe implementations substantially aligned. Heritage-Manage and Forms packages weaker on AtoM. Estimated parity 72% for PSIS coverage of Heratio functionality in this domain.

### High-severity gaps (PSIS missing)
- **Observability infrastructure (Prometheus metrics, tracing, APCu/Redis metrics storage)** — `ahg-observability` → `none`. ahg-observability has 15 .php files including MetricsRegistry, TracerProvider, Trace, Prometheus middleware; no corresponding plugin exists in atom-ahg-plugins
- **SLA policies with escalation actions and warning thresholds** — `ahg-workflow` → `ahgWorkflowPlugin`. Heratio ahg_workflow_sla_policy table with warning_days/due_days; AtoM has WorkflowSlaService but minimal implementation vs Heratio's integration with WorkflowService

### Medium-severity gaps
- Mailable notification classes for workflow tasks (Approved, Rejected, Overdue) — `ahg-workflow` → `ahgWorkflowPlugin`.
- Console command: WorkflowNotifyOverdueCommand for scheduled overdue notifications — `ahg-workflow` → `ahgWorkflowPlugin`.
- ReportBuilder service layer with template/section/binding management — `ahg-reports` → `ahgReportBuilderPlugin`.
- Heritage admin layer (contributor marketplace, analytics, embargoes) — `ahg-heritage-manage` → `ahgHeritagePlugin`.

### Remediation (per AHG twin convention)
- **A — port via patches/plugin extension** (preferred): mirror the Heratio package logic into the AtoM plugin.
- **B — new plugin** where no counterpart exists (e.g. MISSING).
- **C — skip/defer** if not required for PSIS market.

Mirror the Heratio implementation under `/usr/share/nginx/heratio/packages/`.