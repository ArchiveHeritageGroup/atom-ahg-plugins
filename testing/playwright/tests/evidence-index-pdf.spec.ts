import { test } from '@playwright/test';
import { readFileSync, existsSync } from 'node:fs';
import { join } from 'node:path';

/**
 * Generate a single PDF index of the GCIS RFB-001 evidence pack.
 *
 * Each entry: tender clause heading, screenshot code, 1-2 line description,
 * thumbnail of the screenshot itself. Output: ./screenshots/AHG_GCIS_RFB-001_Evidence_Index.pdf
 *
 * Run: cd testing/playwright && npx playwright test tests/evidence-index-pdf.spec.ts
 */

const SHOTS = join(process.cwd(), 'screenshots');

interface Entry {
  code: string;          // S1, X3, etc.
  clause: string;        // tender clause reference
  title: string;         // short heading
  description: string;   // 1-2 lines
  file: string;          // screenshot filename (no extension)
}

/** Tender evidence catalogue. Order matches the bid section 13 listing. */
const ENTRIES: Entry[] = [
  // 4.1.1.1 — Workflow automation + SharePoint integration
  { code: 'S1',  clause: '4.1.1.1', title: 'SharePoint auto-ingest rules — list view',
    description: 'Operator-managed inventory of SharePoint drive sweep rules. Each row shows the drive, the file/path pattern, the cron schedule and the count of items ingested on the last run. Demonstrates automation, not manual upload.',
    file: 'S1-sharepoint-rules-list' },
  { code: 'S2',  clause: '4.1.1.1', title: 'SharePoint rule — edit form',
    description: 'Per-rule configuration: target drive, folder path, glob pattern, Microsoft Purview retention-label filter, mapping template, cron schedule. Mapping templates link Purview labels to AtoM dispositions.',
    file: 'S2-sharepoint-rule-edit' },
  { code: 'S3',  clause: '4.1.1.1', title: 'Live auto-ingest CLI evidence',
    description: 'Console output from <code>php symfony sharepoint:auto-ingest --rule=1</code> showing 12 new items found, queued as job 991, no throttling, 4.8 s duration. Proves the workflow runs end-to-end.',
    file: 'S3-sharepoint-auto-ingest-cli' },

  // 4.1.1.2 — Import from SharePoint
  { code: 'S4',  clause: '4.1.1.2', title: 'Record sourced from SharePoint',
    description: 'AtoM information-object detail page for a record whose payload originated in SharePoint Online. Sidebar shows <code>sp_drive_id</code>, <code>sp_item_id</code> and a back-link to the live SP item.',
    file: 'S4-record-from-sharepoint' },

  // 4.1.1.3 — Secure retrieval + tracking + version mgmt
  { code: 'S5',  clause: '4.1.1.3', title: 'Authenticated retrieval — login screen',
    description: 'AtoM login form (email + password). Demonstrates that record retrieval is gated by authentication. Combined with TLS evidence (S29), satisfies the secure-retrieval clause.',
    file: 'S5-secure-retrieval-login' },
  { code: 'S6',  clause: '4.1.1.3', title: 'Record view with version-history banner',
    description: 'Top of an information-object view page showing the <strong>"Version history (1)"</strong> badge and <strong>"Share this record"</strong> button injected by the AHG plugins. Tracking + sharing surface alongside the standard AtoM chrome.',
    file: 'S6-record-tracking-tab' },
  { code: 'S7',  clause: '4.1.1.3', title: 'Version diff — word-level',
    description: 'Side-by-side comparison of versions 3 and 5 of a record. Word-level LCS diff with green inserts and red strikethrough deletions per field. Powered by <code>AhgVersionControl\\Services\\DiffComputer</code>.',
    file: 'S7-version-diff' },
  { code: 'S8',  clause: '4.1.1.3', title: 'Version restore — admin entry',
    description: 'Version-history page with a "Restore this version" button. Restoring creates a new version row tagged <code>is_restore=1</code> so the history itself is not lost.',
    file: 'S8-version-restore' },

  // 4.1.1.4 — Metadata linkage SP↔AtoM
  { code: 'S9',  clause: '4.1.1.4', title: 'Metadata cross-reference SP ⇄ AtoM',
    description: 'Information-object record with a sidebar block linking back to the active source in SharePoint. Cross-reference fields persist on both sides so neither store loses provenance.',
    file: 'S9-metadata-linkage' },

  // 4.1.1.5 — Batch uploads
  { code: 'S10', clause: '4.1.1.5', title: 'Ingest wizard — step 2 (upload)',
    description: 'Six-step OAIS-aligned ingest wizard. Step 2 accepts CSV, ZIP, EAD or a "From SharePoint" tab; downstream steps handle field mapping, validation, preview and commit.',
    file: 'S10-batch-upload-wizard' },
  { code: 'S11', clause: '4.1.1.5', title: 'Ingest sessions index',
    description: 'List of ingest sessions with status, sector, record count, started-by and duration. Click into a session to see its job progress + commit log.',
    file: 'S11-ingest-job-status' },

  // 4.1.1.6 — Automated archival + API
  { code: 'S12', clause: '4.1.1.6', title: 'Retention-label triggered archival',
    description: 'Auto-ingest rule edit form showing the "Only items carrying specific Purview retention label(s)" filter. Drives Purview-label-driven archival without operator intervention.',
    file: 'S12-retention-trigger-rule' },
  { code: 'S13', clause: '4.1.1.6', title: 'AtoM v2 REST API — endpoint inventory',
    description: 'Full inventory of the v2 API: descriptions CRUD, search, conditions, assets, privacy DSARs, webhooks, API key management. Authenticated via <code>X-API-Key</code> header.',
    file: 'S13-api-documentation' },

  // 4.1.1.7 — Dublin Core + custom fields
  { code: 'S14', clause: '4.1.1.7', title: 'Dublin Core fields on record edit',
    description: 'All 15 Dublin Core elements rendered on the AtoM information-object edit form (identity, context, content, provenance & rights). Persisted to <code>information_object_i18n</code>.',
    file: 'S14-dublin-core-fields' },
  { code: 'S15', clause: '4.1.1.7', title: 'Custom Fields administration',
    description: 'GCIS-specific custom fields (Directorate code, file plan classification, originating department, retention category). Configured at <code>/admin/customFields</code> via the EAV plugin — no code changes per field.',
    file: 'S15-custom-fields-admin' },
  { code: 'S16', clause: '4.1.1.7', title: 'Custom field on a record edit form',
    description: 'The "GCIS Governance" and "Compliance" custom-field sections rendered alongside the standard ISAD(G) / Dublin Core areas on a record-edit form. Values persist to <code>custom_field_value</code>.',
    file: 'S16-custom-field-on-record' },

  // 4.1.1.8 — GCIS file plan
  { code: 'S17', clause: '4.1.1.8', title: 'Taxonomy import — file-plan tree',
    description: 'AtoM taxonomy view of an imported file plan hierarchy. Each node maps to GCIS directorate / function / record category and is selectable as the parent of an information object.',
    file: 'S17-gcis-file-plan-taxonomy' },
  { code: 'S18', clause: '4.1.1.8', title: 'Record placed under a file-plan node',
    description: 'Information-object detail page showing the record\'s placement under its assigned file plan category in the hierarchy sidebar.',
    file: 'S18-records-by-file-plan' },

  // 4.1.1.9 — Tagging + indexing + version control
  { code: 'S19', clause: '4.1.1.9', title: 'Subject access points (tagging)',
    description: 'Record view with subject access points (controlled-vocabulary tags) listed in the right sidebar. Tags are indexed in Elasticsearch and faceted on search results.',
    file: 'S19-record-tags' },
  { code: 'S20', clause: '4.1.1.9', title: 'Elasticsearch search results — facets visible',
    description: 'Search results page showing faceted filters on the left (level of description, repository, date, type) and ranked hits. Demonstrates working ES indexing.',
    file: 'S20-elasticsearch-indexing' },
  { code: 'S21', clause: '4.1.1.9', title: 'Version control — numbered version list',
    description: 'Version history page for a record: numbered versions with timestamps, authors, summaries and "View" / "Compare" actions. List view drives the diff (S7) and restore (S8) flows.',
    file: 'S21-version-control-list' },

  // 4.1.1.10 — Search and retrieval
  { code: 'S22', clause: '4.1.1.10.a', title: 'Full-text search',
    description: 'Search results matching a term that appears inside an OCR\'d body, not just metadata. Proves the search is full-text against Elasticsearch, not table-scan.',
    file: 'S22-full-text-search' },
  { code: 'S23', clause: '4.1.1.10.a', title: 'Advanced search — multi-filter form',
    description: 'Advanced search form with combined filters: query, date range, level of description, repository, digital-object presence, classification.',
    file: 'S23-advanced-search-filters' },
  { code: 'S24', clause: '4.1.1.10', title: 'Federated search — AtoM + SharePoint (exceeds)',
    description: 'Single search box returning ranked hits from <strong>AtoM (archived)</strong> and <strong>SharePoint Online (active)</strong> with source-attribution badges and dedupe pills. Powers the "one search, two stores" experience.',
    file: 'S24-federated-search' },
  { code: 'S25', clause: '4.1.1.10.b', title: 'Quick retrieval — response time visible',
    description: 'Search results header showing "12 results in 80 ms" — demonstrates sub-second retrieval performance at typical collection sizes.',
    file: 'S25-quick-retrieval' },

  // 4.1.1.11 — Links to active records
  { code: 'S26', clause: '4.1.1.11', title: 'Record link to active SharePoint item',
    description: 'Information-object sidebar with a "View active record in SharePoint" button that deep-links to the live SP item. Round-trips Purview-managed active records to AtoM-managed archived records.',
    file: 'S26-link-to-active-record' },

  // 4.1.1.12 — Access control + security
  { code: 'S27', clause: '4.1.1.12.a', title: 'RBAC groups list',
    description: 'AtoM ACL groups: Administrator, Editor, Contributor, Translator plus AHG additions. Each group is a role bundle; users get capabilities by membership.',
    file: 'S27-rbac-groups' },
  { code: 'S28', clause: '4.1.1.12.a', title: 'RBAC permissions matrix',
    description: 'Per-group permission matrix: read / create / update / delete / publish per module + repository. The matrix is the operator-facing surface of the <code>acl_permission</code> table.',
    file: 'S28-rbac-permissions' },
  { code: 'S29', clause: '4.1.1.12.b', title: 'TLS encryption in transit (manual)',
    description: 'Browser address bar with HTTPS lock icon over the PSIS instance. Manual capture — browser chrome is outside the Playwright viewport.',
    file: 'S29-tls-encryption-MANUAL-REQUIRED' },
  { code: 'S30', clause: '4.1.1.12.b', title: 'Encryption at rest — SITA brief (manual)',
    description: 'SITA Private Cloud Foundation Infrastructure service brief confirming AES-256 encryption at rest. Sourced from SITA documentation — to be inserted by the bid team.',
    file: 'S30-sita-encryption-at-rest-MANUAL-REQUIRED' },
  { code: 'S31', clause: '4.1.1.12.c', title: 'MISS classification on record edit',
    description: 'Security classification section on the record edit form: level (Unclassified … Top Secret), classifying authority, MISS §5.1 reason code, review-by date. Persists to <code>object_security_classification</code>.',
    file: 'S31-miss-classification-edit' },
  { code: 'S32', clause: '4.1.1.12.c', title: 'Access denied — over-classified record',
    description: 'Anonymous user receives a clean access-denied page when attempting to view a classified record. Demonstrates that the classification enforcement is reachable, not bypassable.',
    file: 'S32-classification-access-denied' },
  { code: 'S33', clause: '4.1.1.12.d', title: 'Records management admin dashboard',
    description: 'Admin landing page available only to the Administrator / Records Manager groups. Surfaces records-management actions: ingest, reports, retention, audit, plugins.',
    file: 'S33-records-management-access' },
  { code: 'S34', clause: '4.1.1.12.e', title: 'Time-limited share-link — issue modal',
    description: 'F1 — modal launched from a record view. Sets expiry (default 14 d, cap 90 d), recipient email, optional note, optional max-visits quota. HMAC-SHA256 token; auto-revocation on expiry.',
    file: 'S34-share-link-issue' },
  { code: 'S35', clause: '4.1.1.12.e', title: 'Expired share-link in admin list',
    description: 'Admin index of every issued share link with status (active / expired / revoked / exhausted). Each row carries the recipient, issuer, expiry, visit count and revoke action.',
    file: 'S35-share-link-expired' },
  { code: 'S36', clause: '4.1.1.12.f', title: 'Audit trail — browse list',
    description: 'Audit log browser with filter chips by user, action, module, date range. Powered by the <code>ahg_audit_log</code> table; POPIA + NARSSA compliant.',
    file: 'S36-audit-trail-list' },
  { code: 'S37', clause: '4.1.1.12.f', title: 'Audit entry detail with JSON diff',
    description: 'One audit entry detail: UUID, user, request, IP, agent, security classification, plus the field-level JSON old-values / new-values diff.',
    file: 'S37-audit-trail-detail' },

  // 4.1.1.13 — Retention and disposal
  { code: 'S38', clause: '4.1.1.13.a', title: 'Retention / Rights dashboard',
    description: 'Extended-rights dashboard with retention schedules by file-plan category, embargo counts and upcoming review dates. Driven by <code>ahgExtendedRightsPlugin</code>.',
    file: 'S38-retention-schedules' },
  { code: 'S39', clause: '4.1.1.13.a', title: 'Record-level retention block',
    description: 'Information-object detail page sidebar showing the record\'s retention category, expiry date and intended disposition action.',
    file: 'S39-record-retention' },
  { code: 'S40', clause: '4.1.1.13.b', title: 'Embargo / disposal review queue',
    description: 'Embargoes view from the Rights plugin — list of records whose embargo period has elapsed, awaiting reviewer approval before lifting / disposal.',
    file: 'S40-disposal-review-queue' },
  { code: 'S41', clause: '4.1.1.13.b', title: 'Disposal audit trail entry',
    description: 'Audit log filtered to delete / dispose actions. Each row tracks who reviewed, who approved and when the disposal was executed.',
    file: 'S41-disposal-audit-entry' },

  // 4.1.1.14 — Compliance and audit
  { code: 'S42', clause: '4.1.1.14.c', title: 'POPIA / PAIA dashboard',
    description: 'Privacy dashboard with active jurisdiction (POPIA / PAIA), PII pattern coverage, DSAR queue summary and breach-incident counter.',
    file: 'S42-popia-dashboard' },
  { code: 'S43', clause: '4.1.1.14.c', title: 'PII scan — CLI output',
    description: 'Console output from <code>php symfony privacy:scan-pii --jurisdiction=ZA</code> showing detected SA ID numbers, phone numbers and email addresses with the field and excerpt.',
    file: 'S43-pii-scan-output' },
  { code: 'S44', clause: '4.1.1.14.e', title: 'Audit statistics — POPIA-ready report',
    description: 'Audit statistics aggregated by module / action / user / time — POPIA-compliant audit reporting baseline. Exportable to CSV / PDF.',
    file: 'S44-popia-audit-report' },
  { code: 'S45', clause: '4.1.1.14.f', title: 'Per-user activity report',
    description: 'Activity report for a specific user: login count, records viewed, records modified, share links issued, version restores, with timeline of recent actions.',
    file: 'S45-user-activity-report' },
  { code: 'S46', clause: '4.1.1.14.g', title: 'Metadata integrity verification',
    description: 'Integrity-check report listing records missing required metadata or with invalid field values per the active descriptive standard.',
    file: 'S46-metadata-integrity' },
  { code: 'S47', clause: '4.1.1.14.h', title: 'Lifecycle compliance — rights summary',
    description: 'Extended rights summary by retention category: % compliant, count past due review, count past disposal date. Surfaces lifecycle status across the whole collection.',
    file: 'S47-lifecycle-compliance' },

  // Clause 2 + 4.1.3
  { code: 'S48', clause: 'clause 2 + 4.1.3.1', title: 'Multi-tenant "one instance" model',
    description: 'Schema + capabilities of the <code>ahgMultiTenantPlugin</code> built by AHG for SITA/NARSSA. Currently inactive on the demo instance; activates per-deployment to deliver the "single AtoM" model.',
    file: 'S48-multi-tenant-admin' },
  { code: 'S49', clause: '4.1.3.5', title: 'AHG settings — admin surface',
    description: 'Central AHG settings admin: theme, branding, IIIF, ingest defaults, audit retention, share-link policy. Configuration is portable — proves GCIS-owned configuration can be exported and migrated.',
    file: 'S49-settings-export' },

  // Supplementary — exceeds expectations
  { code: 'X1', clause: 'exceeds', title: 'AHG plugin catalogue — installed and active',
    description: 'Plugin admin view showing the breadth of the AHG catalogue active on the demo. Demonstrates depth of the platform beyond the minimum bid scope.',
    file: 'X1-plugin-catalogue' },
  { code: 'X2', clause: 'exceeds (OAIS)', title: 'OAIS-aligned SIP / AIP / DIP package',
    description: 'File tree of a committed OAIS Archival Information Package: <code>manifest.json</code>, <code>premis.json</code>, <code>metadata/</code>, <code>objects/</code>, submission documentation. Conforms to ISO 14721:2012.',
    file: 'X2-oais-sip-aip-dip' },
  { code: 'X3', clause: 'exceeds (IIIF)', title: 'IIIF viewer for digitised content',
    description: 'High-res IIIF deep-zoom viewer on a digitised page. Annotation layer + searchable OCR overlay (ahgIiifPlugin).',
    file: 'X3-iiif-viewer' },
  { code: 'X4', clause: 'exceeds (API)', title: 'Webhook delivery log',
    description: 'Per-event webhook delivery log: each subscribed system receives POST callbacks on archive events (description.create, dsar.created, retention.due). Retries on failure with exponential back-off.',
    file: 'X4-webhook-delivery' },
  { code: 'X5', clause: 'exceeds (UX)', title: 'Responsive / mobile view',
    description: 'Same archival record on a mobile viewport (414 × 896). Bootstrap 5 theme reflows; tablet and phone are first-class clients.',
    file: 'X5-responsive-mobile' },
  { code: 'X6', clause: 'exceeds (backup)', title: 'Backup & restore admin',
    description: 'Backup history with sizes, dates and a tested-restore indicator. Surfaces the operational confidence GCIS needs to commit to the platform.',
    file: 'X6-backup-restore' },
];

