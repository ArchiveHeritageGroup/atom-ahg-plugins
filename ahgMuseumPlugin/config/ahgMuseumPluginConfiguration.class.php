<?php

class ahgMuseumPluginConfiguration extends sfPluginConfiguration
{
    public static $summary = 'AHG Museum Plugin - CCO/Spectrum museum object cataloguing';
    public static $version = '1.0.0';

    public function initialize()
    {
        $this->dispatcher->connect('routing.load_configuration', [$this, 'addRoutes']);

        // Enable modules
        $enabledModules = sfConfig::get('sf_enabled_modules', []);
        $enabledModules[] = 'ahgMuseumPlugin';
        $enabledModules[] = 'cco';
        $enabledModules[] = 'museumReports';
        $enabledModules[] = 'dashboard';
        $enabledModules[] = 'cidoc';
        $enabledModules[] = 'authority';
        $enabledModules[] = 'api';
        // Note: Exhibition module moved to standalone ahgExhibitionPlugin
        // Note: Loan module moved to standalone ahgLoanPlugin
        sfConfig::set('sf_enabled_modules', array_unique($enabledModules));
    }

    public function addRoutes(sfEvent $event)
    {
        $routing = $event->getSubject();

        // === CCO PROVENANCE ===
        $routing->prependRoute('cco_provenance', new sfRoute(
            '/:slug/cco/provenance',
            ['module' => 'cco', 'action' => 'provenance']
        ));

        // === MUSEUM VIEW/BROWSE/CRUD ===
        $routing->prependRoute('museum_view', new AhgMetadataRoute(
            '/museum/:slug',
            ['module' => 'ahgMuseumPlugin', 'action' => 'index'],
            ['slug' => '[a-zA-Z0-9_-]+']
        ));
        $routing->prependRoute('museum_browse', new sfRoute(
            '/museum/browse',
            ['module' => 'ahgMuseumPlugin', 'action' => 'browse']
        ));
        $routing->prependRoute('museum_add', new sfRoute(
            '/museum/add',
            ['module' => 'ahgMuseumPlugin', 'action' => 'add']
        ));
        $routing->prependRoute('museum_edit', new sfRoute(
            '/museum/edit/:slug',
            ['module' => 'ahgMuseumPlugin', 'action' => 'edit'],
            ['slug' => '[a-zA-Z0-9_-]+']
        ));
        // === VOCABULARY/GETTY API ===
        $routing->prependRoute('ahgMuseumPlugin_vocabulary', new sfRoute(
            '/ahgMuseumPlugin/vocabulary',
            ['module' => 'ahgMuseumPlugin', 'action' => 'vocabulary']
        ));
        $routing->prependRoute('ahgMuseumPlugin_getty', new sfRoute(
            '/ahgMuseumPlugin/getty',
            ['module' => 'ahgMuseumPlugin', 'action' => 'gettyAutocomplete']
        ));

        // Note: Exhibition routes moved to standalone ahgExhibitionPlugin
        // Note: Loan routes moved to standalone ahgLoanPlugin
    }
}