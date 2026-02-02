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
    public static $version = '2.0.0';

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
        // API ROUTES
        // =====================================================================
        $routing->prependRoute('api_research_stats', new sfRoute(
            '/api/research/stats',
            ['module' => 'researchapi', 'action' => 'stats']
        ));
        $routing->prependRoute('api_research_annotations', new sfRoute(
            '/api/research/annotations',
            ['module' => 'researchapi', 'action' => 'annotations']
        ));
        $routing->prependRoute('api_research_bibliography_export', new sfRoute(
            '/api/research/bibliographies/:id/export/:format',
            ['module' => 'researchapi', 'action' => 'exportBibliography'],
            ['id' => '\d+']
        ));
        $routing->prependRoute('api_research_bibliographies', new sfRoute(
            '/api/research/bibliographies',
            ['module' => 'researchapi', 'action' => 'bibliographies']
        ));
        $routing->prependRoute('api_research_citation', new sfRoute(
            '/api/research/citations/:id/:format',
            ['module' => 'researchapi', 'action' => 'citation', 'format' => 'chicago'],
            ['id' => '\d+']
        ));
        $routing->prependRoute('api_research_booking', new sfRoute(
            '/api/research/bookings',
            ['module' => 'researchapi', 'action' => 'bookings']
        ));
        $routing->prependRoute('api_research_searches', new sfRoute(
            '/api/research/searches',
            ['module' => 'researchapi', 'action' => 'searches']
        ));
        $routing->prependRoute('api_research_collection', new sfRoute(
            '/api/research/collections/:id',
            ['module' => 'researchapi', 'action' => 'collection'],
            ['id' => '\d+']
        ));
        $routing->prependRoute('api_research_collections', new sfRoute(
            '/api/research/collections',
            ['module' => 'researchapi', 'action' => 'collections']
        ));
        $routing->prependRoute('api_research_projects', new sfRoute(
            '/api/research/projects',
            ['module' => 'researchapi', 'action' => 'projects']
        ));
        $routing->prependRoute('api_research_profile', new sfRoute(
            '/api/research/profile',
            ['module' => 'researchapi', 'action' => 'profile']
        ));

        // =====================================================================
        // AUDIT ROUTES
        // =====================================================================
        $routing->prependRoute('audit_export', new sfRoute(
            '/audit/export',
            ['module' => 'audit', 'action' => 'export']
        ));
        $routing->prependRoute('audit_user', new sfRoute(
            '/audit/user/:id',
            ['module' => 'audit', 'action' => 'user'],
            ['id' => '\d+']
        ));
        $routing->prependRoute('audit_record', new sfRoute(
            '/audit/record/:table/:record_id',
            ['module' => 'audit', 'action' => 'record'],
            ['record_id' => '\d+']
        ));
        $routing->prependRoute('audit_view', new sfRoute(
            '/audit/view/:id',
            ['module' => 'audit', 'action' => 'view'],
            ['id' => '\d+']
        ));
        $routing->prependRoute('audit_index', new sfRoute(
            '/audit',
            ['module' => 'audit', 'action' => 'index']
        ));

        // =====================================================================
        // READING ROOM OPERATIONS ROUTES
        // =====================================================================
        $routing->prependRoute('research_activity_view', new sfRoute(
            '/research/activities/:id',
            ['module' => 'research', 'action' => 'viewActivity'],
            ['id' => '\d+']
        ));
        $routing->prependRoute('research_activities', new sfRoute(
            '/research/activities',
            ['module' => 'research', 'action' => 'activities']
        ));
        $routing->prependRoute('research_walk_in', new sfRoute(
            '/research/walk-in',
            ['module' => 'research', 'action' => 'walkIn']
        ));
        $routing->prependRoute('research_equipment_book', new sfRoute(
            '/research/equipment/book',
            ['module' => 'research', 'action' => 'bookEquipment']
        ));
        $routing->prependRoute('research_equipment', new sfRoute(
            '/research/equipment',
            ['module' => 'research', 'action' => 'equipment']
        ));
        $routing->prependRoute('research_seat_map', new sfRoute(
            '/research/seats/map',
            ['module' => 'research', 'action' => 'seatMap']
        ));
        $routing->prependRoute('research_seat_assign', new sfRoute(
            '/research/seats/assign',
            ['module' => 'research', 'action' => 'assignSeat']
        ));
        $routing->prependRoute('research_seats', new sfRoute(
            '/research/seats',
            ['module' => 'research', 'action' => 'seats']
        ));
        $routing->prependRoute('research_print_call_slips', new sfRoute(
            '/research/call-slips/print',
            ['module' => 'research', 'action' => 'printCallSlips']
        ));
        $routing->prependRoute('research_retrieval_queue', new sfRoute(
            '/research/retrieval-queue',
            ['module' => 'research', 'action' => 'retrievalQueue']
        ));

        // =====================================================================
        // ADMIN ROUTES
        // =====================================================================
        $routing->prependRoute('research_admin_statistics', new sfRoute(
            '/research/admin/statistics',
            ['module' => 'research', 'action' => 'adminStatistics']
        ));
        $routing->prependRoute('research_admin_types_edit', new sfRoute(
            '/research/admin/types/edit/:id',
            ['module' => 'research', 'action' => 'editResearcherType', 'id' => null],
            ['id' => '\d+']
        ));
        $routing->prependRoute('research_admin_types', new sfRoute(
            '/research/admin/types',
            ['module' => 'research', 'action' => 'adminTypes']
        ));

        // =====================================================================
        // ORCID ROUTES
        // =====================================================================
        $routing->prependRoute('research_orcid_disconnect', new sfRoute(
            '/research/orcid/disconnect',
            ['module' => 'research', 'action' => 'orcidDisconnect']
        ));
        $routing->prependRoute('research_orcid_callback', new sfRoute(
            '/research/orcid/callback',
            ['module' => 'research', 'action' => 'orcidCallback']
        ));
        $routing->prependRoute('research_orcid_connect', new sfRoute(
            '/research/orcid/connect',
            ['module' => 'research', 'action' => 'orcidConnect']
        ));

        // =====================================================================
        // WORKSPACE ROUTES
        // =====================================================================
        $routing->prependRoute('research_workspace_invite', new sfRoute(
            '/research/workspaces/:id/invite',
            ['module' => 'research', 'action' => 'inviteWorkspaceMember'],
            ['id' => '\d+']
        ));
        $routing->prependRoute('research_workspace_discussion', new sfRoute(
            '/research/workspaces/:id/discussion/:discussion_id',
            ['module' => 'research', 'action' => 'workspaceDiscussion'],
            ['id' => '\d+', 'discussion_id' => '\d+']
        ));
        $routing->prependRoute('research_view_workspace', new sfRoute(
            '/research/workspaces/:id',
            ['module' => 'research', 'action' => 'viewWorkspace'],
            ['id' => '\d+']
        ));
        $routing->prependRoute('research_workspaces', new sfRoute(
            '/research/workspaces',
            ['module' => 'research', 'action' => 'workspaces']
        ));

        // =====================================================================
        // PROJECT ROUTES
        // =====================================================================
        $routing->prependRoute('research_project_invite', new sfRoute(
            '/research/project/:id/invite',
            ['module' => 'research', 'action' => 'inviteCollaborator'],
            ['id' => '\d+']
        ));
        $routing->prependRoute('research_project_collaborators', new sfRoute(
            '/research/project/:id/collaborators',
            ['module' => 'research', 'action' => 'projectCollaborators'],
            ['id' => '\d+']
        ));
        $routing->prependRoute('research_project_activity', new sfRoute(
            '/research/project/:id/activity',
            ['module' => 'research', 'action' => 'projectActivity'],
            ['id' => '\d+']
        ));
        $routing->prependRoute('research_project_edit', new sfRoute(
            '/research/project/:id/edit',
            ['module' => 'research', 'action' => 'editProject'],
            ['id' => '\d+']
        ));
        $routing->prependRoute('research_view_project', new sfRoute(
            '/research/project/:id',
            ['module' => 'research', 'action' => 'viewProject'],
            ['id' => '\d+']
        ));
        $routing->prependRoute('research_projects', new sfRoute(
            '/research/projects',
            ['module' => 'research', 'action' => 'projects']
        ));

        // =====================================================================
        // BIBLIOGRAPHY ROUTES
        // =====================================================================
        $routing->prependRoute('research_bibliography_export', new sfRoute(
            '/research/bibliography/:id/export/:format',
            ['module' => 'research', 'action' => 'exportBibliography'],
            ['id' => '\d+']
        ));
        $routing->prependRoute('research_bibliography_add_entry', new sfRoute(
            '/research/bibliography/:id/add',
            ['module' => 'research', 'action' => 'addBibliographyEntry'],
            ['id' => '\d+']
        ));
        $routing->prependRoute('research_view_bibliography', new sfRoute(
            '/research/bibliography/:id',
            ['module' => 'research', 'action' => 'viewBibliography'],
            ['id' => '\d+']
        ));
        $routing->prependRoute('research_bibliographies', new sfRoute(
            '/research/bibliographies',
            ['module' => 'research', 'action' => 'bibliographies']
        ));

        // =====================================================================
        // REPRODUCTION ROUTES
        // =====================================================================
        $routing->prependRoute('research_reproduction_download', new sfRoute(
            '/research/reproduction/download/:token',
            ['module' => 'research', 'action' => 'reproductionDownload']
        ));
        $routing->prependRoute('research_reproduction_new', new sfRoute(
            '/research/reproduction/new',
            ['module' => 'research', 'action' => 'newReproduction']
        ));
        $routing->prependRoute('research_view_reproduction', new sfRoute(
            '/research/reproduction/:id',
            ['module' => 'research', 'action' => 'viewReproduction'],
            ['id' => '\d+']
        ));
        $routing->prependRoute('research_reproductions', new sfRoute(
            '/research/reproductions',
            ['module' => 'research', 'action' => 'reproductions']
        ));

        // =====================================================================
        // INVITATIONS
        // =====================================================================
        $routing->prependRoute('research_invitation_accept', new sfRoute(
            '/research/invitation/:type/:id/accept',
            ['module' => 'research', 'action' => 'acceptInvitation'],
            ['id' => '\d+']
        ));
        $routing->prependRoute('research_invitation_decline', new sfRoute(
            '/research/invitation/:type/:id/decline',
            ['module' => 'research', 'action' => 'declineInvitation'],
            ['id' => '\d+']
        ));
        $routing->prependRoute('research_invitations', new sfRoute(
            '/research/invitations',
            ['module' => 'research', 'action' => 'invitations']
        ));

        // =====================================================================
        // PUBLIC ROUTES (No Auth Required)
        // =====================================================================
        $routing->prependRoute('research_password_reset', new sfRoute(
            '/research/password-reset',
            ['module' => 'research', 'action' => 'passwordReset']
        ));
        $routing->prependRoute('research_password_reset_request', new sfRoute(
            '/research/forgot-password',
            ['module' => 'research', 'action' => 'passwordResetRequest']
        ));
        $routing->prependRoute('research_registration_complete', new sfRoute(
            '/research/registration-complete',
            ['module' => 'research', 'action' => 'registrationComplete']
        ));
        $routing->prependRoute('research_public_register', new sfRoute(
            '/research/register-researcher',
            ['module' => 'research', 'action' => 'publicRegister']
        ));

        // =====================================================================
        // ADMIN RESEARCHER ROUTES
        // =====================================================================
        $routing->prependRoute('research_admin_reset_password', new sfRoute(
            '/research/researcher/:id/reset-password',
            ['module' => 'research', 'action' => 'adminResetPassword'],
            ['id' => '\d+']
        ));
        $routing->prependRoute('research_reject_researcher', new sfRoute(
            '/research/researcher/:id/reject',
            ['module' => 'research', 'action' => 'rejectResearcher'],
            ['id' => '\d+']
        ));
        $routing->prependRoute('research_approve_researcher', new sfRoute(
            '/research/researcher/:id/approve',
            ['module' => 'research', 'action' => 'approveResearcher'],
            ['id' => '\d+']
        ));
        $routing->prependRoute('research_edit_room', new sfRoute(
            '/research/rooms/edit',
            ['module' => 'research', 'action' => 'editRoom']
        ));
        $routing->prependRoute('research_rooms', new sfRoute(
            '/research/rooms',
            ['module' => 'research', 'action' => 'rooms']
        ));

        // =====================================================================
        // CHECK IN/OUT ROUTES
        // =====================================================================
        $routing->prependRoute('research_check_out', new sfRoute(
            '/research/booking/:id/check-out',
            ['module' => 'research', 'action' => 'checkOut'],
            ['id' => '\d+']
        ));
        $routing->prependRoute('research_check_in', new sfRoute(
            '/research/booking/:id/check-in',
            ['module' => 'research', 'action' => 'checkIn'],
            ['id' => '\d+']
        ));

        // =====================================================================
        // BOOKING ROUTES
        // =====================================================================
        $routing->prependRoute('research_view_booking', new sfRoute(
            '/research/booking/:id',
            ['module' => 'research', 'action' => 'viewBooking'],
            ['id' => '\d+']
        ));
        $routing->prependRoute('research_book', new sfRoute(
            '/research/book',
            ['module' => 'research', 'action' => 'book']
        ));
        $routing->prependRoute('research_bookings', new sfRoute(
            '/research/bookings',
            ['module' => 'research', 'action' => 'bookings']
        ));

        // =====================================================================
        // RESEARCHER ROUTES
        // =====================================================================
        $routing->prependRoute('research_view_researcher', new sfRoute(
            '/research/researcher/:id',
            ['module' => 'research', 'action' => 'viewResearcher'],
            ['id' => '\d+']
        ));
        $routing->prependRoute('research_researchers', new sfRoute(
            '/research/researchers',
            ['module' => 'research', 'action' => 'researchers']
        ));

        // =====================================================================
        // WORKSPACE & COLLECTION ROUTES
        // =====================================================================
        $routing->prependRoute('research_cite', new sfRoute(
            '/research/cite/:slug',
            ['module' => 'research', 'action' => 'cite']
        ));
        $routing->prependRoute('research_annotations', new sfRoute(
            '/research/annotations',
            ['module' => 'research', 'action' => 'annotations']
        ));
        $routing->prependRoute('research_view_collection', new sfRoute(
            '/research/collection/:id',
            ['module' => 'research', 'action' => 'viewCollection'],
            ['id' => '\d+']
        ));
        $routing->prependRoute('research_collections', new sfRoute(
            '/research/collections',
            ['module' => 'research', 'action' => 'collections']
        ));
        $routing->prependRoute('research_saved_searches', new sfRoute(
            '/research/saved-searches',
            ['module' => 'research', 'action' => 'savedSearches']
        ));
        $routing->prependRoute('research_workspace', new sfRoute(
            '/research/workspace',
            ['module' => 'research', 'action' => 'workspace']
        ));

        // =====================================================================
        // PROFILE ROUTES
        // =====================================================================
        $routing->prependRoute('research_api_keys', new sfRoute(
            '/research/profile/api-keys',
            ['module' => 'research', 'action' => 'apiKeys']
        ));
        $routing->prependRoute('research_profile', new sfRoute(
            '/research/profile',
            ['module' => 'research', 'action' => 'profile']
        ));
        $routing->prependRoute('research_register', new sfRoute(
            '/research/register',
            ['module' => 'research', 'action' => 'register']
        ));

        // =====================================================================
        // AJAX ROUTES
        // =====================================================================
        $routing->prependRoute('research_ajax_add_to_collection', new sfRoute(
            '/research/ajax/add-to-collection',
            ['module' => 'research', 'action' => 'addToCollection']
        ));
        $routing->prependRoute('research_ajax_create_collection', new sfRoute(
            '/research/ajax/create-collection',
            ['module' => 'research', 'action' => 'createCollectionAjax']
        ));
        $routing->prependRoute('research_ajax_search_items', new sfRoute(
            '/research/ajax/search-items',
            ['module' => 'research', 'action' => 'searchItems']
        ));
        $routing->prependRoute('research_ajax_add_to_bibliography', new sfRoute(
            '/research/ajax/add-to-bibliography',
            ['module' => 'research', 'action' => 'addToBibliographyAjax']
        ));

        // =====================================================================
        // DASHBOARD
        // =====================================================================
        $routing->prependRoute('research_dashboard', new sfRoute(
            '/research',
            ['module' => 'research', 'action' => 'dashboard']
        ));
        $routing->prependRoute('research_renewal', new sfRoute(
            '/research/renewal',
            ['module' => 'research', 'action' => 'renewal']
        ));
        $routing->prependRoute('research_index', new sfRoute(
            '/research/dashboard',
            ['module' => 'research', 'action' => 'dashboard']
        ));
    }
}