test('Generate evidence index PDF', async ({ page }) => {
  // Build the index HTML with all entries inline (data URLs for thumbnails)
  let entriesHtml = '';
  for (const e of ENTRIES) {
    const imgPath = join(SHOTS, `${e.file}.png`);
    let imgSrc = '';
    if (existsSync(imgPath)) {
      const b64 = readFileSync(imgPath).toString('base64');
      imgSrc = `data:image/png;base64,${b64}`;
    }
    entriesHtml += `
      <section class="entry">
        <header>
          <span class="code">${e.code}</span>
          <h2>${e.clause} — ${e.title}</h2>
        </header>
        <p class="desc">${e.description}</p>
        <div class="thumb">${imgSrc ? `<img src="${imgSrc}">` : '<div class="missing">screenshot missing</div>'}</div>
        <p class="filename"><code>${e.file}.png</code></p>
      </section>`;
  }

  // Stats footer
  const total = ENTRIES.length;
  const mandatory = ENTRIES.filter(e => e.code.startsWith('S')).length;
  const supplementary = ENTRIES.filter(e => e.code.startsWith('X')).length;

  // Group clauses for the cover page
  const clauseGroups = new Map<string, Entry[]>();
  for (const e of ENTRIES) {
    if (!clauseGroups.has(e.clause)) clauseGroups.set(e.clause, []);
    clauseGroups.get(e.clause)!.push(e);
  }
  let clauseTable = '';
  for (const [clause, entries] of clauseGroups) {
    const codes = entries.map(e => e.code).join(', ');
    clauseTable += `<tr><td>${clause}</td><td>${entries[0].title.replace(/—.*/, '').trim() + (entries.length > 1 ? ' (+ ' + (entries.length - 1) + ' more)' : '')}</td><td>${codes}</td></tr>`;
  }

  const html = `<!doctype html>
<html><head><meta charset="utf-8"><title>GCIS RFB-001 — Evidence Index</title>
<style>
  @page { size: A4; margin: 18mm 15mm; }
  * { box-sizing: border-box; }
  body { font-family: 'Helvetica Neue', Arial, sans-serif; color: #1a1a1a; line-height: 1.45; font-size: 10.5pt; margin: 0; }
  h1 { color: #10692c; border-bottom: 3px solid #10692c; padding-bottom: .3em; font-size: 22pt; margin: 0 0 .4em; }
  h2 { font-size: 12pt; color: #1a1a1a; margin: 0; display: inline; }
  .cover { padding: 6mm 0; }
  .cover p { font-size: 11pt; }
  .cover .meta { color: #666; font-size: 10pt; }
  .toc { width: 100%; border-collapse: collapse; margin: 1em 0; font-size: 9.5pt; }
  .toc th, .toc td { border: 1px solid #ddd; padding: 5px 8px; text-align: left; }
  .toc th { background: #10692c; color: #fff; font-weight: 600; }
  .toc tr:nth-child(even) td { background: #f6f8fa; }
  .summary { background: #d4edda; color: #155724; border-left: 4px solid #10692c; padding: 10px 14px; border-radius: 3px; margin: 1em 0; font-size: 10pt; }
  .pagebreak { page-break-before: always; }
  .entry { page-break-inside: avoid; margin: 0 0 6mm; padding: 5mm 6mm 4mm; border: 1px solid #e0e0e0; border-radius: 4px; background: #fff; }
  .entry header { display: flex; align-items: baseline; gap: 10px; margin-bottom: 4px; border-bottom: 1px solid #eee; padding-bottom: 4px; }
  .entry .code { display: inline-block; background: #10692c; color: #fff; padding: 2px 9px; border-radius: 3px; font-weight: 700; font-size: 9pt; font-family: monospace; }
  .entry .desc { margin: 6px 0 8px; color: #333; font-size: 10pt; }
  .entry .thumb { text-align: center; }
  .entry .thumb img { max-width: 100%; max-height: 120mm; border: 1px solid #ccc; border-radius: 2px; box-shadow: 0 2px 6px rgba(0,0,0,.08); }
  .entry .thumb .missing { display: inline-block; padding: 4em 2em; color: #999; background: #f5f5f5; border: 1px dashed #bbb; }
  .entry .filename { color: #888; font-size: 8.5pt; margin: 4px 0 0; text-align: right; }
  code { background: #f6f8fa; padding: 1px 5px; border-radius: 2px; font-size: .92em; }
</style></head>
<body>
  <div class="cover">
    <h1>GCIS RFB-001 2026/2027 — Evidence Index</h1>
    <p class="meta">AtoM-Aligned Plan · Gate 1 Criterion 2 (System Functionality, weight 20) · The Archive and Heritage Group (Pty) Ltd · 2026-05-12</p>

    <div class="summary">
      <strong>${total}</strong> screenshots covering <strong>${clauseGroups.size}</strong> tender clauses. <strong>${mandatory}</strong> mandatory (S1–S49), <strong>${supplementary}</strong> supplementary (X1–X6) demonstrating "exceeds expectations" capability. Captured live from <code>https://psis.theahg.co.za</code>; non-page artefacts (CLI, schema diagrams, vendor docs) rendered as styled evidence pages.
    </div>

    <p>The pack is organised by tender clause. Each entry includes the screenshot code, the clause reference and a short description of what the screenshot demonstrates. Screenshots are also delivered as individual PNG files under <code>atom-ahg-plugins/testing/playwright/screenshots/</code>.</p>

    <h2 style="display:block;color:#10692c;font-size:14pt;border-bottom:2px solid #10692c;padding-bottom:.2em;margin:1em 0 .5em;">Clause-to-screenshot map</h2>
    <table class="toc">
      <thead><tr><th style="width:18%">Clause</th><th>Coverage area</th><th style="width:30%">Screenshot codes</th></tr></thead>
      <tbody>${clauseTable}</tbody>
    </table>
  </div>

  <div class="pagebreak"></div>
  ${entriesHtml}
</body></html>`;

  await page.setContent(html, { waitUntil: 'load' });
  await page.pdf({
    path: join(SHOTS, 'AHG_GCIS_RFB-001_Evidence_Index.pdf'),
    format: 'A4',
    printBackground: true,
    margin: { top: '15mm', right: '12mm', bottom: '15mm', left: '12mm' },
  });
});
