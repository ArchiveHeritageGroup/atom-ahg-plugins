<?php
class ahgGalleryPluginConfiguration extends sfPluginConfiguration
{
    public static $summary = 'Gallery Management - Artists, Loans, Valuations';
    public static $version = '1.1.0';

    public function initialize()
    {
        $this->dispatcher->connect('routing.load_configuration', [$this, 'addRoutes']);
        $enabledModules = sfConfig::get('sf_enabled_modules', []);
        $enabledModules[] = 'gallery';
        $enabledModules[] = 'galleryReports';
        sfConfig::set('sf_enabled_modules', $enabledModules);
    }

    public function addRoutes(sfEvent $event)
    {
        $router = new \AtomFramework\Routing\RouteLoader('gallery');

        // Order matters! Generic route first, then specific ones prepended after
        $router->any('gallery_view', '/gallery/:slug', 'index', ['slug' => '[a-zA-Z0-9_-]+']);
        $router->any('gallery_edit', '/gallery/edit/:slug', 'edit', ['slug' => '[a-zA-Z0-9_-]+']);
        $router->any('gallery_add', '/gallery/add', 'add');
        $router->any('gallery_browse', '/gallery/browse', 'browse');
        $router->any('gallery_dashboard', '/gallery/dashboard', 'dashboard');

        // Loans
        $router->any('gallery_loans', '/gallery/loans', 'loans');
        $router->any('gallery_create_loan', '/gallery/loans/create', 'createLoan');
        $router->any('gallery_view_loan', '/gallery/loans/:id', 'viewLoan', ['id' => '\d+']);
        $router->any('gallery_facility_report', '/gallery/loans/:loan_id/facility-report', 'facilityReport');

        // Valuations
        $router->any('gallery_valuations', '/gallery/valuations', 'valuations');
        $router->any('gallery_create_valuation', '/gallery/valuations/create', 'createValuation');

        // Artists
        $router->any('gallery_artists', '/gallery/artists', 'artists');
        $router->any('gallery_create_artist', '/gallery/artists/create', 'createArtist');
        $router->any('gallery_view_artist', '/gallery/artists/:id', 'viewArtist', ['id' => '\d+']);

        // Venues
        $router->any('gallery_venues', '/gallery/venues', 'venues');
        $router->any('gallery_create_venue', '/gallery/venues/create', 'createVenue');
        $router->any('gallery_view_venue', '/gallery/venues/:id', 'viewVenue', ['id' => '\d+']);

        $router->register($event->getSubject());
    }
}
