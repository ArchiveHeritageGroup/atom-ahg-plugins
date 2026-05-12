<?php

/**
 * Build a Markdown evidence index, then convert to .docx via pandoc.
 *
 * Reuses the same entry list as the Playwright PDF generator. Run AFTER the
 * Playwright spec has produced the PNGs (or it'll just emit a missing-image
 * note per entry).
 */

$shotsDir = __DIR__ . '/screenshots';
$outDir   = '/usr/share/nginx/archive/atom-extensions-catalog/docs';

/**
 * Catalogue — must stay in sync with tests/evidence-index-pdf.spec.ts.
 * Edit one, edit the other.
 */
$entries = [
    ['S1',  '4.1.1.1', 'SharePoint auto-ingest rules — list view',
        'Operator-managed inventory of SharePoint drive sweep rules. Each row shows the drive, the file/path pattern, the cron schedule and the count of items ingested on the last run. Demonstrates automation, not manual upload.',
        'S1-sharepoint-rules-list'],
    ['S2',  '4.1.1.1', 'SharePoint rule — edit form',
        'Per-rule configuration: target drive, folder path, glob pattern, Microsoft Purview retention-label filter, mapping template, cron schedule. Mapping templates link Purview labels to AtoM dispositions.',
        'S2-sharepoint-rule-edit'],
    ['S3',  '4.1.1.1', 'Live auto-ingest CLI evidence',
        'Console output from `php symfony sharepoint:auto-ingest --rule=1` showing 12 new items found, queued as job 991, no throttling, 4.8 s duration. Proves the workflow runs end-to-end.',
        'S3-sharepoint-auto-ingest-cli'],
    ['S4',  '4.1.1.2', 'Record sourced from SharePoint',
        'AtoM information-object detail page for a record whose payload originated in SharePoint Online. Sidebar shows `sp_drive_id`, `sp_item_id` and a back-link to the live SP item.',
        'S4-record-from-sharepoint'],
    ['S5',  '4.1.1.3', 'Authenticated retrieval — login screen',
        'AtoM login form (email + password). Demonstrates that record retrieval is gated by authentication. Combined with TLS evidence (S29), satisfies the secure-retrieval clause.',
        'S5-secure-retrieval-login'],
    ['S6',  '4.1.1.3', 'Record view with version-history banner',
        'Top of an information-object view page showing the **"Version history (1)"** badge and **"Share this record"** button injected by the AHG plugins. Tracking + sharing surface alongside the standard AtoM chrome.',
        'S6-record-tracking-tab'],
    ['S7',  '4.1.1.3', 'Version diff — word-level',
        'Side-by-side comparison of versions 3 and 5 of a record. Word-level LCS diff with green inserts and red strikethrough deletions per field. Powered by `AhgVersionControl\\Services\\DiffComputer`.',
        'S7-version-diff'],
    ['S8',  '4.1.1.3', 'Version restore — admin entry',
        'Version-history page with a "Restore this version" button. Restoring creates a new version row tagged `is_restore=1` so the history itself is not lost.',
        'S8-version-restore'],
    ['S9',  '4.1.1.4', 'Metadata cross-reference SP ⇄ AtoM',
        'Information-object record with a sidebar block linking back to the active source in SharePoint. Cross-reference fields persist on both sides so neither store loses provenance.',
        'S9-metadata-linkage'],
    ['S10', '4.1.1.5', 'Ingest wizard — step 2 (upload)',
        'Six-step OAIS-aligned ingest wizard. Step 2 accepts CSV, ZIP, EAD or a "From SharePoint" tab; downstream steps handle field mapping, validation, preview and commit.',
        'S10-batch-upload-wizard'],
    ['S11', '4.1.1.5', 'Ingest sessions index',
        'List of ingest sessions with status, sector, record count, started-by and duration. Click into a session to see its job progress + commit log.',
        'S11-ingest-job-status'],
    ['S12', '4.1.1.6', 'Retention-label triggered archival',
        'Auto-ingest rule edit form showing the "Only items carrying specific Purview retention label(s)" filter. Drives Purview-label-driven archival without operator intervention.',
        'S12-retention-trigger-rule'],
    ['S13', '4.1.1.6', 'AtoM v2 REST API — endpoint inventory',
        'Full inventory of the v2 API: descriptions CRUD, search, conditions, assets, privacy DSARs, webhooks, API key management. Authenticated via `X-API-Key` header.',
        'S13-api-documentation'],
    ['S14', '4.1.1.7', 'Dublin Core fields on record edit',
        'All 15 Dublin Core elements rendered on the AtoM information-object edit form (identity, context, content, provenance & rights). Persisted to `information_object_i18n`.',
        'S14-dublin-core-fields'],
    ['S15', '4.1.1.7', 'Custom Fields administration',
        'GCIS-specific custom fields (Directorate code, file plan classification, originating department, retention category). Configured at `/admin/customFields` via the EAV plugin — no code changes per field.',
        'S15-custom-fields-admin'],
    ['S16', '4.1.1.7', 'Custom field on a record edit form',
        'The "GCIS Governance" and "Compliance" custom-field sections rendered alongside the standard ISAD(G) / Dublin Core areas on a record-edit form. Values persist to `custom_field_value`.',
        'S16-custom-field-on-record'],
    ['S17', '4.1.1.8', 'Taxonomy import — file-plan tree',
        'AtoM taxonomy view of an imported file plan hierarchy. Each node maps to GCIS directorate / function / record category and is selectable as the parent of an information object.',
        'S17-gcis-file-plan-taxonomy'],
    ['S18', '4.1.1.8', 'Record placed under a file-plan node',
        'Information-object detail page showing the record\'s placement under its assigned file plan category in the hierarchy sidebar.',
        'S18-records-by-file-plan'],
    ['S19', '4.1.1.9', 'Subject access points (tagging)',
        'Record view with subject access points (controlled-vocabulary tags) listed in the right sidebar. Tags are indexed in Elasticsearch and faceted on search results.',
        'S19-record-tags'],
    ['S20', '4.1.1.9', 'Elasticsearch search results — facets visible',
        'Search results page showing faceted filters on the left (level of description, repository, date, type) and ranked hits. Demonstrates working ES indexing.',
        'S20-elasticsearch-indexing'],
    ['S21', '4.1.1.9', 'Version control — numbered version list',
        'Version history page for a record: numbered versions with timestamps, authors, summaries and "View" / "Compare" actions. List view drives the diff (S7) and restore (S8) flows.',
        'S21-version-control-list'],
    ['S22', '4.1.1.10.a', 'Full-text search',
        'Search results matching a term that appears inside an OCR\'d body, not just metadata. Proves the search is full-text against Elasticsearch, not table-scan.',
        'S22-full-text-search'],
    ['S23', '4.1.1.10.a', 'Advanced search — multi-filter form',
        'Advanced search form with combined filters: query, date range, level of description, repository, digital-object presence, classification.',
        'S23-advanced-search-filters'],
    ['S24', '4.1.1.10', 'Federated search — AtoM + SharePoint (exceeds)',
        'Single search box returning ranked hits from **AtoM (archived)** and **SharePoint Online (active)** with source-attribution badges and dedupe pills. Powers the "one search, two stores" experience.',
        'S24-federated-search'],
    ['S25', '4.1.1.10.b', 'Quick retrieval — response time visible',
        'Search results header showing "12 results in 80 ms" — demonstrates sub-second retrieval performance at typical collection sizes.',
        'S25-quick-retrieval'],
    ['S26', '4.1.1.11', 'Record link to active SharePoint item',
        'Information-object sidebar with a "View active record in SharePoint" button that deep-links to the live SP item. Round-trips Purview-managed active records to AtoM-managed archived records.',
        'S26-link-to-active-record'],
    ['S27', '4.1.1.12.a', 'RBAC groups list',
        'AtoM ACL groups: Administrator, Editor, Contributor, Translator plus AHG additions. Each group is a role bundle; users get capabilities by membership.',
        'S27-rbac-groups'],
    ['S28', '4.1.1.12.a', 'RBAC permissions matrix',
        'Per-group permission matrix: read / create / update / delete / publish per module + repository. The matrix is the operator-facing surface of the `acl_permission` table.',
        'S28-rbac-permissions'],
    ['S29', '4.1.1.12.b', 'TLS encryption in transit (manual)',
        'Browser address bar with HTTPS lock icon over the PSIS instance. Manual capture — browser chrome is outside the Playwright viewport.',
        'S29-tls-encryption-MANUAL-REQUIRED'],
    ['S30', '4.1.1.12.b', 'Encryption at rest — SITA brief (manual)',
        'SITA Private Cloud Foundation Infrastructure service brief confirming AES-256 encryption at rest. Sourced from SITA documentation — to be inserted by the bid team.',
        'S30-sita-encryption-at-rest-MANUAL-REQUIRED'],
    ['S31', '4.1.1.12.c', 'MISS classification on record edit',
        'Security classification section on the record edit form: level (Unclassified … Top Secret), classifying authority, MISS §5.1 reason code, review-by date. Persists to `object_security_classification`.',
        'S31-miss-classification-edit'],
    ['S32', '4.1.1.12.c', 'Access denied — over-classified record',
        'Anonymous user receives a clean access-denied page when attempting to view a classified record. Demonstrates that the classification enforcement is reachable, not bypassable.',
        'S32-classification-access-denied'],
    ['S33', '4.1.1.12.d', 'Records management admin dashboard',
        'Admin landing page available only to the Administrator / Records Manager groups. Surfaces records-management actions: ingest, reports, retention, audit, plugins.',
        'S33-records-management-access'],
    ['S34', '4.1.1.12.e', 'Time-limited share-link — issue modal',
        'F1 — modal launched from a record view. Sets expiry (default 14 d, cap 90 d), recipient email, optional note, optional max-visits quota. HMAC-SHA256 token; auto-revocation on expiry.',
        'S34-share-link-issue'],
    ['S35', '4.1.1.12.e', 'Expired share-link in admin list',
        'Admin index of every issued share link with status (active / expired / revoked / exhausted). Each row carries the recipient, issuer, expiry, visit count and revoke action.',
        'S35-share-link-expired'],
    ['S36', '4.1.1.12.f', 'Audit trail — browse list',
        'Audit log browser with filter chips by user, action, module, date range. Powered by the `ahg_audit_log` table; POPIA + NARSSA compliant.',
        'S36-audit-trail-list'],
    ['S37', '4.1.1.12.f', 'Audit entry detail with JSON diff',
        'One audit entry detail: UUID, user, request, IP, agent, security classification, plus the field-level JSON old-values / new-values diff.',
        'S37-audit-trail-detail'],
    ['S38', '4.1.1.13.a', 'Retention / Rights dashboard',
        'Extended-rights dashboard with retention schedules by file-plan category, embargo counts and upcoming review dates. Driven by `ahgExtendedRightsPlugin`.',
        'S38-retention-schedules'],
    ['S39', '4.1.1.13.a', 'Record-level retention block',
        'Information-object detail page sidebar showing the record\'s retention category, expiry date and intended disposition action.',
        'S39-record-retention'],
    ['S40', '4.1.1.13.b', 'Embargo / disposal review queue',
        'Embargoes view from the Rights plugin — list of records whose embargo period has elapsed, awaiting reviewer approval before lifting / disposal.',
        'S40-disposal-review-queue'],
    ['S41', '4.1.1.13.b', 'Disposal audit trail entry',
        'Audit log filtered to delete / dispose actions. Each row tracks who reviewed, who approved and when the disposal was executed.',
        'S41-disposal-audit-entry'],
    ['S42', '4.1.1.14.c', 'POPIA / PAIA dashboard',
        'Privacy dashboard with active jurisdiction (POPIA / PAIA), PII pattern coverage, DSAR queue summary and breach-incident counter.',
        'S42-popia-dashboard'],
    ['S43', '4.1.1.14.c', 'PII scan — CLI output',
        'Console output from `php symfony privacy:scan-pii --jurisdiction=ZA` showing detected SA ID numbers, phone numbers and email addresses with the field and excerpt.',
        'S43-pii-scan-output'],
    ['S44', '4.1.1.14.e', 'Audit statistics — POPIA-ready report',
        'Audit statistics aggregated by module / action / user / time — POPIA-compliant audit reporting baseline. Exportable to CSV / PDF.',
        'S44-popia-audit-report'],
    ['S45', '4.1.1.14.f', 'Per-user activity report',
        'Activity report for a specific user: login count, records viewed, records modified, share links issued, version restores, with timeline of recent actions.',
        'S45-user-activity-report'],
    ['S46', '4.1.1.14.g', 'Metadata integrity verification',
        'Integrity-check report listing records missing required metadata or with invalid field values per the active descriptive standard.',
        'S46-metadata-integrity'],
    ['S47', '4.1.1.14.h', 'Lifecycle compliance — rights summary',
        'Extended rights summary by retention category: % compliant, count past due review, count past disposal date. Surfaces lifecycle status across the whole collection.',
        'S47-lifecycle-compliance'],
    ['S48', 'clause 2 + 4.1.3.1', 'Multi-tenant "one instance" model',
        'Schema + capabilities of the `ahgMultiTenantPlugin` built by AHG for SITA/NARSSA. Currently inactive on the demo instance; activates per-deployment to deliver the "single AtoM" model.',
        'S48-multi-tenant-admin'],
    ['S49', '4.1.3.5', 'AHG settings — admin surface',
        'Central AHG settings admin: theme, branding, IIIF, ingest defaults, audit retention, share-link policy. Configuration is portable — proves GCIS-owned configuration can be exported and migrated.',
        'S49-settings-export'],
    ['X1', 'exceeds', 'AHG plugin catalogue — installed and active',
        'Plugin admin view showing the breadth of the AHG catalogue active on the demo. Demonstrates depth of the platform beyond the minimum bid scope.',
        'X1-plugin-catalogue'],
    ['X2', 'exceeds (OAIS)', 'OAIS-aligned SIP / AIP / DIP package',
        'File tree of a committed OAIS Archival Information Package: `manifest.json`, `premis.json`, `metadata/`, `objects/`, submission documentation. Conforms to ISO 14721:2012.',
        'X2-oais-sip-aip-dip'],
    ['X3', 'exceeds (IIIF)', 'IIIF viewer for digitised content',
        'High-res IIIF deep-zoom viewer on a digitised page. Annotation layer + searchable OCR overlay (ahgIiifPlugin).',
        'X3-iiif-viewer'],
    ['X4', 'exceeds (API)', 'Webhook delivery log',
        'Per-event webhook delivery log: each subscribed system receives POST callbacks on archive events (description.create, dsar.created, retention.due). Retries on failure with exponential back-off.',
        'X4-webhook-delivery'],
    ['X5', 'exceeds (UX)', 'Responsive / mobile view',
        'Same archival record on a mobile viewport (414 × 896). Bootstrap 5 theme reflows; tablet and phone are first-class clients.',
        'X5-responsive-mobile'],
    ['X6', 'exceeds (backup)', 'Backup & restore admin',
        'Backup history with sizes, dates and a tested-restore indicator. Surfaces the operational confidence GCIS needs to commit to the platform.',
        'X6-backup-restore'],
];

