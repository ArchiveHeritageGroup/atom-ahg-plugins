PSIS-parity epic from the 2026-05-31 two-way audit (see docs/HERATIO_PSIS_PARITY_AUDIT.md).

**Domain:** api-integration — current parity ≈ **75%**.

The api-integration domain shows 75% parity between Heratio and PSIS/AtoM. Heratio excels in modern API design patterns (OpenAPI specs, idempotency, ETags, rate-limit headers) and introduces Europeana EDM export, marketplace REST endpoints, and EXIF/IPTC metadata aggregation — all absent from PSIS. However, AtoM's GraphQL implementation is more mature (using graphql-core library with proper schema builders and security analyzers), and provides better SharePoint integration via PostIngestHookService. The core REST v2 API endpoints (descriptions, authorities, assets, webhooks, sync, privacy, publish) are functional in both systems with comparable coverage. The critical gaps are: (1) Heratio's OpenAPI/Idempotency/ETag features are enterprise-grade but missing from AtoM; (2) AtoM's GraphQL properly uses a schema library vs Heratio's regex-based hand-parsing; (3) Marketplace API is partially wired only in Heratio; (4) Europeana export is Heratio-only. The remaining 25% gap is split between missing advanced Heratio features in PSIS (10-12%) and some AtoM-specific enhancements like GraphQL security rules (8-10%).

### High-severity gaps (PSIS missing)
- **OpenAPI 3.1 Specification Generation** — `ahg-api/src/Services/OpenApiGenerator.php` → `ahgAPIPlugin`. Heratio has reflective OpenAPI spec generation (line 40+), AtoM has no OpenApiGenerator equivalent; no OpenAPI generation found in ahgAPIPlugin
- **Idempotency-Key Request Deduplication (RFC 7231 + custom)** — `ahg-api/src/Middleware/IdempotencyKeyMiddleware.php` → `ahgAPIPlugin`. Heratio middleware caches POST/PUT/PATCH responses by client-provided Idempotency-Key header; no idempotency support found in AtoM
- **ETag Conditional Request Support** — `ahg-api/src/Middleware/ETagMiddleware.php` → `ahgAPIPlugin`. Heratio generates ETag headers on GET responses and honours If-None-Match (304 Not Modified); no ETag handling in AtoM API

### Medium-severity gaps
- Marketplace REST API Endpoints — `ahg-api/routes/api.php:234-239` → `ahgAPIPlugin/modules/apiv2`.
- Marketplace Service & Payment Integration — `ahg-api/src/Controllers/V2/MarketplaceController.php` → `ahgAPIPlugin`.
- Europeana EDM RDF/XML Export Command — `ahg-federation/src/Console/EuropeanaExportCommand.php + src/Edm/EdmSerializer.php` → `ahgFederationPlugin`.
- API Rate Limiting Headers (X-RateLimit-*) — `ahg-api/src/Middleware/ApiRateLimit.php:50-51` → `ahgAPIPlugin`.
- Digital Object Embedded Metadata (EXIF/IPTC/ffprobe) — `ahg-api/src/Services/EmbeddedMetadataService.php` → `ahgAPIPlugin`.
- Batch Operation Support (max 100 ops) — `ahg-api/src/Controllers/V2/BatchController.php` → `ahgAPIPlugin/modules/apiv2/actions/batchAction.class.php`.

### Remediation (per AHG twin convention)
- **A — port via patches/plugin extension** (preferred): mirror the Heratio package logic into the AtoM plugin.
- **B — new plugin** where no counterpart exists (e.g. MISSING).
- **C — skip/defer** if not required for PSIS market.

Mirror the Heratio implementation under `/usr/share/nginx/heratio/packages/`.