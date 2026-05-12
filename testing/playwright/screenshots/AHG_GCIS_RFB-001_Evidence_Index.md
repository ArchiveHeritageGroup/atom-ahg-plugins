# GCIS RFB-001 2026/2027 — Evidence Index

**AtoM-Aligned Plan · Gate 1 Criterion 2 · The Archive and Heritage Group (Pty) Ltd · 2026-05-12**

> 55 screenshots covering **34** tender clauses. **49** mandatory (S1–S49), **6** supplementary (X1–X6) demonstrating "exceeds expectations" capability. Captured live from `https://psis.theahg.co.za`; non-page artefacts (CLI, schema diagrams, vendor docs) rendered as styled evidence pages.

The pack is organised by tender clause. Each entry includes the screenshot code, the clause reference and a short description of what the screenshot demonstrates. Screenshots are also delivered as individual PNG files under `atom-ahg-plugins/testing/playwright/screenshots/`.

## Clause-to-screenshot map

| Clause | Coverage | Screenshot codes |
|---|---|---|
| 4.1.1.1 | SharePoint auto-ingest rules  (+ 2 more) | S1, S2, S3 |
| 4.1.1.2 | Record sourced from SharePoint | S4 |
| 4.1.1.3 | Authenticated retrieval  (+ 3 more) | S5, S6, S7, S8 |
| 4.1.1.4 | Metadata cross-reference SP ⇄ AtoM | S9 |
| 4.1.1.5 | Ingest wizard  (+ 1 more) | S10, S11 |
| 4.1.1.6 | Retention-label triggered archival (+ 1 more) | S12, S13 |
| 4.1.1.7 | Dublin Core fields on record edit (+ 2 more) | S14, S15, S16 |
| 4.1.1.8 | Taxonomy import  (+ 1 more) | S17, S18 |
| 4.1.1.9 | Subject access points (tagging) (+ 2 more) | S19, S20, S21 |
| 4.1.1.10.a | Full-text search (+ 1 more) | S22, S23 |
| 4.1.1.10 | Federated search | S24 |
| 4.1.1.10.b | Quick retrieval | S25 |
| 4.1.1.11 | Record link to active SharePoint item | S26 |
| 4.1.1.12.a | RBAC groups list (+ 1 more) | S27, S28 |
| 4.1.1.12.b | TLS encryption in transit (manual) (+ 1 more) | S29, S30 |
| 4.1.1.12.c | MISS classification on record edit (+ 1 more) | S31, S32 |
| 4.1.1.12.d | Records management admin dashboard | S33 |
| 4.1.1.12.e | Time-limited share-link  (+ 1 more) | S34, S35 |
| 4.1.1.12.f | Audit trail  (+ 1 more) | S36, S37 |
| 4.1.1.13.a | Retention / Rights dashboard (+ 1 more) | S38, S39 |
| 4.1.1.13.b | Embargo / disposal review queue (+ 1 more) | S40, S41 |
| 4.1.1.14.c | POPIA / PAIA dashboard (+ 1 more) | S42, S43 |
| 4.1.1.14.e | Audit statistics | S44 |
| 4.1.1.14.f | Per-user activity report | S45 |
| 4.1.1.14.g | Metadata integrity verification | S46 |
| 4.1.1.14.h | Lifecycle compliance | S47 |
| clause 2 + 4.1.3.1 | Multi-tenant "one instance" model | S48 |
| 4.1.3.5 | AHG settings | S49 |
| exceeds | AHG plugin catalogue | X1 |
| exceeds (OAIS) | OAIS-aligned SIP / AIP / DIP package | X2 |
| exceeds (IIIF) | IIIF viewer for digitised content | X3 |
| exceeds (API) | Webhook delivery log | X4 |
| exceeds (UX) | Responsive / mobile view | X5 |
| exceeds (backup) | Backup & restore admin | X6 |

\newpage

## S1 — 4.1.1.1 — SharePoint auto-ingest rules — list view

Operator-managed inventory of SharePoint drive sweep rules. Each row shows the drive, the file/path pattern, the cron schedule and the count of items ingested on the last run. Demonstrates automation, not manual upload.

![S1](/usr/share/nginx/archive/atom-ahg-plugins/testing/playwright/screenshots/S1-sharepoint-rules-list.png)

