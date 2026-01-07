<?php
class ahgResearchPluginConfiguration extends sfPluginConfiguration
{
    public static $summary = 'Research support plugin with reading room booking';
    public static $version = '1.0.0';

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
        sfConfig::set('sf_enabled_modules', $enabledModules);
    }

    public function addRoutes(sfEvent $event)
    {
        $routing = $event->getSubject();

        // Audit routes
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

        // Public routes (no auth required)
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

        // Admin routes
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

        // Check in/out
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

        // Booking routes
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

        // Researcher routes
        $routing->prependRoute('research_view_researcher', new sfRoute(
            '/research/researcher/:id',
            ['module' => 'research', 'action' => 'viewResearcher'],
            ['id' => '\d+']
        ));
        $routing->prependRoute('research_researchers', new sfRoute(
            '/research/researchers',
            ['module' => 'research', 'action' => 'researchers']
        ));

        // Workspace routes
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

        // Profile routes
        $routing->prependRoute('research_profile', new sfRoute(
            '/research/profile',
            ['module' => 'research', 'action' => 'profile']
        ));
        $routing->prependRoute('research_register', new sfRoute(
            '/research/register',
            ['module' => 'research', 'action' => 'register']
        ));

        // Dashboard
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