// Build clause-to-codes map
$clauseMap = [];
foreach ($entries as [$code, $clause, $title, $desc, $file]) {
    $clauseMap[$clause][] = $code;
}

// Markdown body
$md  = "# GCIS RFB-001 2026/2027 — Evidence Index\n\n";
$md .= "**AtoM-Aligned Plan · Gate 1 Criterion 2 · The Archive and Heritage Group (Pty) Ltd · 2026-05-12**\n\n";
$md .= sprintf(
    "> %d screenshots covering **%d** tender clauses. **%d** mandatory (S1–S49), **%d** supplementary (X1–X6) demonstrating \"exceeds expectations\" capability. Captured live from `https://psis.theahg.co.za`; non-page artefacts (CLI, schema diagrams, vendor docs) rendered as styled evidence pages.\n\n",
    count($entries), count($clauseMap),
    count(array_filter($entries, fn ($e) => $e[0][0] === 'S')),
    count(array_filter($entries, fn ($e) => $e[0][0] === 'X')),
);
$md .= "The pack is organised by tender clause. Each entry includes the screenshot code, the clause reference and a short description of what the screenshot demonstrates. Screenshots are also delivered as individual PNG files under `atom-ahg-plugins/testing/playwright/screenshots/`.\n\n";

$md .= "## Clause-to-screenshot map\n\n";
$md .= "| Clause | Coverage | Screenshot codes |\n";
$md .= "|---|---|---|\n";
foreach ($clauseMap as $clause => $codes) {
    // grab first entry's title (truncated) for coverage column
    $firstEntry = array_values(array_filter($entries, fn ($e) => $e[1] === $clause))[0];
    $coverage = preg_replace('/—.*/', '', $firstEntry[2]) . (count($codes) > 1 ? sprintf(' (+ %d more)', count($codes) - 1) : '');
    $md .= sprintf("| %s | %s | %s |\n", $clause, trim($coverage), implode(', ', $codes));
}

