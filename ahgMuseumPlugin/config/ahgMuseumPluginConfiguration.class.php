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
        $enabledModules[] = 'exhibition';
        $enabledModules[] = 'loan';
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

        // === EXHIBITION MANAGEMENT ===
        $routing->prependRoute('exhibition_index', new sfRoute(
            '/exhibition',
            ['module' => 'exhibition', 'action' => 'index']
        ));
        $routing->prependRoute('exhibition_dashboard', new sfRoute(
            '/exhibition/dashboard',
            ['module' => 'exhibition', 'action' => 'dashboard']
        ));
        $routing->prependRoute('exhibition_add', new sfRoute(
            '/exhibition/add',
            ['module' => 'exhibition', 'action' => 'add']
        ));
        $routing->prependRoute('exhibition_show', new sfRoute(
            '/exhibition/:id',
            ['module' => 'exhibition', 'action' => 'show'],
            ['id' => '\d+']
        ));
        $routing->prependRoute('exhibition_edit', new sfRoute(
            '/exhibition/:id/edit',
            ['module' => 'exhibition', 'action' => 'edit'],
            ['id' => '\d+']
        ));
        $routing->prependRoute('exhibition_objects', new sfRoute(
            '/exhibition/:id/objects',
            ['module' => 'exhibition', 'action' => 'objects'],
            ['id' => '\d+']
        ));
        $routing->prependRoute('exhibition_sections', new sfRoute(
            '/exhibition/:id/sections',
            ['module' => 'exhibition', 'action' => 'sections'],
            ['id' => '\d+']
        ));
        $routing->prependRoute('exhibition_storylines', new sfRoute(
            '/exhibition/:id/storylines',
            ['module' => 'exhibition', 'action' => 'storylines'],
            ['id' => '\d+']
        ));
        $routing->prependRoute('exhibition_events', new sfRoute(
            '/exhibition/:id/events',
            ['module' => 'exhibition', 'action' => 'events'],
            ['id' => '\d+']
        ));
        $routing->prependRoute('exhibition_checklists', new sfRoute(
            '/exhibition/:id/checklists',
            ['module' => 'exhibition', 'action' => 'checklists'],
            ['id' => '\d+']
        ));
        $routing->prependRoute('exhibition_object_list', new sfRoute(
            '/exhibition/:id/object-list',
            ['module' => 'exhibition', 'action' => 'objectList'],
            ['id' => '\d+']
        ));
        $routing->prependRoute('exhibition_storyline', new sfRoute(
            '/exhibition/:id/storyline/:storyline_id',
            ['module' => 'exhibition', 'action' => 'storyline'],
            ['id' => '\d+', 'storyline_id' => '\d+']
        ));
        $routing->prependRoute('exhibition_add_storyline', new sfRoute(
            '/exhibition/:id/add-storyline',
            ['module' => 'exhibition', 'action' => 'addStoryline'],
            ['id' => '\d+']
        ));
        $routing->prependRoute('exhibition_add_stop', new sfRoute(
            '/exhibition/:id/storyline/:storyline_id/add-stop',
            ['module' => 'exhibition', 'action' => 'addStop'],
            ['id' => '\d+', 'storyline_id' => '\d+']
        ));

        // === LOAN MANAGEMENT ===
        $routing->prependRoute('loan_index', new sfRoute(
            '/loan',
            ['module' => 'loan', 'action' => 'index']
        ));
        $routing->prependRoute('loan_add', new sfRoute(
            '/loan/add',
            ['module' => 'loan', 'action' => 'add']
        ));
        $routing->prependRoute('loan_show', new sfRoute(
            '/loan/:id',
            ['module' => 'loan', 'action' => 'show'],
            ['id' => '\d+']
        ));
        $routing->prependRoute('loan_edit', new sfRoute(
            '/loan/:id/edit',
            ['module' => 'loan', 'action' => 'edit'],
            ['id' => '\d+']
        ));
        $routing->prependRoute('loan_add_object', new sfRoute(
            '/loan/:id/add-object',
            ['module' => 'loan', 'action' => 'addObject'],
            ['id' => '\d+']
        ));
        $routing->prependRoute('loan_remove_object', new sfRoute(
            '/loan/:id/remove-object',
            ['module' => 'loan', 'action' => 'removeObject'],
            ['id' => '\d+']
        ));
        $routing->prependRoute('loan_transition', new sfRoute(
            '/loan/:id/transition',
            ['module' => 'loan', 'action' => 'transition'],
            ['id' => '\d+']
        ));
        $routing->prependRoute('loan_extend', new sfRoute(
            '/loan/:id/extend',
            ['module' => 'loan', 'action' => 'extend'],
            ['id' => '\d+']
        ));
        $routing->prependRoute('loan_return', new sfRoute(
            '/loan/:id/return',
            ['module' => 'loan', 'action' => 'return'],
            ['id' => '\d+']
        ));
        $routing->prependRoute('loan_agreement', new sfRoute(
            '/loan/:id/agreement',
            ['module' => 'loan', 'action' => 'agreement'],
            ['id' => '\d+']
        ));
        $routing->prependRoute('loan_upload_document', new sfRoute(
            '/loan/:id/upload-document',
            ['module' => 'loan', 'action' => 'uploadDocument'],
            ['id' => '\d+']
        ));
        $routing->prependRoute('loan_search_objects', new sfRoute(
            '/loan/search-objects',
            ['module' => 'loan', 'action' => 'searchObjects']
        ));
    }
}