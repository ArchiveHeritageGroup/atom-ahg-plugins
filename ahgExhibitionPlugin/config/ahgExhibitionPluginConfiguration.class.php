<?php

/**
 * ahgExhibitionPlugin Configuration.
 *
 * Unified exhibition management for all GLAM/DAM sectors:
 * - Archives
 * - Museums
 * - Galleries
 * - Libraries
 * - DAM institutions
 *
 * Features:
 * - Exhibition lifecycle management (concept to archived)
 * - Object selection and placement
 * - Storylines and narratives
 * - Venue and space management
 * - Installation tracking
 * - Event scheduling
 * - Checklists and tasks
 *
 * @author Johan Pieterse <johan@theahg.co.za>
 */
class ahgExhibitionPluginConfiguration extends sfPluginConfiguration
{
    public static $summary = 'Exhibition management for GLAM/DAM sectors';
    public static $version = '1.0.0';

    public function initialize()
    {
        // Enable modules
        $enabledModules = sfConfig::get('sf_enabled_modules', []);
        $enabledModules[] = 'exhibition';
        sfConfig::set('sf_enabled_modules', $enabledModules);

        // Register routes
        $this->dispatcher->connect('routing.load_configuration', [$this, 'addRoutes']);
    }

    public function addRoutes(sfEvent $event)
    {
        $routing = $event->getSubject();

        // Exhibition browse/index
        $routing->prependRoute('exhibition_index', new sfRoute(
            '/exhibitions',
            ['module' => 'exhibition', 'action' => 'index']
        ));

        // Exhibition dashboard
        $routing->prependRoute('exhibition_dashboard', new sfRoute(
            '/exhibition/dashboard',
            ['module' => 'exhibition', 'action' => 'dashboard']
        ));

        // Create/Add exhibition
        $routing->prependRoute('exhibition_add', new sfRoute(
            '/exhibition/add',
            ['module' => 'exhibition', 'action' => 'add']
        ));

        // View exhibition by ID
        $routing->prependRoute('exhibition_show', new sfRoute(
            '/exhibition/:id',
            ['module' => 'exhibition', 'action' => 'show'],
            ['id' => '\d+']
        ));

        // Edit exhibition
        $routing->prependRoute('exhibition_edit', new sfRoute(
            '/exhibition/:id/edit',
            ['module' => 'exhibition', 'action' => 'edit'],
            ['id' => '\d+']
        ));

        // Exhibition objects management
        $routing->prependRoute('exhibition_objects', new sfRoute(
            '/exhibition/:id/objects',
            ['module' => 'exhibition', 'action' => 'objects'],
            ['id' => '\d+']
        ));

        // Exhibition storylines
        $routing->prependRoute('exhibition_storylines', new sfRoute(
            '/exhibition/:id/storylines',
            ['module' => 'exhibition', 'action' => 'storylines'],
            ['id' => '\d+']
        ));

        // Single storyline
        $routing->prependRoute('exhibition_storyline', new sfRoute(
            '/exhibition/:id/storyline/:storyline_id',
            ['module' => 'exhibition', 'action' => 'storyline'],
            ['id' => '\d+', 'storyline_id' => '\d+']
        ));

        // Exhibition sections
        $routing->prependRoute('exhibition_sections', new sfRoute(
            '/exhibition/:id/sections',
            ['module' => 'exhibition', 'action' => 'sections'],
            ['id' => '\d+']
        ));

        // Exhibition events
        $routing->prependRoute('exhibition_events', new sfRoute(
            '/exhibition/:id/events',
            ['module' => 'exhibition', 'action' => 'events'],
            ['id' => '\d+']
        ));

        // Exhibition checklists
        $routing->prependRoute('exhibition_checklists', new sfRoute(
            '/exhibition/:id/checklists',
            ['module' => 'exhibition', 'action' => 'checklists'],
            ['id' => '\d+']
        ));

        // Object list (for export/print)
        $routing->prependRoute('exhibition_object_list', new sfRoute(
            '/exhibition/:id/object-list',
            ['module' => 'exhibition', 'action' => 'objectList'],
            ['id' => '\d+']
        ));

        // Venues management
        $routing->prependRoute('exhibition_venues', new sfRoute(
            '/exhibition/venues',
            ['module' => 'exhibition', 'action' => 'venues']
        ));

        // View exhibition by slug (must be last - catch-all)
        $routing->prependRoute('exhibition_view', new sfRoute(
            '/exhibition/:slug',
            ['module' => 'exhibition', 'action' => 'show'],
            ['slug' => '[a-z0-9-]+']
        ));
    }
}
