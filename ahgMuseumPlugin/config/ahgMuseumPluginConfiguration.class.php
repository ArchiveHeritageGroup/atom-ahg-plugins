<?php

class ahgMuseumPluginConfiguration extends sfPluginConfiguration
{
    public static $summary = 'AHG Museum Plugin - CCO/Spectrum museum object cataloguing';
    public static $version = '1.1.0';

    public function initialize()
    {
        $this->dispatcher->connect('routing.load_configuration', [$this, 'addRoutes']);

        // Enable modules
        $enabledModules = sfConfig::get('sf_enabled_modules', []);
        $enabledModules[] = 'museum';
        $enabledModules[] = 'cco';
        $enabledModules[] = 'museumReports';
        $enabledModules[] = 'dashboard';
        $enabledModules[] = 'cidoc';
        $enabledModules[] = 'authority';
        $enabledModules[] = 'museumApi';
        // Note: Exhibition module moved to standalone ahgExhibitionPlugin
        // Note: Loan module moved to standalone ahgLoanPlugin
        sfConfig::set('sf_enabled_modules', array_unique($enabledModules));
    }

    public function addRoutes(sfEvent $event)
    {
        $routing = $event->getSubject();

        // CCO module routes
        $cco = new \AtomFramework\Routing\RouteLoader('cco');
        $cco->any('cco_provenance', '/:slug/cco/provenance', 'provenance');
        $cco->register($routing);

        // Museum module routes
        // museum_view uses AhgMetadataRoute (not sfRoute), so register manually
        $routing->prependRoute('museum_view', new AhgMetadataRoute(
            '/museum/:slug',
            ['module' => 'museum', 'action' => 'index'],
            ['slug' => '[a-zA-Z0-9_-]+']
        ));

        $museum = new \AtomFramework\Routing\RouteLoader('museum');
        $museum->any('museum_browse', '/museum/browse', 'browse');
        $museum->any('museum_add', '/museum/add', 'add');
        $museum->any('museum_edit', '/museum/edit/:slug', 'edit', ['slug' => '[a-zA-Z0-9_-]+']);
        $museum->any('museum_vocabulary', '/museum/vocabulary', 'vocabulary');
        $museum->any('museum_getty', '/museum/getty', 'gettyAutocomplete');
        $museum->register($routing);

        // Note: Exhibition routes moved to standalone ahgExhibitionPlugin
        // Note: Loan routes moved to standalone ahgLoanPlugin
    }
}
