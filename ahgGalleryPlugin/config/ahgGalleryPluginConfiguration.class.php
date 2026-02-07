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
        $routing = $event->getSubject();

        // Order matters! Generic route first, then specific ones prepended after
        $routing->prependRoute('gallery_view', new AhgMetadataRoute(
            '/gallery/:slug',
            ['module' => 'gallery', 'action' => 'index'],
            ['slug' => '[a-zA-Z0-9_-]+']
        ));
        $routing->prependRoute('gallery_edit', new sfRoute(
            '/gallery/edit/:slug',
            ['module' => 'gallery', 'action' => 'edit'],
            ['slug' => '[a-zA-Z0-9_-]+']
        ));
        $routing->prependRoute('gallery_add', new sfRoute(
            '/gallery/add',
            ['module' => 'gallery', 'action' => 'add']
        ));
        $routing->prependRoute('gallery_browse', new sfRoute(
            '/gallery/browse',
            ['module' => 'gallery', 'action' => 'browse']
        ));
        $routing->prependRoute('gallery_dashboard', new sfRoute(
            '/gallery/dashboard',
            ['module' => 'gallery', 'action' => 'dashboard']
        ));

        // Loans
        $routing->prependRoute('gallery_loans', new sfRoute(
            '/gallery/loans',
            ['module' => 'gallery', 'action' => 'loans']
        ));
        $routing->prependRoute('gallery_create_loan', new sfRoute(
            '/gallery/loans/create',
            ['module' => 'gallery', 'action' => 'createLoan']
        ));
        $routing->prependRoute('gallery_view_loan', new sfRoute(
            '/gallery/loans/:id',
            ['module' => 'gallery', 'action' => 'viewLoan'],
            ['id' => '\d+']
        ));
        $routing->prependRoute('gallery_facility_report', new sfRoute(
            '/gallery/loans/:loan_id/facility-report',
            ['module' => 'gallery', 'action' => 'facilityReport']
        ));

        // Valuations
        $routing->prependRoute('gallery_valuations', new sfRoute(
            '/gallery/valuations',
            ['module' => 'gallery', 'action' => 'valuations']
        ));
        $routing->prependRoute('gallery_create_valuation', new sfRoute(
            '/gallery/valuations/create',
            ['module' => 'gallery', 'action' => 'createValuation']
        ));

        // Artists
        $routing->prependRoute('gallery_artists', new sfRoute(
            '/gallery/artists',
            ['module' => 'gallery', 'action' => 'artists']
        ));
        $routing->prependRoute('gallery_create_artist', new sfRoute(
            '/gallery/artists/create',
            ['module' => 'gallery', 'action' => 'createArtist']
        ));
        $routing->prependRoute('gallery_view_artist', new sfRoute(
            '/gallery/artists/:id',
            ['module' => 'gallery', 'action' => 'viewArtist'],
            ['id' => '\d+']
        ));

        // Venues
        $routing->prependRoute('gallery_venues', new sfRoute(
            '/gallery/venues',
            ['module' => 'gallery', 'action' => 'venues']
        ));
        $routing->prependRoute('gallery_create_venue', new sfRoute(
            '/gallery/venues/create',
            ['module' => 'gallery', 'action' => 'createVenue']
        ));
        $routing->prependRoute('gallery_view_venue', new sfRoute(
            '/gallery/venues/:id',
            ['module' => 'gallery', 'action' => 'viewVenue'],
            ['id' => '\d+']
        ));
    }
}
