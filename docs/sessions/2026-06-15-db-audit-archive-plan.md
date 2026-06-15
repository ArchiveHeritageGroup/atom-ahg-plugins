# Archive (PSIS) — data/feature gaps vs Heratio

**Date:** 2026-06-15
**Audit half:** Archive ← Heratio (what the PSIS `archive` MySQL DB is MISSING that the `heratio` DB has).
**Inputs:** `/tmp/only_heratio.txt` (218 tables in heratio not in archive), `/tmp/coldiff.txt` (272 column-diff rows on shared tables).
**Method:** every heratio-only table + heratio-only column was checked against the live `archive` schema via `information_schema` + row-count + per-table `DESCRIBE`. The 218-table list is already AtoM-domain — none of the obvious Laravel infra (cache, jobs, sessions, migrations, etc.) appears in it (those tables exist in both DBs), so there is effectively **zero "NOISE" in this list**. The real split is **RENAMED/EQUIVALENT vs GENUINE GAP**.

> ⚠️ **Skeptic's note:** a same-name table diff massively over-counts. Of the 218 "missing" tables, a large fraction are the SAME capability living under a different table name in `archive` (PSIS uses `report_*`, heratio uses `ahg_report_*`; PSIS `ai_act_*`, heratio `ai_*`; etc.). Verify each before building. Many heratio-only tables are also **empty stubs** (0 rows) — present in the schema but never used even on Heratio.

---

## Summary counts

| Class | Count (of 218 tables) | In the plan? |
|-------|----------------------|--------------|
| **NOISE** (Laravel infra) | 0 — list is pre-filtered to AtoM domain | excluded |
| **RENAMED/EQUIVALENT** (capability already in archive under another name) | ~95 | No (appendix only) |
| **GENUINE GAP** (real feature/data archive lacks) | ~123 | Yes (ranked below) |

Of the genuine gaps, **~70 are empty stubs even in Heratio** (0 rows) — schema-present but unproven; treat those as LOW unless the feature is wanted. The high-value gaps are concentrated in: **Research suite (advanced)**, **AI gateway metering/governance**, **3D/photogrammetry reconstruction**, **RiC "Thing" entity model**, **tenant/multi-tenancy**, **blog/storytelling**, and a cluster of **heritage-at-risk / repatriation** tables.

---

## GENUINE GAPS — ranked

### 1. AI gateway metering & cost governance — **HIGH** [needs-external/AI/gateway]
Heratio meters and bills AI usage through the gateway; PSIS does not.
- `ahg_ai_call_cost` (209 rows in heratio), `ahg_ai_pricing` (10), `ahg_ai_quota` (7), `ahg_gpu_endpoint` (3 — GPU node registry for routing).
- **Capability:** per-call cost capture, per-key quota, model pricing tables, GPU endpoint registry — i.e. the in-app side of the AHG AI gateway (`ai.theahg.co.za`) metering/quota story.
- archive HAS: `ahg_ai_service_usage`, `ahg_ai_usage`, `ahg_product_pricing` (generic, not AI-call-priced), `ai_inference_key`, `ai_inference_log` — so basic usage logging exists but **cost/quota/pricing/GPU-routing is absent**.
- Tag: aligns with the host's AI-gateway standing rule. Buildable as tables; only meaningful once PSIS routes via the gateway.

### 2. AI assistant / chatbot extras — **MEDIUM** [buildable]
- `ahg_ai_chatbot_message` (12 rows), `ahg_ai_prompt_template` (0), `ahg_ner_custom_entity` (0), `ahg_translation_memory` (8).
- **Capability:** persisted chatbot message log, AI prompt-template library, custom NER entity dictionary, translation-memory reuse.
- archive HAS: `ahg_prompt_template` (different table — generic, **not** `ahg_ai_prompt_template`), the #121 collection chatbot (FULLTEXT). **Chatbot-message persistence, custom NER entities, and translation memory are genuinely absent.**
- Note: `ahg_ai_condition_client` / `ahg_ai_condition_training` (0 rows) are empty stubs → LOW.