`S1-sharepoint-rules-list.png`

## S2 — 4.1.1.1 — SharePoint rule — edit form

Per-rule configuration: target drive, folder path, glob pattern, Microsoft Purview retention-label filter, mapping template, cron schedule. Mapping templates link Purview labels to AtoM dispositions.

![S2](/usr/share/nginx/archive/atom-ahg-plugins/testing/playwright/screenshots/S2-sharepoint-rule-edit.png)

`S2-sharepoint-rule-edit.png`

## S3 — 4.1.1.1 — Live auto-ingest CLI evidence

Console output from `php symfony sharepoint:auto-ingest --rule=1` showing 12 new items found, queued as job 991, no throttling, 4.8 s duration. Proves the workflow runs end-to-end.

![S3](/usr/share/nginx/archive/atom-ahg-plugins/testing/playwright/screenshots/S3-sharepoint-auto-ingest-cli.png)

`S3-sharepoint-auto-ingest-cli.png`

## S4 — 4.1.1.2 — Record sourced from SharePoint

AtoM information-object detail page for a record whose payload originated in SharePoint Online. Sidebar shows `sp_drive_id`, `sp_item_id` and a back-link to the live SP item.

![S4](/usr/share/nginx/archive/atom-ahg-plugins/testing/playwright/screenshots/S4-record-from-sharepoint.png)

`S4-record-from-sharepoint.png`

## S5 — 4.1.1.3 — Authenticated retrieval — login screen

AtoM login form (email + password). Demonstrates that record retrieval is gated by authentication. Combined with TLS evidence (S29), satisfies the secure-retrieval clause.

![S5](/usr/share/nginx/archive/atom-ahg-plugins/testing/playwright/screenshots/S5-secure-retrieval-login.png)

`S5-secure-retrieval-login.png`

## S6 — 4.1.1.3 — Record view with version-history banner

Top of an information-object view page showing the **"Version history (1)"** badge and **"Share this record"** button injected by the AHG plugins. Tracking + sharing surface alongside the standard AtoM chrome.

![S6](/usr/share/nginx/archive/atom-ahg-plugins/testing/playwright/screenshots/S6-record-tracking-tab.png)

`S6-record-tracking-tab.png`

## S7 — 4.1.1.3 — Version diff — word-level

Side-by-side comparison of versions 3 and 5 of a record. Word-level LCS diff with green inserts and red strikethrough deletions per field. Powered by `AhgVersionControl\Services\DiffComputer`.

![S7](/usr/share/nginx/archive/atom-ahg-plugins/testing/playwright/screenshots/S7-version-diff.png)

`S7-version-diff.png`

## S8 — 4.1.1.3 — Version restore — admin entry

Version-history page with a "Restore this version" button. Restoring creates a new version row tagged `is_restore=1` so the history itself is not lost.

![S8](/usr/share/nginx/archive/atom-ahg-plugins/testing/playwright/screenshots/S8-version-restore.png)

`S8-version-restore.png`

## S9 — 4.1.1.4 — Metadata cross-reference SP ⇄ AtoM

Information-object record with a sidebar block linking back to the active source in SharePoint. Cross-reference fields persist on both sides so neither store loses provenance.

![S9](/usr/share/nginx/archive/atom-ahg-plugins/testing/playwright/screenshots/S9-metadata-linkage.png)

`S9-metadata-linkage.png`

## S10 — 4.1.1.5 — Ingest wizard — step 2 (upload)

Six-step OAIS-aligned ingest wizard. Step 2 accepts CSV, ZIP, EAD or a "From SharePoint" tab; downstream steps handle field mapping, validation, preview and commit.

![S10](/usr/share/nginx/archive/atom-ahg-plugins/testing/playwright/screenshots/S10-batch-upload-wizard.png)

`S10-batch-upload-wizard.png`

## S11 — 4.1.1.5 — Ingest sessions index

List of ingest sessions with status, sector, record count, started-by and duration. Click into a session to see its job progress + commit log.

![S11](/usr/share/nginx/archive/atom-ahg-plugins/testing/playwright/screenshots/S11-ingest-job-status.png)

`S11-ingest-job-status.png`

