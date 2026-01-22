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
        // Register CLI commands
        $this->dispatcher->connect('command.pre_command', [$this, 'registerCommands']);
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
