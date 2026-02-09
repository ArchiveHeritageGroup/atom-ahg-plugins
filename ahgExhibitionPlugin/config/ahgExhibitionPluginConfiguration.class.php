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
        $router = new \AtomFramework\Routing\RouteLoader('exhibition');

        // Exhibition browse/index
        $router->any('exhibition_index', '/exhibitions', 'index');

        // Exhibition dashboard
        $router->any('exhibition_dashboard', '/exhibition/dashboard', 'dashboard');

        // Create/Add exhibition
        $router->any('exhibition_add', '/exhibition/add', 'add');

        // View exhibition by ID
        $router->any('exhibition_show', '/exhibition/:id', 'show', ['id' => '\d+']);

        // Edit exhibition
        $router->any('exhibition_edit', '/exhibition/:id/edit', 'edit', ['id' => '\d+']);

        // Exhibition objects management
        $router->any('exhibition_objects', '/exhibition/:id/objects', 'objects', ['id' => '\d+']);

        // Exhibition storylines
        $router->any('exhibition_storylines', '/exhibition/:id/storylines', 'storylines', ['id' => '\d+']);

        // Single storyline
        $router->any('exhibition_storyline', '/exhibition/:id/storyline/:storyline_id', 'storyline', ['id' => '\d+', 'storyline_id' => '\d+']);

        // Exhibition sections
        $router->any('exhibition_sections', '/exhibition/:id/sections', 'sections', ['id' => '\d+']);

        // Exhibition events
        $router->any('exhibition_events', '/exhibition/:id/events', 'events', ['id' => '\d+']);

        // Exhibition checklists
        $router->any('exhibition_checklists', '/exhibition/:id/checklists', 'checklists', ['id' => '\d+']);

        // Object list (for export/print)
        $router->any('exhibition_object_list', '/exhibition/:id/object-list', 'objectList', ['id' => '\d+']);

        // Venues management
        $router->any('exhibition_venues', '/exhibition/venues', 'venues');

        // View exhibition by slug (must be last - catch-all)
        $router->any('exhibition_view', '/exhibition/:slug', 'show', ['slug' => '[a-z0-9-]+']);

        $router->register($event->getSubject());
    }
}
