<?php

/**
 * ahgSemanticSearchPlugin Configuration.
 *
 * Provides semantic search capabilities including:
 * - Thesaurus management with domain-specific synonyms
 * - WordNet sync via Datamuse API
 * - Wikidata SPARQL integration
 * - Vector embeddings via Ollama
 *
 * @author Johan Pieterse <johan@theahg.co.za>
 */
class ahgSemanticSearchPluginConfiguration extends sfPluginConfiguration
{
    /** Shown by AtoM's stock plugin admin; omitted plugins are hidden there. */
    public static $summary = 'Semantic search with vector embeddings and thesaurus expansion';

    // Required: sfPluginAdminPlugin renders $plugin::$version, and reading an
    // undeclared static property is a fatal Error in PHP 8 - it kills the
    // plugins page mid-render, taking the save button with it.
    public static $version = "1.0.0";

    /** Plugin version */
    public const VERSION = '1.0.0';

    /**
     * {@inheritDoc}
     */
    public function initialize()
    {
        // Enable modules
        $enabledModules = sfConfig::get('sf_enabled_modules', []);
        $enabledModules[] = 'searchEnhancement';
        sfConfig::set('sf_enabled_modules', $enabledModules);

        // Register CLI commands
        $this->dispatcher->connect('command.pre_command', [$this, 'registerCommands']);

        // Register routes
        $this->dispatcher->connect('routing.load_configuration', [$this, 'addRoutes']);

        // Search history: nothing called AdvancedSearchService::recordSearch(),
        // so the history page was always empty.
        $this->dispatcher->connect('controller.change_action', [$this, 'recordSearchHistory']);
    }

    /**
     * Records a signed-in user's search on the main browse pages. Visitors are
     * not recorded, so crawlers cannot fill the table, and no IP address or
     * browser details are stored. A failure here never affects the search.
     */
    public function recordSearchHistory(sfEvent $event)
    {
        static $done = false;
        if ($done) {
            return;
        }
        $route = $event['module'].'/'.$event['action'];
        if (!in_array($route, ['display/browse', 'informationobject/browse'], true)) {
            return;
        }
        try {
            $context = sfContext::getInstance();
            $user = $context->getUser();
            $request = $context->getRequest();
            $query = trim((string) $request->getGetParameter('query', ''));
            if (!$user->isAuthenticated() || '' === $query || !$request->isMethod('get')) {
                return;
            }
            $done = true;
            $params = array_filter((array) $request->getGetParameters(), static fn ($v) => is_scalar($v) && '' !== (string) $v);
            unset($params['page'], $params['_csrf_token']);
            (new \AtomFramework\Services\AdvancedSearchService())->recordSearch([
                'user_id' => (int) $user->getAttribute('user_id'),
                'session_id' => session_id() ?: null,
                'search_query' => mb_substr($query, 0, 500),
                'search_params' => $params,
                'entity_type' => 'informationobject',
            ]);
        } catch (\Throwable $e) {
            error_log('search_history.record_failed: '.$e->getMessage());
        }
    }

    /**
     * Add routes for search enhancement.
     */
    public function addRoutes(sfEvent $event)
    {
        $router = new \AtomFramework\Routing\RouteLoader('searchEnhancement');

        $router->any('search_enhancement_save', '/search/enhancement/save', 'saveSearch');
        $router->any('search_enhancement_saved', '/search/enhancement/saved', 'savedSearches');
        $router->any('search_enhancement_run_saved', '/search/enhancement/run/:id', 'runSavedSearch', ['id' => '\d+']);
        $router->any('search_enhancement_run_template', '/search/enhancement/template/:id', 'runTemplate', ['id' => '\d+']);
        $router->any('search_enhancement_history', '/search/enhancement/history', 'history');
        $router->any('search_enhancement_delete', '/search/enhancement/delete/:id', 'deleteSavedSearch', ['id' => '\d+']);
        $router->any('search_enhancement_admin_templates', '/admin/search-templates', 'adminTemplates');

        $router->register($event->getSubject());
    }

    /**
     * Register CLI commands for thesaurus management.
     */
    public function registerCommands(sfEvent $event)
    {
        // Commands will be registered here when moved from framework
    }

    /**
     * Get plugin version.
     */
    public static function getVersion(): string
    {
        return self::VERSION;
    }
}
