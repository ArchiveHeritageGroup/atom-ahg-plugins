<?php

/**
 * ahgResearchPlugin Configuration
 *
 * Research Portal with comprehensive researcher support features.
 *
 * @package ahgResearchPlugin
 * @version 2.0.0
 */
class ahgResearchPluginConfiguration extends sfPluginConfiguration
{
    public static $summary = 'Professional research support platform with ORCID integration, collaboration, and API access';
    public static $version = '2.1.0';

    public function contextLoadFactories(sfEvent $event)
    {
        $context = $event->getSubject();
        $context->getConfiguration()->loadHelpers(['Asset', 'Url']);
    }

    public function initialize()
    {
        $this->dispatcher->connect('context.load_factories', [$this, 'contextLoadFactories']);
        $this->dispatcher->connect('routing.load_configuration', [$this, 'addRoutes']);

        $enabledModules = sfConfig::get('sf_enabled_modules');
        $enabledModules[] = 'research';
        $enabledModules[] = 'audit';
        $enabledModules[] = 'researchapi';
        sfConfig::set('sf_enabled_modules', $enabledModules);
    }

    public function addRoutes(sfEvent $event)
    {
        $routing = $event->getSubject();

        // =====================================================================
        // API ROUTES (researchapi module)
        // =====================================================================
        $api = new \AtomFramework\Routing\RouteLoader('researchapi');

        $api->any('api_research_stats', '/api/research/stats', 'stats');
        $api->any('api_research_annotations', '/api/research/annotations', 'annotations');
        $api->any('api_research_bibliography_export', '/api/research/bibliographies/:id/export/:format', 'exportBibliography', ['id' => '\d+']);
        $api->any('api_research_bibliographies', '/api/research/bibliographies', 'bibliographies');
        $api->any('api_research_citation', '/api/research/citations/:id/:format', 'citation', ['id' => '\d+'], ['format' => 'chicago']);
        $api->any('api_research_booking', '/api/research/bookings', 'bookings');
        $api->any('api_research_searches', '/api/research/searches', 'searches');
        $api->any('api_research_collection', '/api/research/collections/:id', 'collection', ['id' => '\d+']);
        $api->any('api_research_collections', '/api/research/collections', 'collections');
        $api->any('api_research_projects', '/api/research/projects', 'projects');
        $api->any('api_research_profile', '/api/research/profile', 'profile');

        $api->register($routing);

        // =====================================================================
        // AUDIT ROUTES (audit module)
        // =====================================================================
        $audit = new \AtomFramework\Routing\RouteLoader('audit');

        $audit->any('audit_export', '/audit/export', 'export');
        $audit->any('audit_user', '/audit/user/:id', 'user', ['id' => '\d+']);
        $audit->any('audit_record', '/audit/record/:table/:record_id', 'record', ['record_id' => '\d+']);
        $audit->any('audit_view', '/audit/view/:id', 'view', ['id' => '\d+']);
        $audit->any('audit_index', '/audit', 'index');

        $audit->register($routing);

        // =====================================================================
        // RESEARCH ROUTES (research module)
        // =====================================================================
        $research = new \AtomFramework\Routing\RouteLoader('research');

        // Reading Room Operations
        $research->any('research_activity_view', '/research/activities/:id', 'viewActivity', ['id' => '\d+']);
        $research->any('research_activities', '/research/activities', 'activities');
        $research->any('research_walk_in', '/research/walk-in', 'walkIn');
        $research->any('research_equipment_book', '/research/equipment/book', 'bookEquipment');
        $research->any('research_equipment', '/research/equipment', 'equipment');
        $research->any('research_seat_map', '/research/seats/map', 'seatMap');
        $research->any('research_seat_assign', '/research/seats/assign', 'assignSeat');
        $research->any('research_seats', '/research/seats', 'seats');
        $research->any('research_print_call_slips', '/research/call-slips/print', 'printCallSlips');
        $research->any('research_retrieval_queue', '/research/retrieval-queue', 'retrievalQueue');

        // Admin
        $research->any('research_admin_statistics', '/research/admin/statistics', 'adminStatistics');
        $research->any('research_admin_types_edit', '/research/admin/types/edit/:id', 'editResearcherType', ['id' => '\d+'], ['id' => null]);
        $research->any('research_admin_types', '/research/admin/types', 'adminTypes');

        // ORCID
        $research->any('research_orcid_disconnect', '/research/orcid/disconnect', 'orcidDisconnect');
        $research->any('research_orcid_callback', '/research/orcid/callback', 'orcidCallback');
        $research->any('research_orcid_connect', '/research/orcid/connect', 'orcidConnect');

        // Workspaces
        $research->any('research_workspace_invite', '/research/workspaces/:id/invite', 'inviteWorkspaceMember', ['id' => '\d+']);
        $research->any('research_workspace_discussion', '/research/workspaces/:id/discussion/:discussion_id', 'workspaceDiscussion', ['id' => '\d+', 'discussion_id' => '\d+']);
        $research->any('research_view_workspace', '/research/workspaces/:id', 'viewWorkspace', ['id' => '\d+']);
        $research->any('research_workspaces', '/research/workspaces', 'workspaces');

        // Projects
        $research->any('research_project_invite', '/research/project/:id/invite', 'inviteCollaborator', ['id' => '\d+']);
        $research->any('research_project_collaborators', '/research/project/:id/collaborators', 'projectCollaborators', ['id' => '\d+']);
        $research->any('research_project_activity', '/research/project/:id/activity', 'projectActivity', ['id' => '\d+']);
        $research->any('research_project_edit', '/research/project/:id/edit', 'editProject', ['id' => '\d+']);
        $research->any('research_view_project', '/research/project/:id', 'viewProject', ['id' => '\d+']);
        $research->any('research_projects', '/research/projects', 'projects');

        // Bibliographies
        $research->any('research_bibliography_export', '/research/bibliography/:id/export/:format', 'exportBibliography', ['id' => '\d+']);
        $research->any('research_bibliography_add_entry', '/research/bibliography/:id/add', 'addBibliographyEntry', ['id' => '\d+']);
        $research->any('research_view_bibliography', '/research/bibliography/:id', 'viewBibliography', ['id' => '\d+']);
        $research->any('research_bibliographies', '/research/bibliographies', 'bibliographies');

        // Reproductions
        $research->any('research_reproduction_download', '/research/reproduction/download/:token', 'reproductionDownload');
        $research->any('research_reproduction_new', '/research/reproduction/new', 'newReproduction');
        $research->any('research_view_reproduction', '/research/reproduction/:id', 'viewReproduction', ['id' => '\d+']);
        $research->any('research_reproductions', '/research/reproductions', 'reproductions');

        // Invitations
        $research->any('research_invitation_accept', '/research/invitation/:type/:id/accept', 'acceptInvitation', ['id' => '\d+']);
        $research->any('research_invitation_decline', '/research/invitation/:type/:id/decline', 'declineInvitation', ['id' => '\d+']);
        $research->any('research_invitations', '/research/invitations', 'invitations');

        // Public Routes (No Auth Required)
        $research->any('research_password_reset', '/research/password-reset', 'passwordReset');
        $research->any('research_password_reset_request', '/research/forgot-password', 'passwordResetRequest');
        $research->any('research_registration_complete', '/research/registration-complete', 'registrationComplete');
        $research->any('research_public_register', '/research/register-researcher', 'publicRegister');

        // Admin Researcher Routes
        $research->any('research_admin_reset_password', '/research/researcher/:id/reset-password', 'adminResetPassword', ['id' => '\d+']);
        $research->any('research_reject_researcher', '/research/researcher/:id/reject', 'rejectResearcher', ['id' => '\d+']);
        $research->any('research_approve_researcher', '/research/researcher/:id/approve', 'approveResearcher', ['id' => '\d+']);
        $research->any('research_edit_room', '/research/rooms/edit', 'editRoom');
        $research->any('research_rooms', '/research/rooms', 'rooms');

        // Check In/Out
        $research->any('research_check_out', '/research/booking/:id/check-out', 'checkOut', ['id' => '\d+']);
        $research->any('research_check_in', '/research/booking/:id/check-in', 'checkIn', ['id' => '\d+']);

        // Bookings
        $research->any('research_view_booking', '/research/booking/:id', 'viewBooking', ['id' => '\d+']);
        $research->any('research_book', '/research/book', 'book');
        $research->any('research_bookings', '/research/bookings', 'bookings');

        // Researchers
        $research->any('research_view_researcher', '/research/researcher/:id', 'viewResearcher', ['id' => '\d+']);
        $research->any('research_researchers', '/research/researchers', 'researchers');

        // Workspace & Collections
        $research->any('research_cite', '/research/cite/:slug', 'cite');
        $research->any('research_annotations', '/research/annotations', 'annotations');
        $research->any('research_view_collection', '/research/collection/:id', 'viewCollection', ['id' => '\d+']);
        $research->any('research_collections', '/research/collections', 'collections');
        $research->any('research_saved_searches', '/research/saved-searches', 'savedSearches');
        $research->any('research_workspace', '/research/workspace', 'workspace');

        // Profile
        $research->any('research_api_keys', '/research/profile/api-keys', 'apiKeys');
        $research->any('research_profile', '/research/profile', 'profile');
        $research->any('research_register', '/research/register', 'register');

        // AJAX
        $research->any('research_ajax_add_to_collection', '/research/ajax/add-to-collection', 'addToCollection');
        $research->any('research_ajax_create_collection', '/research/ajax/create-collection', 'createCollectionAjax');
        $research->any('research_ajax_search_items', '/research/ajax/search-items', 'searchItems');
        $research->any('research_ajax_search_entities', '/research/ajax/search-entities', 'searchEntities');
        $research->any('research_ajax_add_to_bibliography', '/research/ajax/add-to-bibliography', 'addToBibliographyAjax');
        $research->any('research_ajax_upload_note_image', '/research/ajax/upload-note-image', 'uploadNoteImage');
        $research->any('research_ajax_resolve_thumbnail', '/research/ajax/resolve-thumbnail', 'resolveThumbnail');
        $research->any('research_ajax_clipboard_to_project', '/research/ajax/clipboard-to-project', 'clipboardToProject');
        $research->any('research_ajax_manage_clipboard_item', '/research/ajax/manage-clipboard-item', 'manageClipboardItem');
        $research->any('research_ajax_manage_milestone', '/research/ajax/manage-milestone', 'manageMilestone');
        $research->any('research_ajax_remove_collaborator', '/research/ajax/remove-collaborator', 'removeCollaborator');

        // Issue 149 Phase 7: Collaboration
        $research->any('research_comment_api', '/research/api/comment', 'commentApi');
        $research->any('research_submit_review', '/research/report/:id/review/:review_id', 'submitReview', ['id' => '\d+', 'review_id' => '\d+']);
        $research->any('research_request_review', '/research/report/:id/review/request', 'requestReview', ['id' => '\d+']);

        // Issue 149 Phase 6: Institutional Sharing
        $research->any('research_institutions_edit', '/research/admin/institutions/edit/:id', 'editInstitution', ['id' => '\d+'], ['id' => null]);
        $research->any('research_institutions', '/research/admin/institutions', 'institutions');
        $research->any('research_share_project', '/research/project/:id/share', 'shareProject', ['id' => '\d+']);
        $research->any('research_accept_share', '/research/share/:token/accept', 'acceptShare');
        $research->any('research_external_access', '/research/share/:token', 'externalAccess');

        // Issue 149 Phase 5: Visualization
        $research->any('research_visualization_data', '/research/visualization-data', 'visualizationData');

        // Issue 149 Phase 4: Notifications
        $research->any('research_notifications_api', '/research/notifications/api', 'notificationsApi');
        $research->any('research_notifications', '/research/notifications', 'notifications');

        // Issue 149 Phase 3: Export + Import
        $research->any('research_export_report', '/research/report/:id/export/:format', 'exportReport', ['id' => '\d+']);
        $research->any('research_export_notes', '/research/notes/export/:format', 'exportNotes');
        $research->any('research_export_finding_aid', '/research/collection/:id/export/:format', 'exportFindingAid', ['id' => '\d+']);
        $research->any('research_export_journal', '/research/journal/export/:format', 'exportJournal');
        $research->any('research_bibliography_import', '/research/bibliography/:id/import', 'importBibliography', ['id' => '\d+']);

        // Issue 149 Phase 2: Reports
        $research->any('research_report_section_edit', '/research/report/:id/section/:section_id', 'editReportSection', ['id' => '\d+', 'section_id' => '\d+']);
        $research->any('research_report_reorder', '/research/report/:id/reorder', 'reorderReportSections', ['id' => '\d+']);
        $research->any('research_report_new', '/research/report/new', 'newReport');
        $research->any('research_report_edit', '/research/report/:id/edit', 'editReport', ['id' => '\d+']);
        $research->any('research_view_report', '/research/report/:id', 'viewReport', ['id' => '\d+']);
        $research->any('research_report_list', '/research/report', 'reports');
        $research->any('research_reports', '/research/reports', 'reports');

        // Issue 149 Phase 1: Journal
        $research->any('research_journal_new', '/research/journal/new', 'journalNew');
        $research->any('research_journal_entry', '/research/journal/:id', 'journalEntry', ['id' => '\d+']);
        $research->any('research_journal', '/research/journal', 'journal');

        // =====================================================================
        // ISSUE 159 PHASE 2a: SNAPSHOTS
        // =====================================================================
        $research->any('research_create_snapshot', '/research/snapshot/create', 'createSnapshot');
        $research->any('research_delete_snapshot', '/research/snapshot/:id/delete', 'deleteSnapshot', ['id' => '\\d+']);
        $research->any('research_compare_snapshots', '/research/snapshot/compare', 'compareSnapshots');
        $research->any('research_view_snapshot', '/research/snapshot/:id', 'viewSnapshot', ['id' => '\\d+']);
        $research->any('research_snapshots', '/research/snapshots/:project_id', 'snapshots', ['project_id' => '\\d+']);

        // =====================================================================
        // ISSUE 159 PHASE 2a: HYPOTHESES
        // =====================================================================
        $research->any('research_update_hypothesis', '/research/hypothesis/:id/update', 'updateHypothesis', ['id' => '\\d+']);
        $research->any('research_view_hypothesis', '/research/hypothesis/:id', 'viewHypothesis', ['id' => '\\d+']);
        $research->any('research_hypotheses', '/research/hypotheses/:project_id', 'hypotheses', ['project_id' => '\\d+']);

        // =====================================================================
        // ISSUE 159 PHASE 2a: SOURCE ASSESSMENT & TRUST
        // =====================================================================
        $research->any('research_save_source_assessment', '/research/source-assessment/save', 'saveSourceAssessment');
        $research->any('research_trust_score', '/research/trust-score/:object_id', 'trustScore', ['object_id' => '\\d+']);
        $research->any('research_source_assessment', '/research/source-assessment/:object_id', 'sourceAssessment', ['object_id' => '\\d+']);

        // =====================================================================
        // ISSUE 159 PHASE 2a: W3C WEB ANNOTATIONS v2
        // =====================================================================
        $research->any('research_create_annotation_v2', '/research/annotation-v2/create', 'createAnnotationV2');
        $research->any('research_view_annotation_v2', '/research/annotation-v2/:id', 'viewAnnotationV2', ['id' => '\\d+']);
        $research->any('research_import_annotations_iiif', '/research/annotations/import/:object_id', 'importAnnotationsIIIF', ['object_id' => '\\d+']);
        $research->any('research_export_annotations_iiif', '/research/annotations/export/:object_id', 'exportAnnotationsIIIF', ['object_id' => '\\d+']);
        $research->any('research_annotation_studio', '/research/annotation-studio/:object_id', 'annotationStudio', ['object_id' => '\\d+']);

        // =====================================================================
        // ISSUE 159 PHASE 2a: ASSERTIONS (KNOWLEDGE GRAPH)
        // =====================================================================
        $research->any('research_create_assertion', '/research/assertion/create', 'createAssertion');
        $research->any('research_update_assertion_status', '/research/assertion/:id/status', 'updateAssertionStatus', ['id' => '\\d+']);
        $research->any('research_add_assertion_evidence', '/research/assertion/:id/evidence', 'addAssertionEvidence', ['id' => '\\d+']);
        $research->any('research_assertion_conflicts', '/research/assertion/:id/conflicts', 'assertionConflicts', ['id' => '\\d+']);
        $research->any('research_view_assertion', '/research/assertion/:id', 'viewAssertion', ['id' => '\\d+']);
        $research->any('research_knowledge_graph_data', '/research/knowledge-graph-data', 'knowledgeGraphData');
        $research->any('research_knowledge_graph', '/research/knowledge-graph/:project_id', 'knowledgeGraph', ['project_id' => '\\d+']);
        $research->any('research_assertions', '/research/assertions/:project_id', 'assertions', ['project_id' => '\\d+']);

        // =====================================================================
        // ISSUE 159 ENHANCEMENT 5: DISCOVERY — SEARCH DIFF + SNAPSHOT
        // =====================================================================
        $research->any('research_search_diff', '/research/search-diff/:id', 'diffSearchResults', ['id' => '\\d+']);
        $research->any('research_search_snapshot', '/research/search-snapshot/:id', 'snapshotSearchResults', ['id' => '\\d+']);

        // =====================================================================
        // ISSUE 159 PHASE 2b: EXTRACTION ORCHESTRATION
        // =====================================================================
        $research->any('research_create_extraction_job', '/research/extraction-job/create', 'createExtractionJob');
        $research->any('research_view_extraction_job', '/research/extraction-job/:id', 'viewExtractionJob', ['id' => '\\d+']);
        $research->any('research_extraction_jobs', '/research/extraction-jobs/:project_id', 'extractionJobs', ['project_id' => '\\d+']);

        // =====================================================================
        // ISSUE 159 PHASE 2b: VALIDATION QUEUE
        // =====================================================================
        $research->any('research_bulk_validate', '/research/bulk-validate', 'bulkValidate');
        $research->any('research_validate_result', '/research/validate/:id', 'validateResult', ['id' => '\\d+']);
        $research->any('research_validation_queue', '/research/validation-queue', 'validationQueue');

        // =====================================================================
        // ISSUE 159 PHASE 2b: DOCUMENT TEMPLATES
        // =====================================================================
        $research->any('research_edit_document_template', '/research/document-template/:id/edit', 'editDocumentTemplate', ['id' => '\\d+'], ['id' => null]);
        $research->any('research_document_templates', '/research/document-templates', 'documentTemplates');

        // =====================================================================
        // ISSUE 159 PHASE 2c: ENTITY RESOLUTION
        // =====================================================================
        $research->any('research_find_entity_candidates', '/research/entity-resolution/candidates', 'findEntityCandidates');
        $research->any('research_propose_entity_match', '/research/entity-resolution/propose', 'proposeEntityMatch');
        $research->any('research_resolve_entity_match', '/research/entity-resolution/:id/resolve', 'resolveEntityMatch', ['id' => '\\d+']);
        $research->any('research_entity_resolution', '/research/entity-resolution', 'entityResolution');

        // =====================================================================
        // ISSUE 159 PHASE 2c: TIMELINE
        // =====================================================================
        $research->any('research_timeline_event_api', '/research/timeline-event-api', 'timelineEventApi');
        $research->any('research_timeline_data', '/research/timeline-data', 'timelineData');
        $research->any('research_timeline_builder', '/research/timeline/:project_id', 'timelineBuilder', ['project_id' => '\\d+']);

        // =====================================================================
        // ISSUE 159 PHASE 2c: MAP
        // =====================================================================
        $research->any('research_map_point_api', '/research/map-point-api', 'mapPointApi');
        $research->any('research_map_data', '/research/map-data', 'mapData');
        $research->any('research_map_builder', '/research/map/:project_id', 'mapBuilder', ['project_id' => '\\d+']);

        // =====================================================================
        // ISSUE 159 PHASE 2c: NETWORK GRAPH
        // =====================================================================
        $research->any('research_export_graph_graphml', '/research/network-graph/:project_id/export/graphml', 'exportGraphML', ['project_id' => '\\d+']);
        $research->any('research_export_graph_gexf', '/research/network-graph/:project_id/export/gexf', 'exportGraphGEXF', ['project_id' => '\\d+']);
        $research->any('research_network_graph_data', '/research/network-graph-data', 'networkGraphData');
        $research->any('research_network_graph', '/research/network-graph/:project_id', 'networkGraph', ['project_id' => '\\d+']);

        // =====================================================================
        // ISSUE 159 PHASE 2d: RO-CRATE
        // =====================================================================
        $research->any('research_package_collection', '/research/ro-crate/collection/:id', 'packageCollection', ['id' => '\\d+']);
        $research->any('research_package_project', '/research/ro-crate/:project_id', 'packageProject', ['project_id' => '\\d+']);

        // =====================================================================
        // ISSUE 159 PHASE 2d: ODRL
        // =====================================================================
        $research->any('research_evaluate_access', '/research/odrl/evaluate', 'evaluateAccess');
        $research->any('research_create_odrl_policy', '/research/odrl/create', 'createOdrlPolicy');
        $research->any('research_delete_odrl_policy', '/research/odrl/delete/:id', 'deleteOdrlPolicy', ['id' => '\\d+']);
        $research->any('research_odrl_policies', '/research/odrl/policies', 'odrlPolicies');

        // =====================================================================
        // ISSUE 159 PHASE 2d: REPRODUCIBILITY & DOI
        // =====================================================================
        $research->any('research_mint_doi', '/research/doi/:project_id', 'mintDoi', ['project_id' => '\\d+']);
        $research->any('research_project_json_ld', '/research/json-ld/:project_id', 'projectJsonLd', ['project_id' => '\\d+']);
        $research->any('research_reproducibility_pack', '/research/reproducibility/:project_id', 'reproducibilityPack', ['project_id' => '\\d+']);

        // =====================================================================
        // ISSUE 159 PHASE 2: EVIDENCE VIEWER + COMPLIANCE DASHBOARD
        // =====================================================================
        $research->any('research_evidence_viewer', '/research/evidence/:object_id', 'evidenceViewer', ['object_id' => '\\d+']);
        $research->any('research_compliance_dashboard', '/research/compliance/:project_id', 'complianceDashboard', ['project_id' => '\\d+']);

        // =====================================================================
        // ISSUE 159 PHASE 2e: ENHANCED COLLABORATION
        // =====================================================================
        $research->any('research_assertion_batch_review', '/research/assertion-batch-review/:project_id', 'assertionBatchReview', ['project_id' => '\\d+']);
        $research->any('research_ethics_milestones', '/research/ethics-milestones/:project_id', 'ethicsMilestones', ['project_id' => '\\d+']);

        // Dashboard
        $research->any('research_dashboard', '/research', 'dashboard');
        $research->any('research_renewal', '/research/renewal', 'renewal');
        $research->any('research_index', '/research/dashboard', 'dashboard');

        $research->register($routing);
    }
}
