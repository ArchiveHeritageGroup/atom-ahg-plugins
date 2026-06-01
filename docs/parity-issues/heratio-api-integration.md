Heratio-side parity gaps from the 2026-05-31 two-way audit (PSIS/AtoM has these; Heratio does not).

**Domain:** api-integration

### Features to add to Heratio (present in PSIS/AtoM)
- **[medium]** Full GraphQL Schema Builder with Type System — _PSIS plugin: ahgGraphQLPlugin/lib/GraphQL/Schema/SchemaBuilder.php + Types/*_: AtoM uses graphql-core library with proper ObjectType/InterfaceType/ConnectionTypes; Heratio GraphqlController hand-parses queries with regex patterns, no schema builder
- **[low]** GraphQL Query Complexity & Depth Analysis — _PSIS plugin: ahgGraphQLPlugin/lib/GraphQL/Security/ComplexityAnalyzer.php + DepthLimitRule.php_: AtoM enforces query complexity limits and depth restrictions; Heratio has no equivalent security layer
- **[low]** Spectrum Collections Events Feed (timeline) — _PSIS plugin: ahgSpectrumPlugin (inferred from SpectrumApiController reference)_: Heratio SpectrumApiController exists but is partially implemented; AtoM Spectrum integration is complete

Mirror the PSIS/AtoM implementation under `/usr/share/nginx/archive/atom-ahg-plugins/`.