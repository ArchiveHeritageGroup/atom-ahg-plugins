# ahgAuthorityResolutionPlugin - Technical Documentation

Symfony 1.4 plugin that implements the AHG Authority Resolution Engine on the AtoM Heratio side. Mirror of the Laravel `packages/ahg-authority-resolution/` package: same MySQL tables, same five decision outcomes, same ten evidence evaluators, same RDF-Star provenance shape (isolated by named-graph URI), same seven external authority adapters.

- Plugin root: `atom-ahg-plugins/ahgAuthorityResolutionPlugin/`
- Database: `archive`
- Licence: GPL-3.0-or-later
- Status: Tasks 1-10 shipped (engine end-to-end + create-new sub-workflow + park queue + NER feedback + CLI consolidation)

## Plugin layout

```
atom-ahg-plugins/ahgAuthorityResolutionPlugin/
  config/
    ahgAuthorityResolutionPluginConfiguration.class.php   # registers routes + module
  database/
    install.sql                                            # 7 tables (idempotent)
    seed_candidate_config.sql                              # adapter precedence + top-N
    seed_lookup_settings.sql                               # 37 external-adapter settings
    seed_role_language.sql                                 # ~120 role-language tokens
  lib/
    Services/
      Adapters/                                            # candidate-generation adapters
      AuthorityCreator.php                                 # Qubit CTI inserts (Task 6)
      CandidateGeneratorService.php                        # Task 3
      ContextDerivationService.php                         # Task 2
      DecisionProvenanceWriter.php                         # decisions RDF-Star (Task 8)
      DecisionRecorder.php                                 # Task 5 single-entry decision pipeline
      Evidence/                                            # 10 evaluators + EvidenceSignal
      EvidenceScorer.php                                   # Task 4 orchestrator
      FieldProvenanceWriter.php                            # field-provenance RDF-Star (Task 6)
      FusekiUpdateService.php                              # SPARQL Update + Query
      Lookup/
        AbstractLookupAdapter.php
        LookupAdapterInterface.php
        PrefillEngine.php
        Adapters/                                          # ViafAdapter, WikidataAdapter, ...
      NerFeedbackService.php                               # Task 9
      ParkQueueService.php                                 # Task 7
      PromoteToMentionService.php                          # Task 1
    task/
      authResCacheClearTask.class.php
      authResCacheStatsTask.class.php
      authResExportNerFeedbackTask.class.php
      authResGenerateCandidatesTask.class.php
      authResPromoteSampleTask.class.php
      authResReprocessParkedTask.class.php
      authResReprocessTask.class.php
      authResScanParkedTask.class.php
      authResScoreEvidenceTask.class.php
      authResStatusTask.class.php
      authResWriteProvenanceTask.class.php
  modules/
    authorityResolution/
      actions/actions.class.php                            # 7+ actions
      templates/
        indexSuccess.php                                   # queue list
        reviewSuccess.php                                  # 3-region review screen
        _candidateCard.php
        _evidenceRow.php
        _linkDifferentModal.php
        _parkModal.php
        _rejectModal.php
        _parkRow.php
        _prefillField.php
        createNewSuccess.php                               # Task 6 wizard
        lookupSettingsSuccess.php                          # admin settings
        parkListSuccess.php                                # Task 7 dedicated screen
  docs/
    help/                                                  # this article + 7 siblings
```

## Schema (7 tables)

| Table | Purpose |
|---|---|
| `ahg_mention` | One workflow row per promoted NER entity |
| `ahg_mention_context` | Neighbourhood context packet (1:1 with mention) |
| `ahg_mention_candidate` | Ranked candidates per mention |
| `ahg_mention_decision` | Immutable audit. One row per decision event. |
| `ahg_mention_park` | One active row per parked mention (UNIQUE on mention_id) |
| `ahg_ner_feedback` | One row per `decision_type=reject`. Source for NER retraining. |
| `ahg_authority_lookup_cache` | Cache for external authority lookups (VIAF / Wikidata / ...) |

All `InnoDB` + `utf8mb4_unicode_ci`. No foreign keys to base AtoM tables (decouples from Qubit schema migrations; orphan rows are tolerated and filtered in the UI).

`ahg_mention.state`: `pending` / `linked` / `parked` / `rejected` / `new_record_created` (VARCHAR(30) with COMMENT, not ENUM, per CLAUDE.md).

`ahg_mention_decision.decision_type`: `link` / `link_different` / `create_new` / `park` / `reject`.

See the `authority-resolution-user-guide` and `authority-resolution-park-queue` articles for column-level documentation.

## Routes (11)