## S12 — 4.1.1.6 — Retention-label triggered archival

Auto-ingest rule edit form showing the "Only items carrying specific Purview retention label(s)" filter. Drives Purview-label-driven archival without operator intervention.

![S12](/usr/share/nginx/archive/atom-ahg-plugins/testing/playwright/screenshots/S12-retention-trigger-rule.png)

`S12-retention-trigger-rule.png`

## S13 — 4.1.1.6 — AtoM v2 REST API — endpoint inventory

Full inventory of the v2 API: descriptions CRUD, search, conditions, assets, privacy DSARs, webhooks, API key management. Authenticated via `X-API-Key` header.

![S13](/usr/share/nginx/archive/atom-ahg-plugins/testing/playwright/screenshots/S13-api-documentation.png)

`S13-api-documentation.png`

## S14 — 4.1.1.7 — Dublin Core fields on record edit

All 15 Dublin Core elements rendered on the AtoM information-object edit form (identity, context, content, provenance & rights). Persisted to `information_object_i18n`.

![S14](/usr/share/nginx/archive/atom-ahg-plugins/testing/playwright/screenshots/S14-dublin-core-fields.png)

`S14-dublin-core-fields.png`

## S15 — 4.1.1.7 — Custom Fields administration

GCIS-specific custom fields (Directorate code, file plan classification, originating department, retention category). Configured at `/admin/customFields` via the EAV plugin — no code changes per field.

![S15](/usr/share/nginx/archive/atom-ahg-plugins/testing/playwright/screenshots/S15-custom-fields-admin.png)

`S15-custom-fields-admin.png`

## S16 — 4.1.1.7 — Custom field on a record edit form

The "GCIS Governance" and "Compliance" custom-field sections rendered alongside the standard ISAD(G) / Dublin Core areas on a record-edit form. Values persist to `custom_field_value`.

![S16](/usr/share/nginx/archive/atom-ahg-plugins/testing/playwright/screenshots/S16-custom-field-on-record.png)

`S16-custom-field-on-record.png`

## S17 — 4.1.1.8 — Taxonomy import — file-plan tree

AtoM taxonomy view of an imported file plan hierarchy. Each node maps to GCIS directorate / function / record category and is selectable as the parent of an information object.

![S17](/usr/share/nginx/archive/atom-ahg-plugins/testing/playwright/screenshots/S17-gcis-file-plan-taxonomy.png)

`S17-gcis-file-plan-taxonomy.png`

## S18 — 4.1.1.8 — Record placed under a file-plan node

Information-object detail page showing the record's placement under its assigned file plan category in the hierarchy sidebar.

![S18](/usr/share/nginx/archive/atom-ahg-plugins/testing/playwright/screenshots/S18-records-by-file-plan.png)

`S18-records-by-file-plan.png`

## S19 — 4.1.1.9 — Subject access points (tagging)

Record view with subject access points (controlled-vocabulary tags) listed in the right sidebar. Tags are indexed in Elasticsearch and faceted on search results.

![S19](/usr/share/nginx/archive/atom-ahg-plugins/testing/playwright/screenshots/S19-record-tags.png)

`S19-record-tags.png`

## S20 — 4.1.1.9 — Elasticsearch search results — facets visible

Search results page showing faceted filters on the left (level of description, repository, date, type) and ranked hits. Demonstrates working ES indexing.

![S20](/usr/share/nginx/archive/atom-ahg-plugins/testing/playwright/screenshots/S20-elasticsearch-indexing.png)

`S20-elasticsearch-indexing.png`

## S21 — 4.1.1.9 — Version control — numbered version list

Version history page for a record: numbered versions with timestamps, authors, summaries and "View" / "Compare" actions. List view drives the diff (S7) and restore (S8) flows.

![S21](/usr/share/nginx/archive/atom-ahg-plugins/testing/playwright/screenshots/S21-version-control-list.png)

`S21-version-control-list.png`

## S22 — 4.1.1.10.a — Full-text search

Search results matching a term that appears inside an OCR'd body, not just metadata. Proves the search is full-text against Elasticsearch, not table-scan.

![S22](/usr/share/nginx/archive/atom-ahg-plugins/testing/playwright/screenshots/S22-full-text-search.png)

`S22-full-text-search.png`

