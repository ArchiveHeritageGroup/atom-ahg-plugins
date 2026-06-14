# Heratio → PSIS Parity Port — Waves 1–3 (session 2026-06-13/14)

**Repo:** ArchiveHeritageGroup/atom-ahg-plugins · **Releases:** v3.62.9 → v3.62.21
**Goal:** Port Heratio (Laravel, v1.142.x) functionality INTO PSIS Symfony plugins for parity — *feature porting into plugins, never a code copy* (PSIS is live production AtoM).

## Method: Phase-0 audit + verify-first
- Phase-0 multi-agent audit of all **114 Heratio packages** → matrix + 8-wave plan (`docs/sessions/2026-06-13-heratio-psis-parity-audit.md`).
- **Every wave verified before building** (read-only agents check claimed gaps vs live code). This stripped **~60% of the audit as over-counts** — already-present, in a sibling plugin, or base AtoM. Avoided large amounts of duplicate work.

## Wave 1 — Core entities & metadata (RELEASED v3.62.9–v3.62.12, live)
- **ahgCustomFieldsPlugin**: multiselect field type.
- **ahgFunctionManagePlugin**: parallel/other names (148/149) + maintenance note (127) + related functions/resources display.
- **ahgTermTaxonomyPlugin**: SKOS export (RDF/Turtle/NT/JSON-LD) + related-authorities + `skos:validate` + `skos:import` CLI (round-trip verified).
- **Shared IO** (`InformationObjectCrudService`/`IoFormHelper` + dacs/rad): related material descriptions (type 173), presence-flag-guarded (other IO saves byte-identical). Live smoke-test incl. regression passed.
- De-scoped (over-count): rad-manage, IO Export/Import (covered by ahgExportPlugin/ahgMetadataExportPlugin), repository-manage (base AtoM owns ISDIAH edit/view).

## Wave 2 — Compliance, rights & governance (substantially shipped)
- **ahgNAZPlugin**: research-visit logging + researcher edit + full compliance reports (v3.62.13–15, live).
- **ahgNMMZPlugin**: monument-inspection recording UI (v3.62.15, live).
- **ahgIntegrityPlugin**: records-management — vital records, declarations, destruction certificates, retention events (new `IntegrityRecordsService` + 4 tables + `/admin/integrity/records`). **Deployed** (DDL + release + activate).
- **ahgSecurityClearancePlugin** (unlocked by Johan): ACL group management — groups/membership/permission-matrix (AtoM-native model) + admin UI. **Deployed** (v3.62.20). Enforcement deferred (flag-off; `checkPermission` unwired). MFA OTP/policy skipped (WebAuthn + framework TotpService already cover MFA).
- **vendor**: #1263 audit-trail already wired; PII #1264 deferred to a dedicated encryption initiative.
- De-scoped: cdpa (0 real), rights (10/12 present), extended-rights (PREMIS subsystem lives in ahgRightsPlugin).

## Wave 3 — Discovery/search/semantic/API (RELEASED v3.62.21, live)
- **ahgDiscoveryPlugin**: `/discovery/suggest` type-ahead.
- **ahgMetadataExportPlugin**: MODS + EAC-CPF + METS exporters; **fixed `getFormats()`** (was undefined → index UI fataled); RSS feed + DCAT + VoID in the linkedData module.
- **ahgAPIPlugin**: `/api/v2/descriptions/:slug/citation` (BibTeX/RIS/Dublin Core).
- De-scoped: ric (1/23 — ahgRicExplorerPlugin mature), oai (0/13 — arOaiPlugin), metadata-export (mature; only 3 exporters genuinely missing), sitemap/OpenAPI/IIIF (already present), graph/SPARQL (in ahgRicExplorerPlugin).

## Deferred → dedicated future sessions
- **AI-wave**: vector/semantic search (Qdrant + AI gateway), semantic-search north-star (endangered-heritage/repatriation/research-leads/language-corpus), privacy autopilot, MFA OTP/policy.
- **PII encryption** (#1261 donor + #1264 vendor): framework `EncryptableFieldService` registry + column-widening DDL + backfill — high-risk, careful sequencing.
- **ACL enforcement wiring** (currently flag-off).
- **Deploys to other instances**: WDB (#135) and ANC (#134) — out of PSIS scope; Johan to run.

## Key gotchas captured
- `bin/release` does git only; PSIS needs `rm -rf cache/qubit/prod/* && systemctl restart php8.3-fpm` to activate (opcache.validate_timestamps=0 + compiled routing). version.json is gitignored (the tag is the source of truth).
- mysql: `~/.my.cnf` has wrong creds → use `--no-defaults` + password from `config/config.php` via `MYSQL_PWD`.
- Harness blocks prod DDL + GitHub writes → Johan runs those.
- Template escaping: use `$sf_data->getRaw()` for action arrays embedded in templates.
