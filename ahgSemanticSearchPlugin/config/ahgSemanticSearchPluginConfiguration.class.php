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
    }

    /**
     * Add routes for search enhancement.
     */
    public function addRoutes(sfEvent $event)
    {
        $routing = $event->getSubject();

        $routing->prependRoute('search_enhancement_save', new sfRoute(
            '/search/enhancement/save',
            ['module' => 'searchEnhancement', 'action' => 'saveSearch']
        ));

        $routing->prependRoute('search_enhancement_saved', new sfRoute(
            '/search/enhancement/saved',
            ['module' => 'searchEnhancement', 'action' => 'savedSearches']
        ));

        $routing->prependRoute('search_enhancement_run_saved', new sfRoute(
            '/search/enhancement/run/:id',
            ['module' => 'searchEnhancement', 'action' => 'runSavedSearch'],
            ['id' => '\d+']
        ));

        $routing->prependRoute('search_enhancement_run_template', new sfRoute(
            '/search/enhancement/template/:id',
            ['module' => 'searchEnhancement', 'action' => 'runTemplate'],
            ['id' => '\d+']
        ));

        $routing->prependRoute('search_enhancement_history', new sfRoute(
            '/search/enhancement/history',
            ['module' => 'searchEnhancement', 'action' => 'history']
        ));

        $routing->prependRoute('search_enhancement_delete', new sfRoute(
            '/search/enhancement/delete/:id',
            ['module' => 'searchEnhancement', 'action' => 'deleteSavedSearch'],
            ['id' => '\d+']
        ));

        $routing->prependRoute('search_enhancement_admin_templates', new sfRoute(
            '/admin/search-templates',
            ['module' => 'searchEnhancement', 'action' => 'adminTemplates']
        ));
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