Registered through `AtomFramework\Routing\RouteLoader` in `ahgAuthorityResolutionPluginConfiguration::configure()`. No `routing.yml` is needed; the plugin configuration class is autoloaded once the plugin is enabled via `atom_plugin.is_enabled = 1`.

```
GET  /admin/authorityResolution                                   ar_auth_res_index
GET  /admin/authorityResolution/:id/review                        ar_auth_res_review
POST /admin/authorityResolution/:id/link                          ar_auth_res_link
POST /admin/authorityResolution/:id/link-different                ar_auth_res_link_different
GET  /admin/authorityResolution/:id/create-new                    ar_auth_res_create_new
POST /admin/authorityResolution/:id/create-new-submit             ar_auth_res_create_new_submit
POST /admin/authorityResolution/:id/park                          ar_auth_res_park
POST /admin/authorityResolution/:id/reject                        ar_auth_res_reject
GET  /admin/authorityResolution/lookup                            ar_auth_res_lookup
GET  /admin/authorityResolution/park                              ar_auth_res_park_list
POST /admin/authorityResolution/park/:id/unpark                   ar_auth_res_unpark
GET  /admin/authorityResolution/park/dashboard.json               ar_auth_res_park_dashboard_json
GET  /admin/authorityResolution/settings/lookup                   ar_auth_res_lookup_settings
POST /admin/authorityResolution/settings/lookup                   ar_auth_res_lookup_settings_save
```

Action handlers detect `X-Requested-With: XMLHttpRequest` or `?format=json` and return `application/json`; otherwise they redirect to the next pending review or back to the queue (flash-noticed).

## ACL

Reuses the existing AtoM credential check pattern (`$this->context->user->isAuthenticated()` and `hasCredential('editor')` / `isAdministrator()`):

- Index, review, lookup, park-list, park-dashboard: `requireAuth()`
- All POST decision handlers + unpark + create-new: `requireEditor()`
- Settings page (`lookupSettings*`): `requireAuth()` + `isAdministrator()`

No new ACL system. When granular `AclService::check('authorityResolution.decide')` lands later, it slots into `requireEditor()` without rewiring the action handlers.

## SF1.4-specific patterns

- **Explicit `require_once`** at the top of every action method and every task. Symfony 1.4 has no PSR-4 autoloader for the plugin's `AtomFramework\Services\AuthorityResolution\` tree. Services are top-loaded centrally in the four loader helpers on `authorityResolutionActions`.
- **`AhgCore\Core\AhgDb::init()`** in every task `execute()` to get the Capsule handle. Service classes assume Capsule, not Propel.
- **Action class name is `<module>Actions`** (no `ar` prefix). E.g. `authorityResolutionActions extends sfActions`. The module is auto-discovered once enabled in the plugin configuration's `initialize()` via `sfConfig::set('sf_enabled_modules', ...)`.
- **Template layout is `layout_1col`** from `ahgThemeB5Plugin/templates/`. Templates start with `<?php decorate_with('layout_1col'); ?>` and contribute three slots: `title`, `before-content`, `content`. The theme bundle injects navbar, footer, voice-commands modal, clipboard normalizer, and BS5 CSS. No manual `<head>` boilerplate is needed.

## Decision pipeline (`DecisionRecorder::record()`)

Single entry point for all five decision types. In sequence:

1. Freeze the visible candidate slate as a JSON snapshot.
2. Freeze the chosen candidate's evidence signals + data as a JSON snapshot.
3. Insert an immutable `ahg_mention_decision` row.
4. Transition `ahg_mention.state`.
5. For `link` / `link_different`: back-update `ahg_ner_entity.linked_actor_id` (the existing consumer contract used by `ahgPrivacyPlugin`, `ahgLibraryPlugin`, `ahgDiscoveryPlugin`).
6. For `park`: write the `ahg_mention_park` row with reason.
7. For `reject`: call `NerFeedbackService::captureFromRejection()` inside a try/catch (failure does NOT roll back the decision; surfaces as `feedback_error`).
8. Synchronously call `DecisionProvenanceWriter::write()` so the RDF-Star tuple lands in `urn:atom:auth-res:graph:decisions` on Fuseki within the same request. There is no queue on AtoM.

## The 10 evaluators

```
lib/Services/Evidence/
  EvidenceSignal.php            # MATCH / CONFLICT / SILENT / ABSENT enum
  EvaluatorInterface.php
  TemporalEvaluator.php         # PERSON / ORG
  GeographicEvaluator.php       # PERSON / ORG
  RelationalEvaluator.php       # PERSON / ORG
  RoleEvaluator.php             # PERSON / ORG
  ConflictEvaluator.php         # PERSON / ORG
  HierarchicalEvaluator.php     # PLACE
  DocumentPriorService.php      # PLACE - helper, not evaluator
  PriorEvaluator.php            # PLACE
  CoOccurringPersonEvaluator.php # PLACE
  PlaceConflictEvaluator.php    # PLACE
  ScaleEvaluator.php            # PLACE
```

