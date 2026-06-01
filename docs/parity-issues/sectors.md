PSIS-parity epic from the 2026-05-31 two-way audit (see docs/HERATIO_PSIS_PARITY_AUDIT.md).

**Domain:** sectors — current parity ≈ **42%**.

Heratio and PSIS/AtoM have significant divergence on the sectors domain, with only ~42% parity. AtoM has substantially more advanced features in Museum (loan management, Getty integration, condition reporting, CIDOC-CRM) and Marketplace (12 vs 3 services, multi-sector support). Heratio leads in Bibliographic standards (BIBFRAME, FRBR dedicated packages) and Library APIs (full API suite for orders, serials, vendors, budgets). Library and Exhibition/Gallery are roughly equivalent in structure but with different execution models (Artisan vs Symfony tasks). Museum is the most divergent: AtoM is feature-complete for institutional loan workflows while Heratio museum is a skeleton. Neither side has the other's biblio packages or comprehensive Getty integration—these are Heratio-only and AtoM-only respectively. Heratio is still Phase 1 (basic browse/display) while AtoM's sectors plugins are mature operational systems.

### High-severity gaps (PSIS missing)
- **BIBFRAME 2.0 bibliographic serialization** — `ahg-biblio-bf` → `N/A (no BIBFRAME plugin exists)`. Heratio has BibframeService.php, BibframeSerialisationService.php with RDF/XML/Turtle/JSON-LD serialization; AtoM has no equivalent plugin
- **FRBR (IFLA) conceptual model support** — `ahg-biblio-frbr` → `N/A (no FRBR plugin exists)`. Heratio has FrbrService.php with Work-Expression-Manifestation-Item mapping and OpenRiC integration; AtoM has no dedicated FRBR plugin (partial FRBR in library)
- **Library API suite (order, serial, vendor, budget APIs)** — `ahg-library` → `ahgLibraryPlugin`. Heratio has LibraryOrderApiController, LibrarySerialApiController, LibraryVendorApiController, LibraryBudgetApiController in Api/ folder; AtoM has no libraryApi module or equivalent APIs
- **OPAC patron self-service and OPAC search** — `ahg-library` → `ahgLibraryPlugin`. Heratio has OpacPatronController and LibraryOpacSearchService (with ES fallback); AtoM has SearchService with limited Lucene-style support only, no dedicated OPAC patron module
- **Museum loan management (loan out/in, courier, facility)** — `ahg-museum (minimal)` → `ahgMuseumPlugin`. AtoM has loan_schema.sql, LoanService.php, LoanDashboardService.php, CourierManagementService.php, FacilityReportService.php; Heratio museum package has no loan management at all
- **Museum Getty vocabulary linking (AAT, TGN, ULAN)** — `ahg-museum (missing)` → `ahgMuseumPlugin`. AtoM has GettyLinkingService.php, AatService.php, TgnService.php, UlanService.php with batch linking capabilities; Heratio museum has no Getty integration

### Medium-severity gaps
- Museum condition reporting and provenance tracking — `ahg-museum (missing)` → `ahgMuseumPlugin`.
- Museum object comparison and deduplication — `ahg-museum (missing)` → `ahgMuseumPlugin`.
- Console commands for serial management — `ahg-library` → `ahgLibraryPlugin`.
- Exhibition staging and workflow management — `ahg-exhibition` → `ahgExhibitionPlugin`.

### Remediation (per AHG twin convention)
- **A — port via patches/plugin extension** (preferred): mirror the Heratio package logic into the AtoM plugin.
- **B — new plugin** where no counterpart exists (e.g. MISSING).
- **C — skip/defer** if not required for PSIS market.

Mirror the Heratio implementation under `/usr/share/nginx/heratio/packages/`.