## S23 — 4.1.1.10.a — Advanced search — multi-filter form

Advanced search form with combined filters: query, date range, level of description, repository, digital-object presence, classification.

![S23](/usr/share/nginx/archive/atom-ahg-plugins/testing/playwright/screenshots/S23-advanced-search-filters.png)

`S23-advanced-search-filters.png`

## S24 — 4.1.1.10 — Federated search — AtoM + SharePoint (exceeds)

Single search box returning ranked hits from **AtoM (archived)** and **SharePoint Online (active)** with source-attribution badges and dedupe pills. Powers the "one search, two stores" experience.

![S24](/usr/share/nginx/archive/atom-ahg-plugins/testing/playwright/screenshots/S24-federated-search.png)

`S24-federated-search.png`

## S25 — 4.1.1.10.b — Quick retrieval — response time visible

Search results header showing "12 results in 80 ms" — demonstrates sub-second retrieval performance at typical collection sizes.

![S25](/usr/share/nginx/archive/atom-ahg-plugins/testing/playwright/screenshots/S25-quick-retrieval.png)

`S25-quick-retrieval.png`

## S26 — 4.1.1.11 — Record link to active SharePoint item

Information-object sidebar with a "View active record in SharePoint" button that deep-links to the live SP item. Round-trips Purview-managed active records to AtoM-managed archived records.

![S26](/usr/share/nginx/archive/atom-ahg-plugins/testing/playwright/screenshots/S26-link-to-active-record.png)

`S26-link-to-active-record.png`

## S27 — 4.1.1.12.a — RBAC groups list

AtoM ACL groups: Administrator, Editor, Contributor, Translator plus AHG additions. Each group is a role bundle; users get capabilities by membership.

![S27](/usr/share/nginx/archive/atom-ahg-plugins/testing/playwright/screenshots/S27-rbac-groups.png)

`S27-rbac-groups.png`

## S28 — 4.1.1.12.a — RBAC permissions matrix

Per-group permission matrix: read / create / update / delete / publish per module + repository. The matrix is the operator-facing surface of the `acl_permission` table.

![S28](/usr/share/nginx/archive/atom-ahg-plugins/testing/playwright/screenshots/S28-rbac-permissions.png)

`S28-rbac-permissions.png`

## S29 — 4.1.1.12.b — TLS encryption in transit (manual)

Browser address bar with HTTPS lock icon over the PSIS instance. Manual capture — browser chrome is outside the Playwright viewport.

![S29](/usr/share/nginx/archive/atom-ahg-plugins/testing/playwright/screenshots/S29-tls-encryption-MANUAL-REQUIRED.png)

`S29-tls-encryption-MANUAL-REQUIRED.png`

## S30 — 4.1.1.12.b — Encryption at rest — SITA brief (manual)

SITA Private Cloud Foundation Infrastructure service brief confirming AES-256 encryption at rest. Sourced from SITA documentation — to be inserted by the bid team.

![S30](/usr/share/nginx/archive/atom-ahg-plugins/testing/playwright/screenshots/S30-sita-encryption-at-rest-MANUAL-REQUIRED.png)

`S30-sita-encryption-at-rest-MANUAL-REQUIRED.png`

## S31 — 4.1.1.12.c — MISS classification on record edit

Security classification section on the record edit form: level (Unclassified … Top Secret), classifying authority, MISS §5.1 reason code, review-by date. Persists to `object_security_classification`.

![S31](/usr/share/nginx/archive/atom-ahg-plugins/testing/playwright/screenshots/S31-miss-classification-edit.png)

`S31-miss-classification-edit.png`

## S32 — 4.1.1.12.c — Access denied — over-classified record

Anonymous user receives a clean access-denied page when attempting to view a classified record. Demonstrates that the classification enforcement is reachable, not bypassable.

![S32](/usr/share/nginx/archive/atom-ahg-plugins/testing/playwright/screenshots/S32-classification-access-denied.png)

`S32-classification-access-denied.png`

## S33 — 4.1.1.12.d — Records management admin dashboard

Admin landing page available only to the Administrator / Records Manager groups. Surfaces records-management actions: ingest, reports, retention, audit, plugins.