The orchestrator (`EvidenceScorer`) dispatches by `supports($entity_type)`. Composite formula:

```
composite = name_similarity_score
  + 0.10 per match
  - 0.30 per conflict
  + 0    per silent
  + 0    per absent
composite = clamp(composite, 0.0, 1.0)
```

See `authority-resolution-evidence-scoring` for the per-evaluator detail.

## The 7 external lookup adapters

```
lib/Services/Lookup/Adapters/
  ViafAdapter.php               # CC0-1.0 - live
  WikidataAdapter.php           # CC0-1.0 - live
  GeoNamesAdapter.php           # CC BY 4.0 - live (requires username)
  TgnAdapter.php                # ODbL 1.0 - stub
  GndAdapter.php                # CC0-1.0 - stub
  IsniAdapter.php               # ISNI ToU - stub
  SagncAdapter.php              # TBD - stub
```

All disabled by default. 37 settings keys in `ahg_settings` (seeded by `database/seed_lookup_settings.sql`) drive enable/rate-limit/cache-TTL/licence per source, plus precedence ordering and the GeoNames username.

## Two Fuseki named graphs

- `urn:atom:auth-res:graph:decisions` - decision provenance (one `prov:Activity` per decision)
- `urn:atom:auth-res:graph:field-provenance` - per-field create-new provenance (reified RDF-Star, one assertion per accepted pre-fill)

Both URIs are configurable via `ahg_settings.authority_resolution.decisions_graph_uri` and `authority_resolution.field_provenance_graph_uri`. Codebase-prefixed (`atom:` vs `heratio:`) so a single Fuseki instance can hold both codebases' provenance without bleed.

## File inventory cross-reference

| Layer | Tasks 2-5 | Task 6 (create-new) | Tasks 7+9 (park+feedback) | Task 10 (CLI) |
|---|---|---|---|---|
| Services | PromoteToMention, ContextDerivation, CandidateGenerator, EvidenceScorer (+10 evaluators), DecisionRecorder, DecisionProvenanceWriter, FusekiUpdate | AuthorityCreator, FieldProvenanceWriter, PrefillEngine (+7 adapters) | ParkQueueService, NerFeedbackService | (extensions to FusekiUpdateService) |
| Tasks | promote-sample, generate-candidates, score-evidence, write-provenance | (none new) | scan-parked, export-ner-feedback | status, reprocess, reprocess-parked, cache-stats, cache-clear |
| Routes | index, review, link, link-different, park, reject, lookup | create-new, create-new-submit, lookupSettings, lookupSettingsSave | parkList, unpark, parkDashboardJson | (none new) |
| Templates | indexSuccess, reviewSuccess, _candidateCard, _evidenceRow, _linkDifferentModal, _parkModal | createNewSuccess, _prefillField, lookupSettingsSuccess | parkListSuccess, _parkRow, _rejectModal | (none new) |

## Convergence with Laravel side

Both codebases write to the same `ahg_*` tables (when both are pointed at the same database) and the same Fuseki dataset (isolated by graph prefix). The two implementations track each other:

- Same MySQL schema (per-file `install.sql` is byte-identical except for trailing engine/charset comments).
- Same five decision outcomes; same VARCHAR enum values.
- Same ten evaluators; same signal vocabulary; same composite formula.
- Same seven external adapters; same 37 settings keys.
- Same JSONL line shape for NER feedback export.
- Same CLI surface (eleven tasks on AtoM, eleven artisan commands on Laravel; one extra `auth-res:write-provenance` on AtoM because Laravel writes provenance synchronously inside the recorder).

If you are running both side-by-side, schedule the periodic tasks (`auth-res:scan-parked`, `auth-res:reprocess-parked`, `auth-res:export-ner-feedback`) on ONE codebase only.

## See also

- `authority-resolution-user-guide` - the high-level overview
- `authority-resolution-review-screen` - the three-region review UI
- `authority-resolution-park-queue` - the dedicated park queue
- `authority-resolution-create-new-authority` - the create-new wizard
- `authority-resolution-evidence-scoring` - the ten evaluators in detail
- `authority-resolution-provenance` - the RDF-Star audit trail
- `authority-resolution-cli-tasks` - the eleven Symfony 1.4 tasks