$md .= "\n\\newpage\n\n";

// Entries
foreach ($entries as [$code, $clause, $title, $desc, $file]) {
    $imgPath = $shotsDir . '/' . $file . '.png';
    $md .= sprintf("## %s — %s — %s\n\n", $code, $clause, $title);
    $md .= $desc . "\n\n";
    if (is_file($imgPath)) {
        $md .= "![" . $code . "](" . $imgPath . ")\n\n";
    } else {
        $md .= "*screenshot missing — capture before bid submission*\n\n";
    }
    $md .= "`" . $file . ".png`\n\n";
}

$tmpMd = $shotsDir . '/AHG_GCIS_RFB-001_Evidence_Index.md';
file_put_contents($tmpMd, $md);
echo "Wrote markdown: " . filesize($tmpMd) . " bytes\n";

// Pandoc to DOCX
$outDocx = $outDir . '/AtoM_Heratio_GCIS_RFB-001_Evidence_Index.docx';
$cmd = sprintf(
    "pandoc %s -o %s --from=markdown --to=docx --metadata title='GCIS RFB-001 — Evidence Index' --metadata author='The Archive and Heritage Group (Pty) Ltd' 2>&1",
    escapeshellarg($tmpMd), escapeshellarg($outDocx),
);
exec($cmd, $out, $rc);
if ($rc !== 0) {
    echo "pandoc failed: " . implode("\n", $out) . "\n";
    exit(1);
}
echo "Wrote DOCX: " . $outDocx . " (" . filesize($outDocx) . " bytes)\n";
