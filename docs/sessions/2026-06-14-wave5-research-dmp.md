# Wave 5 — Research Data Management Plans (DMP) (2026-06-14)

**Repo:** atom-ahg-plugins · **Plugin:** ahgResearchPlugin · **Status:** built + lint-clean + **deployed live to PSIS** (DDL run, activated). NOT released.

## Context
Wave-5 verify-first sweep (read-only vs live PSIS): **portable-export + favorites = PRESENT**; the rest PARTIAL. `ahg-ingest` AI-enrichment gap needs the gateway key (deferred with the AI-wave). Of the clean, self-contained gaps, built the **Research DMP** (one of the `ahg-research` PARTIAL gaps); avoided records-manage retention (would overlap ahgIntegrityPlugin's existing retention/holds/disposition) and the object-coupled request-to-publish workflow.

## Built — Data Management Plans (Science Europe / Horizon Europe core)
- **2 tables** (`database/dmp.sql`): `research_dmp` (researcher-owned, optional project link, 9 narrative sections: data summary + FAIR a–d + resources + security + ethics + other, status/version/funder/grant) + `research_dmp_dataset` (per-dataset: type, formats, volume, sensitivity, personal-data flag, license, repository, retention, sharing policy). No ENUMs, no core-table FKs.
- **`lib/Services/DmpService.php`** — CRUD + dataset CRUD + ownership guard + completeness scoring (% sections filled) + Markdown & JSON export.
- **5 actions** added to `researchActions` (`dmps`, `dmpEdit`, `dmpView`, `dmpExport`; helper `currentResearcherOrRedirect`) — researcher-owned, ownership-checked, `sfView::NONE` after redirects.
- **3 templates** (`dmpsSuccess`, `dmpEditSuccess`, `dmpViewSuccess`) — `decorate_with('layout_2col')` + research sidebar, Bootstrap 5, completeness progress bars, dataset table + add modal, MD/JSON export buttons.
- **Sidebar link** added to `_researchSidebar.php` (Data Management Plans, active='dmps').
- **Routes** `/research/dmps|dmp/edit|dmp/view|dmp/export`.

## Verification
- All 7 files `php -l` clean; 2 tables created; `/research/dmps` → 302→`/user/login` (auth gate, HTTP 200), **no 500, no ahg_error_log entries**. Authenticated UI render not visually confirmed (researcher login required) — consistent with prior login-gated waves.
- No base-AtoM changes.

## Deployed (already done this session)
- `mysql archive < database/dmp.sql` ✅ · `rm -rf cache/qubit/prod/* && systemctl restart php8.3-fpm` ✅

## Unit 2 — GraphQL research types + ORCID (ahgGraphQLPlugin) — built + schema-verified + live
- New `lib/GraphQL/Schema/Types/ResearchType.php` (Researcher / ResearchProject / Annotation ObjectTypes) + `lib/GraphQL/Resolvers/ResearchResolver.php` (queries research_researcher/_project/_annotation via Illuminate).
- Wired into `GraphQLService::buildContext` (+`use`) and `SchemaBuilder` query fields: `researcher(id|orcid)`, `researchers`, `researchProject(id)`, `researchProjects`, `annotations(objectId?)`. ORCID now exposed (orcid + orcidVerified on Researcher; lookup by ORCID).
- **Privacy-safe:** only approved researchers, public projects, public+non-private annotations; email admin-gated; api_key / ORCID tokens / ID numbers never selected.
- **Verified:** all files `php -l` clean; standalone `Schema::assertValid()` PASSED — 5 new query fields + 3 new types (13/13/10 fields) present, existing fields intact; live endpoint returns clean GraphQL JSON (401 auth-required, no 500). No DDL (queries existing tables). fpm restarted. Authed query needs a GraphQL API key (not run).

## Unit 3 — Provenance trace API + coverage reporting (ahgProvenancePlugin) — built + live-verified
- New `lib/Service/CoverageService.php` (namespace AhgProvenancePlugin\Service): `report()` (published-catalogue coverage %, certainty/research-status distributions, gaps, incomplete, Nazi-era diligence outstanding/unclear, cultural-property status), `gaps()`, `uncovered()` (published IOs with no provenance record).
- 3 actions on `provenanceActions`: `apiTrace` (JSON chain of custody, public records only), `apiCoverage` (JSON report, admin), `coverage` (admin dashboard). `requireAdmin()` helper.
- New `coverageSuccess.php` dashboard + routes via new `addRoutes()` in config (config previously had none).
- ⚠️ **Gotcha:** nginx on PSIS has `location ^~ /api/provenance/` (proxied elsewhere → bare 404), so routes live under **`/provenance/`** not `/api/provenance/`: `/provenance/trace/:id`, `/provenance/coverage-data`, `/provenance/coverage`. (`/api/v2` falls through to Symfony fine; only specific `^~ /api/...` prefixes are intercepted.)
- **Live-verified:** all `php -l` clean; `/provenance/trace/631` → **200 JSON** (real record); `/provenance/coverage*` → 403 admin gate (no 500). No DDL (existing provenance tables). fpm restarted. Admin dashboard render not visually confirmed (auth).

## Unit 4 — Request-to-Publish workflow (ahgRequestToPublishPlugin) — built + live-verified
- New companion tables `rtp_workflow` (receipt token, is_anonymous, triage_status, priority, assignment, internal_notes) + `rtp_review` (peer reviews) — core object-coupled `request_to_publish(_i18n)` table untouched.
- New `lib/Services/WorkflowService.php` (ns ahgRequestToPublishPlugin\Services): ensureWorkflow+token, getByToken, inbox+counts, assign/triage/priority/notes, addReview/getReviews, statusLabel (220 in-review / 219 approved / 221 rejected).
- 3 new actions (camelCase `requestToPublish` module — the live one; lowercase `requesttopublish` is legacy): `receipt` (public token tracking), `inbox` (admin curator triage queue), `review` (admin peer-review + notes). submitAction hooked to mint a receipt token + redirect to /receipt.
- 3 templates (layout_1col) + routes added LAST in loadRoutes (loader prepends → matched before generic `/requesttopublish/:slug`).
- **Live-verified:** all `php -l` clean; `/requesttopublish/receipt` 200 (public form), `/receipt/:token` 200 (lookup), `/inbox` 403 (admin gate); no 500. rtp_workflow+rtp_review created; fpm restarted. Authed inbox/review render + submit→token e2e not visually confirmed.

## Wave-5 remaining genuine gaps (not built)
- ahg-research: writing/publication/method/grant "studios" (LLM — better after gateway key); formal ethics-review workflow.
- ahg-records-manage: disposal approval workflow / review queue / auto-classification / compliance assessment (⚠️ check overlap with ahgIntegrityPlugin retention/holds/disposition first).
- ahg-graphql: research/annotation/researcher types + ORCID field resolution.
- ahg-ingest: AI enrichment (LLM — needs gateway key).
- ahg-request-publish: anonymous submit + receipt token + curator inbox + peer review (object-coupled; more involved).
- ahg-provenance: trace API + coverage reporting.