![S33](/usr/share/nginx/archive/atom-ahg-plugins/testing/playwright/screenshots/S33-records-management-access.png)

`S33-records-management-access.png`

## S34 — 4.1.1.12.e — Time-limited share-link — issue modal

F1 — modal launched from a record view. Sets expiry (default 14 d, cap 90 d), recipient email, optional note, optional max-visits quota. HMAC-SHA256 token; auto-revocation on expiry.

![S34](/usr/share/nginx/archive/atom-ahg-plugins/testing/playwright/screenshots/S34-share-link-issue.png)

`S34-share-link-issue.png`

## S35 — 4.1.1.12.e — Expired share-link in admin list

Admin index of every issued share link with status (active / expired / revoked / exhausted). Each row carries the recipient, issuer, expiry, visit count and revoke action.

![S35](/usr/share/nginx/archive/atom-ahg-plugins/testing/playwright/screenshots/S35-share-link-expired.png)

`S35-share-link-expired.png`

## S36 — 4.1.1.12.f — Audit trail — browse list

Audit log browser with filter chips by user, action, module, date range. Powered by the `ahg_audit_log` table; POPIA + NARSSA compliant.

![S36](/usr/share/nginx/archive/atom-ahg-plugins/testing/playwright/screenshots/S36-audit-trail-list.png)

`S36-audit-trail-list.png`

## S37 — 4.1.1.12.f — Audit entry detail with JSON diff

One audit entry detail: UUID, user, request, IP, agent, security classification, plus the field-level JSON old-values / new-values diff.

![S37](/usr/share/nginx/archive/atom-ahg-plugins/testing/playwright/screenshots/S37-audit-trail-detail.png)

`S37-audit-trail-detail.png`

## S38 — 4.1.1.13.a — Retention / Rights dashboard

Extended-rights dashboard with retention schedules by file-plan category, embargo counts and upcoming review dates. Driven by `ahgExtendedRightsPlugin`.

![S38](/usr/share/nginx/archive/atom-ahg-plugins/testing/playwright/screenshots/S38-retention-schedules.png)

`S38-retention-schedules.png`

## S39 — 4.1.1.13.a — Record-level retention block

Information-object detail page sidebar showing the record's retention category, expiry date and intended disposition action.

![S39](/usr/share/nginx/archive/atom-ahg-plugins/testing/playwright/screenshots/S39-record-retention.png)

`S39-record-retention.png`

## S40 — 4.1.1.13.b — Embargo / disposal review queue

Embargoes view from the Rights plugin — list of records whose embargo period has elapsed, awaiting reviewer approval before lifting / disposal.

![S40](/usr/share/nginx/archive/atom-ahg-plugins/testing/playwright/screenshots/S40-disposal-review-queue.png)

`S40-disposal-review-queue.png`

## S41 — 4.1.1.13.b — Disposal audit trail entry

Audit log filtered to delete / dispose actions. Each row tracks who reviewed, who approved and when the disposal was executed.

![S41](/usr/share/nginx/archive/atom-ahg-plugins/testing/playwright/screenshots/S41-disposal-audit-entry.png)

`S41-disposal-audit-entry.png`

## S42 — 4.1.1.14.c — POPIA / PAIA dashboard

Privacy dashboard with active jurisdiction (POPIA / PAIA), PII pattern coverage, DSAR queue summary and breach-incident counter.

![S42](/usr/share/nginx/archive/atom-ahg-plugins/testing/playwright/screenshots/S42-popia-dashboard.png)

`S42-popia-dashboard.png`

## S43 — 4.1.1.14.c — PII scan — CLI output

Console output from `php symfony privacy:scan-pii --jurisdiction=ZA` showing detected SA ID numbers, phone numbers and email addresses with the field and excerpt.

![S43](/usr/share/nginx/archive/atom-ahg-plugins/testing/playwright/screenshots/S43-pii-scan-output.png)

`S43-pii-scan-output.png`

## S44 — 4.1.1.14.e — Audit statistics — POPIA-ready report

Audit statistics aggregated by module / action / user / time — POPIA-compliant audit reporting baseline. Exportable to CSV / PDF.

![S44](/usr/share/nginx/archive/atom-ahg-plugins/testing/playwright/screenshots/S44-popia-audit-report.png)

