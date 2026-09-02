# Research Portal

Research knowledge platform with evidence graphs, source criticism, cross-collection synthesis, W3C annotations, AI extraction orchestration, validation queues, snapshots, assertions, hypotheses, ORCID integration, collaboration, bibliography, reproduction requests, workspaces, journal, reports, notifications, RO-Crate packaging, ODRL rights, reproducibility packs, DOI minting, and REST API access

| | |
|---|---|
| Machine name | `ahgResearchPlugin` |
| Version | 3.1.0 |
| Category | research |
| Licence | AGPL-3.0-or-later |
| Author | The Archive and Heritage Group |

## Features

- orcid_integration
- multi_researcher_collaboration
- research_projects
- bibliography_management
- reproduction_requests
- private_workspaces
- real_time_alerts
- rest_api
- admin_configurable_types
- verification_system
- activity_tracking
- analytics_dashboard
- research_journal
- research_reports
- report_templates
- pdf_docx_export
- bibtex_ris_import
- notification_center
- data_visualization
- institutional_sharing
- peer_review
- comments_threads
- snapshots
- hypotheses
- source_assessment
- trust_scoring
- w3c_annotations
- assertions_knowledge_graph
- ai_extraction_orchestration
- validation_queue
- document_templates
- entity_resolution
- timeline_builder
- map_builder
- network_graph
- ro_crate_packaging
- odrl_rights_policies
- reproducibility_packs
- doi_minting
- canonical_event_log

## Requirements

| Component | Version |
|---|---|
| atom framework | `>=1.0.0` |
| atom | `>=2.8` |
| php | `>=8.1` |

## Depends on

- `ahgCorePlugin`
- `ahgAccessRequestPlugin`

## Optional integrations

These are used when present and are not required:

- `ahgAuditTrailPlugin`

## Database tables

Creates 145 table(s):

- `research_researcher_type`
- `research_verification`
- `research_researcher`
- `research_researcher_audit`
- `research_reading_room`
- `research_booking`
- `research_material_request`
- `research_request_status_history`
- `research_reproduction_request`
- `research_reproduction_item`
- `research_reproduction_file`
- `research_project`
- `research_project_collaborator`
- `research_project_resource`
- `research_project_milestone`
- `research_activity_log`
- `research_collection`
- `research_collection_item`
- `research_annotation`
- `research_saved_search`
- `research_search_alert_log`
- `research_bibliography`
- `research_bibliography_entry`
- `research_workspace`
- `research_workspace_member`
- `research_workspace_resource`
- `research_discussion`
- `research_statistics_daily`
- `research_citation_log`
- `research_password_reset`
- `research_api_key`
- `research_api_log`
- `research_researcher_type_i18n`
- `research_journal_entry`
- `research_report`
- `research_report_section`
- `research_report_template`
- `research_notification`
- `research_notification_preference`
- `research_institution`
- `research_institutional_share`
- `research_external_collaborator`
- `research_comment`
- `research_peer_review`
- `research_clipboard_project`
- `research_snapshot`
- `research_snapshot_item`
- `research_hypothesis`
- `research_hypothesis_evidence`
- `research_source_assessment`
- `research_quality_metric`
- `research_annotation_v2`
- `research_annotation_target`
- `research_assertion`
- `research_assertion_evidence`
- `research_extraction_job`
- `research_extraction_result`
- `research_validation_queue`
- `research_document_template`
- `research_entity_resolution`
- `research_timeline_event`
- `research_map_point`
- `research_rights_policy`
- `research_access_decision`
- `research_room`
- `research_room_participant`
- `research_room_manifest`
- `research_equipment_maintenance`
- `research_studio_artefact`
- `research_notebook`
- `research_notebook_item`
- `research_cross_fonds_query`
- `research_collaboration_session`
- `research_collaboration_presence`
- `research_orcid_link`
- `research_offline_sync_log`
- `training_course`
- `training_module`
- `training_assessment`
- `training_enrolment`
- `training_progress`
- `training_certificate`
- `research_activity`
- `research_activity_material`
- `research_activity_participant`
- `research_ai_disclosure_log`
- `research_analysis_code`
- `research_analysis_result`
- `research_analysis_result_claim`
- `research_argument`
- `research_argument_step`
- `research_claim_meta`
- `research_contradiction`
- `research_copilot_answer`
- `research_custody_handoff`
- `research_decision_log`
- `research_dmp`
- `research_dmp_dataset`
- `research_equipment`
- `research_equipment_booking`
- `research_ethics`
- `research_export_log`
- `research_field_alert`
- `research_field_watch`
- `research_funding`
- `research_grant_call`
- `research_grant_draft`
- `research_grant_section`
- `research_impact_signal`
- `research_inbox_item`
- `research_journal`
- `research_journal_article`
- `research_journal_issue`
- `research_lead`
- `research_lecture`
- `research_lecture_resource`
- `research_lecture_section`
- `research_memory_item`
- `research_method_protocol`
- `research_method_template`
- `research_milestone`
- `research_output`
- `research_print_template`
- `research_question_brief`
- `research_question_brief_version`
- `research_reading_room_seat`
- `research_replication_log`
- `research_request_correspondence`
- `research_request_queue`
- `research_retrieval_schedule`
- `research_review_comment`
- `research_review_run`
- `research_seat_assignment`
- `research_source_triage`
- `research_submission`
- `research_submission_requirement`
- `research_submission_response`
- `research_target_journal`
- `research_team_member`
- `research_walk_in_visitor`
- `research_writing_doc`
- `research_writing_section`
- `research_writing_version`
- `research_metadata_suggestion`
- `research_offline_attachment`

## Installation

This plugin requires **atom-framework**. It is not optional: the framework
provides `AhgController`, `AtomFramework\*` and the routing and settings
services that this plugin builds on.

```bash
# 1. Fetch into the AtoM plugins directory as a REAL DIRECTORY.
#    A symlink fails the prefix test in pluginsAction.class.php and the
#    plugin is then invisible in the stock admin UI with no error shown.
cd <atom-root>/plugins
git clone --depth 1 --filter=blob:none --sparse \
    https://github.com/ArchiveHeritageGroup/atom-ahg-plugins.git tmp-fetch
cd tmp-fetch && git sparse-checkout set ahgResearchPlugin && cd ..
mv tmp-fetch/ahgResearchPlugin ./ahgResearchPlugin && rm -rf tmp-fetch

# 2. Apply the schema.
mysql -u <user> -p <database> < ahgResearchPlugin/database/install.sql

# 3. Enable it, then clear the cache and reload PHP-FPM.
cd <atom-root>
php symfony cc
sudo systemctl reload php8.3-fpm
```

**Enabling differs by instance shape.** Check which list governs:

```bash
grep -c 'loadPluginsFromDatabase' <atom-root>/config/ProjectConfiguration.class.php
```

- `0` (stock AtoM): plugins load from the serialised `plugins` row in
  `setting_i18n`. The `atom_plugin` table is inert and the admin screen can
  show a plugin as enabled that does not load.
- `1` or more (AHG): `atom_plugin` is the source of truth.

Verify against whichever list governs, not against the admin screen.

## Licence

AGPL-3.0-or-later. Copyright The Archive and Heritage Digital Commons Group (Pty) Ltd.