### 3. EU AI Act governance — **mostly RENAMED, small genuine residue** — **LOW/MEDIUM** [buildable]
- heratio: `ai_model_registry` (6), `ai_oversight_policy` (7), `ai_risk_register` (8), `ai_review_decision` (0), `ai_operator_attestation` (0), `ai_risk_incident` (0).
- archive HAS the equivalent under `ai_act_*`: `ai_act_model`, `ai_act_risk`, `ai_act_system`, `ai_act_attestation`. **So model-registry / risk-register / attestation are RENAMED — not gaps.**
- **Genuine residue:** `ai_oversight_policy`, `ai_review_decision`, `ai_risk_incident` have no clear `ai_act_*` analog → minor gap (human-oversight policy + review-decision audit + incident log). Verify column-level overlap with `ai_act_*` before building.

### 4. Research suite — advanced research-lifecycle tooling — **MEDIUM** (HIGH for research-portal clients) [buildable, some AI]
archive already has a very rich `research_*` set (~120 tables incl. DMP, journals, lectures, reading-room, reproduction). The heratio-only research tables are the **newer "scholarly workbench" layer**, all empty stubs in Heratio (0 rows) except `research_method_template` (11):
- **Grant management:** `research_grant_call`, `research_grant_draft`, `research_grant_section`, `research_funding`, `research_lead`, `research_impact_signal`.
- **Scholarly writing/argumentation:** `research_writing_doc`, `research_writing_section`, `research_writing_version`, `research_argument`, `research_argument_step`, `research_assertion`-meta (`research_claim_meta`), `research_contradiction`, `research_decision_log`.
- **Analysis/reproducibility:** `research_analysis_code`, `research_analysis_result`, `research_analysis_result_claim`, `research_replication_log`, `research_method_protocol`, `research_method_template`.
- **AI copilot / discovery:** `research_copilot_answer`, `research_ai_disclosure_log`, `research_field_watch`, `research_field_alert`, `research_inbox_item`, `research_memory_item`, `research_source_triage`, `research_question_brief`(+`_version`), `research_scholarship_discovery` (see `ahg_scholarship_discovery`).
- **Submission/peer-review/ethics/team:** `research_submission`(+`_requirement`/`_response`), `research_review_run`, `research_review_comment`, `research_evidence_comment`, `research_ethics`, `research_team_member`, `research_milestone`, `research_output`, `research_export_log`, `research_dmp_section`.
- **Capability:** end-to-end scholarly research lifecycle (grant → DMP-sections → method protocol → analysis code/results → argument/contradiction graph → writing/versioning → submission/peer-review → impact tracking) with an AI copilot. PSIS has the reading-room/reproduction/journal layer but **not** this scholarly-workbench layer.
- ⚠️ All 0-row in Heratio → unproven; build only against a confirmed client need (research-portal #114–117 lineage).

### 5. RiC "Thing" entity model — **MEDIUM** [buildable]
- `ric_thing`, `ric_thing_i18n`, `ric_thing_instantiation`, `ric_thing_location`, `ric_occupation` (all 0 rows in heratio).
- **Capability:** Records-in-Context generic `Thing` node + i18n + instantiation + place-link + occupation vocabulary — a fuller RiC ontology than archive's `ric_activity/ric_place/ric_instantiation`.
- archive HAS: `ric_activity(+i18n)`, `ric_place(+i18n)`, `ric_instantiation(+i18n)`, `ric_rule`, `ric_shacl_report`, `ric_sync_*`. So PSIS has Activity/Place/Instantiation but **lacks the generic Thing + Occupation entities**. Genuine but unproven (0 rows).

### 6. 3D / photogrammetry reconstruction & advanced 3D capture — **MEDIUM** [buildable]
- `ahg_gaussian_splat` (4 rows), `ahg_point_cloud` (0), `ahg_lost_place_reconstruction` (0), `ahg_reconstruction_stage` (0), `viewer_3d_settings` (9).
- **Capability:** Gaussian-splat assets, point-cloud storage, "lost place" 3D reconstruction pipeline (stages + montage), per-viewer 3D settings. archive has none of these (no `point_cloud`/`splat`/`reconstruction` tables).
- Plus a **large set of heratio-only columns on the shared `object_3d_model` table** — see Missing Columns §B (the richest single column gap).

### 7. Multi-tenancy (SaaS) — **MEDIUM** [buildable]
- `ahg_tenant` (1 row), `ahg_tenant_user`, `ahg_tenant_branding`, `ahg_tenant_settings`, `ahg_tenant_email_branding`.
- **Capability:** repository-scoped multi-tenancy (tenant record, per-tenant users/branding/settings/email-branding, trial/suspension lifecycle).
- archive HAS only `heritage_tenant*` (heritage-platform-scoped) + `sharepoint_tenant`. The generic `ahg_tenant*` SaaS layer is **absent** — but note `ahgMultiTenantPlugin` is "currently disabled" per project docs, so this is forward-looking, not a regression.

### 8. Blog / storytelling / public engagement — **MEDIUM** [buildable]
- `blog_post` (20 rows), `blog_comment` (1), `blog_attachment` (6), `ahg_story` (0), `ahg_suggested_connection` (0).
- **Capability:** a blog (posts/comments/attachments) + curated "story" feature + AI-suggested record connections. archive has `registry_blog_post` and `heritage_curated_story` (different scope) but **no general blog or `ahg_story`**.
- `blog_*` carry real data on Heratio → if PSIS wants public blogging this is a true gap.

### 9. Heritage-at-risk / repatriation / language revival — **LOW/MEDIUM** [buildable]
- `displaced_heritage_claim`, `endangered_heritage_item`, `repatriation_knowledge_contribution`, `language_revival_glossary`, `language_transcription_contribution`, `mandate`, `heritage_region`, `heritage_rule`, `heritage_standard`, `heritage_access_purpose`, `heritage_search_click` (all 0 rows).
- **Capability:** displaced/endangered-heritage registers, repatriation knowledge crowdsourcing, indigenous-language revival glossary + transcription contributions, heritage mandates/regions/standards.
- archive has a huge `heritage_*` set but NOT these specific tables. All 0-row in Heratio → unproven concepts; LOW unless a client (indigenous/repatriation) asks.

### 10. Compliance / privacy extras — **mostly RENAMED, small residue** — **LOW** [buildable]
- `ahg_dpia` (0), `ahg_processing_activity` (5), `ahg_pii_scan_report` (0), `ahg_retention_proposal` (0), `privacy_dpia_log` (6), `privacy_dsar_object` (0), `personal_data_log` (0), `ahg_premis_rights` (0).
- archive HAS the equivalents: `privacy_dpia`, `privacy_processing_activity(+i18n)`, `cdpa_dpia`, `cdpa_processing_activity`, `privacy_breach_notification`, plus rights via `rights_grant`/`premis_*`. **DPIA + processing-activity are RENAMED.**
- **Genuine residue:** `ahg_pii_scan_report` (PII scan results store — memory notes #751 GPS/PII work; verify if it landed under another name), `privacy_dpia_log` (DPIA audit log distinct from the DPIA record), `personal_data_log`, `ahg_retention_proposal` (proposed disposals queue — archive has `integrity_disposition_queue`/`rm_*`, likely RENAMED).

### 11. Preservation / fixity extras — **mostly RENAMED, small residue** — **LOW** [buildable]
- `core_fixity_check_log` (0), `preservation_conversion` (0), `preservation_identification` (0), `preservation_self_assessment`(+`_rating`) (0), `ahg_preservation_targets` (0), `integrity_policy`/`integrity_alert` (0).
- archive HAS rich equivalents: `preservation_format_conversion`, `preservation_object_format`, `preservation_fixity_check`, `oais_fixity_check`, and a full `integrity_*` suite (`integrity_ledger`, `integrity_run`, `integrity_schedule`, `integrity_alert_config`, `integrity_retention_policy`, `integrity_legal_hold`). **integrity_policy/alert and the fixity/conversion/identification tables are RENAMED/equivalent.**
- **Genuine residue:** `preservation_self_assessment(+_rating)` — NDSA-style preservation self-assessment scoring; no archive analog. Minor.

### 12. Backup / cron / job orchestration — **mostly RENAMED** — **LOW** [buildable]
- `ahg_backup_run`/`_binlog`/`_replication` (0), `ahg_cron_run`/`ahg_cron_missed_run` (0), `ahg_job_execution` (0).
- archive HAS: `backup_history`, `backup_schedule`, `backup_setting`, `preservation_backup_verification`, `cron_schedule`. **Backup + cron capability exists — these are RENAMED/granular variants.** `ahg_backup_binlog`/`_replication` (binlog + replication-based backup) is the only genuine extra. LOW.

### 13. Web-archiving / Europeana / DataCite events — **LOW** [buildable, some external]
- `warc_capture` (0), `ahg_europeana_export` (0), `ahg_datacite_event` (0).
- **Capability:** WARC web-archive capture, Europeana harvest export, DataCite event stream. archive HAS `ahg_doi*` (DOI/DataCite minting) but **not** the event/export/WARC tables. All 0-row stubs → LOW.

### 14. Email delivery / notifications — **partly RENAMED** — **LOW** [buildable]
- `ahg_email_bounce` (0), `ahg_sent_email` (0), `ahg_notification` (9), `embargo_notification_log` (0), `ahg_payment_notifications` (0).
- archive HAS: `ahg_email_suppression` (bounce/suppression — RENAMED-ish), many domain notification tables (`research_notification`, `spectrum_notification`, `privacy_notification`, `ahg_loan_notification_log`). **The generic `ahg_notification` bell-queue + `ahg_sent_email` outbound log are genuine** (archive has suppression but not a unified sent-email log / generic notification table). LOW-MEDIUM.

### 15. Misc single-feature gaps — **LOW** [buildable]
- `ahg_iiif_workspace` (0) — IIIF annotation workspaces (archive has `ahg_iiif_annotation` table but no workspace). 
- `image_alt_text` (0) — accessibility alt-text store. **Genuine, useful for WCAG**; MEDIUM if accessibility is a priority.
- `vocabulary_label_cache` (2344 rows) — denormalized term-label cache (perf); genuine but a cache, rebuildable.
- `ahg_term_cross_match` (0) — cross-vocabulary term matching.
- `ahg_capture_queue` (0) — digitisation capture queue (archive has `scan_folder` + `rm_email_capture`, different); MEDIUM for digitisation workflow.
- `ahg_io_funding` / `ahg_io_geolocation` / `ahg_io_facet_denorm` (0) — per-information-object funding, geolocation, and denormalized facet cache. **Geolocation + funding are genuine descriptive extensions**; MEDIUM.
- `item_physical_location` (0) — archive HAS `information_object_physical_location` (RENAMED).
- `object_compartment_access` (0) — archive HAS `object_compartment` + `user_compartment_access` + `security_compartment` (RENAMED).
- `dam_asset` (0) — archive HAS `dam_external_links`/`dam_iptc_metadata`/`dam_format_holdings`/`dam_version_links` (DAM exists under granular names; `dam_asset` central table may be RENAMED — verify).
- `finding_aid` (0) — archive has NO finding_aid table; AtoM generates finding aids on the fly + `finding-aid:generate` CLI. Likely **not a gap** (finding aids are file artifacts, not a DB feature). VERIFY before building.
- `portable_export_share_token` (0) — archive HAS `information_object_share_token` (RENAMED for portable export, verify scope).
- `ahg_publish_request` (0) — archive HAS `request_to_publish(+i18n)` (RENAMED).
- `ahg_cart_downloads` (0) — cart download tracking; archive cart exists, this is a sub-log. LOW.
- `media_caption_track` (1) — video/audio caption tracks (WebVTT); genuine accessibility feature. MEDIUM.
- `c2pa`/openric: `ahg_c2pa_provenance` (1) — archive HAS `ahg_c2pa_manifest` (different granularity, see column diffs); `openric_audit_log`(15)/`openric_key_request`(0) — OpenRiC API access; genuine if OpenRiC API is exposed.
- `ahg_discovery_simulated_run` (100) / `ahg_search_query_log` (0) / `ahg_search_template` (0) — discovery A/B simulation + search query log + saved search templates. LOW-MEDIUM (analytics).
- `ahg_loan_tour_booking` (0) — guided-tour booking on loans. LOW.

---

## Missing COLUMNS on shared tables (real data fields PSIS lacks)

Only the **material** ones are listed. Many `heratio_only` columns are trivial timestamps (`created_at`/`updated_at`) or audit fields; those are noted but low-impact. **Note:** several shared tables actually have `archive_only` columns too (archive AHEAD of heratio) — those are NOT gaps for PSIS and are ignored here.

### B. `object_3d_model` — **HIGH for 3D/preservation** [buildable]
The single richest column gap: ~33 heratio-only columns capturing **3D capture provenance & technical metadata** that PSIS lacks:
`capture_method`, `capture_device`, `capture_operator`, `capture_date`, `source_count`, `processing_software`, `processing_notes`, `accuracy_mm`, `point_density`, `is_watertight`, `is_lossless_master`, `coordinate_system`, `georeference`, `bounding_box`, `real_width/height/depth`, `dimension_unit`, `scale_note`, `lod_levels`, `pbr_maps`, `texture_colorspace`, `has_rig`, `compression`, `format_version`, `model_author`, `model_license(+_holder)`, `attribution`, `alt_text`, `derivation_note`, `turntable_mp4_path`, `turntable_generated_at`.
→ Full photogrammetry/3D-scan provenance + PBR/LOD technical fields + licensing + accessibility alt-text. **Add as columns to `object_3d_model`.**

### C. `marketplace_listing` — **MEDIUM** [buildable]
~14 heratio-only columns for **artist-licensing & reservations**: `artist_id`, `artist_base_price`, `markup_type`, `markup_value`, `reserved_by_user_id`, `reserved_until`, and a `licence_template_*` block (type/scope/territory/exclusivity/duration_days/max_copies/attribution_required/modifications_allowed/sublicensing_allowed). archive's marketplace lacks the artist-markup + reservation + licence-template fields. Pairs with missing tables `marketplace_artist`, `marketplace_reservation`, `marketplace_licence_agreement`.

### D. `library_*` — **MEDIUM** [buildable]
- `library_item.work_key` (FRBR work clustering key — note archive has `frbr_work_key`/`frbr_override_type`/`work_key` mix; **verify, likely already covered** — archive is actually AHEAD here with `marc_005/008/leader`).
- `library_order` / `library_order_line`: write-off fields (`written_off_by/date/reason`, `vendor_reference`) + line `format`/`pub_year`/`supplier_code`. Genuine acquisitions extras.
- `library_z3950_target`: column-name drift (`active` vs `is_active`, `database_name` vs `database`, `password` vs `password_hash`, `element_set`, `sort_order`) — **schema drift, RENAMED columns**, not a feature gap.
- `library_kbart_import_log` / `library_serial_issue`: minor field drift (binding fields) — low.

### E. Security / audit hardening — **MEDIUM** [buildable]
- `ahg_audit_log`: heratio adds `kid`, `seq`, `signature`, `tenant_id` (cryptographic hash-chain seal + tenant scoping). **archive's hash-chaining (memory: webauthn/MFA v3.56.0) may already cover this under `security_access_log.entry_hash/prev_hash`** — those columns are `archive_only` on `security_access_log`, so archive has chaining there but **not** on `ahg_audit_log`. Genuine: extend `ahg_audit_log` with seal columns.
- `security_access_log`: heratio adds `details`, `request_id`, `reviewer`; archive adds `entry_hash`/`prev_hash`/`session_id`/`compartment_id` (archive AHEAD). Minor heratio extras.
- `user`: `email_bounced_at`, `preferred_locale` — genuine, small (bounce tracking + i18n preference).
- `user_totp_secret`: `enabled_at`, `last_used_at`, `recovery_codes_generated_at`, `updated_at` — TOTP lifecycle timestamps; minor.

### F. Other notable column gaps — **LOW/MEDIUM**
- `information_object` / `actor`: `icip_sensitivity` — Indigenous Cultural & IP sensitivity flag (ties to `ahgICIPPlugin`). **Genuine, MEDIUM** if ICIP is in scope.
- `ahg_exhibition_space`: building-plan + floorplan + guided-tour + walkthrough columns (`building_plan_*`, `floorplan_width_m/height_m`, `guided_tour_json`, `walkthrough_path_json`, `intro_text`, `room_blurb`, `ric_activity_id`). NOTE: archive has its OWN exhibition columns (`floor_color`, `furniture_json`, `room_width/height`) — the two diverged. memory confirms PSIS exhibition port (v3.62.x) used different column names → **likely functional parity under different names; verify, probably not a real gap.**
- `ahg_iiif_annotation`: `ner_entity_type`, `ner_confidence`, `ner_run_id`, `body_selector_json`, `etag`, `visibility`, `project_id` — NER-linked IIIF annotations + ETag concurrency. MEDIUM for IIIF/NER workflow.
- `ahg_discovery_log`: `keywords`, `pre/post_merge_ranks`, `strategy_breakdown`, `dwell_ms`, `clicked_at` — discovery analytics depth. LOW.
- `ahg_form_field.atom_field`, `ahg_form_template.config` — forms→AtoM-field mapping + template config. MEDIUM (ahgFormsPlugin consumer wiring, memory #231).
- `embargo` (`notes`, `public_message`) + `embargo_audit` (sent/recipients/reason/error/days_before) — embargo notification audit. LOW-MEDIUM.
- `heritage_contribution`/`heritage_contributor` verify-token + object linkage; `research_*` activity-log fields — minor domain extras.
- `ahg_settings.is_locked`, `atom_plugin.admin_only` — settings lock flag + admin-only plugin flag. Trivial-to-add, LOW.
- `tiff_pdf_merge_job.output_format`, `media_transcription.segments`, `media_snippets.created_by`, `scan_folder.notify_emails/notify_on_failure`, `cart.kind/listing_id`, `favorites.url`, `ahg_order.payment_reference`, `ahg_webhook_delivery.{event,data,timestamp}`, `ahg_workflow_task.last_overdue_notification_at`, `ahg_dedupe_scan.scope` — assorted small functional fields, LOW; add opportunistically when touching the owning plugin.

---

## Appendix — RENAMED / EQUIVALENT (NOT gaps)

These heratio-only tables already exist in `archive` under a different name. **Do NOT build.**

| Heratio table | Archive equivalent | Capability |
|---------------|-------------------|------------|
| `ahg_report`, `ahg_report_section`, `ahg_report_template`, `ahg_report_schedule`, `ahg_report_share`, `ahg_report_version`, `ahg_report_comment`, `ahg_report_link`, `ahg_report_attachment` (all 0-row) | `report`, `report_section`, `report_template`, `report_schedule`, `report_share`, `report_version`, `report_comment`, `report_link`, `report_attachment` (+ archive extras `report_query`, `report_definition`, `report_archive`) | Enterprise report builder — **archive is fully equivalent / arguably ahead**. (`ahg_report_widget`, `ahg_report_snapshot` have no archive analog but are 0-row stubs; reconcile only if widget/snapshot UI is wanted.) |
| `ai_model_registry`, `ai_risk_register`, `ai_operator_attestation` | `ai_act_model`, `ai_act_risk`, `ai_act_attestation` (+ `ai_act_system`) | EU AI Act registry/risk/attestation |
| `ahg_dpia`, `privacy_dpia_log` (record), `ahg_processing_activity` | `privacy_dpia`, `cdpa_dpia`, `privacy_processing_activity(+i18n)`, `cdpa_processing_activity` | DPIA + processing-activity register |
| `z3950_targets`, `z3950_query_log`, `z3950_import_log` | `library_z3950_target`, `library_z3950_server_request`, `library_z3950_import_log` (+ `library_z3950_server_config`) | Z39.50 search/import |
| `heritage_asset_journal`, `heritage_asset_valuation`, `heritage_asset_impairment`, `heritage_asset_movement` | `heritage_journal_entry`, `heritage_valuation_history`, `heritage_impairment_assessment`, `heritage_movement_register` | Heritage-asset accounting (GRAP 103) |
| `item_physical_location` | `information_object_physical_location` | Physical location link |
| `object_compartment_access` | `object_compartment` + `user_compartment_access` + `security_compartment` | Security-compartment access |
| `user_registration_request` | `ahg_registration_request` | Self-registration queue |
| `ahg_publish_request` | `request_to_publish(+i18n)` | Publication requests |
| `portable_export_share_token` | `information_object_share_token` | Share tokens (verify portable scope) |
| `integrity_policy`, `integrity_alert`, `core_fixity_check_log` | `integrity_alert_config`, `integrity_retention_policy`, `integrity_ledger`, `integrity_run`, `integrity_schedule`, `oais_fixity_check`, `preservation_fixity_check` | Integrity/fixity suite — **archive ahead** |
| `preservation_conversion`, `preservation_identification` | `preservation_format_conversion`, `preservation_object_format` | Format conversion / identification |
| `ahg_backup_run` (+cron `ahg_cron_run`/`ahg_job_execution`) | `backup_history`, `backup_schedule`, `backup_setting`, `cron_schedule` | Backup + scheduling |
| `ahg_email_bounce` | `ahg_email_suppression` (partial — suppression yes, raw bounce-event log no) | Email bounce/suppression |
| `library_serial_subscription` | `library_subscription` | Serials subscriptions |
| `dam_asset` (verify) | `dam_external_links` / `dam_iptc_metadata` / `dam_format_holdings` / `dam_version_links` | DAM asset metadata (granular) |
| `finding_aid` (verify) | (none — AtoM generates finding aids as files via `finding-aid:generate`; likely not a DB feature) | Finding aids |

> Column-level RENAMES (schema drift, not gaps): `library_z3950_target` (`active↔is_active`, `database_name↔database`, `password↔password_hash`); `library_item` FRBR keys (archive ahead); `ahg_exhibition_space`/`ahg_exhibition_placement` (divergent column names, functional parity per v3.62.x port — verify before treating as gap).

---

## Recommended build order (genuine, client-relevant)

1. **`object_3d_model` columns + 3D reconstruction tables** (HIGH) — biggest concrete, data-bearing gap; pure schema additions.
2. **AI gateway metering** (`ahg_ai_call_cost`/`_pricing`/`_quota`/`ahg_gpu_endpoint`) (HIGH) — aligns with host AI-gateway rule; do alongside routing PSIS via the gateway.
3. **Accessibility fields** (`image_alt_text`, `media_caption_track`, `object_3d_model.alt_text`) (MEDIUM) — low effort, WCAG value.
4. **AI assistant extras** (`ahg_ai_chatbot_message`, `ahg_translation_memory`, `ahg_ner_custom_entity`, `ahg_ai_prompt_template`) (MEDIUM) — extends the live #121 chatbot.
5. **`ahg_audit_log` seal columns** (`kid/seq/signature/tenant_id`) (MEDIUM) — extends existing hash-chaining to the AHG audit log.
6. **RiC Thing model** + **research scholarly-workbench** + **blog/story** + **multi-tenancy** (MEDIUM) — only on confirmed client demand (all 0-row stubs in Heratio).
7. Everything else (LOW) — add opportunistically when the owning plugin is already being touched.

> Every build item must respect the base-AtoM lock (schema changes via plugin `data/*.sql` + atom-framework migrations only; **never** alter core tables `object`/`information_object`/`actor`/`term`/`taxonomy`/`setting`/`user`/`repository`/`digital_object`). `icip_sensitivity` on `information_object`/`actor` is a core-table column add → must go via a sanctioned `atom-framework/patches` migration or a side EAV table, NOT a direct core ALTER.