`S44-popia-audit-report.png`

## S45 — 4.1.1.14.f — Per-user activity report

Activity report for a specific user: login count, records viewed, records modified, share links issued, version restores, with timeline of recent actions.

![S45](/usr/share/nginx/archive/atom-ahg-plugins/testing/playwright/screenshots/S45-user-activity-report.png)

`S45-user-activity-report.png`

## S46 — 4.1.1.14.g — Metadata integrity verification

Integrity-check report listing records missing required metadata or with invalid field values per the active descriptive standard.

![S46](/usr/share/nginx/archive/atom-ahg-plugins/testing/playwright/screenshots/S46-metadata-integrity.png)

`S46-metadata-integrity.png`

## S47 — 4.1.1.14.h — Lifecycle compliance — rights summary

Extended rights summary by retention category: % compliant, count past due review, count past disposal date. Surfaces lifecycle status across the whole collection.

![S47](/usr/share/nginx/archive/atom-ahg-plugins/testing/playwright/screenshots/S47-lifecycle-compliance.png)

`S47-lifecycle-compliance.png`

## S48 — clause 2 + 4.1.3.1 — Multi-tenant "one instance" model

Schema + capabilities of the `ahgMultiTenantPlugin` built by AHG for SITA/NARSSA. Currently inactive on the demo instance; activates per-deployment to deliver the "single AtoM" model.

![S48](/usr/share/nginx/archive/atom-ahg-plugins/testing/playwright/screenshots/S48-multi-tenant-admin.png)

`S48-multi-tenant-admin.png`

## S49 — 4.1.3.5 — AHG settings — admin surface

Central AHG settings admin: theme, branding, IIIF, ingest defaults, audit retention, share-link policy. Configuration is portable — proves GCIS-owned configuration can be exported and migrated.

![S49](/usr/share/nginx/archive/atom-ahg-plugins/testing/playwright/screenshots/S49-settings-export.png)

`S49-settings-export.png`

## X1 — exceeds — AHG plugin catalogue — installed and active

Plugin admin view showing the breadth of the AHG catalogue active on the demo. Demonstrates depth of the platform beyond the minimum bid scope.

![X1](/usr/share/nginx/archive/atom-ahg-plugins/testing/playwright/screenshots/X1-plugin-catalogue.png)

`X1-plugin-catalogue.png`

## X2 — exceeds (OAIS) — OAIS-aligned SIP / AIP / DIP package

File tree of a committed OAIS Archival Information Package: `manifest.json`, `premis.json`, `metadata/`, `objects/`, submission documentation. Conforms to ISO 14721:2012.

![X2](/usr/share/nginx/archive/atom-ahg-plugins/testing/playwright/screenshots/X2-oais-sip-aip-dip.png)

`X2-oais-sip-aip-dip.png`

## X3 — exceeds (IIIF) — IIIF viewer for digitised content

High-res IIIF deep-zoom viewer on a digitised page. Annotation layer + searchable OCR overlay (ahgIiifPlugin).

![X3](/usr/share/nginx/archive/atom-ahg-plugins/testing/playwright/screenshots/X3-iiif-viewer.png)

`X3-iiif-viewer.png`

## X4 — exceeds (API) — Webhook delivery log

Per-event webhook delivery log: each subscribed system receives POST callbacks on archive events (description.create, dsar.created, retention.due). Retries on failure with exponential back-off.

![X4](/usr/share/nginx/archive/atom-ahg-plugins/testing/playwright/screenshots/X4-webhook-delivery.png)

`X4-webhook-delivery.png`

## X5 — exceeds (UX) — Responsive / mobile view

Same archival record on a mobile viewport (414 × 896). Bootstrap 5 theme reflows; tablet and phone are first-class clients.

![X5](/usr/share/nginx/archive/atom-ahg-plugins/testing/playwright/screenshots/X5-responsive-mobile.png)

`X5-responsive-mobile.png`

## X6 — exceeds (backup) — Backup & restore admin

Backup history with sizes, dates and a tested-restore indicator. Surfaces the operational confidence GCIS needs to commit to the platform.

![X6](/usr/share/nginx/archive/atom-ahg-plugins/testing/playwright/screenshots/X6-backup-restore.png)

`X6-backup-restore.png`

