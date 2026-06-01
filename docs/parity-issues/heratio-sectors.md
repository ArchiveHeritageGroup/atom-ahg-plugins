Heratio-side parity gaps from the 2026-05-31 two-way audit (PSIS/AtoM has these; Heratio does not).

**Domain:** sectors

### Features to add to Heratio (present in PSIS/AtoM)
- **[high]** Museum loan management suite (loan out/in, courier, facility, calendar) — _PSIS plugin: ahgMuseumPlugin_: AtoM has comprehensive loan_schema.sql (loan, loan_item, condition_report, courier tables), LoanService.php, LoanDashboardService.php, LoanNotificationService.php, LoanCalendarService.php, CourierManagementService.php; Heratio museum is minimal (only browse + vocabulary)
- **[high]** Museum Getty vocabulary integration (SPARQL, auto-linking, caching) — _PSIS plugin: ahgMuseumPlugin_: AtoM has Getty/ subfolder with GettySparqlService.php, GettyLinkingService.php, AatService.php, TgnService.php, UlanService.php, GettyCacheService.php with batch linking and confidence scoring; Heratio museum has no Getty services
- **[high]** Museum CCO (Cataloging Cultural Objects) taxonomy — _PSIS plugin: ahgMuseumPlugin_: AtoM has cco_taxonomies.sql with complete CCO vocabulary mapping and CcoTaxonomyService.php; Heratio museum has no CCO support
- **[medium]** Museum CIDOC-CRM compliance module — _PSIS plugin: ahgMuseumPlugin_: AtoM has ahgMuseumPlugin/modules/cidoc/ module; Heratio has no CIDOC-CRM module
- **[medium]** Museum object measurements and technical details — _PSIS plugin: ahgMuseumPlugin_: AtoM has MeasurementService.php for structured measurements; Heratio has no measurement service
- **[medium]** Museum condition assessment and reporting — _PSIS plugin: ahgMuseumPlugin_: AtoM has ConditionReportService.php with structured condition assessment; Heratio has no condition assessment
- **[medium]** Museum provenance and custody chain — _PSIS plugin: ahgMuseumPlugin_: AtoM has ProvenanceService.php for detailed provenance tracking; Heratio has no provenance service
- **[low]** Gallery artist and collections management — _PSIS plugin: ahgGalleryPlugin_: Gallery modules exist in AtoM but Heratio gallery is minimal (only browse + CSV import)

Mirror the PSIS/AtoM implementation under `/usr/share/nginx/archive/atom-ahg-plugins/`.